<?php

namespace App\Http\Controllers;

use App\Exports\RendimientoExport;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\User;
use App\Models\Visita;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
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
        $tab = $request->get('tab', 'medicos');

        [$kpiInspecciones, $kpiVisitas, $kpiMedicosActivos, $kpiAnimales, $kpiReactores] =
            $this->getKpisCached($fechaDesde, $fechaHasta, $estado, $zona, $medicoId);

        $medicos = User::role('Medico_Campo')->orderBy('name')->get();

        $medicosRendimiento = $this->getMedicosRendimiento($medicos, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId);

        [$actividades, $nombresPruebas] = $this->getActividadesData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId);

        $zonas = $this->getZonasData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId);

        [$cuarentenasD, $cuarentenasP, $totalSinCuarentena] =
            $this->getCuarentenasData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId);

        $meses = $this->getMesesData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId);

        $detalleMedico = $this->getDetalleMedico($medicoId, $fechaDesde, $fechaHasta, $estado);

        $driver = DB::connection()->getDriverName();

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

        return Excel::download(
            new RendimientoExport($fechaDesde, $fechaHasta, $estado, $zona, $medicoId),
            'rendimiento_medicos.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $estado = $request->get('estado');
        $zona = $request->get('zona');
        $medicoId = $request->get('medico_id');
        $incluirPortada = $request->boolean('incluir_portada');

        [$inspecciones] = $this->filteredInspecciones($fechaDesde, $fechaHasta, $estado, $zona, $medicoId);

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

        $pdf = new Fpdi();
        $tmpFiles = [];

        if ($incluirPortada) {
            $pdf = $this->prependPortada($pdf, $inspecciones, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId);
        }

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

    private function filteredInspecciones($fechaDesde, $fechaHasta, $estado, $zona, $medicoId): array
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

        $inspecciones = $query->select('inspecciones.*')->get();

        return [$inspecciones, $query];
    }

    private function getKpisCached($fechaDesde, $fechaHasta, $estado, $zona, $medicoId): array
    {
        $cacheKey = 'rendimiento_kpis_' . md5(serialize(compact('fechaDesde', 'fechaHasta', 'estado', 'zona', 'medicoId')));

        return Cache::remember($cacheKey, 300, function () use ($fechaDesde, $fechaHasta, $estado, $zona, $medicoId) {
            return $this->getKpis($fechaDesde, $fechaHasta, $estado, $zona, $medicoId);
        });
    }

    private function getKpis($fechaDesde, $fechaHasta, $estado, $zona, $medicoId): array
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

    private function getMedicosRendimiento($medicos, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId)
    {
        return $medicos->map(function ($user) use ($fechaDesde, $fechaHasta, $estado, $zona) {
            $query = Inspeccion::where('inspecciones.veterinario_id', $user->id)
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
            if ($zona) {
                $query->where('productores.zona', $zona);
            }

            $dateExpr = $this->dateExprMonth();

            $inspeccionesPorMes = (clone $query)
                ->select(DB::raw("{$dateExpr} as mes"), DB::raw('COUNT(*) as total'))
                ->groupBy(DB::raw($dateExpr))
                ->orderBy('mes')
                ->get();

            $visitasQuery = Visita::where('veterinario_id', $user->id)
                ->when($fechaDesde, fn ($q) => $q->whereDate('fecha_programada', '>=', $fechaDesde))
                ->when($fechaHasta, fn ($q) => $q->whereDate('fecha_programada', '<=', $fechaHasta));

            return (object) [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'total_inspecciones' => (clone $query)->count(),
                'total_visitas' => (clone $visitasQuery)->count(),
                'visitas_completadas' => (clone $visitasQuery)->where('estado', 'completada')->count(),
                'predios_atendidos' => (clone $query)->distinct('inspecciones.predio_id')->count('inspecciones.predio_id'),
                'primera_inspeccion' => (clone $query)->min('inspecciones.fecha'),
                'ultima_inspeccion' => (clone $query)->max('inspecciones.fecha'),
                'inspecciones_por_mes' => $inspeccionesPorMes,
            ];
        })->filter(fn ($m) => $m->total_inspecciones > 0 || ! $medicoId || $medicoId == $m->id)->values();
    }

    private function getActividadesData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId): array
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

        $this->applyCommonFilters($query, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId);
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

    private function getZonasData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId)
    {
        $query = Inspeccion::query()
            ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id')
            ->select('productores.zona', DB::raw('COUNT(*) as total'))
            ->groupBy('productores.zona')
            ->orderBy('productores.zona');

        $this->applyCommonFilters($query, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId);

        return $query->get();
    }

    private function getCuarentenasData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId): array
    {
        $base = Inspeccion::query()
            ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id');

        $detalleQuery = (clone $base)
            ->whereNotNull('productores.clave_cuarentena')
            ->select('productores.clave_cuarentena', DB::raw('COUNT(*) as total'))
            ->groupBy('productores.clave_cuarentena')
            ->orderBy('productores.clave_cuarentena');

        $this->applyCommonFilters($detalleQuery, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId);
        $detalle = $detalleQuery->get();

        $cuarentenasD = collect();
        $cuarentenasP = collect();

        foreach ($detalle as $item) {
            $tipo = substr($item->clave_cuarentena, 0, 2);
            $letter = substr($tipo, 1, 1);
            $target = $letter === 'P' ? $cuarentenasP : $cuarentenasD;

            if (! $target->has($tipo)) {
                $target[$tipo] = (object) ['tipo' => $tipo, 'total' => 0, 'detalle' => collect()];
            }
            $target[$tipo]->total += $item->total;
            $target[$tipo]->detalle->push($item);
        }

        $sinQuery = (clone $base)
            ->whereNull('productores.clave_cuarentena')
            ->select(DB::raw('COUNT(*) as total'));

        $this->applyCommonFilters($sinQuery, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId);
        $totalSinCuarentena = $sinQuery->value('total') ?? 0;

        return [$cuarentenasD->sortKeys(), $cuarentenasP->sortKeys(), $totalSinCuarentena];
    }

    private function getMesesData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId)
    {
        $dateExpr = $this->dateExprMonth();

        $query = Inspeccion::query()
            ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id')
            ->select(DB::raw("{$dateExpr} as mes"), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw($dateExpr))
            ->orderBy('mes');

        $this->applyCommonFilters($query, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId);

        return $query->get();
    }

    private function getDetalleMedico($medicoId, $fechaDesde, $fechaHasta, $estado)
    {
        if (! $medicoId) {
            return null;
        }

        return Inspeccion::with(['predio.productor'])
            ->where('veterinario_id', $medicoId)
            ->when($fechaDesde, fn ($q) => $q->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('fecha', '<=', $fechaHasta))
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->latest('fecha')
            ->take(20)
            ->get();
    }

    private function applyCommonFilters($query, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId): void
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
    }

    private function prependPortada(Fpdi $pdf, $inspecciones, $fechaDesde, $fechaHasta, $estado, $zona, $medicoId): Fpdi
    {
        $medicoNombre = $medicoId ? (User::find($medicoId)?->name ?? 'Todos') : 'Todos';
        $totalAnimales = $inspecciones->sum(fn ($i) => $i->detalles->count());
        $negativos = $inspecciones->sum(fn ($i) => $i->detalles->where('resultado_prueba', 'Negativo')->count());
        $positivos = $inspecciones->sum(fn ($i) => $i->detalles->where('resultado_prueba', 'Positivo')->count());
        $sospechosos = $inspecciones->sum(fn ($i) => $i->detalles->where('resultado_prueba', 'Sospechoso')->count());

        $portadaHtml = Pdf::loadView('reports.portada_pdf', compact(
            'inspecciones',
            'fechaDesde',
            'fechaHasta',
            'estado',
            'zona',
            'medicoNombre',
            'totalAnimales',
            'negativos',
            'positivos',
            'sospechosos',
        ))->output();

        $tmp = tempnam(sys_get_temp_dir(), 'portada_');
        file_put_contents($tmp, $portadaHtml);
        $pageCount = $pdf->setSourceFile($tmp);
        for ($i = 1; $i <= $pageCount; $i++) {
            $tpl = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tpl);
            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);
        }
        unlink($tmp);

        return $pdf;
    }
}
