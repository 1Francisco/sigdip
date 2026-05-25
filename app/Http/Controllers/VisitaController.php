<?php

namespace App\Http\Controllers;

use App\Models\Visita;
use App\Models\Predio;
use App\Models\User;
use App\Models\Productor;
use Illuminate\Http\Request;

class VisitaController extends Controller
{
    public function index(Request $request)
    {
        $query = Visita::with(['predio.productor', 'veterinario', 'inspeccion']);
        
        if ($request->filled('fecha')) {
            $query->whereDate('fecha_programada', $request->fecha);
        }
        
        $query->orderBy('id', 'desc');
        
        if (!auth()->user()->hasRole('Administrador')) {
            $query->where('veterinario_id', auth()->id());
        }
        
        $visitas = $query->paginate(10)->withQueryString();
        return view('visitas.index', compact('visitas'));
    }

    public function create()
    {
        $productores = Productor::with('predios')->get();
        $veterinarios = User::role('Medico_Campo')->get();
        return view('visitas.create', compact('productores', 'veterinarios'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'predio_id' => 'required|exists:predios,id',
            'veterinario_id' => 'required|exists:users,id',
            'fecha_programada' => 'required|date|after_or_equal:today',
            'observaciones' => 'nullable|string',
        ]);

        // Security: Non-admins can only assign visits to themselves
        if (!auth()->user()->hasRole('Administrador')) {
            $validated['veterinario_id'] = auth()->id();
        }

        Visita::create($validated);

        return redirect()->route('visitas.index')
            ->with('success', 'Visita programada con éxito.');
    }

    public function updateEstado(Request $request, Visita $visita)
    {
        $request->validate(['estado' => 'required|in:pendiente,completada,cancelada']);
        $visita->update(['estado' => $request->estado]);

        return back()->with('success', 'Estado de la visita actualizado.');
    }

    public function edit(Visita $visita)
    {
        $visita->load('predio.productor');
        $productores = Productor::with('predios')->get();
        $veterinarios = User::role('Medico_Campo')->get();
        return view('visitas.edit', compact('visita', 'productores', 'veterinarios'));
    }

    public function update(Request $request, Visita $visita)
    {
        $validated = $request->validate([
            'predio_id' => 'required|exists:predios,id',
            'veterinario_id' => 'required|exists:users,id',
            'fecha_programada' => 'required|date',
            'observaciones' => 'nullable|string',
        ]);

        if (!auth()->user()->hasRole('Administrador')) {
            $validated['veterinario_id'] = $visita->veterinario_id; // No puede reasignar
        }

        $visita->update($validated);

        return redirect()->route('visitas.index')->with('success', 'Visita actualizada.');
    }

    public function reprogramar(Request $request, Visita $visita)
    {
        $request->validate([
            'fecha_programada' => 'required|date',
        ]);

        $visita->update([
            'fecha_programada' => $request->fecha_programada,
            'estado' => 'pendiente',
        ]);

        return back()->with('success', 'Visita reprogramada con éxito para el ' . $visita->fecha_programada->format('d/m/Y') . '.');
    }
}
