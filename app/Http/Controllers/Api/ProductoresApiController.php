<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Predio;
use App\Models\Productor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductoresApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Productor::withCount('predios')->latest();

        if ($user && ! $user->hasRole('Administrador')) {
            $query->where('medico_id', $user->id);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido_paterno', 'like', "%{$search}%")
                    ->orWhere('apellido_materno', 'like', "%{$search}%")
                    ->orWhere('curp', 'like', "%{$search}%")
                    ->orWhere('upp', 'like', "%{$search}%");
            });
        }

        $productores = Productor::attachVinculados($query->get())
            ->map(fn (Productor $productor) => array_merge(
                $this->toProductorArray($productor),
                ['vinculados' => $productor->vinculados ?? []]
            ));

        return response()->json([
            'success' => true,
            'data' => $productores,
        ]);
    }

    public function previewClave(Request $request)
    {
        $validated = $request->validate([
            'tipo_actividad' => 'required|string|in:Barrido,Buffer,Seguimiento',
            'sub_tipo_actividad' => 'nullable|string|in:Cuarentena Precautoria,Cuarentena Definitiva,Hatos Relacionados y Expuestos',
            'zona' => 'nullable|string|in:A,B',
            'medico_id' => 'nullable|exists:users,id',
        ]);

        $generated = Productor::siguienteClave(
            $validated['tipo_actividad'],
            $validated['sub_tipo_actividad'] ?? null,
            $validated['zona'] ?? null,
            $validated['medico_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'clave' => $generated['clave'],
            'zona' => $generated['zona'] ?? ($validated['zona'] ?? null),
        ]);
    }

    public function buscar(Request $request)
    {
        $q = $request->get('q', '');
        $q = trim($q);

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $user = $request->user();
        $query = Productor::with('predios')->where(function ($query) use ($q) {
            $query->where('nombre', 'like', "%{$q}%")
                ->orWhere('apellido_paterno', 'like', "%{$q}%")
                ->orWhere('apellido_materno', 'like', "%{$q}%")
                ->orWhere('clave', 'like', "%{$q}%");
        });

        if ($user && ! $user->hasRole('Administrador')) {
            $query->where('medico_id', $user->id);
        }

        $productores = $query
            ->orderByRaw('CASE WHEN clave LIKE ? THEN 0 ELSE 1 END', [$q.'%'])
            ->orderBy('nombre')
            ->limit(15)
            ->get();

        $productores->each(function ($p) {
            $primerPredio = $p->predios->first();
            $p->setAttribute('_predio_nombre_rancho', $primerPredio?->nombre_rancho);
            $p->setAttribute('_predio_clave_unidad_produccion', $primerPredio?->clave_unidad_produccion);
            $p->setAttribute('_predio_latitud', $primerPredio?->latitud);
            $p->setAttribute('_predio_longitud', $primerPredio?->longitud);
            $p->setAttribute('_predio_domicilio', $primerPredio?->domicilio);
            $p->setAttribute('_predio_municipio', $primerPredio?->municipio);
            $p->setAttribute('_predio_localidad', $primerPredio?->localidad);
            unset($p->predios);
        });

        return response()->json($productores);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $productor = Productor::with(['predios.inspecciones', 'medico'])->withCount('predios')->findOrFail($id);

        if ($user && ! $user->hasRole('Administrador') && $productor->medico_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver este productor.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->toProductorArray($productor, true),
        ]);
    }

    public function predios(Request $request)
    {
        $user = $request->user();
        $query = Predio::with('productor')->latest();

        if ($user && ! $user->hasRole('Administrador')) {
            $query->whereHas('productor', function ($q) use ($user) {
                $q->where('medico_id', $user->id);
            });
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre_rancho', 'like', "%{$search}%")
                    ->orWhere('clave_unidad_produccion', 'like', "%{$search}%")
                    ->orWhere('localidad', 'like', "%{$search}%")
                    ->orWhereHas('productor', function ($pq) use ($search) {
                        $pq->where('nombre', 'like', "%{$search}%")
                            ->orWhere('apellido_paterno', 'like', "%{$search}%");
                    });
            });
        }

        $predios = $query->get()
            ->map(function (Predio $predio) {
                return [
                    'id' => $predio->id,
                    'nombre' => $predio->nombre_rancho,
                    'nombre_rancho' => $predio->nombre_rancho,
                    'clave_unidad_produccion' => $predio->clave_unidad_produccion,
                    'upp' => $predio->clave_unidad_produccion,
                    'latitud' => $predio->latitud,
                    'longitud' => $predio->longitud,
                    'domicilio' => $predio->domicilio,
                    'municipio' => $predio->municipio,
                    'localidad' => $predio->localidad,
                    'productor_id' => $predio->productor_id,
                    'productor' => $predio->productor ? [
                        'id' => $predio->productor->id,
                        'nombre' => $predio->productor->nombre,
                        'apellido_paterno' => $predio->productor->apellido_paterno,
                        'apellido_materno' => $predio->productor->apellido_materno,
                        'curp' => $predio->productor->curp,
                        'upp' => $predio->productor->upp,
                        'telefono' => $predio->productor->telefono,
                        'domicilio' => $predio->productor->domicilio,
                        'municipio' => $predio->productor->municipio,
                        'localidad' => $predio->productor->localidad,
                        'estado' => $predio->productor->estado,
                        'email' => $predio->productor->email,
                        'clave' => $predio->productor->clave,
                        'zona' => $predio->productor->zona,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $predios,
        ]);
    }

    public function showPredio(Request $request, $id)
    {
        $user = $request->user();
        $predio = Predio::with(['productor', 'animales'])->findOrFail($id);

        if ($user && ! $user->hasRole('Administrador') && (! $predio->productor || $predio->productor->medico_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver este predio.',
            ], 403);
        }

        $otrosPredios = collect();
        if ($predio->clave_unidad_produccion) {
            $otrosPredios = Predio::with(['productor', 'productor.medico'])
                ->where('clave_unidad_produccion', $predio->clave_unidad_produccion)
                ->where('productor_id', '!=', $predio->productor_id)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'nombre_rancho' => $item->nombre_rancho,
                        'clave_unidad_produccion' => $item->clave_unidad_produccion,
                        'productor' => $item->productor ? [
                            'id' => $item->productor->id,
                            'nombre' => $item->productor->nombre,
                            'apellido_paterno' => $item->productor->apellido_paterno,
                            'apellido_materno' => $item->productor->apellido_materno,
                            'medico' => $item->productor->medico ? [
                                'name' => $item->productor->medico->name,
                            ] : null,
                        ] : null,
                    ];
                });
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $predio->id,
                'nombre' => $predio->nombre_rancho,
                'nombre_rancho' => $predio->nombre_rancho,
                'clave_unidad_produccion' => $predio->clave_unidad_produccion,
                'upp' => $predio->clave_unidad_produccion,
                'latitud' => $predio->latitud,
                'longitud' => $predio->longitud,
                'domicilio' => $predio->domicilio,
                'municipio' => $predio->municipio,
                'localidad' => $predio->localidad,
                'productor_id' => $predio->productor_id,
                'productor' => $predio->productor ? [
                    'id' => $predio->productor->id,
                    'nombre' => $predio->productor->nombre,
                    'apellido_paterno' => $predio->productor->apellido_paterno,
                    'apellido_materno' => $predio->productor->apellido_materno,
                    'curp' => $predio->productor->curp,
                    'upp' => $predio->productor->upp,
                    'telefono' => $predio->productor->telefono,
                    'domicilio' => $predio->productor->domicilio,
                    'municipio' => $predio->productor->municipio,
                    'localidad' => $predio->productor->localidad,
                    'estado' => $predio->productor->estado,
                    'email' => $predio->productor->email,
                    'clave' => $predio->productor->clave,
                    'zona' => $predio->productor->zona,
                ] : null,
                'animales' => $predio->animales->map(function ($animal) {
                    return [
                        'id' => $animal->id,
                        'numero_arete_siniiga' => $animal->numero_arete_siniiga,
                        'raza' => $animal->raza,
                        'sexo' => $animal->sexo,
                        'edad' => $animal->edad,
                    ];
                })->values(),
                'otros_predios' => $otrosPredios,
            ],
        ]);
    }

    public function storeProductor(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'apellido_paterno' => 'required|string|max:255',
                'apellido_materno' => 'nullable|string|max:255',
                'curp' => 'nullable|string|size:18|unique:productores,curp',
                'upp' => 'nullable|string|unique:productores,upp',
                'telefono' => 'nullable|string|max:20',
                'domicilio' => 'nullable|string',
                'municipio' => 'nullable|string',
                'localidad' => 'nullable|string',
                'estado' => 'nullable|string',
                'email' => 'nullable|email',
                'medico_id' => 'nullable|exists:users,id',
                'clave' => ['nullable', 'string', 'regex:/^(AD|AP|BD|BP|BA|BF|BFC|BFE|BU|SG|GP|BAF|BAE)-?\d*$/i'],
                'zona' => 'nullable|string|in:A,B',
                'tipo_actividad' => 'nullable|string|in:Barrido,Buffer,Seguimiento',
                'sub_tipo_actividad' => 'required_if:tipo_actividad,Seguimiento|nullable|string|in:Cuarentena Precautoria,Cuarentena Definitiva,Hatos Relacionados y Expuestos',

                // Validaciones para el predio opcional (copia exacta de la web)
                'registrar_predio' => 'nullable|boolean',
                'nombre_rancho' => 'required_if:registrar_predio,1|nullable|string|max:255',
                'clave_unidad_produccion' => 'required_if:registrar_predio,1|nullable|string',
                'predio_municipio' => 'required_if:registrar_predio,1|nullable|string|max:255',
                'predio_localidad' => 'required_if:registrar_predio,1|nullable|string|max:255',
            ]);

            return DB::transaction(function () use ($validated, $request) {
                $user = $request->user();

                $medicoId = $validated['medico_id'] ?? null;
                if ($user && ! $user->hasRole('Administrador')) {
                    $medicoId = $user->id;
                    $tipo = strtolower($validated['tipo_actividad'] ?? '');
                    if ($tipo === 'barrido' || $tipo === 'buffer') {
                        unset($validated['clave']);
                    } else {
                        unset($validated['clave'], $validated['zona']);
                    }
                }

                if (($validated['tipo_actividad'] ?? '') !== 'Seguimiento') {
                    $validated['sub_tipo_actividad'] = null;
                }

                // 1. Crear el Productor
                $productor = Productor::create([
                    'nombre' => $validated['nombre'],
                    'apellido_paterno' => $validated['apellido_paterno'],
                    'apellido_materno' => $validated['apellido_materno'] ?? null,
                    'curp' => $validated['curp'] ? strtoupper($validated['curp']) : null,
                    'upp' => $validated['upp'] ?? null,
                    'telefono' => $validated['telefono'] ?? null,
                    'domicilio' => $validated['domicilio'] ?? 'Domicilio Conocido',
                    'municipio' => $validated['municipio'] ?? 'General',
                    'localidad' => $validated['localidad'] ?? 'General',
                    'estado' => $validated['estado'] ?? 'NAYARIT',
                    'email' => $validated['email'] ?? null,
                    'medico_id' => $medicoId,
                    'clave' => $validated['clave'] ?? null,
                    'zona' => $validated['zona'] ?? null,
                    'tipo_actividad' => $validated['tipo_actividad'] ?? null,
                    'sub_tipo_actividad' => $validated['sub_tipo_actividad'] ?? null,
                ]);

                // 2. Crear el Predio si se solicitó (copia exacta de la web)
                $predio = null;
                if ($request->has('registrar_predio') && ($request->registrar_predio == '1' || $request->registrar_predio === true)) {
                    $predio = Predio::create([
                        'nombre_rancho' => $validated['nombre_rancho'],
                        'clave_unidad_produccion' => $validated['clave_unidad_produccion'],
                        'municipio' => $validated['predio_municipio'] ?? 'General',
                        'localidad' => $validated['predio_localidad'] ?? 'General',
                        'productor_id' => $productor->id,
                        'domicilio' => 'Conocido',
                    ]);

                    // Cargar relación productor
                    $predio->load('productor');
                }

                return response()->json([
                    'success' => true,
                    'message' => $predio ? 'Productor y Unidad de Producción (UPP) registrados con éxito.' : 'Productor registrado con éxito.',
                    'productor' => $productor,
                    'predio' => $predio,
                ], 201);
            });

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando productor via API: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error crítico en el servidor: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroyProductor($id)
    {
        try {
            $user = request()->user();
            $productor = Productor::findOrFail($id);

            if ($user && ! $user->hasRole('Administrador') && $productor->medico_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar este productor.',
                ], 403);
            }

            $productor->delete();

            return response()->json([
                'success' => true,
                'message' => 'Productor eliminado con éxito.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Productor no encontrado.',
            ], 404);
        }
    }

    public function updateCoordenadas(Request $request, $id)
    {
        try {
            $user = $request->user();
            $predio = Predio::with('productor')->findOrFail($id);

            if ($user && ! $user->hasRole('Administrador') && (! $predio->productor || $predio->productor->medico_id !== $user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para modificar este predio.',
                ], 403);
            }

            $request->validate([
                'latitud' => 'required|numeric|between:-90,90',
                'longitud' => 'required|numeric|between:-180,180',
            ]);

            $predio->update([
                'latitud' => $request->latitud,
                'longitud' => $request->longitud,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Coordenadas actualizadas con éxito.',
                'data' => [
                    'id' => $predio->id,
                    'latitud' => $predio->latitud,
                    'longitud' => $predio->longitud,
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Predio no encontrado.'], 404);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        }
    }

    public function destroyPredio($id)
    {
        try {
            $user = request()->user();
            $predio = Predio::with('productor')->findOrFail($id);

            if ($user && ! $user->hasRole('Administrador') && (! $predio->productor || $predio->productor->medico_id !== $user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar este predio.',
                ], 403);
            }

            $predio->delete();

            return response()->json([
                'success' => true,
                'message' => 'Predio eliminado con éxito.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Predio no encontrado.',
            ], 404);
        }
    }

    /**
     * Actualizar un productor desde la API Móvil
     */
    public function updateProductor(Request $request, $id)
    {
        try {
            $user = $request->user();
            $productor = Productor::findOrFail($id);

            if ($user && ! $user->hasRole('Administrador') && $productor->medico_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para actualizar este productor.',
                ], 403);
            }

            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'apellido_paterno' => 'required|string|max:255',
                'apellido_materno' => 'nullable|string|max:255',
                'curp' => 'nullable|string|size:18|unique:productores,curp,'.$productor->id,
                'upp' => 'nullable|string|unique:productores,upp,'.$productor->id,
                'telefono' => 'nullable|string|max:20',
                'domicilio' => 'nullable|string',
                'municipio' => 'nullable|string',
                'localidad' => 'nullable|string',
                'estado' => 'nullable|string',
                'email' => 'nullable|email',
                'medico_id' => 'nullable|exists:users,id',
                'clave' => ['nullable', 'string', 'regex:/^(AD|AP|BD|BP|BA|BF|BFC|BFE|BU|SG|GP|BAF|BAE)-?\d*$/i'],
                'zona' => 'nullable|string|in:A,B',
                'tipo_actividad' => 'nullable|string|in:Barrido,Buffer,Seguimiento',
                'sub_tipo_actividad' => 'required_if:tipo_actividad,Seguimiento|nullable|string|in:Cuarentena Precautoria,Cuarentena Definitiva,Hatos Relacionados y Expuestos',
            ]);

            if (($validated['tipo_actividad'] ?? '') !== 'Seguimiento') {
                $validated['sub_tipo_actividad'] = null;
            }

            $medicoId = $validated['medico_id'] ?? $productor->medico_id;
            if ($user && ! $user->hasRole('Administrador')) {
                $medicoId = $user->id;
                $tipo = strtolower($validated['tipo_actividad'] ?? '');
                if ($tipo === 'barrido' || $tipo === 'buffer') {
                    unset($validated['clave']);
                } else {
                    unset($validated['clave'], $validated['zona']);
                }
            }
            $validated['medico_id'] = $medicoId;

            if (isset($validated['curp'])) {
                $validated['curp'] = strtoupper($validated['curp']);
            }

            $productor->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Productor actualizado con éxito en el servidor.',
                'productor' => $productor,
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Productor no encontrado en el servidor.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando productor via API: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error crítico en el servidor: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un rancho (predio) desde la API Móvil
     */
    public function storeRancho(Request $request)
    {
        try {
            $user = $request->user();
            if ($user && ! $user->hasRole('Administrador')) {
                $productorExists = Productor::where('id', $request->productor_id)
                    ->where('medico_id', $user->id)
                    ->exists();
                if (! $productorExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El productor seleccionado no está asignado a tu usuario.',
                    ], 403);
                }
            }

            $validated = $request->validate([
                'nombre_rancho' => 'required|string|max:255',
                'clave_unidad_produccion' => [
                    'required',
                    'string',
                    Rule::unique('predios')->where(function ($query) use ($request) {
                        return $query->where('productor_id', $request->productor_id);
                    }),
                ],
                'localidad' => 'required|string|max:255',
                'municipio' => 'nullable|string|max:255',
                'productor_id' => 'required|exists:productores,id',
                'domicilio' => 'nullable|string|max:255',
                'latitud' => 'nullable|numeric',
                'longitud' => 'nullable|numeric',
            ]);

            $predio = Predio::create([
                'nombre_rancho' => $validated['nombre_rancho'],
                'clave_unidad_produccion' => $validated['clave_unidad_produccion'],
                'localidad' => $validated['localidad'],
                'municipio' => $validated['municipio'] ?? 'General',
                'productor_id' => $validated['productor_id'],
                'domicilio' => $validated['domicilio'] ?? 'Conocido',
                'latitud' => $validated['latitud'] ?? null,
                'longitud' => $validated['longitud'] ?? null,
            ]);

            // Cargar relación para devolver la estructura idéntica
            $predio->load('productor');

            return response()->json([
                'success' => true,
                'message' => 'Rancho creado con éxito en el servidor.',
                'predio' => $predio,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando rancho via API: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error crítico en el servidor: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar un rancho (predio) desde la API móvil
     */
    public function updateRancho(Request $request, $id)
    {
        try {
            $user = $request->user();
            $predio = Predio::with('productor')->findOrFail($id);

            if ($user && ! $user->hasRole('Administrador')) {
                if (! $predio->productor || $predio->productor->medico_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permiso para modificar este predio.',
                    ], 403);
                }
                $productorExists = Productor::where('id', $request->productor_id)
                    ->where('medico_id', $user->id)
                    ->exists();
                if (! $productorExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El productor seleccionado no está asignado a tu usuario.',
                    ], 403);
                }
            }

            $validated = $request->validate([
                'nombre_rancho' => 'required|string|max:255',
                'clave_unidad_produccion' => [
                    'required',
                    'string',
                    Rule::unique('predios')->ignore($predio->id)->where(function ($query) use ($request) {
                        return $query->where('productor_id', $request->productor_id);
                    }),
                ],
                'localidad' => 'required|string|max:255',
                'municipio' => 'nullable|string|max:255',
                'productor_id' => 'required|exists:productores,id',
                'domicilio' => 'nullable|string|max:255',
                'latitud' => 'nullable|numeric',
                'longitud' => 'nullable|numeric',
            ]);

            $predio->update([
                'nombre_rancho' => $validated['nombre_rancho'],
                'clave_unidad_produccion' => $validated['clave_unidad_produccion'],
                'localidad' => $validated['localidad'],
                'municipio' => $validated['municipio'] ?? 'General',
                'productor_id' => $validated['productor_id'],
                'domicilio' => $validated['domicilio'] ?? 'Conocido',
                'latitud' => $validated['latitud'] ?? null,
                'longitud' => $validated['longitud'] ?? null,
            ]);

            $predio->load('productor');

            return response()->json([
                'success' => true,
                'message' => 'Rancho actualizado con éxito en el servidor.',
                'predio' => [
                    'id' => $predio->id,
                    'nombre' => $predio->nombre_rancho,
                    'nombre_rancho' => $predio->nombre_rancho,
                    'clave_unidad_produccion' => $predio->clave_unidad_produccion,
                    'upp' => $predio->clave_unidad_produccion,
                    'latitud' => $predio->latitud,
                    'longitud' => $predio->longitud,
                    'domicilio' => $predio->domicilio,
                    'municipio' => $predio->municipio,
                    'localidad' => $predio->localidad,
                    'productor_id' => $predio->productor_id,
                    'productor' => $predio->productor ? [
                        'id' => $predio->productor->id,
                        'nombre' => $predio->productor->nombre,
                        'apellido_paterno' => $predio->productor->apellido_paterno,
                        'apellido_materno' => $predio->productor->apellido_materno,
                        'curp' => $predio->productor->curp,
                        'upp' => $predio->productor->upp,
                        'telefono' => $predio->productor->telefono,
                        'domicilio' => $predio->productor->domicilio,
                        'municipio' => $predio->productor->municipio,
                        'localidad' => $predio->productor->localidad,
                        'estado' => $predio->productor->estado,
                        'email' => $predio->productor->email,
                        'clave' => $predio->productor->clave,
                        'zona' => $predio->productor->zona,
                    ] : null,
                ],
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rancho no encontrado en el servidor.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando rancho via API: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error crítico en el servidor: '.$e->getMessage(),
            ], 500);
        }
    }

    public function vincularAHato(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'productor_id' => 'required|exists:productores,id|different:'.$id,
            ]);

            $productor = Productor::findOrFail($id);
            $vinculado = Productor::findOrFail($validated['productor_id']);

            if (! $productor->clave) {
                $productor->save();
            }

            if (! $productor->clave) {
                return response()->json([
                    'success' => false,
                    'message' => 'El productor no tiene clave ni tipo de actividad para generar una.',
                ], 422);
            }

            if ($vinculado->clave === $productor->clave) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este productor ya está vinculado a este hato.',
                ], 422);
            }

            if ($vinculado->clave) {
                return response()->json([
                    'success' => false,
                    'message' => "Este productor ya pertenece al hato {$vinculado->clave}. Gestiona ese hato para desvincularlo antes de asignarlo.",
                ], 422);
            }

            $vinculado->update(['clave' => $productor->clave]);

            return response()->json([
                'success' => true,
                'message' => 'Productor vinculado al hato exitosamente.',
                'data' => [
                    'productor' => $this->toProductorArray($productor, true),
                    'vinculado' => $this->toProductorArray($vinculado),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error crítico en el servidor: '.$e->getMessage(),
            ], 500);
        }
    }

    public function desvincularDeHato(Request $request, $id)
    {
        try {
            $productor = Productor::findOrFail($id);

            if (! $productor->clave) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este productor no pertenece a ningún hato.',
                ], 422);
            }

            $productor->update(['clave' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Productor quitado del hato con éxito.',
                'data' => $this->toProductorArray($productor->refresh()),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Productor no encontrado.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error desvinculando productor del hato: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error crítico en el servidor: '.$e->getMessage(),
            ], 500);
        }
    }

    private function toProductorArray(Productor $productor, bool $includePredios = false): array
    {
        $data = [
            'id' => $productor->id,
            'nombre' => $productor->nombre,
            'apellido_paterno' => $productor->apellido_paterno,
            'apellido_materno' => $productor->apellido_materno,
            'nombre_completo' => $productor->nombre_completo,
            'curp' => $productor->curp,
            'upp' => $productor->upp,
            'domicilio' => $productor->domicilio,
            'municipio' => $productor->municipio,
            'localidad' => $productor->localidad,
            'estado' => $productor->estado,
            'telefono' => $productor->telefono,
            'email' => $productor->email,
            'clave' => $productor->clave,
            'zona' => $productor->zona,
            'tipo_actividad' => $productor->tipo_actividad,
            'sub_tipo_actividad' => $productor->sub_tipo_actividad,
            'predios_count' => $productor->predios_count ?? $productor->predios()->count(),
            'medico_id' => $productor->medico_id,
        ];

        if ($includePredios) {
            $data['predios'] = $productor->predios->map(function (Predio $predio) {
                return [
                    'id' => $predio->id,
                    'nombre' => $predio->nombre_rancho,
                    'nombre_rancho' => $predio->nombre_rancho,
                    'clave_unidad_produccion' => $predio->clave_unidad_produccion,
                    'latitud' => $predio->latitud,
                    'longitud' => $predio->longitud,
                    'domicilio' => $predio->domicilio,
                    'municipio' => $predio->municipio,
                    'localidad' => $predio->localidad,
                ];
            })->values();

            $vinculados = collect();
            if ($productor->clave) {
                $vinculados = Productor::with('medico')
                    ->where('clave', $productor->clave)
                    ->where('id', '!=', $productor->id)
                    ->get();
            }
            $data['vinculados'] = $vinculados->map(function (Productor $v) {
                return [
                    'id' => $v->id,
                    'nombre_completo' => $v->nombre_completo,
                    'nombre' => $v->nombre,
                    'apellido_paterno' => $v->apellido_paterno,
                    'apellido_materno' => $v->apellido_materno,
                    'upp' => $v->upp,
                    'telefono' => $v->telefono,
                    'clave' => $v->clave,
                    'zona' => $v->zona,
                    'medico_id' => $v->medico_id,
                    'medico' => $v->medico ? [
                        'id' => $v->medico->id,
                        'name' => $v->medico->name,
                    ] : null,
                ];
            })->values();
        }

        return $data;
    }
}
