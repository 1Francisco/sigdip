<?php

namespace App\Http\Controllers;

use App\Models\Inspeccion;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // Requiere dompdf/laravel-dompdf
use Maatwebsite\Excel\Facades\Excel; // Requiere maatwebsite/excel

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
        
        return $pdf->stream("inspeccion_{$inspeccion->id}.pdf");
    }

    public function exportPdf($id)
    {
        $inspeccion = Inspeccion::with(['predio.productor', 'detalles.animal', 'veterinario'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('reports.inspeccion_pdf', compact('inspeccion'));
        
        return $pdf->download("inspeccion_{$inspeccion->id}.pdf");
    }

    /**
     * Exporta la "Sábana" de inspecciones a Excel.
     */
    public function exportExcel()
    {
        return Excel::download(new \App\Exports\InspeccionExport, 'dictamenes_pecuarios_' . date('Ymd') . '.xlsx');
    }
}
