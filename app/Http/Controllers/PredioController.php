<?php

namespace App\Http\Controllers;

use App\Models\AreteCenso;
use App\Models\Predio;
use App\Models\Productor;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

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

        /** @var LengthAwarePaginator $predios */
        $predios = $query->paginate(10);
        $predios = $predios->withQueryString();

        $aretes = null;
        $tab = $request->get('tab', 'predios');
        if ($user->hasRole('Administrador')) {
            $aretesQuery = AreteCenso::with(['productor', 'predio']);

            if ($searchArete = $request->get('search_arete')) {
                $aretesQuery->where(function ($q) use ($searchArete) {
                    $q->where('numero_arete', 'like', "%{$searchArete}%")
                        ->orWhere('raza', 'like', "%{$searchArete}%")
                        ->orWhereHas('productor', function ($pq) use ($searchArete) {
                            $pq->where('nombre', 'like', "%{$searchArete}%")
                                ->orWhere('apellido_paterno', 'like', "%{$searchArete}%");
                        });
                });
            }

            $aretes = $aretesQuery->latest()->paginate(20, ['*'], 'arete_page');
            $aretes = $aretes->withQueryString();
        }

        return view('predios.index', compact('predios', 'aretes', 'tab'));
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
            'clave_unidad_produccion' => [
                'required',
                'string',
                Rule::unique('predios')->where(function ($query) use ($request) {
                    return $query->where('productor_id', $request->productor_id);
                })
            ],
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
            'clave_unidad_produccion' => [
                'required',
                'string',
                Rule::unique('predios')->ignore($predio->id)->where(function ($query) use ($request) {
                    return $query->where('productor_id', $request->productor_id);
                })
            ],
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

    public function show(Request $request, Predio $predio)
    {
        $user = auth()->user();
        if ($user && ! $user->hasRole('Administrador') && $predio->productor->medico_id !== $user->id) {
            abort(403, 'No tienes permiso para ver este predio.');
        }

        $predio->load('productor');

        $animalSearch = $request->get('animal_search');
        $animales = $predio->animales()
            ->when($animalSearch, fn ($q) => $q->where(function ($q) use ($animalSearch) {
                $q->where('numero_arete_siniiga', 'like', "%{$animalSearch}%")
                    ->orWhere('raza', 'like', "%{$animalSearch}%");
            }))
            ->paginate(10);

        // Obtener otros predios/productores con la misma clave de unidad de producción (UPP)
        $otrosPredios = collect();
        if ($predio->clave_unidad_produccion) {
            $otrosPredios = Predio::with(['productor', 'productor.medico'])
                ->where('clave_unidad_produccion', $predio->clave_unidad_produccion)
                ->where('productor_id', '!=', $predio->productor_id)
                ->get();
        }

        return view('predios.show', compact('predio', 'animales', 'otrosPredios'));
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
