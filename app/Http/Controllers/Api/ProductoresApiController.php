<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Productor;
use App\Models\Predio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductoresApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Productor::with(['predios' => function ($prediosQuery) {
            $prediosQuery->latest();
        }])->withCount('predios')->latest();

        $productores = $query->get()->map(fn (Productor $productor) => $this->toProductorArray($productor));

        return response()->json([
            'success' => true,
            'data' => $productores,
        ]);
    }

    public function show(Request $request, $id)
    {
        $productor = Productor::with(['predios.inspecciones', 'medico'])->withCount('predios')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->toProductorArray($productor, true),
        ]);
    }

    public function predios(Request $request)
    {
        $predios = Predio::with('productor')
            ->latest()
            ->get()
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
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $predios,
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

                // Validaciones para el predio opcional (copia exacta de la web)
                'registrar_predio' => 'nullable|boolean',
                'nombre_rancho' => 'required_if:registrar_predio,1|nullable|string|max:255',
                'clave_unidad_produccion' => 'required_if:registrar_predio,1|nullable|string|unique:predios,clave_unidad_produccion',
                'predio_municipio' => 'required_if:registrar_predio,1|nullable|string|max:255',
                'predio_localidad' => 'required_if:registrar_predio,1|nullable|string|max:255',
            ]);

            return DB::transaction(function () use ($validated, $request) {
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
                    'predio' => $predio
                ], 201);
            });

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando productor via API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error crítico en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un productor desde la API Móvil
     */
    public function updateProductor(Request $request, $id)
    {
        try {
            $productor = Productor::findOrFail($id);

            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'apellido_paterno' => 'required|string|max:255',
                'apellido_materno' => 'nullable|string|max:255',
                'curp' => 'nullable|string|size:18|unique:productores,curp,' . $productor->id,
                'upp' => 'nullable|string|unique:productores,upp,' . $productor->id,
                'telefono' => 'nullable|string|max:20',
                'domicilio' => 'nullable|string',
                'municipio' => 'nullable|string',
                'localidad' => 'nullable|string',
                'estado' => 'nullable|string',
                'email' => 'nullable|email',
            ]);

            if (isset($validated['curp'])) {
                $validated['curp'] = strtoupper($validated['curp']);
            }

            $productor->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Productor actualizado con éxito en el servidor.',
                'productor' => $productor
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Productor no encontrado en el servidor.'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando productor via API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error crítico en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un rancho (predio) desde la API Móvil
     */
    public function storeRancho(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre_rancho' => 'required|string|max:255',
                'clave_unidad_produccion' => 'required|string|unique:predios,clave_unidad_produccion',
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
                'predio' => $predio
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando rancho via API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error crítico en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un rancho (predio) desde la API móvil
     */
    public function updateRancho(Request $request, $id)
    {
        try {
            $predio = Predio::findOrFail($id);

            $validated = $request->validate([
                'nombre_rancho' => 'required|string|max:255',
                'clave_unidad_produccion' => 'required|string|unique:predios,clave_unidad_produccion,' . $predio->id,
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
                    ] : null,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rancho no encontrado en el servidor.'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando rancho via API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error crítico en el servidor: ' . $e->getMessage()
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
        }

        return $data;
    }
}
