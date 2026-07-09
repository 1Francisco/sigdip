<?php

namespace App\Exports;

use App\Models\Inspeccion;
use App\Models\User;
use App\Models\Visita;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RendimientoExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private ?string $fechaDesde;

    private ?string $fechaHasta;

    private ?string $estado;

    private ?string $zona;

    private ?string $medicoId;

    public function __construct(
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        ?string $estado = null,
        ?string $zona = null,
        ?string $medicoId = null,
    ) {
        $this->fechaDesde = $fechaDesde;
        $this->fechaHasta = $fechaHasta;
        $this->estado = $estado;
        $this->zona = $zona;
        $this->medicoId = $medicoId;
    }

    public function collection()
    {
        $medicos = User::role('Medico_Campo')->orderBy('name')->get();

        return $medicos->map(function ($user) {
            $inspeccionesQuery = Inspeccion::where('inspecciones.veterinario_id', $user->id)
                ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
                ->join('productores', 'predios.productor_id', '=', 'productores.id');

            if ($this->fechaDesde) {
                $inspeccionesQuery->whereDate('inspecciones.fecha', '>=', $this->fechaDesde);
            }
            if ($this->fechaHasta) {
                $inspeccionesQuery->whereDate('inspecciones.fecha', '<=', $this->fechaHasta);
            }
            if ($this->estado) {
                $inspeccionesQuery->where('inspecciones.estado', $this->estado);
            }
            if ($this->zona) {
                $inspeccionesQuery->where('productores.zona', $this->zona);
            }

            $totalInspecciones = (clone $inspeccionesQuery)->count();
            $prediosAtendidos = (clone $inspeccionesQuery)->distinct('inspecciones.predio_id')->count('inspecciones.predio_id');
            $ultima = (clone $inspeccionesQuery)->max('inspecciones.fecha');

            $visitasQuery = Visita::where('veterinario_id', $user->id);
            if ($this->fechaDesde) {
                $visitasQuery->whereDate('fecha_programada', '>=', $this->fechaDesde);
            }
            if ($this->fechaHasta) {
                $visitasQuery->whereDate('fecha_programada', '<=', $this->fechaHasta);
            }
            $totalVisitas = (clone $visitasQuery)->count();
            $visitasCompletadas = (clone $visitasQuery)->where('estado', 'completada')->count();

            return [
                $user->name,
                $totalInspecciones,
                $totalVisitas,
                $visitasCompletadas,
                $prediosAtendidos,
                $ultima ? date('d/m/Y', strtotime($ultima)) : 'Sin actividad',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Médico',
            'Inspecciones',
            'Visitas',
            'Visitas Completadas',
            'Predios Atendidos',
            'Última Actividad',
        ];
    }

    public function map($row): array
    {
        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        $headerStyle = [
            'font' => ['bold' => true, 'name' => 'Arial', 'size' => 11],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('A2:F'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
