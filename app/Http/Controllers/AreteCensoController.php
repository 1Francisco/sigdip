<?php

namespace App\Http\Controllers;

use App\Models\AreteCenso;
use App\Models\Predio;
use App\Models\Productor;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AreteCensoController extends Controller
{
    public function index(Request $request)
    {
        $query = AreteCenso::with(['productor', 'predio']);

        if ($request->has('productor_id')) {
            $query->where('productor_id', $request->productor_id);
        }

        if ($request->has('predio_id')) {
            $query->where('predio_id', $request->predio_id);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_arete', 'like', "%{$search}%")
                    ->orWhere('raza', 'like', "%{$search}%")
                    ->orWhere('sexo', 'like', "%{$search}%")
                    ->orWhereHas('productor', function ($pq) use ($search) {
                        $pq->where('nombre', 'like', "%{$search}%")
                            ->orWhere('apellido_paterno', 'like', "%{$search}%");
                    });
            });
        }

        /** @var LengthAwarePaginator $aretes */
        $aretes = $query->latest()->paginate(20);
        $aretes = $aretes->withQueryString();
        $productores = Productor::all();
        $predios = Predio::with('productor')->get();

        return view('aretes-censo.index', compact('aretes', 'productores', 'predios'));
    }

    public function show(AreteCenso $aretes_censo)
    {
        $aretes_censo->load(['productor', 'predio']);

        return view('aretes-censo.show', compact('aretes_censo'));
    }

    public function create()
    {
        $productores = Productor::all();
        $predios = Predio::with('productor')->get();

        return view('aretes-censo.create', compact('productores', 'predios'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_arete' => 'required|string|max:255|unique:aretes_censo',
            'productor_id' => 'required|exists:productores,id',
            'predio_id' => 'required|exists:predios,id',
            'raza' => 'nullable|string|max:255',
            'sexo' => 'nullable|string|max:50',
            'fecha_nacimiento' => 'nullable|date',
            'edad_meses' => 'nullable|integer|min:0',
            'sacrificio' => 'nullable|boolean',
        ]);

        AreteCenso::create($validated);

        return redirect()->route('aretes-censo.index')->with('success', 'Arete del censo registrado correctamente.');
    }

    public function edit(AreteCenso $aretes_censo)
    {
        $productores = Productor::all();
        $predios = Predio::with('productor')->get();

        return view('aretes-censo.edit', compact('aretes_censo', 'productores', 'predios'));
    }

    public function update(Request $request, AreteCenso $aretes_censo)
    {
        $validated = $request->validate([
            'numero_arete' => 'required|string|max:255|unique:aretes_censo,numero_arete,'.$aretes_censo->id,
            'productor_id' => 'required|exists:productores,id',
            'predio_id' => 'required|exists:predios,id',
            'raza' => 'nullable|string|max:255',
            'sexo' => 'nullable|string|max:50',
            'fecha_nacimiento' => 'nullable|date',
            'edad_meses' => 'nullable|integer|min:0',
            'sacrificio' => 'nullable|boolean',
        ]);

        $aretes_censo->update($validated);

        return redirect()->route('aretes-censo.index')->with('success', 'Arete del censo actualizado correctamente.');
    }

    public function destroy(AreteCenso $aretes_censo)
    {
        $aretes_censo->delete();

        return redirect()->route('aretes-censo.index')->with('success', 'Arete del censo eliminado.');
    }
}
