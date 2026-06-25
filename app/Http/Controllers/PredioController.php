<?php

namespace App\Http\Controllers;

use App\Models\Predio;
use App\Models\Productor;
use Illuminate\Http\Request;

class PredioController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Predio::with('productor')->latest();

        if ($user && ! $user->hasRole('Administrador')) {
            $query->whereHas('productor', function ($q) use ($user) {
                $q->where('medico_id', $user->id);
            });
        }

        if ($request->has('productor_id')) {
            $query->where('productor_id', $request->productor_id);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre_rancho', 'like', "%{$search}%")
                    ->orWhere('clave_unidad_produccion', 'like', "%{$search}%")
                    ->orWhere('localidad', 'like', "%{$search}%")
                    ->orWhere('municipio', 'like', "%{$search}%");
            });
        }

        $predios = $query->paginate(10)->withQueryString();

        return view('predios.index', compact('predios'));
    }

    public function create()
    {
        $user = auth()->user();
        if ($user && ! $user->hasRole('Administrador')) {
            $productores = Productor::where('medico_id', $user->id)->get();
        } else {
            $productores = Productor::all();
        }

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

        $user = auth()->user();
        if ($user && ! $user->hasRole('Administrador')) {
            $productorExists = Productor::where('id', $request->productor_id)
                ->where('medico_id', $user->id)
                ->exists();
            if (! $productorExists) {
                return back()->withInput()->with('error', 'El productor seleccionado no está asignado a tu usuario.');
            }
        }

        Predio::create($validated);

        return redirect()->route('predios.index')
            ->with('success', 'Predio registrado con éxito.');
    }

    public function edit(Predio $predio)
    {
        $user = auth()->user();
        if ($user && ! $user->hasRole('Administrador') && $predio->productor->medico_id !== $user->id) {
            abort(403, 'No tienes permiso para editar este predio.');
        }

        if ($user && ! $user->hasRole('Administrador')) {
            $productores = Productor::where('medico_id', $user->id)->get();
        } else {
            $productores = Productor::all();
        }

        return view('predios.edit', compact('predio', 'productores'));
    }

    public function update(Request $request, Predio $predio)
    {
        $user = auth()->user();
        if ($user && ! $user->hasRole('Administrador')) {
            if ($predio->productor->medico_id !== $user->id) {
                abort(403, 'No tienes permiso para modificar este predio.');
            }
            $productorExists = Productor::where('id', $request->productor_id)
                ->where('medico_id', $user->id)
                ->exists();
            if (! $productorExists) {
                return back()->withInput()->with('error', 'El productor seleccionado no está asignado a tu usuario.');
            }
        }

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

    public function show(Predio $predio)
    {
        $user = auth()->user();
        if ($user && ! $user->hasRole('Administrador') && $predio->productor->medico_id !== $user->id) {
            abort(403, 'No tienes permiso para ver este predio.');
        }

        $predio->load(['productor', 'animales']);

        return view('predios.show', compact('predio'));
    }

    public function destroy(Predio $predio)
    {
        $user = auth()->user();
        if ($user && ! $user->hasRole('Administrador') && $predio->productor->medico_id !== $user->id) {
            abort(403, 'No tienes permiso para eliminar este predio.');
        }

        $predio->animales()->delete();
        $predio->delete();

        return redirect()->route('predios.index')
            ->with('success', 'Predio y sus animales eliminados.');
    }

    /**
     * Actualiza las coordenadas del predio vía AJAX.
     */
    public function updateCoordenadas(Request $request, Predio $predio)
    {
        $user = auth()->user();
        if ($user && ! $user->hasRole('Administrador') && $predio->productor->medico_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para modificar este predio.',
            ], 403);
        }

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
                'message' => 'Error al guardar coordenadas: '.$e->getMessage(),
            ], 422);
        }
    }
}
