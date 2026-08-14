<?php

namespace App\Http\Controllers\Concerns;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

trait MergesDictamenPdf
{
    /**
     * Combina los dictámenes (PDF individuales) de las inspecciones en un solo PDF.
     */
    private function mergeDictamenPdf(Collection $inspecciones): string
    {
        $pdf = new Fpdi;
        $tmpFiles = [];

        foreach ($inspecciones as $inspeccion) {
            $imported = false;
            if ($inspeccion->dictamen_comite_path) {
                $filePath = Storage::disk('public')->path($inspeccion->dictamen_comite_path);
                if (file_exists($filePath)) {
                    try {
                        $pageCount = $pdf->setSourceFile($filePath);
                        for ($i = 1; $i <= $pageCount; $i++) {
                            $tpl = $pdf->importPage($i);
                            $size = $pdf->getTemplateSize($tpl);
                            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                            $pdf->useTemplate($tpl);
                        }
                        $imported = true;
                    } catch (\Throwable $e) {
                        Log::warning("Could not merge uploaded PDF for inspection {$inspeccion->id} due to FPDI limitations: ".$e->getMessage());
                    }
                }
            }

            if ($imported) {
                continue;
            }

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

        return $pdf->Output('S');
    }
}
