<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| Rutas de la Aplicación Móvil (API)
|--------------------------------------------------------------------------
*/

// Rutas Públicas
Route::post('/login', [AuthController::class, 'login']);

// Rutas Protegidas (Requieren el Token que devuelve el login)
Route::middleware('auth:sanctum')->group(function () {
    
    // Para que la app verifique quién es el usuario actual
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        $user->roles = $user->getRoleNames();
        return $user;
    });

    // Para cerrar la sesión de la app
    Route::post('/logout', [AuthController::class, 'logout']);

    // Sincronización Móvil
    Route::get('/sync/catalogos', [\App\Http\Controllers\Api\SyncController::class, 'catalogos']);
    Route::post('/sync/inspecciones', [\App\Http\Controllers\Api\SyncController::class, 'uploadInspecciones']);
});
