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
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\ProductoresApiController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\VisitasApiController;
use App\Http\Controllers\Api\InspeccionesApiController;
use App\Http\Controllers\Api\MedicosApiController;

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
    Route::get('/sync/catalogos', [SyncController::class, 'catalogos']);
    Route::post('/sync/inspecciones', [SyncController::class, 'uploadInspecciones']);
    Route::get('/dashboard/stats', [DashboardApiController::class, 'getStats']);

    // Gestión de Productores y Predios desde la App Móvil
    Route::get('/productores', [ProductoresApiController::class, 'index']);
    Route::get('/productores/{id}', [ProductoresApiController::class, 'show']);
    Route::get('/predios', [ProductoresApiController::class, 'predios']);
    Route::post('/productores', [ProductoresApiController::class, 'storeProductor']);
    Route::put('/productores/{id}', [ProductoresApiController::class, 'updateProductor']);
    Route::post('/predios', [ProductoresApiController::class, 'storeRancho']);
    Route::put('/predios/{id}', [ProductoresApiController::class, 'updateRancho']);

    // Visitas desde la App Móvil
    Route::get('/visitas', [VisitasApiController::class, 'index']);
    Route::get('/visitas/{id}', [VisitasApiController::class, 'show']);
    Route::post('/visitas', [VisitasApiController::class, 'store']);
    Route::put('/visitas/{id}', [VisitasApiController::class, 'update']);
    Route::patch('/visitas/{id}/estado', [VisitasApiController::class, 'updateEstado']);
    Route::patch('/visitas/{id}/reprogramar', [VisitasApiController::class, 'reprogramar']);

    // Inspecciones desde la App Móvil
    Route::get('/inspecciones', [InspeccionesApiController::class, 'index']);
    Route::get('/inspecciones/{id}', [InspeccionesApiController::class, 'show']);
    Route::get('/inspecciones/{id}/pdf', [InspeccionesApiController::class, 'pdf']);
    Route::patch('/inspecciones/{id}', [InspeccionesApiController::class, 'update']);
    Route::get('/reportes/sábana-excel', [\App\Http\Controllers\ReporteController::class, 'exportExcel']);
    Route::get('/censo/buscar-arete/{numero}', [\App\Http\Controllers\InspeccionController::class, 'buscarArete']);

    // Médicos desde la App Móvil
    Route::get('/medicos', [MedicosApiController::class, 'index']);
    Route::post('/medicos', [MedicosApiController::class, 'store']);
    Route::delete('/medicos/{id}', [MedicosApiController::class, 'destroy']);
});
