<?php

namespace App\Exports;

use App\Models\Inspeccion;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InspeccionExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private ?string $zona;

    private ?string $tipoActividad;

    private ?string $medicoId;

    public function __construct(
        ?string $zona = null,
        ?string $tipoActividad = null,
        ?string $medicoId = null
    ) {
        $this->zona = $zona;
        $this->tipoActividad = $tipoActividad;
        $this->medicoId = $medicoId;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $query = Inspeccion::with(['predio.productor', 'detalles', 'veterinario']);

        $user = auth()->user();
        if ($user && ! $user->hasRole('Administrador')) {
            $query->where('veterinario_id', $user->id);
        }

        $query->applySabanaFilters($this->zona, $this->tipoActividad, $this->medicoId);

        return $query->get();
    }

    public function headings(): array
    {
        return [
            [
                'CLAVE',
                'PREDIO',
                'UPP',
                'NOMBRE DEL BENEFICIARIO',
                'MUNICIPIO',
                'LOCALIDAD',
                'FIN ZOOTECNICO',
                'FECHA',
                'PRUEBA PLIEGUE CAUDAL',
                '',
                '',
                'UBICACIÓN GPS',
                '',
                '',
                'OBSERVACIONES',
            ],
            [
                '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            ],
            [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'PROBADOS',
                'NEGATIVOS',
                'REACTORES',
                'LATITUD',
                'LONGITUD',
                'ALTITUD',
                '',
            ],
        ];
    }

    public function map($inspeccion): array
    {
        $negativos = $inspeccion->detalles->where('resultado_prueba', 'Negativo')->count();
        $reactores = $inspeccion->detalles->whereIn('resultado_prueba', ['Positivo', 'Sospechoso'])->count();
        $totalProbados = $inspeccion->detalles->count();

        return [
            $inspeccion->clave_interna ?: $inspeccion->folio,
            $inspeccion->predio?->nombre_rancho,
            $inspeccion->predio?->clave_unidad_produccion,
            $inspeccion->predio?->productor
                ? trim($inspeccion->predio->productor->nombre.' '.$inspeccion->predio->productor->apellido_paterno.' '.$inspeccion->predio->productor->apellido_materno)
                : '',
            $inspeccion->predio?->municipio ?? '',
            $inspeccion->predio?->localidad,
            $inspeccion->funcion_zootecnica,
            $inspeccion->fecha ? $inspeccion->fecha->format('d/m/Y') : '',
            $totalProbados,
            $negativos,
            $reactores,
            $inspeccion->predio?->latitud,
            $inspeccion->predio?->longitud,
            '', // Altitud
            $inspeccion->observaciones,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Merging cells for the headers
        $sheet->mergeCells('A1:A3');
        $sheet->mergeCells('B1:B3');
        $sheet->mergeCells('C1:C3');
        $sheet->mergeCells('D1:D3');
        $sheet->mergeCells('E1:E3');
        $sheet->mergeCells('F1:F3');
        $sheet->mergeCells('G1:G3');
        $sheet->mergeCells('H1:H3');
        $sheet->mergeCells('I1:K2');
        $sheet->mergeCells('L1:N2');
        $sheet->mergeCells('O1:O3');

        // Apply style to headers with explicit ARGB definitions to fix OpenXML rendering in Excel
        $headerStyle = [
            'font' => [
                'bold' => true,
                'name' => 'Arial',
                'size' => 9,
                'color' => [
                    'argb' => 'FFFFFFFF',
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FF1155CC',
                ],
                'endColor' => [
                    'argb' => 'FF1155CC',
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        $sheet->getStyle('A1:O3')->applyFromArray($headerStyle);

        // Adjust row heights for headers
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Center align the data rows for columns A, C, G, H, I, J, K, L, M, N
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 3) {
            $sheet->getStyle('A4:A'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C4:C'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G4:K'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('L4:N'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
