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

            // KPIs de eficiencia global
            $year = (int) now()->year;
            $totalInspeccionesYear = Inspeccion::whereYear('fecha', $year)->count();
            $totalFinalizadas = Inspeccion::whereYear('fecha', $year)->where('estado', 'finalizado')->count();
            $totalAnimalesYear = DetalleInspeccion::whereIn('inspeccion_id', function ($q) use ($year) {
                $q->select('id')->from('inspecciones')->whereYear('fecha', $year);
            })->count();
            $totalReactores = DetalleInspeccion::whereIn('inspeccion_id', function ($q) use ($year) {
                $q->select('id')->from('inspecciones')->whereYear('fecha', $year);
            })->whereIn('resultado_prueba', ['Positivo', 'Sospechoso'])->count();

            $animalesPorInspeccion = $totalInspeccionesYear > 0 ? round($totalAnimalesYear / $totalInspeccionesYear, 1) : 0;
            $porcentajeReactores = $totalAnimalesYear > 0 ? round(($totalReactores / $totalAnimalesYear) * 100, 1) : 0;
            $porcentajeFinalizacion = $totalInspeccionesYear > 0 ? round(($totalFinalizadas / $totalInspeccionesYear) * 100, 1) : 0;
            $eficienciaScore = round(($animalesPorInspeccion * 0.3) + ((100 - $porcentajeReactores) * 0.3) + ($porcentajeFinalizacion * 0.4), 1);

            // Ranking de médicos por eficiencia
            $medicosRanking = User::select(
                'users.id',
                'users.name',
                DB::raw('COUNT(DISTINCT inspecciones.id) as total_inspecciones'),
                DB::raw('COUNT(DISTINCT detalles_inspeccion.id) as total_animales'),
                DB::raw("COALESCE(SUM(CASE WHEN detalles_inspeccion.resultado_prueba IN ('Positivo','Sospechoso') THEN 1 ELSE 0 END), 0) as total_reactores"),
                DB::raw("COALESCE(SUM(CASE WHEN inspecciones.estado = 'finalizado' THEN 1 ELSE 0 END), 0) as finalizadas")
            )
                ->leftJoin('inspecciones', 'users.id', '=', 'inspecciones.veterinario_id')
                ->leftJoin('detalles_inspeccion', 'inspecciones.id', '=', 'detalles_inspeccion.inspeccion_id')
                ->whereYear('inspecciones.fecha', $year)
                ->where('users.id', '!=', 1) // exclude admin
                ->groupBy('users.id', 'users.name')
                ->get()
                ->map(function ($medico) {
                    $medico->animales_por_inspeccion = $medico->total_inspecciones > 0 ? round($medico->total_animales / $medico->total_inspecciones, 1) : 0;
                    $medico->porcentaje_reactores = $medico->total_animales > 0 ? round(($medico->total_reactores / $medico->total_animales) * 100, 1) : 0;
                    $medico->porcentaje_finalizacion = $medico->total_inspecciones > 0 ? round(($medico->finalizadas / $medico->total_inspecciones) * 100, 1) : 0;
                    $medico->eficiencia_score = round(
                        ($medico->animales_por_inspeccion * 0.3) +
                        ((100 - $medico->porcentaje_reactores) * 0.3) +
                        ($medico->porcentaje_finalizacion * 0.4),
                        1);

                    return $medico;
                })
                ->sortByDesc('eficiencia_score')
                ->values();

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
                'kpisData' => [
                    'animales_por_inspeccion' => $animalesPorInspeccion,
                    'porcentaje_reactores' => $porcentajeReactores,
                    'porcentaje_finalizacion' => $porcentajeFinalizacion,
                    'eficiencia_score' => $eficienciaScore,
                ],
                'medicosRanking' => $medicosRanking,
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
