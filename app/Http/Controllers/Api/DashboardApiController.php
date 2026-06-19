<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardApiController extends Controller
{
    /**
     * Obtener estadísticas y datos del dashboard según el rol del usuario (API)
     */
    public function getStats(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $isAdmin = $user->hasRole('Administrador');

        if ($isAdmin) {
            // --- ADMINISTRADOR ---
            $totalInspecciones = Inspeccion::count();

            $totalAnimales = DetalleInspeccion::whereIn('inspeccion_id', function ($q) {
                $q->select('id')->from('inspecciones');
            })->count();

            $totalVisitasPendientes = Visita::where('estado', 'pendiente')->count();

            // Inspecciones por Localidad (Gráfico de Barras)
            $inspeccionesPorLocalidad = Predio::select('localidad', DB::raw('count(*) as total'))
                ->join('inspecciones', 'predios.id', '=', 'inspecciones.predio_id')
                ->groupBy('localidad')
                ->get();

            // Rendimiento Veterinarios (Gráfico de Dona)
            $rendimientoVeterinarios = User::select('name', DB::raw('count(*) as total'))
                ->join('inspecciones', 'users.id', '=', 'inspecciones.veterinario_id')
                ->groupBy('name')
                ->get();

            // Próximas Visitas Globales
            $proximasVisitasGlobales = Visita::with(['predio.productor', 'veterinario'])
                ->where('estado', 'pendiente')
                ->orderBy('fecha_programada', 'asc')
                ->take(6)
                ->get();

            // Dictámenes Incompletos Globales (Borradores)
            $borradoresGlobales = Inspeccion::with(['predio', 'veterinario'])
                ->where('estado', 'borrador')
                ->latest()
                ->take(6)
                ->get();

            // Proporción de resultados de pruebas
            $resultados = DetalleInspeccion::select('resultado_prueba', DB::raw('count(*) as total'))
                ->groupBy('resultado_prueba')
                ->get();

            return response()->json([
                'isAdmin' => true,
                'totalInspecciones' => $totalInspecciones,
                'totalAnimales' => $totalAnimales,
                'totalVisitasPendientes' => $totalVisitasPendientes,
                'inspeccionesPorLocalidad' => $inspeccionesPorLocalidad,
                'rendimientoVeterinarios' => $rendimientoVeterinarios,
                'proximasVisitasGlobales' => $proximasVisitasGlobales,
                'borradoresGlobales' => $borradoresGlobales,
                'resultados' => $resultados,
            ]);

        } else {
            // --- MÉDICO / VETERINARIO ---
            $totalInspecciones = Inspeccion::where('veterinario_id', $user->id)
                ->where('estado', '!=', 'borrador')
                ->count();

            $totalAnimales = DetalleInspeccion::whereIn('inspeccion_id', function ($q) use ($user) {
                $q->select('id')->from('inspecciones')
                    ->where('veterinario_id', $user->id)
                    ->where('estado', '!=', 'borrador');
            })->count();

            // Visitas Pendientes del Médico
            $visitasPendientes = Visita::with('predio.productor')
                ->where('veterinario_id', $user->id)
                ->where('estado', 'pendiente')
                ->latest()
                ->take(5)
                ->get();

            // Dictámenes Borrador del Médico
            $dictamenesBorrador = Inspeccion::with('predio')
                ->where('veterinario_id', $user->id)
                ->where('estado', 'borrador')
                ->latest()
                ->take(5)
                ->get();

            // Proporción de resultados de pruebas del médico
            $resultados = DetalleInspeccion::select('resultado_prueba', DB::raw('count(*) as total'))
                ->whereIn('inspeccion_id', function ($q) use ($user) {
                    $q->select('id')->from('inspecciones')->where('veterinario_id', $user->id);
                })
                ->groupBy('resultado_prueba')
                ->get();

            return response()->json([
                'isAdmin' => false,
                'totalInspecciones' => $totalInspecciones,
                'totalAnimales' => $totalAnimales,
                'visitasPendientes' => $visitasPendientes,
                'dictamenesBorrador' => $dictamenesBorrador,
                'resultados' => $resultados,
            ]);
        }
    }
}
