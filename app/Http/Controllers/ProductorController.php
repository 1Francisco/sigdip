<?php

namespace App\Http\Controllers;

use App\Models\Productor;
use App\Models\Predio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductorController extends Controller
{
    public function index()
    {
        $productores = Productor::withCount('predios')->latest()->paginate(10);
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
                    'apellido_materno' => $validated['apellido_materno'],
                    'curp' => $validated['curp'],
                    'upp' => $validated['upp'],
                    'domicilio' => $validated['domicilio'],
                    'municipio' => $validated['municipio'],
                    'localidad' => $validated['localidad'],
                    'estado' => $validated['estado'],
                    'telefono' => $validated['telefono'],
                    'email' => $validated['email'],
                ]);

                $message = "Productor registrado con éxito.";

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
                    $message = "Productor y su Unidad de Producción (UPP) registrados con éxito.";
                }

                return redirect()->route('productores.index')->with('success', $message);
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al procesar el registro: ' . $e->getMessage());
        }
    }

    public function edit(Productor $productore)
    {
        return view('productores.edit', ['productor' => $productore]);
    }

    public function update(Request $request, Productor $productore)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'curp' => 'nullable|string|size:18|unique:productores,curp,'.$productore->id,
            'upp' => 'nullable|string|unique:productores,upp,'.$productore->id,
            'domicilio' => 'nullable|string',
            'municipio' => 'nullable|string',
            'localidad' => 'nullable|string',
            'estado' => 'nullable|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
        ]);

        $productore->update($validated);

        return redirect()->route('productores.index')
            ->with('success', 'Datos del productor actualizados.');
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
