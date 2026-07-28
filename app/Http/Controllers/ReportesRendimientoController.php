<?php

namespace App\Http\Controllers;

use App\Exports\RendimientoExport;
use App\Exports\RendimientoMensualExport;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\User;
use App\Models\Visita;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use setasign\Fpdi\Fpdi;

class ReportesRendimientoController extends Controller
{
    public function index(Request $request)
    {
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $estado = $request->get('estado');
        $zona = $request->get('zona');
        $medicoId = $request->get('medico_id');
        $year = $request->get('year');
        $localidad = $request->get('localidad');
        $tab = $request->get('tab', 'medicos');

        $driver = DB::connection()->getDriverName();

        $years = Inspeccion::selectRaw(($driver === 'sqlite' ? "strftime('%Y', fecha)" : 'YEAR(fecha)').' as year')
            ->distinct()->orderBy('year', 'desc')->pluck('year');
        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        $localidades = Predio::select('localidad')->distinct()->orderBy('localidad')->pluck('localidad');

        [$kpiInspecciones, $kpiVisitas, $kpiMedicosActivos, $kpiAnimales, $kpiReactores] =
            $this->getKpisCached($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad);

        $medicos = User::role('Medico_Campo')->orderBy('name')->get();

        $medicosRendimiento = $this->getMedicosRendimiento($medicos, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad);

        [$actividades, $nombresPruebas] = $this->getActividadesData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad);

