<?php

namespace App\Http\Controllers;

use App\Exports\InspeccionExport;
use App\Models\Inspeccion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

// Requiere maatwebsite/excel

class ReporteController extends Controller
{
    /**
     * Exporta los resultados de una inspección a PDF.
     */
    public function streamPdf($id)
    {
        $inspeccion = Inspeccion::with(['predio.productor', 'detalles.animal', 'veterinario'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('reports.inspeccion_pdf', compact('inspeccion'));

        return $pdf->stream($inspeccion->buildPdfFilename());
    }

    public function exportPdf($id)
    {
        $inspeccion = Inspeccion::with(['predio.productor', 'detalles.animal', 'veterinario'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('reports.inspeccion_pdf', compact('inspeccion'));

        return $pdf->download($inspeccion->buildPdfFilename());
    }

    /**
     * Exporta la "Sábana" de inspecciones a Excel.
     */
    public function exportExcel(Request $request)
    {
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');

        $suffix = $desde ? date('d-m-Y') : 'todo';
        if ($desde && $hasta) {
            $suffix = $desde.'_'.$hasta;
        } elseif ($desde) {
            $suffix = 'desde_'.$desde;
        }

        return Excel::download(new InspeccionExport($desde, $hasta), 'dictamenes_pecuarios_'.$suffix.'.xlsx');
    }
}
