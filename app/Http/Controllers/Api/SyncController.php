<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Http\Request;
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

        // Obtener predios con sus productores (filtrados si no es administrador)
        $prediosQuery = Predio::with('productor');
        if ($user && !$user->hasRole('Administrador')) {
            $prediosQuery->whereHas('productor', function ($q) use ($veterinarioId) {
                $q->where('medico_id', $veterinarioId);
            });
        }
        $predios = $prediosQuery->get();

        // Obtener todas las visitas asignadas a este veterinario (o todas si es Administrador) con relaciones completas
        $query = Visita::with(['predio.productor', 'veterinario', 'inspeccion']);
        if ($user && ! $user->hasRole('Administrador')) {
            $query->where('veterinario_id', $veterinarioId);
        }
        $visitas = $query->orderByDesc('id')->get()->map(function ($visita) {
            return [
                'id' => $visita->id,
                'codigo' => $visita->codigo,
                'predio_id' => $visita->predio_id,
                'veterinario_id' => $visita->veterinario_id,
                'fecha_programada' => optional($visita->fecha_programada)->format('Y-m-d'),
                'estado' => $visita->estado,
                'inyeccion' => (bool) $visita->inyeccion,
                'observaciones' => $visita->observaciones,
                'predio' => $visita->predio ? [
                    'id' => $visita->predio->id,
                    'nombre' => $visita->predio->nombre_rancho,
                    'nombre_rancho' => $visita->predio->nombre_rancho,
                    'clave_unidad_produccion' => $visita->predio->clave_unidad_produccion,
                    'latitud' => $visita->predio->latitud,
                    'longitud' => $visita->predio->longitud,
                    'domicilio' => $visita->predio->domicilio,
                    'municipio' => $visita->predio->municipio,
                    'localidad' => $visita->predio->localidad,
                    'productor' => $visita->predio->productor ? [
                        'id' => $visita->predio->productor->id,
                        'nombre' => $visita->predio->productor->nombre,
                        'apellido_paterno' => $visita->predio->productor->apellido_paterno,
                        'apellido_materno' => $visita->predio->productor->apellido_materno,
                        'curp' => $visita->predio->productor->curp,
                        'upp' => $visita->predio->productor->upp,
                        'telefono' => $visita->predio->productor->telefono,
                        'domicilio' => $visita->predio->productor->domicilio,
                        'municipio' => $visita->predio->productor->municipio,
                        'localidad' => $visita->predio->productor->localidad,
                        'estado' => $visita->predio->productor->estado,
                        'email' => $visita->predio->productor->email,
                        'clave_cuarentena' => $visita->predio->productor->clave_cuarentena,
                        'zona' => $visita->predio->productor->zona,
                    ] : null,
                ] : null,
                'veterinario' => $visita->veterinario ? [
                    'id' => $visita->veterinario->id,
                    'name' => $visita->veterinario->name,
                    'email' => $visita->veterinario->email,
                ] : null,
                'inspeccion' => $visita->inspeccion ? [
                    'id' => $visita->inspeccion->id,
                    'folio' => $visita->inspeccion->folio,
                    'estado' => $visita->inspeccion->estado,
                    'fecha' => optional($visita->inspeccion->fecha)->format('Y-m-d'),
                    'fecha_inyeccion' => optional($visita->inspeccion->fecha_inyeccion)->format('Y-m-d'),
                    'hora_inyeccion' => $visita->inspeccion->hora_inyeccion,
                    'fecha_lectura' => optional($visita->inspeccion->fecha_lectura)->format('Y-m-d'),
                    'hora_lectura' => $visita->inspeccion->hora_lectura,
                ] : null,
            ];
        });

        // Obtener productores (filtrados si no es administrador) y médicos para caché offline completo
        $productoresQuery = Productor::with('predios');
        if ($user && !$user->hasRole('Administrador')) {
            $productoresQuery->where('medico_id', $veterinarioId);
        }
        $productores = $productoresQuery->get();
        $medicos = User::role('Medico_Campo')
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
            ],
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
                if (! isset($data['folio']) || ! isset($data['predio_id'])) {
                    $errores[] = ['folio' => $data['folio'] ?? 'Desconocido', 'error' => 'Faltan datos requeridos (folio o predio_id)'];

                    continue;
                }

                $isDraft = ($data['estado'] ?? 'sincronizado') === 'borrador';

                // Generar clave_interna si no viene en los datos
                if (empty($data['clave_interna'])) {
                    $predio = \App\Models\Predio::with('productor')->find($data['predio_id']);
                    if ($predio && $predio->productor) {
                        $data['clave_interna'] = generarClaveInterna($predio->productor);
                    }
                }

                // Si hay clave_interna, buscar borrador existente para evitar duplicados
                if (! empty($data['clave_interna'])) {
                    $existingByClave = Inspeccion::where('clave_interna', $data['clave_interna'])
                        ->where('estado', 'borrador')
                        ->first();
                    if ($existingByClave && $existingByClave->folio !== $data['folio']) {
                        // Actualizar el existente en lugar de crear uno nuevo
                        $existingByClave->update([
                            'folio' => $data['folio'],
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
                            'clave_interna' => $data['clave_interna'],
                        ]);
                        $inspeccion = $existingByClave;
                    } else {
                        $inspeccion = Inspeccion::updateOrCreate(
                            ['folio' => $data['folio']],
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
                                'clave_interna' => $data['clave_interna'] ?? null,
                            ]
                        );
                    }
                } else {
                    $inspeccion = Inspeccion::updateOrCreate(
                        ['folio' => $data['folio']],
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
                }

                // Procesar Animales si existen
                if (isset($data['animales']) && is_array($data['animales'])) {
                    $inspeccion->load('visita');
                    // Determinar si estamos en fase de lectura y capturar aretes existentes
                    $isLectura = $inspeccion->visita && $inspeccion->visita->inyeccion;
                    $existingAretes = [];
                    if ($isLectura) {
                        $existingAretes = $inspeccion->detalles()
                            ->with('animal')
                            ->get()
                            ->pluck('animal.numero_arete_siniiga')
                            ->toArray();
                    }

                    // Borrar detalles anteriores si es una actualización (evitar duplicados en resync)
                    DetalleInspeccion::where('inspeccion_id', $inspeccion->id)->delete();

                    $edadMinimaPrueba = 6;
                    $predio = Predio::with('productor')->find($data['predio_id']);
                    if ($predio && $predio->productor) {
                        $edadMinimaPrueba = $predio->productor->edad_minima_prueba;
                    }

                    foreach ($data['animales'] as $item) {
                        if (empty($item['identificador']) && $isDraft) {
                            continue;
                        }

                        $sexo = $item['sexo'] ?? 'Macho';
                        if ($sexo === 'H' || $sexo === 'Hembra') {
                            $sexo = 'Hembra';
                        } elseif ($sexo === 'M' || $sexo === 'Macho') {
                            $sexo = 'Macho';
                        }

                        $edadMeses = $item['edad_meses'] ?? 0;
                        $resultado = $item['resultado'] ?? 'Negativo';
                        $motivoNoAplica = $item['motivo_no_aplica'] ?? null;
                        if ($edadMeses > 0 && $edadMeses < $edadMinimaPrueba) {
                            $resultado = 'No Aplica';
                            $motivoNoAplica = "Menor a {$edadMeses} meses";
                        }

                        $animal = Animal::firstOrCreate(
                            ['numero_arete_siniiga' => $item['identificador']],
                            [
                                'raza' => $item['raza'] ?? 'No especificada',
                                'sexo' => $sexo,
                                'predio_id' => $data['predio_id'],
                                'edad' => $edadMeses,
                            ]
                        );

                        $agregadoEnLectura = $isLectura && ! in_array($item['identificador'], $existingAretes);

                        DetalleInspeccion::create([
                            'inspeccion_id' => $inspeccion->id,
                            'animal_id' => $animal->id,
                            'tipo_arete' => ! empty($item['tipo_arete']) ? $item['tipo_arete'] : ($item['tipo_arete_default'] ?? 'SINIIGA'),
                            'edad_meses' => $item['edad_meses'] ?? null,
                            'raza' => $item['raza'] ?? null,
                            'sexo' => $sexo,
                            'fierro' => $item['fierro'] ?? null,
                            'resultado_prueba' => $resultado,
                            'motivo_no_aplica' => $motivoNoAplica,
                            'observaciones_animal' => $item['observaciones'] ?? null,
                            'agregado_en_lectura' => $agregadoEnLectura,
                        ]);
                    }
                }

                // Si la inspección viene de una visita, actualizar inyección y estado de la visita
                if (isset($data['visita_id'])) {
                    $visita = Visita::find($data['visita_id']);
                    if ($visita) {
                        $visitaUpdate = [];
                        if (! $isDraft || ! empty($data['fecha_inyeccion']) || ($data['inyeccion_realizada'] ?? false)) {
                            $visitaUpdate['inyeccion'] = true;
                            $visitaUpdate['estado'] = 'completada';
                        }
                        if (! empty($visitaUpdate)) {
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
                'errores' => $errores,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en sincronización móvil: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Hubo un error crítico al sincronizar los datos.',
                'error' => $e->getMessage(),
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
            if (! isset($data['codigo'])) {
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
