<?php

namespace App\Http\Controllers;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\DetalleInspeccion;
use App\Models\Animal;
use App\Models\Visita;
use App\Models\Productor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InspeccionController extends Controller
{
    /**
     * Lista las inspecciones recientes.
     */
    public function index()
    {
        $query = Inspeccion::with(['predio', 'veterinario'])->orderBy('id', 'desc');
        
        if (!auth()->user()->hasRole('Administrador')) {
            $query->where('veterinario_id', auth()->id());
        }
        
        $inspecciones = $query->paginate(10);

        return view('inspecciones.index', compact('inspecciones'));
    }

    /**
     * Muestra el formulario para crear una nueva inspección.
     */
    public function create(Request $request)
    {
        $productoresModel = Productor::with('predios')->get();
        
        $productores = $productoresModel->map(function($prod) {
            return [
                'id' => $prod->id,
                'nombre' => $prod->nombre,
                'curp' => $prod->curp,
                'predios' => $prod->predios->map(function($predio) {
                    return [
                        'id' => $predio->id,
                        'nombre_rancho' => $predio->nombre_rancho,
                        'localidad' => $predio->localidad,
                        'municipio' => $predio->municipio,
                        'latitud' => $predio->latitud,
                        'longitud' => $predio->longitud,
                        'domicilio' => $predio->domicilio,
                        'clave_unidad_produccion' => $predio->clave_unidad_produccion,
                    ];
                })->toArray(),
            ];
        })->toArray();

        $visita_id = $request->visita_id;
        
        $visita = $visita_id ? \App\Models\Visita::with('predio.productor')->find($visita_id) : null;
        
        // Direct request parameters (useful when coming from a specific producer or predio link)
        $selected_productor_id = $request->productor_id ?? ($visita ? $visita->predio->productor_id : null);
        $selected_predio_id = $request->predio_id ?? ($visita ? $visita->predio_id : null);
        
        return view('inspecciones.create', compact('productores', 'productoresModel', 'visita', 'selected_productor_id', 'selected_predio_id'));
    }

    /**
     * Almacena una nueva inspección y sus detalles.
     */
    public function store(Request $request)
    {
        // Validación de seguridad backend: no se permite finalizar antes de la fecha programada de inyección
        if ($request->visita_id) {
            $visita = \App\Models\Visita::find($request->visita_id);
            if ($visita && $visita->fecha_programada) {
                $hoy = \Carbon\Carbon::now()->startOfDay();
                $fechaProg = $visita->fecha_programada->startOfDay();
                if ($hoy->lt($fechaProg)) {
                    if ($request->estado === 'sincronizado' || $request->inyeccion_realizada == 1) {
                        return back()->withInput()->with('error', 'No se puede finalizar la inyección antes de la fecha programada de la visita (' . $visita->fecha_programada->format('d/m/Y') . ').');
                    }
                }
            }
        }

        $isDraft = $request->estado === 'borrador';
        $existingDraft = null;

        if ($isDraft && $request->visita_id) {
            $existingDraft = Inspeccion::where('visita_id', $request->visita_id)
                ->where('estado', 'borrador')
                ->where('veterinario_id', auth()->id() ?? 1)
                ->latest('id')
                ->first();
        }

        // Mapear sexo (H/M) y resultado pendiente
        if ($request->has('animales') && is_array($request->animales)) {
            $animales = $request->animales;
            foreach ($animales as $index => $item) {
                if (isset($item['sexo'])) {
                    if ($item['sexo'] === 'H') {
                        $animales[$index]['sexo'] = 'Hembra';
                    } elseif ($item['sexo'] === 'M') {
                        $animales[$index]['sexo'] = 'Macho';
                    }
                }
                if (empty($item['resultado'])) {
                    $animales[$index]['resultado'] = $isDraft ? 'Pendiente' : '';
                }
            }
            $request->merge(['animales' => $animales]);
        }

        if (empty($request->folio)) {
            $request->merge(['folio' => null]);
        }

        if ($request->has('fecha_inyeccion') && $request->fecha_inyeccion) {
            $fechaInyeccion = \Carbon\Carbon::parse($request->fecha_inyeccion);
            $request->merge([
                'fecha_lectura' => $fechaInyeccion->copy()->addDays(3)->format('Y-m-d')
            ]);
        }

        $rules = [
            'predio_id' => 'required|exists:predios,id',
            'fecha' => 'required|date',
            'folio' => 'nullable|unique:inspecciones,folio' . ($existingDraft ? ',' . $existingDraft->id : ''),
            'tipo_prueba' => 'nullable|string',
            'motivo_prueba' => 'nullable|string',
            'funcion_zootecnica' => 'nullable|string',
            'vigencia_fecha' => 'nullable|date',
        ];

        if (!$isDraft) {
            $rules = array_merge($rules, [
                'fecha_inyeccion' => 'required|date',
                'hora_inyeccion' => 'required',
                'fecha_lectura' => 'required|date',
                'hora_lectura' => 'required',
                'animales' => 'required|array|min:1',
                'animales.*.identificador' => 'required',
                'animales.*.resultado' => 'required|in:Negativo,Positivo,Sospechoso',
            ]);
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $inspeccionData = [
                'predio_id' => $request->predio_id,
                'veterinario_id' => auth()->id() ?? 1,
                'visita_id' => $request->visita_id,
                'fecha' => $request->fecha,
                'folio' => $request->folio,
                'tipo_inspeccion' => 'Movilización',
                'tipo_prueba' => $request->tipo_prueba,
                'fecha_inyeccion' => $request->fecha_inyeccion,
                'hora_inyeccion' => $request->hora_inyeccion,
                'fecha_lectura' => $request->fecha_lectura,
                'hora_lectura' => $request->hora_lectura,
                'motivo_prueba' => $request->motivo_prueba,
                'funcion_zootecnica' => $request->funcion_zootecnica,
                'vigencia_fecha' => $request->vigencia_fecha,
                'sementales' => $request->sementales ?? 0,
                'vacas' => $request->vacas ?? 0,
                'vaquillas' => $request->vaquillas ?? 0,
                'becerras' => $request->becerras ?? 0,
                'becerros' => $request->becerros ?? 0,
                'fecha_prueba_anterior' => $request->fecha_prueba_anterior,
                'dictamen_anterior_no' => $request->dictamen_anterior_no,
                'exencion_no' => $request->exencion_no,
                'exencion_fecha' => $request->exencion_fecha,
                'hato_libre_no' => $request->hato_libre_no,
                'hato_libre_fecha' => $request->hato_libre_fecha,
                'observaciones' => $request->observaciones,
                'estado' => $request->estado ?? 'sincronizado',
            ];

            if ($existingDraft) {
                $existingDraft->update($inspeccionData);
                $inspeccion = $existingDraft;
                $inspeccion->detalles()->delete();
            } else {
                $inspeccion = Inspeccion::create($inspeccionData);
            }

            if ($request->has('animales') && is_array($request->animales)) {
                foreach ($request->animales as $item) {
                    if (!$item['identificador'] && $isDraft) continue;

                    $animal = Animal::firstOrCreate(
                        ['numero_arete_siniiga' => $item['identificador']],
                        [
                            'raza' => $item['raza'] ?? 'No especificada', 
                            'sexo' => $item['sexo'] ?? 'Macho', 
                            'predio_id' => $request->predio_id,
                            'edad' => $item['edad_meses'] ?? 0
                        ]
                    );

                    DetalleInspeccion::create([
                        'inspeccion_id' => $inspeccion->id,
                        'animal_id' => $animal->id,
                        'tipo_arete' => !empty($item['tipo_arete']) ? $item['tipo_arete'] : ($item['tipo_arete_default'] ?? 'SINIIGA'),
                        'edad_meses' => $item['edad_meses'] ?? null,
                        'raza' => $item['raza'] ?? null,
                        'sexo' => $item['sexo'] ?? null,
                        'fierro' => $item['fierro'] ?? null,
                        'resultado_prueba' => $item['resultado'],
                        'observaciones_animal' => $item['observaciones'] ?? null,
                    ]);
                }
            }

            // Marcar visita como completada y registrar inyección (solo con "Finalizar y Sincronizar")
            if ($request->visita_id && $request->inyeccion_realizada) {
                $visita = \App\Models\Visita::find($request->visita_id);
                if ($visita) {
                    $visita->update([
                        'estado' => 'completada',
                        'inyeccion' => true,
                    ]);
                }
            }

            DB::commit();

            $msg = $isDraft ? 'Borrador guardado correctamente.' : 'Inspección finalizada correctamente.';
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'id' => $inspeccion->id,
                    'redirect' => route('inspecciones.index'),
                ]);
            }

            return redirect()->route('inspecciones.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function edit(Inspeccion $inspeccion)
    {
        // Edit Lock Guard
        if ($inspeccion->estado !== 'borrador' && !auth()->user()->hasRole('Administrador')) {
            abort(403, 'No tienes permiso para editar un dictamen finalizado.');
        }

        $productores = Productor::with('predios')->get();
        $inspeccion->load(['detalles.animal', 'visita.predio.productor', 'predio.productor']);
        $visita = $inspeccion->visita;
        
        return view('inspecciones.edit', compact('inspeccion', 'productores', 'visita'));
    }

    public function update(Request $request, Inspeccion $inspeccion)
    {
        // Edit Lock Guard
        if ($inspeccion->estado !== 'borrador' && !auth()->user()->hasRole('Administrador')) {
            abort(403, 'No tienes permiso para editar un dictamen finalizado.');
        }

        // Validación de seguridad backend: inyección y lectura
        $visita = $inspeccion->visita;
        if ($visita) {
            $hoy = \Carbon\Carbon::now()->startOfDay();
            if (!$visita->inyeccion) {
                // Fase de Inyección
                if ($visita->fecha_programada) {
                    $fechaProg = $visita->fecha_programada->startOfDay();
                    if ($hoy->lt($fechaProg)) {
                        if ($request->estado === 'sincronizado' || $request->inyeccion_realizada == 1) {
                            return back()->withInput()->with('error', 'No se puede finalizar la inyección antes de la fecha programada de la visita (' . $visita->fecha_programada->format('d/m/Y') . ').');
                        }
                    }
                }
            } else {
                // Fase de Lectura
                if ($inspeccion->fecha_lectura) {
                    $fechaLectura = $inspeccion->fecha_lectura->startOfDay();
                    if ($hoy->lt($fechaLectura)) {
                        if ($request->estado === 'sincronizado') {
                            return back()->withInput()->with('error', 'No se puede finalizar el dictamen antes de la fecha programada de la lectura (' . $inspeccion->fecha_lectura->format('d/m/Y') . ').');
                        }
                    }
                }
            }
        }

        $isDraft = $request->estado === 'borrador';

        // Mapear sexo (H/M) y resultado pendiente
        if ($request->has('animales') && is_array($request->animales)) {
            $animales = $request->animales;
            foreach ($animales as $index => $item) {
                if (isset($item['sexo'])) {
                    if ($item['sexo'] === 'H') {
                        $animales[$index]['sexo'] = 'Hembra';
                    } elseif ($item['sexo'] === 'M') {
                        $animales[$index]['sexo'] = 'Macho';
                    }
                }
                if (empty($item['resultado'])) {
                    $animales[$index]['resultado'] = $isDraft ? 'Pendiente' : '';
                }
            }
            $request->merge(['animales' => $animales]);
        }

        if (empty($request->folio)) {
            $request->merge(['folio' => null]);
        }

        if ($request->has('fecha_inyeccion') && $request->fecha_inyeccion) {
            $fechaInyeccion = \Carbon\Carbon::parse($request->fecha_inyeccion);
            $request->merge([
                'fecha_lectura' => $fechaInyeccion->copy()->addDays(3)->format('Y-m-d')
            ]);
        }

        $rules = [
            'predio_id' => 'required|exists:predios,id',
            'fecha' => 'required|date',
            'folio' => 'nullable|unique:inspecciones,folio,'.$inspeccion->id,
        ];

        if (!$isDraft) {
            $rules = array_merge($rules, [
                'fecha_inyeccion' => 'required|date',
                'hora_inyeccion' => 'required',
                'fecha_lectura' => 'required|date',
                'hora_lectura' => 'required',
                'animales' => 'required|array|min:1',
                'animales.*.identificador' => 'required',
                'animales.*.resultado' => 'required|in:Negativo,Positivo,Sospechoso',
            ]);
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $inspeccion->update($request->except(['animales', '_token', '_method']));

            // Sync animals (simplistic: delete and recreate for this MVP)
            if ($request->has('animales')) {
                $inspeccion->detalles()->delete();
                foreach ($request->animales as $item) {
                    if (!$item['identificador'] && $isDraft) continue;

                    $animal = Animal::firstOrCreate(
                        ['numero_arete_siniiga' => $item['identificador']],
                        ['predio_id' => $request->predio_id, 'raza' => $item['raza'] ?? 'N/A', 'edad' => $item['edad_meses'] ?? 0]
                    );

                    DetalleInspeccion::create([
                        'inspeccion_id' => $inspeccion->id,
                        'animal_id' => $animal->id,
                        'tipo_arete' => !empty($item['tipo_arete']) ? $item['tipo_arete'] : ($item['tipo_arete_default'] ?? 'SINIIGA'),
                        'edad_meses' => $item['edad_meses'] ?? null,
                        'raza' => $item['raza'] ?? null,
                        'sexo' => $item['sexo'] ?? null,
                        'fierro' => $item['fierro'] ?? null,
                        'resultado_prueba' => $item['resultado'],
                        'observaciones_animal' => $item['observaciones'] ?? null,
                    ]);
                }
            }

            // Marcar visita como completada y registrar inyección (solo con "Finalizar y Sincronizar")
            if ($inspeccion->visita_id && $request->inyeccion_realizada) {
                $visita = \App\Models\Visita::find($inspeccion->visita_id);
                if ($visita) {
                    $visita->update([
                        'estado' => 'completada',
                        'inyeccion' => true,
                    ]);
                }
            }

            DB::commit();
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dictamen actualizado.',
                    'id' => $inspeccion->id,
                    'redirect' => route('inspecciones.index'),
                ]);
            }

            return redirect()->route('inspecciones.index')->with('success', 'Dictamen actualizado.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el detalle completo de un dictamen.
     */
    public function show(Inspeccion $inspeccion)
    {
        $inspeccion->load(['predio.productor', 'detalles.animal', 'veterinario']);
        return view('inspecciones.show', compact('inspeccion'));
    }

    /**
     * Busca los datos de un arete en el censo para el autocompletado.
     */
    public function buscarArete($numero)
    {
        // Limpiar el número de arete de espacios
        $numero = trim($numero);
        
        $animal = \App\Models\AreteCenso::where('numero_arete', $numero)->first();

        if ($animal) {
            return response()->json([
                'success' => true,
                'data' => [
                    'raza' => $animal->raza,
                    'sexo' => $animal->sexo,
                    'edad_meses' => $animal->edad_meses,
                    'fecha_nacimiento' => $animal->fecha_nacimiento,
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Arete no encontrado en el censo'], 404);
    }
}
