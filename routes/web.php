<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\InspeccionController;
use App\Http\Controllers\ProductorController;
use App\Http\Controllers\PredioController;
use App\Http\Controllers\VisitaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImportExcelController;

Route::get('/', function () {
    return redirect('/login');
});

// Autenticación Web
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login']);
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// Rutas Protegidas por Login
Route::middleware(['auth'])->group(function () {
    
    // Admin Dashboard
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Gestión de Usuarios (Médicos) - SOLO ADMINISTRADORES
    Route::middleware('role:Administrador')->group(function () {
        Route::resource('usuarios', UserController::class);
    });

    // Inspecciones
    Route::get('/inspecciones', [InspeccionController::class, 'index'])->name('inspecciones.index');
    Route::get('/inspecciones/nuevo', [InspeccionController::class, 'create'])->name('inspecciones.create');
    Route::post('/inspecciones', [InspeccionController::class, 'store'])->name('inspecciones.store');
    Route::get('/inspecciones/{inspeccion}/editar', [InspeccionController::class, 'edit'])->name('inspecciones.edit');
    Route::patch('/inspecciones/{inspeccion}', [InspeccionController::class, 'update'])->name('inspecciones.update');
    Route::get('/inspecciones/{inspeccion}', [InspeccionController::class, 'show'])->name('inspecciones.show');

    // Productores y Predios
    Route::post('/productores/ajax', [ProductorController::class, 'storeAjax'])->name('productores.store.ajax');
    Route::resource('productores', ProductorController::class);
    Route::post('/predios/{predio}/coordenadas', [PredioController::class, 'updateCoordenadas'])->name('predios.updateCoordenadas');
    Route::resource('predios', PredioController::class);

    // Planificación de Visitas
    Route::resource('visitas', VisitaController::class);
    Route::patch('/visitas/{visita}/estado', [VisitaController::class, 'updateEstado'])->name('visitas.updateEstado');
    Route::patch('/visitas/{visita}/reprogramar', [VisitaController::class, 'reprogramar'])->name('visitas.reprogramar');

    // Reportes PDF (Disponibles para Médicos para sus propios dictámenes)
    Route::get('/reportes/inspeccion/{id}/ver', [ReporteController::class, 'streamPdf'])->name('reportes.stream');
    Route::get('/reportes/inspeccion/{id}/pdf', [ReporteController::class, 'exportPdf'])->name('reportes.pdf');
    
    // Importación de Excel (Exclusivo Administrador)
    Route::middleware('role:Administrador')->group(function () {
        Route::get('/importar-excel', [ImportExcelController::class, 'index'])->name('import.excel.index');
        Route::post('/importar-excel/preview', [ImportExcelController::class, 'preview'])->name('import.excel.preview');
        Route::post('/importar-excel/import', [ImportExcelController::class, 'import'])->name('import.excel.import');
        Route::get('/reportes/sábana-excel', [ReporteController::class, 'exportExcel'])->name('reportes.excel');
    });

    // API para búsqueda de aretes (Autocompletado en Dictámenes)
    Route::get('/api/buscar-arete/{numero}', [InspeccionController::class, 'buscarArete'])->name('api.buscar.arete');
});
