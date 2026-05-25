<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Inicio de sesión para la Aplicación Móvil (PWA / Capacitor)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        // Validar que el usuario exista y la contraseña sea correcta
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        // Crear el token usando Laravel Sanctum
        $token = $user->createToken('mobile-app-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames() // Enviamos los roles al celular para mostrar/ocultar menús
            ],
            'token' => $token
        ]);
    }

    /**
     * Cierre de sesión y revocación de tokens
     */
    public function logout(Request $request)
    {
        // Borra el token actual con el que se está autenticando
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ]);
    }
}
