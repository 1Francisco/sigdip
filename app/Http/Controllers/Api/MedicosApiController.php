<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MedicosApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores pueden gestionar médicos.');

        $medicos = User::role('Medico_Campo')
            ->orderByDesc('id')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'created_at' => $u->created_at->format('d/m/Y'),
            ]);

        return response()->json([
            'success' => true,
            'data' => $medicos,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores pueden registrar médicos.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $medico = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $medico->assignRole('Medico_Campo');

        return response()->json([
            'success' => true,
            'message' => 'Médico Verificador registrado correctamente.',
            'data' => [
                'id' => $medico->id,
                'name' => $medico->name,
                'email' => $medico->email,
                'created_at' => $medico->created_at->format('d/m/Y'),
            ]
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores pueden eliminar médicos.');

        $medico = User::role('Medico_Campo')->findOrFail($id);
        $medico->delete();

        return response()->json([
            'success' => true,
            'message' => 'Médico eliminado del sistema con éxito.'
        ]);
    }
}
