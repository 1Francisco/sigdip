<?php

namespace App\Http\Controllers;

use App\Models\Predio;
use App\Models\Productor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductorController extends Controller
{
    public function index(Request $request)
    {
        $query = Productor::withCount('predios')->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido_paterno', 'like', "%{$search}%")
                    ->orWhere('apellido_materno', 'like', "%{$search}%")
                    ->orWhere('curp', 'like', "%{$search}%")
                    ->orWhere('upp', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        $productores = $query->paginate(10)->withQueryString();

        return view('productores.index', compact('productores'));
    }

    public function create()
    {
        return view('productores.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'curp' => 'nullable|string|size:18|unique:productores',
            'upp' => 'nullable|string|unique:productores',
            'domicilio' => 'nullable|string',
            'municipio' => 'nullable|string',
            'localidad' => 'nullable|string',
            'estado' => 'nullable|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',

            // Validaciones para el predio (si se envían)
            'registrar_predio' => 'nullable|boolean',
            'nombre_rancho' => 'required_if:registrar_predio,1|nullable|string|max:255',
            'clave_unidad_produccion' => 'required_if:registrar_predio,1|nullable|string|unique:predios,clave_unidad_produccion',
            'predio_municipio' => 'required_if:registrar_predio,1|nullable|string|max:255',
            'predio_localidad' => 'required_if:registrar_predio,1|nullable|string|max:255',
            'latitud' => 'nullable|string|max:50',
            'longitud' => 'nullable|string|max:50',
            'predio_domicilio' => 'nullable|string|max:255',
        ]);

        try {
            return DB::transaction(function () use ($request, $validated) {
                // 1. Crear el Productor
                $productor = Productor::create([
                    'nombre' => $validated['nombre'],
                    'apellido_paterno' => $validated['apellido_paterno'],
                    'apellido_materno' => $validated['apellido_materno'] ?? null,
                    'curp' => $validated['curp'] ?? null,
                    'upp' => $validated['upp'] ?? null,
                    'domicilio' => $validated['domicilio'] ?? null,
                    'municipio' => $validated['municipio'] ?? null,
                    'localidad' => $validated['localidad'] ?? null,
                    'estado' => $validated['estado'] ?? 'Sinaloa',
                    'telefono' => $validated['telefono'] ?? null,
                    'email' => $validated['email'] ?? null,
                ]);

                $message = 'Productor registrado con éxito.';

                // 2. Crear el Predio si se solicitó
                if ($request->has('registrar_predio') && $request->registrar_predio == '1') {
                    Predio::create([
                        'nombre_rancho' => $validated['nombre_rancho'],
                        'clave_unidad_produccion' => $validated['clave_unidad_produccion'],
                        'municipio' => $validated['predio_municipio'],
                        'localidad' => $validated['predio_localidad'],
                        'latitud' => $validated['latitud'],
                        'longitud' => $validated['longitud'],
                        'domicilio' => $validated['predio_domicilio'],
                        'productor_id' => $productor->id,
                    ]);
                    $message = 'Productor y su Unidad de Producción (UPP) registrados con éxito.';
                }

                return redirect()->route('productores.index')->with('success', $message);
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al procesar el registro: '.$e->getMessage());
        }
    }

    public function edit(Productor $productor)
    {
        return view('productores.edit', ['productor' => $productor]);
    }

    public function update(Request $request, Productor $productor)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'curp' => 'nullable|string|size:18|unique:productores,curp,'.$productor->id,
            'upp' => 'nullable|string|unique:productores,upp,'.$productor->id,
            'domicilio' => 'nullable|string',
            'municipio' => 'nullable|string',
            'localidad' => 'nullable|string',
            'estado' => 'nullable|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
        ]);

        $productor->update($validated);

        return redirect()->route('productores.index')
            ->with('success', 'Datos del productor actualizados.');
    }

    public function show(Productor $productor)
    {
        $productor->load(['predios', 'medico']);

        return view('productores.show', ['productor' => $productor]);
    }

    public function destroy(Productor $productor)
    {
        $productor->predios()->delete();
        $productor->delete();

        return redirect()->route('productores.index')
            ->with('success', 'Productor y sus predios eliminados.');
    }

    public function storeAjax(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'curp' => 'nullable|string|size:18|unique:productores',
            'upp' => 'nullable|string|unique:productores',
            'domicilio' => 'nullable|string',
            'municipio' => 'nullable|string',
            'localidad' => 'nullable|string',
            'estado' => 'nullable|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
        ]);

        $productor = Productor::create($validated);

        return response()->json($productor);
    }
}
