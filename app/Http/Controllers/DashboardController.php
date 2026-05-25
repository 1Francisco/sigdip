<?php

namespace App\Http\Controllers;

use App\Models\Inspeccion;
use App\Models\DetalleInspeccion;
use App\Models\Predio;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Muestra el dashboard administrativo con estadísticas.
     */
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('Administrador');

        // Variables para el Administrador
        $totalInspecciones = 0;
        $totalAnimales = 0;
        $inspeccionesPorLocalidad = collect();
        $rendimientoVeterinarios = collect();
        $proximasVisitasGlobales = collect();
        $totalVisitasPendientes = 0;
        $borradoresGlobales = collect();
        
        // Variables para el Médico
        $visitasPendientes = collect();
        $dictamenesBorrador = collect();

        if ($isAdmin) {
            $totalInspecciones = Inspeccion::count();
            
            $totalAnimales = DetalleInspeccion::whereIn('inspeccion_id', function($q) {
                $q->select('id')->from('inspecciones');
            })->count();
            
            $inspeccionesPorLocalidad = Predio::select('localidad', DB::raw('count(*) as total'))
                ->join('inspecciones', 'predios.id', '=', 'inspecciones.predio_id')
                ->groupBy('localidad')->get();
                
            $rendimientoVeterinarios = User::select('name', DB::raw('count(*) as total'))
                ->join('inspecciones', 'users.id', '=', 'inspecciones.veterinario_id')
                ->groupBy('name')
                ->get();
                
            $totalVisitasPendientes = Visita::where('estado', 'pendiente')->count();
            
            $proximasVisitasGlobales = Visita::with(['predio.productor', 'veterinario'])
                ->where('estado', 'pendiente')
                ->orderBy('fecha_programada', 'asc')
                ->take(6)
                ->get();
                
            $borradoresGlobales = Inspeccion::with(['predio', 'veterinario'])
                ->where('estado', 'borrador')
                ->latest()
                ->take(6)
                ->get();
        } else {
            // Datos Operativos del Médico
            $visitasPendientes = Visita::with('predio.productor')
                ->where('veterinario_id', $user->id)
                ->where('estado', 'pendiente')
                ->latest()
                ->take(5)
                ->get();

            $dictamenesBorrador = Inspeccion::with('predio')
                ->where('veterinario_id', $user->id)
                ->where('estado', 'borrador')
                ->latest()
                ->take(5)
                ->get();
                
            $totalInspecciones = Inspeccion::where('veterinario_id', $user->id)
                ->where('estado', '!=', 'borrador')
                ->count();
                
            $totalAnimales = DetalleInspeccion::whereIn('inspeccion_id', function($q) use ($user) {
                $q->select('id')->from('inspecciones')
                  ->where('veterinario_id', $user->id)
                  ->where('estado', '!=', 'borrador');
            })->count();
        }

        // Proporción de resultados (Para ambos, pero filtrado)
        $resultadosQuery = DetalleInspeccion::select('resultado_prueba', DB::raw('count(*) as total'));
        if (!$isAdmin) {
            $resultadosQuery->whereIn('inspeccion_id', function($q) use ($user) {
                $q->select('id')->from('inspecciones')->where('veterinario_id', $user->id);
            });
        }
        $resultados = $resultadosQuery->groupBy('resultado_prueba')->get();

        return view('admin.dashboard', compact(
            'isAdmin',
            'totalInspecciones', 
            'totalAnimales', 
            'inspeccionesPorLocalidad', 
            'rendimientoVeterinarios',
            'resultados',
            'visitasPendientes',
            'dictamenesBorrador',
            'totalVisitasPendientes',
            'proximasVisitasGlobales',
            'borradoresGlobales'
        ));
    }
}