        $zonas = $this->getZonasData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad);

        [$cuarentenasD, $cuarentenasP, $totalSinCuarentena] =
            $this->getCuarentenasData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad);

        $meses = $this->getMesesData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad);

        $detalleMedico = $this->getDetalleMedico($medicoId, $fechaDesde, $fechaHasta, $estado, $localidad);

        return view('reportes.rendimiento', compact(
            'tab',
            'kpiInspecciones',
            'kpiVisitas',
            'kpiMedicosActivos',
            'kpiAnimales',
            'kpiReactores',
            'medicos',
            'medicosRendimiento',
            'actividades',
            'nombresPruebas',
            'zonas',
            'cuarentenasD',
            'cuarentenasP',
            'totalSinCuarentena',
            'meses',
            'fechaDesde',
            'fechaHasta',
            'estado',
            'zona',
            'medicoId',
            'year',
            'localidad',
            'years',
            'localidades',
            'detalleMedico',
            'driver'
        ));
    }

    public function exportExcel(Request $request)
    {
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $estado = $request->get('estado');
        $zona = $request->get('zona');
        $medicoId = $request->get('medico_id');
        $localidad = $request->get('localidad');

        return Excel::download(
            new RendimientoExport($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad),
            'rendimiento_medicos.xlsx'
        );
    }

    public function exportExcelMensual(Request $request)
    {
        $year = $request->get('year', (int) now()->year);

        return Excel::download(
            new RendimientoMensualExport(
                (int) $year,
                $request->get('medico_id'),
                $request->get('zona'),
                $request->get('estado'),
            ),
            "rendimiento_mensual_{$year}.xlsx",
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function rendimientoMensual(Request $request)
    {
        $year = $request->get('year', (int) now()->year);
        $medicoId = $request->get('medico_id');
        $zona = $request->get('zona');
        $estado = $request->get('estado');

        $medicos = User::role('Medico_Campo')->orderBy('name')->get();
        $driver = DB::connection()->getDriverName();
        $yearExpr = $driver === 'sqlite' ? "strftime('%Y', fecha)" : 'YEAR(fecha)';

        $years = Inspeccion::selectRaw("{$yearExpr} as year")
            ->distinct()->orderBy('year', 'desc')->pluck('year');
        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        $rows = $this->getMensualData($year, $medicoId, $zona, $estado);

        return view('reportes.rendimiento_mensual', compact('rows', 'medicos', 'years', 'year', 'medicoId', 'zona', 'estado', 'driver'));
    }

    public function exportPdfMensual(Request $request)
    {
        $year = $request->get('year', (int) now()->year);
        $medicoId = $request->get('medico_id');
        $zona = $request->get('zona');
        $estado = $request->get('estado');

        $rows = $this->getMensualData($year, $medicoId, $zona, $estado);

        $pdf = Pdf::loadView('reports.rendimiento_mensual_pdf', compact('rows', 'year'));

        return $pdf->download("rendimiento_mensual_{$year}.pdf");
    }

    private function getMensualData($year, $medicoId, $zona, $estado): Collection
    {
        $medicos = User::role('Medico_Campo')->orderBy('name')->get();
        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite' ? "strftime('%Y-%m', inspecciones.fecha)" : "DATE_FORMAT(inspecciones.fecha, '%Y-%m')";
        $mesExprVisitas = $driver === 'sqlite' ? "strftime('%Y-%m', visitas.fecha_programada)" : "DATE_FORMAT(visitas.fecha_programada, '%Y-%m')";
        $mesExprDetalles = $driver === 'sqlite' ? "strftime('%Y-%m', inspecciones.fecha)" : "DATE_FORMAT(inspecciones.fecha, '%Y-%m')";

        $rawRows = Inspeccion::select(
            'inspecciones.veterinario_id',
            'inspecciones.tipo_prueba',
            DB::raw("{$monthExpr} as mes"),
            DB::raw('COUNT(*) as total_inspecciones'),
            DB::raw('COUNT(DISTINCT inspecciones.predio_id) as predios')
        )
            ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id')
            ->whereYear('inspecciones.fecha', $year)
            ->groupBy('inspecciones.veterinario_id', 'inspecciones.tipo_prueba', DB::raw($monthExpr))
            ->orderBy('mes')
            ->when($medicoId, fn ($q) => $q->where('inspecciones.veterinario_id', $medicoId))
            ->when($zona, fn ($q) => $q->where('productores.zona', $zona))
            ->when($estado, fn ($q) => $q->where('inspecciones.estado', $estado))
            ->get();

        $medicoNames = $medicos->pluck('name', 'id');
        $grouped = collect();

        foreach ($rawRows as $row) {
            $key = $row->veterinario_id.'|'.$row->mes;
            if (! $grouped->has($key)) {
                $grouped[$key] = (object) [
                    'veterinario_id' => $row->veterinario_id,
                    'mes' => $row->mes,
                    'medico_nombre' => $medicoNames[$row->veterinario_id] ?? 'Desconocido',
                    'ppc' => 0,
                    'pcc' => 0,
                    'total_inspecciones' => 0,
                    'predios' => 0,
                ];
            }
            $existing = $grouped[$key];
            $existing->total_inspecciones += $row->total_inspecciones;
            $existing->predios = max($existing->predios, $row->predios);
            $tipo = in_array($row->tipo_prueba, ['P.P.C.', 'PPC']) ? 'ppc' : 'pcc';
            $existing->{$tipo} += $row->total_inspecciones;
        }

        $rows = $grouped->values();

        $visitasData = Visita::select(
            'veterinario_id',
            DB::raw("{$mesExprVisitas} as mes"),
            DB::raw('COUNT(*) as total_visitas')
        )
            ->whereYear('fecha_programada', $year)
            ->groupBy('veterinario_id', DB::raw($mesExprVisitas))
            ->when($medicoId, fn ($q) => $q->where('veterinario_id', $medicoId))
            ->get()
            ->keyBy(fn ($v) => $v->veterinario_id.'|'.$v->mes);

        $detallesQuery = DetalleInspeccion::select(
            'inspecciones.veterinario_id',
            'inspecciones.tipo_prueba',
            DB::raw("{$mesExprDetalles} as mes"),
            DB::raw('COUNT(*) as total_animales'),
            DB::raw("COALESCE(SUM(CASE WHEN detalles_inspeccion.resultado_prueba IN ('Positivo','Sospechoso') THEN 1 ELSE 0 END), 0) as total_reactores")
        )
            ->join('inspecciones', 'detalles_inspeccion.inspeccion_id', '=', 'inspecciones.id')
            ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id')
            ->whereYear('inspecciones.fecha', $year)
            ->groupBy('inspecciones.veterinario_id', 'inspecciones.tipo_prueba', DB::raw($mesExprDetalles))
            ->when($medicoId, fn ($q) => $q->where('inspecciones.veterinario_id', $medicoId))
            ->when($zona, fn ($q) => $q->where('productores.zona', $zona))
            ->when($estado, fn ($q) => $q->where('inspecciones.estado', $estado))
            ->get()
            ->groupBy(fn ($d) => $d->veterinario_id.'|'.$d->mes);

        foreach ($rows as $row) {
            $key = $row->veterinario_id.'|'.$row->mes;
            $row->total_visitas = $visitasData->get($key)?->total_visitas ?? 0;
            $row->total_animales = 0;
            $row->total_reactores = 0;
            $row->reactores_ppc = 0;
            $row->reactores_pcc = 0;

            $detalles = $detallesQuery->get($key, collect());
            foreach ($detalles as $d) {
                $row->total_animales += $d->total_animales;
                $row->total_reactores += $d->total_reactores;
                $tipo = in_array($d->tipo_prueba, ['P.P.C.', 'PPC']) ? 'reactores_ppc' : 'reactores_pcc';
                $row->{$tipo} += $d->total_reactores;
            }
        }

        return $rows;
    }

    public function exportPdf(Request $request)
    {
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $estado = $request->get('estado');
        $zona = $request->get('zona');
        $medicoId = $request->get('medico_id');
        $localidad = $request->get('localidad');
        [$inspecciones] = $this->filteredInspecciones($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad);

        if ($inspecciones->count() > 50) {
            return redirect()->back()->with('error', 'Hay más de 50 inspecciones con los filtros actuales. Refina los filtros para descargar el PDF consolidado.');
        }

        if ($inspecciones->isEmpty()) {
            return redirect()->back()->with('error', 'No hay inspecciones para los filtros seleccionados.');
        }

        $pageEstimate = $inspecciones->sum(fn ($i) => 1 + max(0, ceil(($i->detalles->count() - 30) / 50)));
        if ($pageEstimate > 200) {
            return redirect()->back()->with('error', "La selección generaría aproximadamente {$pageEstimate} páginas. Refina los filtros para reducir el tamaño del PDF.");
        }

        $pdf = new Fpdi;
        $tmpFiles = [];

        foreach ($inspecciones as $inspeccion) {
            $individual = Pdf::loadView('reports.inspeccion_pdf', ['inspeccion' => $inspeccion])->output();
            $tmp = tempnam(sys_get_temp_dir(), 'pdf_');
            file_put_contents($tmp, $individual);
            $tmpFiles[] = $tmp;
            $pageCount = $pdf->setSourceFile($tmp);
            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tpl);
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl);
            }
        }

        foreach ($tmpFiles as $tmp) {
            unlink($tmp);
        }

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="dictamenes_consolidados.pdf"');
    }

    // ──────────────────────────────────────────────
    //  Private helpers
    // ──────────────────────────────────────────────

    private function dateExprMonth(): string
    {
        $driver = DB::connection()->getDriverName();

        return $driver === 'sqlite'
            ? "strftime('%Y-%m', inspecciones.fecha)"
            : "DATE_FORMAT(inspecciones.fecha, '%Y-%m')";
    }

    private function filteredInspecciones($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad = null): array
    {
        $query = Inspeccion::with(['predio.productor', 'detalles.animal', 'veterinario'])
            ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id');

        if ($fechaDesde) {
            $query->whereDate('inspecciones.fecha', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('inspecciones.fecha', '<=', $fechaHasta);
        }
        if ($estado) {
            $query->where('inspecciones.estado', $estado);
        }
        if ($medicoId) {
            $query->where('inspecciones.veterinario_id', $medicoId);
        }
        if ($zona) {
            $query->where('productores.zona', $zona);
        }
        if ($localidad) {
            $query->where('predios.localidad', $localidad);
        }

        $inspecciones = $query->select('inspecciones.*')->get();

        return [$inspecciones, $query];
    }

    private function getKpisCached($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad = null): array
    {
        $cacheKey = 'rendimiento_kpis_'.md5(serialize(compact('fechaDesde', 'fechaHasta', 'estado', 'zona', 'medicoId', 'localidad')));

        return Cache::remember($cacheKey, 1800, function () use ($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad) {
            return $this->getKpis($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad);
        });
    }

    private function getKpis($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad = null): array
    {
        $base = Inspeccion::query()
            ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id');

        if ($fechaDesde) {
            $base->whereDate('inspecciones.fecha', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $base->whereDate('inspecciones.fecha', '<=', $fechaHasta);
        }
        if ($estado) {
            $base->where('inspecciones.estado', $estado);
        }
        if ($medicoId) {
            $base->where('inspecciones.veterinario_id', $medicoId);
        }
        if ($zona) {
            $base->where('productores.zona', $zona);
        }
        if ($localidad) {
            $base->where('predios.localidad', $localidad);
        }

        $kpiInspecciones = (clone $base)->count();

        $kpiVisitas = Visita::query()
            ->when($medicoId, fn ($q) => $q->where('veterinario_id', $medicoId))
            ->when($fechaDesde, fn ($q) => $q->whereDate('fecha_programada', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('fecha_programada', '<=', $fechaHasta))
            ->count();

        $kpiMedicosActivos = User::role('Medico_Campo')
            ->whereHas('inspecciones', function ($q) use ($fechaDesde, $fechaHasta, $estado) {
                if ($fechaDesde) {
                    $q->whereDate('fecha', '>=', $fechaDesde);
                }
                if ($fechaHasta) {
                    $q->whereDate('fecha', '<=', $fechaHasta);
                }
                if ($estado) {
                    $q->where('estado', $estado);
                }
            })
            ->count();

        $inspeccionesIds = (clone $base)->select('inspecciones.id');

        $kpiAnimales = DetalleInspeccion::whereIn('inspeccion_id', $inspeccionesIds)->count();

        $kpiReactores = DetalleInspeccion::whereIn('resultado_prueba', ['Positivo', 'Sospechoso'])
            ->whereIn('inspeccion_id', $inspeccionesIds)
            ->count();

        return [$kpiInspecciones, $kpiVisitas, $kpiMedicosActivos, $kpiAnimales, $kpiReactores];
    }

    private function getMedicosRendimiento($medicos, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad = null)
    {
        $inspeccionesStats = Inspeccion::join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id')
            ->select(
                'inspecciones.veterinario_id',
                DB::raw('COUNT(*) as total_inspecciones'),
                DB::raw('COUNT(DISTINCT inspecciones.predio_id) as predios_atendidos'),
                DB::raw('MIN(inspecciones.fecha) as primera_inspeccion'),
                DB::raw('MAX(inspecciones.fecha) as ultima_inspeccion')
            )
            ->when($fechaDesde, fn ($q) => $q->whereDate('inspecciones.fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('inspecciones.fecha', '<=', $fechaHasta))
            ->when($estado, fn ($q) => $q->where('inspecciones.estado', $estado))
            ->when($zona, fn ($q) => $q->where('productores.zona', $zona))
            ->when($localidad, fn ($q) => $q->where('predios.localidad', $localidad))
            ->groupBy('inspecciones.veterinario_id')
            ->get()
            ->keyBy('veterinario_id');

        $dateExpr = $this->dateExprMonth();

        $inspeccionesPorMesAll = Inspeccion::join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id')
            ->select(
                'inspecciones.veterinario_id',
                DB::raw("{$dateExpr} as mes"),
                DB::raw('COUNT(*) as total')
            )
            ->when($fechaDesde, fn ($q) => $q->whereDate('inspecciones.fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('inspecciones.fecha', '<=', $fechaHasta))
            ->when($estado, fn ($q) => $q->where('inspecciones.estado', $estado))
            ->when($zona, fn ($q) => $q->where('productores.zona', $zona))
            ->when($localidad, fn ($q) => $q->where('predios.localidad', $localidad))
            ->groupBy('inspecciones.veterinario_id', DB::raw($dateExpr))
            ->orderBy('mes')
            ->get()
            ->groupBy('veterinario_id');

        $visitasStats = Visita::select(
            'veterinario_id',
            DB::raw('COUNT(*) as total_visitas'),
            DB::raw("SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as visitas_completadas")
        )
            ->when($fechaDesde, fn ($q) => $q->whereDate('fecha_programada', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('fecha_programada', '<=', $fechaHasta))
            ->groupBy('veterinario_id')
            ->get()
            ->keyBy('veterinario_id');

        $detallesStats = DetalleInspeccion::select(
            'inspecciones.veterinario_id',
            DB::raw('COUNT(*) as total_animales'),
            DB::raw("SUM(CASE WHEN detalles_inspeccion.resultado_prueba IN ('Positivo','Sospechoso') THEN 1 ELSE 0 END) as total_reactores")
        )
            ->join('inspecciones', 'detalles_inspeccion.inspeccion_id', '=', 'inspecciones.id')
            ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id')
            ->when($fechaDesde, fn ($q) => $q->whereDate('inspecciones.fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('inspecciones.fecha', '<=', $fechaHasta))
            ->when($estado, fn ($q) => $q->where('inspecciones.estado', $estado))
            ->when($zona, fn ($q) => $q->where('productores.zona', $zona))
            ->when($localidad, fn ($q) => $q->where('predios.localidad', $localidad))
            ->groupBy('inspecciones.veterinario_id')
            ->get()
            ->keyBy('veterinario_id');

        $medicosConDatos = $medicos->map(function ($user) use ($inspeccionesStats, $inspeccionesPorMesAll, $visitasStats, $detallesStats) {
            $stats = $inspeccionesStats->get($user->id);
            $vStats = $visitasStats->get($user->id);
            $dStats = $detallesStats->get($user->id);
            $inspeccionesPorMes = $inspeccionesPorMesAll->get($user->id, collect());

            $totalInspecciones = $stats?->total_inspecciones ?? 0;
            $totalVisitas = $vStats?->total_visitas ?? 0;
            $visitasCompletadas = $vStats?->visitas_completadas ?? 0;
            $totalAnimales = $dStats?->total_animales ?? 0;
            $totalReactores = $dStats?->total_reactores ?? 0;

            $promedioAnimales = $totalInspecciones > 0 ? round($totalAnimales / $totalInspecciones, 1) : 0;
            $tasaReactores = $totalAnimales > 0 ? round($totalReactores / $totalAnimales * 100, 1) : 0;
            $tasaFinalizacion = $totalVisitas > 0 ? round($visitasCompletadas / $totalVisitas * 100, 1) : 0;

            $score = (
                min($totalInspecciones / 50, 1) * 40 +
                min($promedioAnimales / 10, 1) * 30 +
                ($tasaFinalizacion / 100) * 30
            );

            return (object) [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'total_inspecciones' => $totalInspecciones,
                'total_visitas' => $totalVisitas,
                'visitas_completadas' => $visitasCompletadas,
                'predios_atendidos' => $stats?->predios_atendidos ?? 0,
                'total_animales' => $totalAnimales,
                'total_reactores' => $totalReactores,
                'promedio_animales' => $promedioAnimales,
                'tasa_reactores' => $tasaReactores,
                'tasa_finalizacion' => $tasaFinalizacion,
                'eficiencia_score' => round($score, 1),
                'primera_inspeccion' => $stats?->primera_inspeccion,
                'ultima_inspeccion' => $stats?->ultima_inspeccion,
                'inspecciones_por_mes' => $inspeccionesPorMes,
            ];
        })->filter(fn ($m) => $m->total_inspecciones > 0 || ! $medicoId || $medicoId == $m->id)->values();

        return $medicosConDatos->sortByDesc('eficiencia_score')->values();
    }

    private function getActividadesData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad = null): array
    {
        $query = Inspeccion::query()
            ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id')
            ->whereIn('inspecciones.tipo_prueba', ['P.P.C.', 'PPC', 'PCC'])
            ->select(
                DB::raw("CASE WHEN inspecciones.tipo_prueba IN ('P.P.C.','PPC') THEN 'PPC' ELSE inspecciones.tipo_prueba END as tipo"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw("CASE WHEN inspecciones.tipo_prueba IN ('P.P.C.','PPC') THEN 'PPC' ELSE inspecciones.tipo_prueba END"))
            ->orderByDesc('total');

        $this->applyCommonFilters($query, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad);
        $raw = $query->get();

        $actividades = collect([
            (object) ['tipo' => 'PPC', 'total' => 0],
            (object) ['tipo' => 'PCC', 'total' => 0],
        ]);

        foreach ($raw as $row) {
            $actividades = $actividades->map(fn ($item) => $item->tipo === $row->tipo ? (object) ['tipo' => $item->tipo, 'total' => $row->total] : $item);
        }

        $nombresPruebas = [
            'PPC' => 'Prueba de Pliegue Caudal (PPC)',
            'PCC' => 'Prueba Cervical Comparativa (PCC)',
        ];

        return [$actividades, $nombresPruebas];
    }

    private function getZonasData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad = null)
    {
        $query = Inspeccion::query()
            ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id')
            ->select('productores.zona', DB::raw('COUNT(*) as total'))
            ->groupBy('productores.zona')
            ->orderBy('productores.zona');

        $this->applyCommonFilters($query, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad);

        return $query->get();
    }

    private function getCuarentenasData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad = null): array
    {
        $base = Inspeccion::query()
            ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id');

        $detalleQuery = (clone $base)
            ->whereNotNull('productores.clave')
            ->select('productores.clave', DB::raw('COUNT(*) as total'))
            ->groupBy('productores.clave')
            ->orderBy('productores.clave');

        $this->applyCommonFilters($detalleQuery, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad);
        $detalle = $detalleQuery->get();

        $cuarentenasD = collect();
        $cuarentenasP = collect();

        foreach ($detalle as $item) {
            $tipo = substr($item->clave, 0, 2);
            $letter = substr($tipo, 1, 1);
            $target = $letter === 'P' ? $cuarentenasP : $cuarentenasD;

            if (! $target->has($tipo)) {
                $target[$tipo] = (object) ['tipo' => $tipo, 'total' => 0, 'detalle' => collect()];
            }
            $target[$tipo]->total += $item->total;
            $target[$tipo]->detalle->push($item);
        }

        $sinQuery = (clone $base)
            ->whereNull('productores.clave')
            ->select(DB::raw('COUNT(*) as total'));

        $this->applyCommonFilters($sinQuery, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad);
        $totalSinCuarentena = $sinQuery->value('total') ?? 0;

        return [$cuarentenasD->sortKeys(), $cuarentenasP->sortKeys(), $totalSinCuarentena];
    }

    private function getMesesData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad = null)
    {
        $dateExpr = $this->dateExprMonth();

        $query = Inspeccion::query()
            ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id')
            ->select(DB::raw("{$dateExpr} as mes"), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw($dateExpr))
            ->orderBy('mes');

        $this->applyCommonFilters($query, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad);

        $mesesActual = $query->get()->keyBy('mes');

        $driver = DB::connection()->getDriverName();
        $yearExpr = $driver === 'sqlite' ? "strftime('%Y', inspecciones.fecha)" : 'YEAR(inspecciones.fecha)';

        $yearsPresent = $mesesActual->keys()->map(fn ($m) => explode('-', $m)[0])->unique();

        $mesesConAnterior = collect();
        foreach ($yearsPresent as $yr) {
            $prevYr = (int) $yr - 1;

            $prevQuery = Inspeccion::query()
                ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
                ->join('productores', 'predios.productor_id', '=', 'productores.id')
                ->select(DB::raw("{$dateExpr} as mes"), DB::raw('COUNT(*) as total'))
                ->whereYear('inspecciones.fecha', $prevYr)
                ->groupBy(DB::raw($dateExpr))
                ->when($estado, fn ($q) => $q->where('inspecciones.estado', $estado))
                ->when($zona, fn ($q) => $q->where('productores.zona', $zona))
                ->when($medicoId, fn ($q) => $q->where('inspecciones.veterinario_id', $medicoId))
                ->when($localidad, fn ($q) => $q->where('predios.localidad', $localidad))
                ->get()
                ->keyBy('mes');

            for ($m = 1; $m <= 12; $m++) {
                $key = sprintf('%s-%02d', $yr, $m);
                $prevKey = sprintf('%s-%02d', $prevYr, $m);

                if ($mesesActual->has($key)) {
                    $row = $mesesActual->get($key);
                    $mesesConAnterior->push((object) [
                        'mes' => $key,
                        'total' => $row->total,
                        'total_anterior' => $prevQuery->get($prevKey)?->total ?? 0,
                    ]);
                }
            }
        }

        return $mesesConAnterior->sortBy('mes')->values();
    }

    private function getDetalleMedico($medicoId, $fechaDesde, $fechaHasta, $estado, $localidad = null)
    {
        if (! $medicoId) {
            return null;
        }

        return Inspeccion::with(['predio.productor'])
            ->where('veterinario_id', $medicoId)
            ->when($fechaDesde, fn ($q) => $q->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('fecha', '<=', $fechaHasta))
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->when($localidad, fn ($q) => $q->whereHas('predio', fn ($pq) => $pq->where('localidad', $localidad)))
            ->latest('fecha')
            ->take(20)
            ->get();
    }

    private function applyCommonFilters($query, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad = null): void
    {
        if ($fechaDesde) {
            $query->whereDate('inspecciones.fecha', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('inspecciones.fecha', '<=', $fechaHasta);
        }
        if ($estado) {
            $query->where('inspecciones.estado', $estado);
        }
        if ($medicoId) {
            $query->where('inspecciones.veterinario_id', $medicoId);
        }
        if ($zona) {
            $query->where('productores.zona', $zona);
        }
        if ($localidad) {
            $query->where('predios.localidad', $localidad);
        }
    }
}
