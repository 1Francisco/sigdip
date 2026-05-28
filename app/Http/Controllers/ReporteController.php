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
        
        return $pdf->stream($this->buildPdfFilename($inspeccion));
    }

    public function exportPdf($id)
    {
        $inspeccion = Inspeccion::with(['predio.productor', 'detalles.animal', 'veterinario'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('reports.inspeccion_pdf', compact('inspeccion'));
        
        return $pdf->download($this->buildPdfFilename($inspeccion));
    }

    /**
     * Genera el nombre del archivo PDF con el formato:
     * DICTAMEN_{NOMBRE_PRODUCTOR}_{FECHA_INSPECCION}.pdf
     */
    private function buildPdfFilename(Inspeccion $inspeccion): string
    {
        $productor = $inspeccion->predio->productor;

        // Nombre: apellido paterno + primer nombre
        $nombre = trim($productor->apellido_paterno . ' ' . $productor->nombre);

        // Sanitizar: quitar acentos, ñ, y caracteres no permitidos en nombres de archivo
        $nombre = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombre);
        $nombre = preg_replace('/[^A-Za-z0-9 _-]/', '', $nombre);
        $nombre = preg_replace('/\s+/', '_', trim($nombre));
        $nombre = strtoupper($nombre);

        // Fecha: usar fecha de inyección si existe, si no la fecha de hoy
        $fecha = $inspeccion->fecha_inyeccion
            ? \Carbon\Carbon::parse($inspeccion->fecha_inyeccion)->format('d-m-Y')
            : now()->format('d-m-Y');

        return "DICTAMEN_{$nombre}_{$fecha}.pdf";
    }

    /**
     * Exporta la "Sábana" de inspecciones a Excel.
     */
    public function exportExcel()
    {
        return Excel::download(new \App\Exports\InspeccionExport, 'dictamenes_pecuarios_' . date('d-m-Y') . '.xlsx');
    }
}
