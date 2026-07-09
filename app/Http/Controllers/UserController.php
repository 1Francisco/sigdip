<?php

namespace App\Http\Controllers;

use App\Models\Productor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::role('Medico_Campo')->withCount('productores');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $medicos = $query->get();

        return view('users.index', compact('medicos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Asignar automáticamente el rol de Médico de Campo
        $user->assignRole('Medico_Campo');

        return redirect()->route('usuarios.index')->with('success', 'Médico Verificador registrado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $usuario)
    {
        $usuario->load('roles');
        $usuario->loadCount('productores');
        $productores = Productor::where('medico_id', $usuario->id)
            ->with('medico')
            ->withCount('predios')
            ->latest()
            ->paginate(10);

        return view('users.show', compact('usuario', 'productores'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $usuario)
    {
        return view('users.edit', compact('usuario'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$usuario->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $usuario->name = $request->name;
        $usuario->email = $request->email;

        if ($request->filled('password')) {
            $usuario->password = Hash::make($request->password);
        }

        $usuario->save();

        return redirect()->route('usuarios.index')->with('success', 'Datos del médico actualizados.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return redirect()->route('usuarios.index')->with('error', 'No puedes eliminarte a ti mismo.');
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', 'Médico eliminado del sistema.');
    }

    /**
     * Show form to assign productores to a medico.
     */
    public function asignarProductores(User $usuario)
    {
        $asignados = Productor::where('medico_id', $usuario->id)
            ->withCount('predios')
            ->orderBy('nombre')
            ->get();

        $disponibles = Productor::whereNull('medico_id')
            ->orWhere('medico_id', '!=', $usuario->id)
            ->withCount('predios')
            ->orderBy('nombre')
            ->paginate(20);

        return view('users.asignar-productores', compact('usuario', 'asignados', 'disponibles'));
    }

    /**
     * Save productores assignment to a medico.
     */
    public function guardarAsignacion(Request $request, User $usuario)
    {
        $request->validate([
            'productor_ids' => 'nullable|array',
            'productor_ids.*' => 'exists:productores,id',
        ]);

        $productorIds = $request->input('productor_ids', []);

        // Asignar los productores seleccionados a este médico
        Productor::whereIn('id', $productorIds)->update(['medico_id' => $usuario->id]);

        // Desasignar productores que ya no están en la lista (solo los que eran de este médico)
        Productor::where('medico_id', $usuario->id)
            ->whereNotIn('id', $productorIds)
            ->update(['medico_id' => null]);

        $count = count($productorIds);

        return redirect()->route('usuarios.show', $usuario)
            ->with('success', "Se asignaron {$count} productores a {$usuario->name} correctamente.");
    }

    /**
     * Unassign a single productor from a medico.
     */
    public function desasignarProductor(User $usuario, Productor $productor)
    {
        if ($productor->medico_id !== $usuario->id) {
            abort(404, 'Este productor no está asignado a este médico.');
        }

        $productor->update(['medico_id' => null]);

        return redirect()->route('usuarios.show', $usuario)
            ->with('success', "Productor {$productor->nombre} desasignado de {$usuario->name}.");
    }

    // ========== API METHODS FOR MOBILE APP ==========

    /**
     * API: Get assignable productores for a medico (asignados + disponibles).
     */
    public function productoresAsignablesApi(Request $request, User $usuario)
    {
        if ($request->user() && ! $request->user()->hasRole('Administrador')) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $asignados = Productor::where('medico_id', $usuario->id)
            ->withCount('predios')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'apellido_paterno' => $p->apellido_paterno,
                'apellido_materno' => $p->apellido_materno,
                'curp' => $p->curp,
                'upp' => $p->upp,
                'predios_count' => $p->predios_count,
            ]);

        $disponibles = Productor::whereNull('medico_id')
            ->withCount('predios')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'apellido_paterno' => $p->apellido_paterno,
                'apellido_materno' => $p->apellido_materno,
                'curp' => $p->curp,
                'upp' => $p->upp,
                'predios_count' => $p->predios_count,
            ]);

        return response()->json([
            'success' => true,
            'medico' => ['id' => $usuario->id, 'name' => $usuario->name, 'email' => $usuario->email],
            'asignados' => $asignados,
            'disponibles' => $disponibles,
        ]);
    }

    /**
     * API: Save productores assignment to a medico.
     */
    public function guardarAsignacionApi(Request $request, User $usuario)
    {
        if ($request->user() && ! $request->user()->hasRole('Administrador')) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $request->validate([
            'productor_ids' => 'nullable|array',
            'productor_ids.*' => 'exists:productores,id',
        ]);

        $productorIds = $request->input('productor_ids', []);

        Productor::whereIn('id', $productorIds)->update(['medico_id' => $usuario->id]);

        $count = count($productorIds);

        return response()->json([
            'success' => true,
            'message' => "Se asignaron {$count} productores a {$usuario->name} correctamente.",
            'asignados_count' => $count,
        ]);
    }

    /**
     * API: Unassign a single productor from a medico.
     */
    public function desasignarProductorApi(Request $request, User $usuario, Productor $productor)
    {
        if ($request->user() && ! $request->user()->hasRole('Administrador')) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        if ($productor->medico_id !== $usuario->id) {
            return response()->json([
                'success' => false,
                'message' => 'Este productor no está asignado a este médico.',
            ], 404);
        }

        $productor->update(['medico_id' => null]);

        return response()->json([
            'success' => true,
            'message' => "Productor {$productor->nombre} desasignado de {$usuario->name}.",
        ]);
    }
}
