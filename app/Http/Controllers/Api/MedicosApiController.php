<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MedicosApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores pueden gestionar médicos.');

        $medicos = User::role('Medico_Campo')
            ->withCount('productores')
            ->orderByDesc('id')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'zona' => $u->zona,
                'actividad' => $u->actividad,
                'created_at' => $u->created_at->format('d/m/Y'),
                'productores_count' => $u->productores_count,
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
            'zona' => 'required|in:A,B',
            'actividad' => 'required|string',
        ]);

        $medico = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'zona' => $validated['zona'],
            'actividad' => $validated['actividad'],
        ]);

        $medico->assignRole('Medico_Campo');

        return response()->json([
            'success' => true,
            'message' => 'Médico Verificador registrado correctamente.',
            'data' => [
                'id' => $medico->id,
                'name' => $medico->name,
                'email' => $medico->email,
                'zona' => $medico->zona,
                'actividad' => $medico->actividad,
                'created_at' => $medico->created_at->format('d/m/Y'),
            ],
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores pueden gestionar médicos.');

        $medico = User::role('Medico_Campo')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $medico->id,
                'name' => $medico->name,
                'email' => $medico->email,
                'zona' => $medico->zona,
                'actividad' => $medico->actividad,
                'created_at' => $medico->created_at->format('d/m/Y'),
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores pueden modificar médicos.');

        $medico = User::role('Medico_Campo')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$medico->id,
            'password' => 'nullable|string|min:8',
            'zona' => 'required|in:A,B',
            'actividad' => 'required|string',
        ]);

        $medico->name = $validated['name'];
        $medico->email = $validated['email'];
        $medico->zona = $validated['zona'];
        $medico->actividad = $validated['actividad'];

        if (! empty($validated['password'])) {
            $medico->password = Hash::make($validated['password']);
        }

        $medico->save();

        return response()->json([
            'success' => true,
            'message' => 'Médico actualizado correctamente.',
            'data' => [
                'id' => $medico->id,
                'name' => $medico->name,
                'email' => $medico->email,
                'zona' => $medico->zona,
                'actividad' => $medico->actividad,
                'created_at' => $medico->created_at->format('d/m/Y'),
            ],
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores pueden eliminar médicos.');

        $medico = User::role('Medico_Campo')->findOrFail($id);
        $medico->delete();

        return response()->json([
            'success' => true,
            'message' => 'Médico eliminado del sistema con éxito.',
        ]);
    }
}
