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
        $user = $request->user();
        $veterinarioId = $user->id ?? auth()->id() ?? 1;

        // Obtener predios con sus productores
        $predios = Predio::with('productor')->get();

        // Obtener visitas pendientes asignadas a este veterinario (o todas si es Administrador)
        $query = Visita::with('predio')->where('estado', 'pendiente');
        if ($user && !$user->hasRole('Administrador')) {
            $query->where('veterinario_id', $veterinarioId);
        }
        $visitas = $query->get();

        // Obtener productores y médicos para caché offline completo
        $productores = \App\Models\Productor::with('predios')->get();
        $medicos = \App\Models\User::role('Medico_Campo')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'predios' => $predios,
                'productores' => $productores,
                'medicos' => $medicos,
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

                        $sexo = $item['sexo'] ?? 'Macho';
                        if ($sexo === 'H' || $sexo === 'Hembra') {
                            $sexo = 'Hembra';
                        } elseif ($sexo === 'M' || $sexo === 'Macho') {
                            $sexo = 'Macho';
                        }

                        $animal = Animal::firstOrCreate(
                            ['numero_arete_siniiga' => $item['identificador']],
                            [
                                'raza' => $item['raza'] ?? 'No especificada', 
                                'sexo' => $sexo, 
                                'predio_id' => $data['predio_id'],
                                'edad' => $item['edad_meses'] ?? 0
                            ]
                        );

                        DetalleInspeccion::create([
                            'inspeccion_id' => $inspeccion->id,
                            'animal_id' => $animal->id,
                            'tipo_arete' => !empty($item['tipo_arete']) ? $item['tipo_arete'] : ($item['tipo_arete_default'] ?? 'SINIIGA'),
                            'edad_meses' => $item['edad_meses'] ?? null,
                            'raza' => $item['raza'] ?? null,
                            'sexo' => $sexo,
                            'fierro' => $item['fierro'] ?? null,
                            'resultado_prueba' => $item['resultado'] ?? 'Negativo',
                            'observaciones_animal' => $item['observaciones'] ?? null,
                        ]);
                    }
                }

                // Si la inspección viene de una visita, actualizar inyección y estado de la visita
                if (isset($data['visita_id'])) {
                    $visita = Visita::find($data['visita_id']);
                    if ($visita) {
                        $visitaUpdate = [];
                        if (!$isDraft || !empty($data['fecha_inyeccion']) || ($data['inyeccion_realizada'] ?? false)) {
                            $visitaUpdate['inyeccion'] = true;
                            $visitaUpdate['estado'] = 'completada';
                        }
                        if (!empty($visitaUpdate)) {
                            $visita->update($visitaUpdate);
                        }
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

    /**
     * Subida de visitas generadas offline
     */
    public function uploadVisitas(Request $request)
    {
        $request->validate([
            'visitas' => 'required|array',
        ]);

        $procesados = [];
        $errores = [];

        foreach ($request->visitas as $data) {
            if (!isset($data['codigo'])) {
                $errores[] = ['error' => 'Falta código de visita'];
                continue;
            }

            try {
                $existing = Visita::where('codigo', $data['codigo'])->first();
                if ($existing) {
                    $procesados[] = $data['codigo'];
                    continue;
                }

                Visita::create([
                    'codigo' => $data['codigo'],
                    'predio_id' => $data['predio_id'],
                    'veterinario_id' => $data['veterinario_id'] ?? auth()->id(),
                    'fecha_programada' => $data['fecha_programada'],
                    'observaciones' => $data['observaciones'] ?? null,
                    'estado' => 'pendiente',
                    'inyeccion' => false,
                ]);

                $procesados[] = $data['codigo'];
            } catch (\Exception $e) {
                $errores[] = ['codigo' => $data['codigo'], 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'status' => 'success',
            'procesados' => $procesados,
            'errores' => $errores,
        ]);
    }
}
