<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Predio;
use App\Models\Visita;
use App\Models\Inspeccion;
use App\Models\Animal;
use App\Models\DetalleInspeccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    /**
     * Descarga de catálogos para la App Móvil
     */
    public function catalogos(Request $request)
    {
        $veterinarioId = $request->user()->id ?? auth()->id() ?? 1;

        // Obtener predios con sus productores
        $predios = Predio::with('productor')->get();

        // Obtener visitas pendientes asignadas a este veterinario
        $visitas = Visita::with('predio')
            ->where('veterinario_id', $veterinarioId)
            ->where('estado', 'pendiente')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'predios' => $predios,
                'visitas' => $visitas,
            ]
        ]);
    }

    /**
     * Subida de dictámenes generados offline
     */
    public function uploadInspecciones(Request $request)
    {
        $request->validate([
            'inspecciones' => 'required|array',
        ]);

        $veterinarioId = $request->user()->id ?? auth()->id() ?? 1;
        $inspeccionesProcesadas = [];
        $errores = [];

        DB::beginTransaction();

        try {
            foreach ($request->inspecciones as $data) {
                // Validación básica de cada objeto
                if (!isset($data['folio']) || !isset($data['predio_id'])) {
                    $errores[] = ['folio' => $data['folio'] ?? 'Desconocido', 'error' => 'Faltan datos requeridos (folio o predio_id)'];
                    continue;
                }

                $isDraft = ($data['estado'] ?? 'sincronizado') === 'borrador';

                // Crear o actualizar la inspección
                $inspeccion = Inspeccion::updateOrCreate(
                    ['folio' => $data['folio']], // Buscar por folio único
                    [
                        'predio_id' => $data['predio_id'],
                        'veterinario_id' => $veterinarioId,
                        'visita_id' => $data['visita_id'] ?? null,
                        'fecha' => $data['fecha'] ?? now(),
                        'tipo_inspeccion' => 'Movilización',
                        'tipo_prueba' => $data['tipo_prueba'] ?? 'P.P.C.',
                        'fecha_inyeccion' => $data['fecha_inyeccion'] ?? null,
                        'hora_inyeccion' => $data['hora_inyeccion'] ?? null,
                        'fecha_lectura' => $data['fecha_lectura'] ?? null,
                        'hora_lectura' => $data['hora_lectura'] ?? null,
                        'motivo_prueba' => $data['motivo_prueba'] ?? null,
                        'funcion_zootecnica' => $data['funcion_zootecnica'] ?? null,
                        'vigencia_fecha' => $data['vigencia_fecha'] ?? null,
                        'sementales' => $data['sementales'] ?? 0,
                        'vacas' => $data['vacas'] ?? 0,
                        'vaquillas' => $data['vaquillas'] ?? 0,
                        'becerras' => $data['becerras'] ?? 0,
                        'becerros' => $data['becerros'] ?? 0,
                        'estado' => $data['estado'] ?? 'sincronizado',
                    ]
                );

                // Procesar Animales si existen
                if (isset($data['animales']) && is_array($data['animales'])) {
                    // Borrar detalles anteriores si es una actualización (evitar duplicados en resync)
                    DetalleInspeccion::where('inspeccion_id', $inspeccion->id)->delete();

                    foreach ($data['animales'] as $item) {
                        if (empty($item['identificador']) && $isDraft) continue;

                        $animal = Animal::firstOrCreate(
                            ['numero_arete_siniiga' => $item['identificador']],
                            [
                                'raza' => $item['raza'] ?? 'No especificada', 
                                'sexo' => $item['sexo'] ?? 'Macho', 
                                'predio_id' => $data['predio_id'],
                                'edad' => $item['edad_meses'] ?? 0
                            ]
                        );

                        DetalleInspeccion::create([
                            'inspeccion_id' => $inspeccion->id,
                            'animal_id' => $animal->id,
                            'edad_meses' => $item['edad_meses'] ?? null,
                            'raza' => $item['raza'] ?? null,
                            'sexo' => $item['sexo'] ?? null,
                            'fierro' => $item['fierro'] ?? null,
                            'resultado_prueba' => $item['resultado'] ?? 'Negativo',
                            'observaciones_animal' => $item['observaciones'] ?? null,
                        ]);
                    }
                }

                // Si se finalizó la inspección y viene de una visita, marcar visita como completada
                if (!$isDraft && isset($data['visita_id'])) {
                    $visita = Visita::find($data['visita_id']);
                    if ($visita) {
                        $visita->update(['estado' => 'completada']);
                    }
                }

                $inspeccionesProcesadas[] = $data['folio'];
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Sincronización completada',
                'procesados' => $inspeccionesProcesadas,
                'errores' => $errores
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en sincronización móvil: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Hubo un error crítico al sincronizar los datos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
