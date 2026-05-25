<?php

namespace App\Http\Controllers;

use App\Models\Predio;
use App\Models\Productor;
use Illuminate\Http\Request;

class PredioController extends Controller
{
    public function index(Request $request)
    {
        $query = Predio::with('productor')->latest();

        if ($request->has('productor_id')) {
            $query->where('productor_id', $request->productor_id);
        }

        $predios = $query->paginate(10);
        return view('predios.index', compact('predios'));
    }

    public function create()
    {
        $productores = Productor::all();
        return view('predios.create', compact('productores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_rancho' => 'required|string|max:255',
            'clave_unidad_produccion' => 'required|string|unique:predios',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'domicilio' => 'nullable|string',
            'municipio' => 'nullable|string',
            'localidad' => 'required|string',
            'productor_id' => 'required|exists:productores,id',
        ]);

        Predio::create($validated);

        return redirect()->route('predios.index')
            ->with('success', 'Predio registrado con éxito.');
    }

    public function edit(Predio $predio)
    {
        $productores = Productor::all();
        return view('predios.edit', compact('predio', 'productores'));
    }

    public function update(Request $request, Predio $predio)
    {
        $validated = $request->validate([
            'nombre_rancho' => 'required|string|max:255',
            'clave_unidad_produccion' => 'required|string|unique:predios,clave_unidad_produccion,'.$predio->id,
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'domicilio' => 'nullable|string',
            'municipio' => 'nullable|string',
            'localidad' => 'required|string',
            'productor_id' => 'required|exists:productores,id',
        ]);

        $predio->update($validated);

        return redirect()->route('predios.index')
            ->with('success', 'Datos del predio actualizados.');
    }

    /**
     * Actualiza las coordenadas del predio vía AJAX.
     */
    public function updateCoordenadas(Request $request, Predio $predio)
    {
        try {
            $validated = $request->validate([
                'latitud' => 'required|numeric|between:-90,90',
                'longitud' => 'required|numeric|between:-180,180',
            ]);

            $predio->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Coordenadas del predio actualizadas con éxito.',
                'latitud' => $predio->latitud,
                'longitud' => $predio->longitud,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar coordenadas: ' . $e->getMessage()
            ], 422);
        }
    }
}
