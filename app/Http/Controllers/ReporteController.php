<?php

namespace App\Http\Controllers;

use App\Exports\InspeccionExport;
use App\Http\Controllers\Concerns\MergesDictamenPdf;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\Productor;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    use MergesDictamenPdf;

    /**
     * Interfaz web para visualizar y filtrar la "Sábana" de inspecciones.
     */
    public function indexSabana(Request $request)
    {
        $zona = $request->get('zona');
        $tipoActividad = $request->get('tipo_actividad') ?? $request->get('tipo_prueba');
        $medicoId = $request->get('medico_id');

        $user = auth()->user();

        // Opciones de filtros
        try {
            $medicos = User::role('Medico_Campo')->orderBy('name')->get();
            if ($medicos->isEmpty()) {
                $medicos = User::orderBy('name')->get();
            }
        } catch (\Throwable $e) {
            $medicos = User::orderBy('name')->get();
        }

        $zonas = Productor::whereNotNull('zona')
            ->distinct()
            ->pluck('zona')
            ->filter()
            ->sort()
            ->values();
        if ($zonas->isEmpty()) {
            $zonas = collect(['A', 'B']);
        }

        $tiposActividad = collect([
            'Cuarentenas Definitivas',
            'Cuarentenas Precautorias',
            'Hatos Relacionados y Expuestos',
            'Seguimiento',
            'Barrido',
            'Buffer',
        ]);

        // Construir consulta base
        $query = Inspeccion::with(['predio.productor', 'detalles', 'veterinario']);

        if ($user && ! $user->hasRole('Administrador')) {
            $query->where('veterinario_id', $user->id);
        }

        $query->applySabanaFilters($zona, $tipoActividad, $medicoId);

        // Clonar para KPIs rápidos
        $inspeccionesIds = (clone $query)->pluck('id');
        $totalInspecciones = $inspeccionesIds->count();

        $totalProbados = DetalleInspeccion::whereIn('inspeccion_id', $inspeccionesIds)->count();
        $totalNegativos = DetalleInspeccion::whereIn('inspeccion_id', $inspeccionesIds)
            ->where('resultado_prueba', 'Negativo')
            ->count();
        $totalReactores = DetalleInspeccion::whereIn('inspeccion_id', $inspeccionesIds)
            ->whereIn('resultado_prueba', ['Positivo', 'Sospechoso'])
            ->count();

        // Obtener resultados paginados para la tabla interactiva
        /** @var LengthAwarePaginator $inspecciones */
        $inspecciones = $query->latest('fecha')->latest('id')->paginate(15);
        $inspecciones = $inspecciones->withQueryString();

        return view('reportes.sabana', compact(
            'inspecciones',
            'medicos',
            'zonas',
            'tiposActividad',
            'zona',
            'tipoActividad',
            'medicoId',
            'totalInspecciones',
            'totalProbados',
            'totalNegativos',
            'totalReactores'
        ));
    }

    /**
     * API JSON para la Sábana Excel (app móvil).
     */
    public function apiSabana(Request $request)
    {
        $zona = $request->get('zona');
        $tipoActividad = $request->get('tipo_actividad') ?? $request->get('tipo_prueba');
        $medicoId = $request->get('medico_id');
        $page = $request->get('page', 1);

        $user = auth()->user();

        $medicos = collect();
        try {
            $medicos = User::role('Medico_Campo')->orderBy('name')->get(['id', 'name']);
            if ($medicos->isEmpty()) {
                $medicos = User::orderBy('name')->get(['id', 'name']);
            }
        } catch (\Throwable $e) {
            $medicos = User::orderBy('name')->get(['id', 'name']);
        }

        $zonas = Productor::whereNotNull('zona')
            ->distinct()
            ->pluck('zona')
            ->filter()
            ->sort()
            ->values();
        if ($zonas->isEmpty()) {
            $zonas = collect(['A', 'B']);
        }

        $tiposActividad = collect([
            'Cuarentenas Definitivas',
            'Cuarentenas Precautorias',
            'Hatos Relacionados y Expuestos',
            'Seguimiento',
            'Barrido',
            'Buffer',
        ]);

        $query = Inspeccion::with(['predio.productor', 'detalles', 'veterinario']);

        if ($user && ! $user->hasRole('Administrador')) {
            $query->where('veterinario_id', $user->id);
        }

        $query->applySabanaFilters($zona, $tipoActividad, $medicoId);

        $inspeccionesIds = (clone $query)->pluck('id');
        $totalInspecciones = $inspeccionesIds->count();

        $totalProbados = DetalleInspeccion::whereIn('inspeccion_id', $inspeccionesIds)->count();
        $totalNegativos = DetalleInspeccion::whereIn('inspeccion_id', $inspeccionesIds)
            ->where('resultado_prueba', 'Negativo')
            ->count();
        $totalReactores = DetalleInspeccion::whereIn('inspeccion_id', $inspeccionesIds)
            ->whereIn('resultado_prueba', ['Positivo', 'Sospechoso'])
            ->count();

        $perPage = 20;
        /** @var LengthAwarePaginator $inspecciones */
        $inspecciones = $query->latest('fecha')->latest('id')->paginate($perPage, ['*'], 'page', $page);

        $items = $inspecciones->map(function ($ins) {
            $negativos = $ins->detalles->where('resultado_prueba', 'Negativo')->count();
            $reactores = $ins->detalles->whereIn('resultado_prueba', ['Positivo', 'Sospechoso'])->count();
            $productor = $ins->predio?->productor;

            return [
                'id' => $ins->id,
                'clave' => $ins->clave_interna ?: $ins->folio,
                'predio' => $ins->predio?->nombre_rancho,
                'upp' => $ins->predio?->clave_unidad_produccion,
                'productor' => $productor ? $productor->nombre_completo : null,
                'municipio' => $ins->predio?->municipio,
                'localidad' => $ins->predio?->localidad,
                'prueba' => $ins->motivo_prueba ?? $ins->tipo_prueba ?? $ins->tipo_inspeccion,
                'funcion_zootecnica' => $ins->funcion_zootecnica,
                'fecha' => $ins->fecha ? Carbon::parse($ins->fecha)->format('d/m/Y') : null,
                'probados' => $ins->detalles->count(),
                'negativos' => $negativos,
                'reactores' => $reactores,
                'latitud' => $ins->predio?->latitud,
                'longitud' => $ins->predio?->longitud,
                'observaciones' => $ins->observaciones,
                'veterinario' => $ins->veterinario?->name,
            ];
        });

        return response()->json([
            'kpis' => [
                'total_inspecciones' => $totalInspecciones,
                'total_probados' => $totalProbados,
                'total_negativos' => $totalNegativos,
                'total_reactores' => $totalReactores,
            ],
            'inspecciones' => $items,
            'pagination' => [
                'current_page' => $inspecciones->currentPage(),
                'last_page' => $inspecciones->lastPage(),
                'per_page' => $inspecciones->perPage(),
                'total' => $inspecciones->total(),
                'from' => $inspecciones->firstItem(),
                'to' => $inspecciones->lastItem(),
            ],
            'filter_options' => [
                'zonas' => $zonas,
                'tipos_actividad' => $tiposActividad,
                'medicos' => $medicos,
            ],
            'is_admin' => $user && $user->hasRole('Administrador'),
        ]);
    }

    /**
     * Exporta los resultados de una inspección a PDF.
     */
    public function streamPdf(Request $request, $id)
    {
        $inspeccion = Inspeccion::with(['predio.productor', 'detalles.animal', 'veterinario'])
            ->findOrFail($id);

        if (! $request->has('prototype') && $inspeccion->dictamen_comite_path) {
            $filePath = Storage::disk('public')->path($inspeccion->dictamen_comite_path);
            if (file_exists($filePath)) {
                return response()->file($filePath, [
                    'Content-Disposition' => 'inline; filename="'.$inspeccion->buildPdfFilename().'"',
                ]);
            }
        }

        $pdf = Pdf::loadView('reports.inspeccion_pdf', compact('inspeccion'));

        return $pdf->stream($inspeccion->buildPdfFilename());
    }

    public function exportPdf(Request $request, $id)
    {
        $inspeccion = Inspeccion::with(['predio.productor', 'detalles.animal', 'veterinario'])
            ->findOrFail($id);

        if (! $request->has('prototype') && $inspeccion->dictamen_comite_path) {
            $filePath = Storage::disk('public')->path($inspeccion->dictamen_comite_path);
            if (file_exists($filePath)) {
                return response()->download($filePath, $inspeccion->buildPdfFilename());
            }
        }

        $pdf = Pdf::loadView('reports.inspeccion_pdf', compact('inspeccion'));

        return $pdf->download($inspeccion->buildPdfFilename());
    }

    /**
     * Exporta la "Sábana" de inspecciones a Excel aplicando los filtros.
     */
    public function exportExcel(Request $request)
    {
        $zona = $request->get('zona');
        $tipoActividad = $request->get('tipo_actividad') ?? $request->get('tipo_prueba');
        $medicoId = $request->get('medico_id');

        $user = auth()->user();
        if ($user && ! $user->hasRole('Administrador')) {
            $medicoId = $user->id;
        }

        $suffixParts = [];
        if ($zona) {
            $suffixParts[] = 'zona_'.$zona;
        }
        if ($tipoActividad) {
            $suffixParts[] = 'act_'.$tipoActividad;
        }

        $suffix = ! empty($suffixParts) ? implode('_', $suffixParts) : date('d-m-Y');
        $filename = 'sabana_dictamenes_'.$suffix.'.xlsx';

        return Excel::download(
            new InspeccionExport($zona, $tipoActividad, $medicoId),
            $filename
        );
    }

    /**
     * Exporta los dictámenes de la "Sábana" a un PDF consolidado aplicando los filtros.
     */
    public function exportPdfSabana(Request $request)
    {
        $zona = $request->get('zona');
        $tipoActividad = $request->get('tipo_actividad') ?? $request->get('tipo_prueba');
        $medicoId = $request->get('medico_id');

        $user = auth()->user();
        if ($user && ! $user->hasRole('Administrador')) {
            $medicoId = $user->id;
        }

        $inspecciones = Inspeccion::with(['predio.productor', 'detalles.animal', 'veterinario'])
            ->applySabanaFilters($zona, $tipoActividad, $medicoId)
            ->latest('fecha')
            ->latest('id')
            ->get();

        if ($inspecciones->isEmpty()) {
            return $this->sabanaPdfError($request, 'No hay dictámenes que coincidan con los filtros seleccionados.');
        }

        if ($inspecciones->count() > 50) {
            return $this->sabanaPdfError($request, 'Hay más de 50 dictámenes con los filtros seleccionados. Refina los filtros para descargar el PDF.');
        }

        $pageEstimate = $inspecciones->sum(function ($ins) {
            return 1 + (int) ceil(($ins->detalles->count() ?? 0) / 30);
        });

        if ($pageEstimate > 200) {
            return $this->sabanaPdfError($request, 'El PDF supera el límite de páginas estimadas ('.$pageEstimate.'). Refina los filtros.');
        }

        $output = $this->mergeDictamenPdf($inspecciones);

        $suffixParts = [];
        if ($zona) {
            $suffixParts[] = 'zona_'.$zona;
        }
        if ($tipoActividad) {
            $suffixParts[] = 'act_'.$tipoActividad;
        }

        $suffix = ! empty($suffixParts) ? implode('_', $suffixParts) : date('d-m-Y');
        $filename = 'dictamenes_sabana_'.$suffix.'.pdf';

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function sabanaPdfError(Request $request, string $message)
    {
        if ($request->is('api/*')) {
            return response()->json(['message' => $message], 400);
        }

        return redirect()->back()->with('error', $message);
    }
}
