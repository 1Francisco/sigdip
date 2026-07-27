<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Predio;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    public function index(Request $request)
    {
        $query = Animal::with('predio.productor');

        if ($request->has('predio_id')) {
            $query->where('predio_id', $request->predio_id);
        }

        if ($request->has('especie')) {
            $query->where('especie', $request->especie);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_arete_siniiga', 'like', "%{$search}%")
                    ->orWhere('raza', 'like', "%{$search}%")
                    ->orWhere('sexo', 'like', "%{$search}%")
                    ->orWhereHas('predio', function ($pq) use ($search) {
                        $pq->where('nombre_rancho', 'like', "%{$search}%");
                    });
            });
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $animales */
        $animales = $query->latest()->paginate(20);
        $animales = $animales->withQueryString();
        $predios = Predio::with('productor')->get();

        return view('animales.index', compact('animales', 'predios'));
    }

    public function show(Animal $animale)
    {
        $animale->load('predio.productor');

        return view('animales.show', compact('animale'));
    }

    public function create()
    {
        $predios = Predio::with('productor')->get();

        return view('animales.create', compact('predios'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_arete_siniiga' => 'required|string|max:255|unique:animales',
            'predio_id' => 'required|exists:predios,id',
            'raza' => 'nullable|string|max:255',
            'sexo' => 'nullable|string|max:50',
            'edad' => 'nullable|integer|min:0',
        ]);

        Animal::create($validated);

        return redirect()->route('animales.index')->with('success', 'Animal registrado correctamente.');
    }

    public function edit(Animal $animale)
    {
        $predios = Predio::with('productor')->get();

        return view('animales.edit', compact('animale', 'predios'));
    }

    public function update(Request $request, Animal $animale)
    {
        $validated = $request->validate([
            'numero_arete_siniiga' => 'required|string|max:255|unique:animales,numero_arete_siniiga,'.$animale->id,
            'predio_id' => 'required|exists:predios,id',
            'raza' => 'nullable|string|max:255',
            'sexo' => 'nullable|string|max:50',
            'edad' => 'nullable|integer|min:0',
        ]);

        $animale->update($validated);

        return redirect()->route('animales.index')->with('success', 'Animal actualizado correctamente.');
    }

    public function destroy(Animal $animale)
    {
        $animale->delete();

        return redirect()->route('animales.index')->with('success', 'Animal eliminado del sistema.');
    }
}
