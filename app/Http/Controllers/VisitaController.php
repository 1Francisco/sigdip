<?php

namespace App\Http\Controllers;

use App\Models\Productor;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Http\Request;

class VisitaController extends Controller
{
    public function index(Request $request)
    {
        $query = Visita::with(['predio.productor', 'veterinario', 'inspeccion']);

        if ($request->filled('fecha')) {
            $query->whereDate('fecha_programada', $request->fecha);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('codigo_unico', 'like', "%{$search}%")
                    ->orWhere('observaciones', 'like', "%{$search}%")
                    ->orWhereHas('predio', function ($pq) use ($search) {
                        $pq->where('nombre_rancho', 'like', "%{$search}%")
                            ->orWhereHas('productor', function ($prq) use ($search) {
                                $prq->where('nombre', 'like', "%{$search}%")
                                    ->orWhere('apellido_paterno', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $query->orderBy('id', 'desc');

        if (! auth()->user()->hasRole('Administrador')) {
            $query->where('veterinario_id', auth()->id());
        }

        $visitas = $query->paginate(10)->withQueryString();

        return view('visitas.index', compact('visitas'));
    }

    public function create()
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Administrador')) {
            $productores = Productor::with('predios')->where('medico_id', $user->id)->get();
        } else {
            $productores = Productor::with('predios')->get();
        }
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

        $user = auth()->user();
        if ($user && !$user->hasRole('Administrador')) {
            $validated['veterinario_id'] = $user->id;
            $predio = \App\Models\Predio::with('productor')->find($request->predio_id);
            if (!$predio || !$predio->productor || $predio->productor->medico_id !== $user->id) {
                return back()->withInput()->with('error', 'El predio seleccionado no pertenece a tus productores asignados.');
            }
        }

        Visita::create($validated);

        return redirect()->route('visitas.index')
            ->with('success', 'Visita programada con éxito.');
    }

    public function updateEstado(Request $request, Visita $visita)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Administrador') && $visita->veterinario_id !== $user->id) {
            abort(403, 'No tienes permiso para modificar esta visita.');
        }

        $request->validate(['estado' => 'required|in:pendiente,completada,cancelada']);
        $visita->update(['estado' => $request->estado]);

        return back()->with('success', 'Estado de la visita actualizado.');
    }

    public function edit(Visita $visita)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Administrador') && $visita->veterinario_id !== $user->id) {
            abort(403, 'No tienes permiso para editar esta visita.');
        }

        $visita->load('predio.productor');
        if ($user && !$user->hasRole('Administrador')) {
            $productores = Productor::with('predios')->where('medico_id', $user->id)->get();
        } else {
            $productores = Productor::with('predios')->get();
        }
        $veterinarios = User::role('Medico_Campo')->get();

        return view('visitas.edit', compact('visita', 'productores', 'veterinarios'));
    }

    public function update(Request $request, Visita $visita)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Administrador')) {
            if ($visita->veterinario_id !== $user->id) {
                abort(403, 'No tienes permiso para modificar esta visita.');
            }
            $predio = \App\Models\Predio::with('productor')->find($request->predio_id);
            if (!$predio || !$predio->productor || $predio->productor->medico_id !== $user->id) {
                return back()->withInput()->with('error', 'El predio seleccionado no pertenece a tus productores asignados.');
            }
        }

        $validated = $request->validate([
            'predio_id' => 'required|exists:predios,id',
            'veterinario_id' => 'required|exists:users,id',
            'fecha_programada' => 'required|date',
            'observaciones' => 'nullable|string',
        ]);

        if (! auth()->user()->hasRole('Administrador')) {
            $validated['veterinario_id'] = $visita->veterinario_id; // No puede reasignar
        }

        $visita->update($validated);

        return redirect()->route('visitas.index')->with('success', 'Visita actualizada.');
    }

    public function show(Visita $visita)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Administrador') && $visita->veterinario_id !== $user->id) {
            abort(403, 'No tienes permiso para acceder a esta visita.');
        }

        $visita->load(['predio.productor', 'veterinario', 'inspeccion']);

        return view('visitas.show', compact('visita'));
    }

    public function destroy(Visita $visita)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Administrador') && $visita->veterinario_id !== $user->id) {
            abort(403, 'No tienes permiso para eliminar esta visita.');
        }

        $visita->delete();

        return redirect()->route('visitas.index')
            ->with('success', 'Visita eliminada.');
    }

    public function reprogramar(Request $request, Visita $visita)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Administrador') && $visita->veterinario_id !== $user->id) {
            abort(403, 'No tienes permiso para reprogramar esta visita.');
        }

        $request->validate([
            'fecha_programada' => 'required|date',
        ]);

        $visita->update([
            'fecha_programada' => $request->fecha_programada,
            'estado' => 'pendiente',
        ]);

        return back()->with('success', 'Visita reprogramada con éxito para el '.$visita->fecha_programada->format('d/m/Y').'.');
    }
}
