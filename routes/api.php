<?php

use App\Http\Controllers\Api\AnimalesApiController;
use App\Http\Controllers\Api\AreteCensoApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardApiController;
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

use App\Http\Controllers\Api\InspeccionController as InspeccionDetallesController;
use App\Http\Controllers\Api\InspeccionesApiController;
use App\Http\Controllers\Api\MedicosApiController;
use App\Http\Controllers\Api\ProductoresApiController;
use App\Http\Controllers\Api\ReportesRendimientoApiController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\VisitasApiController;
use App\Http\Controllers\InspeccionController;
use App\Http\Controllers\ProductorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la Aplicación Móvil (API)
|--------------------------------------------------------------------------
*/

// Rutas Públicas
Route::post('/login', [AuthController::class, 'login']);
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'time' => now()->toIso8601String()]);
});

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
    Route::post('/sync/visitas', [SyncController::class, 'uploadVisitas']);
    Route::post('/sync/productores', [SyncController::class, 'uploadProductores']);
    Route::post('/sync/predios', [SyncController::class, 'uploadPredios']);
    Route::get('/dashboard/stats', [DashboardApiController::class, 'getStats']);

    // Gestión de Productores y Predios desde la App Móvil
    Route::get('/productores/buscar-por-clave', [ProductorController::class, 'buscarPorClave']);
    Route::get('/productores/buscar', [ProductoresApiController::class, 'buscar']);
    Route::get('/productores/preview-clave', [ProductoresApiController::class, 'previewClave']);
    Route::get('/productores', [ProductoresApiController::class, 'index']);
    Route::get('/productores/{id}', [ProductoresApiController::class, 'show']);
    Route::get('/predios', [ProductoresApiController::class, 'predios']);
    Route::get('/predios/{id}', [ProductoresApiController::class, 'showPredio']);
    Route::post('/productores', [ProductoresApiController::class, 'storeProductor']);
    Route::put('/productores/{id}', [ProductoresApiController::class, 'updateProductor']);
    Route::delete('/productores/{id}', [ProductoresApiController::class, 'destroyProductor']);
    Route::post('/predios', [ProductoresApiController::class, 'storeRancho']);
    Route::put('/predios/{id}', [ProductoresApiController::class, 'updateRancho']);
    Route::delete('/predios/{id}', [ProductoresApiController::class, 'destroyPredio']);
    Route::post('/predios/{id}/coordenadas', [ProductoresApiController::class, 'updateCoordenadas']);
    Route::post('/productores/{productor}/vincular-a-hato', [ProductoresApiController::class, 'vincularAHato']);
    Route::post('/productores/{productor}/desvincular-de-hato', [ProductoresApiController::class, 'desvincularDeHato']);

    // Visitas desde la App Móvil
    Route::get('/visitas', [VisitasApiController::class, 'index']);
    Route::get('/visitas/check-codigo/{codigo}', [VisitasApiController::class, 'checkCodigo']);
    Route::get('/visitas/by-codigo/{codigo}', [VisitasApiController::class, 'showByCodigo']);
    Route::get('/visitas/{id}', [VisitasApiController::class, 'show']);
    Route::post('/visitas', [VisitasApiController::class, 'store']);
    Route::put('/visitas/{id}', [VisitasApiController::class, 'update']);
    Route::patch('/visitas/{id}/estado', [VisitasApiController::class, 'updateEstado']);
    Route::patch('/visitas/{id}/reprogramar', [VisitasApiController::class, 'reprogramar']);
    Route::delete('/visitas/{id}', [VisitasApiController::class, 'destroy']);

    // Inspecciones desde la App Móvil
    Route::get('/inspecciones', [InspeccionesApiController::class, 'index']);
    Route::get('/inspecciones/{id}', [InspeccionesApiController::class, 'show']);
    Route::get('/inspecciones/{id}/pdf', [InspeccionesApiController::class, 'pdf']);
    Route::get('/inspecciones/{id}/ver', [InspeccionesApiController::class, 'ver']);
    Route::patch('/inspecciones/{id}', [InspeccionesApiController::class, 'update']);
    Route::delete('/inspecciones/{id}', [InspeccionesApiController::class, 'destroy']);
    Route::post('/inspecciones/{id}/sync-detalles', [InspeccionDetallesController::class, 'sync']);
    Route::post('/inspecciones/{id}/upload-dictamen-comite', [InspeccionesApiController::class, 'uploadDictamenComite']);
    Route::get('/inspecciones/{id}/download-dictamen-comite', [InspeccionesApiController::class, 'downloadDictamenComite']);
    Route::delete('/inspecciones/{id}/delete-dictamen-comite', [InspeccionesApiController::class, 'deleteDictamenComite']);
    Route::get('/reportes/sabana-excel', [ReporteController::class, 'exportExcel']);
    Route::get('/reportes/sábana-excel', [ReporteController::class, 'exportExcel']);
    Route::get('/reportes/sabana-excel/data', [ReporteController::class, 'apiSabana']);
    Route::get('/censo/buscar-arete/{numero}', [InspeccionController::class, 'buscarArete']);
    Route::get('/reportes/rendimiento', [ReportesRendimientoApiController::class, 'index']);
    Route::get('/reportes/rendimiento/excel', [ReportesRendimientoApiController::class, 'exportExcel']);
    Route::get('/reportes/rendimiento/pdf', [ReportesRendimientoApiController::class, 'exportPdf']);
    Route::get('/reportes/rendimiento/mensual/excel', [ReportesRendimientoApiController::class, 'exportExcelMensual']);
    Route::get('/reportes/rendimiento/mensual/pdf', [ReportesRendimientoApiController::class, 'exportPdfMensual']);

    // Médicos desde la App Móvil
    Route::get('/medicos', [MedicosApiController::class, 'index']);
    Route::get('/medicos/{id}', [MedicosApiController::class, 'show']);
    Route::post('/medicos', [MedicosApiController::class, 'store']);
    Route::put('/medicos/{id}', [MedicosApiController::class, 'update']);
    Route::delete('/medicos/{id}', [MedicosApiController::class, 'destroy']);

    // Asignación de Productores a Médicos (Solo Administradores)
    Route::get('/usuarios/{usuario}/productores-asignables', [UserController::class, 'productoresAsignablesApi']);
    Route::post('/usuarios/{usuario}/asignar-productores', [UserController::class, 'guardarAsignacionApi']);
    Route::post('/usuarios/{usuario}/desasignar-productor/{productor}', [UserController::class, 'desasignarProductorApi']);

    // Animales desde la App Móvil
    Route::get('/animales', [AnimalesApiController::class, 'index']);
    Route::get('/animales/{id}', [AnimalesApiController::class, 'show']);
    Route::post('/animales', [AnimalesApiController::class, 'store']);
    Route::put('/animales/{id}', [AnimalesApiController::class, 'update']);
    Route::delete('/animales/{id}', [AnimalesApiController::class, 'destroy']);

    // Aretes del Censo desde la App Móvil
    Route::get('/aretes-censo', [AreteCensoApiController::class, 'index']);
    Route::get('/aretes-censo/{id}', [AreteCensoApiController::class, 'show']);
    Route::post('/aretes-censo', [AreteCensoApiController::class, 'store']);
    Route::put('/aretes-censo/{id}', [AreteCensoApiController::class, 'update']);
    Route::delete('/aretes-censo/{id}', [AreteCensoApiController::class, 'destroy']);
});
