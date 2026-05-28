<?php

namespace App\Exports;

use App\Models\Inspeccion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InspeccionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Inspeccion::with(['predio.productor', 'detalles'])->get();
    }

    public function headings(): array
    {
        return [
            'NUM',
            'RECURSO',
            'CLAVE (FOLIO)',
            'PREDIO',
            'UPP',
            'NOMBRE DEL BENEFICIARIO',
            'MUNICIPIO',
            'LOCALIDAD',
            'POBLACION N',
            'SEMENTALES',
            'VACAS',
            'VAQUILLAS',
            'BECERRAS',
            'BECERROS',
            'FIN ZOOTECNICO',
            'FECHA INYECCION',
            'FECHA LECTURA',
            'ANIMALES PROBADOS',
            'NEGATIVOS',
            'SOSPECHOSOS',
            'REACTORES (POSITIVOS)',
            'LATITUD',
            'LONGITUD',
            'OBSERVACIONES',
        ];
    }

    public function map($inspeccion): array
    {
        $negativos = $inspeccion->detalles->where('resultado_prueba', 'Negativo')->count();
        $sospechosos = $inspeccion->detalles->where('resultado_prueba', 'Sospechoso')->count();
        $positivos = $inspeccion->detalles->where('resultado_prueba', 'Positivo')->count();
        $totalProbados = $inspeccion->detalles->count();

        return [
            $inspeccion->id,
            'DEFINITIVA',
            $inspeccion->folio,
            $inspeccion->predio->nombre_rancho,
            $inspeccion->predio->clave_unidad_produccion,
            $inspeccion->predio->productor->nombre . ' ' . $inspeccion->predio->productor->apellido_paterno . ' ' . $inspeccion->predio->productor->apellido_materno,
            $inspeccion->predio->municipio ?? $inspeccion->predio->localidad,
            $inspeccion->predio->localidad,
            $inspeccion->semental + $inspeccion->vacas + $inspeccion->vaquillas + $inspeccion->becerras + $inspeccion->becerros,
            $inspeccion->semental,
            $inspeccion->vacas,
            $inspeccion->vaquillas,
            $inspeccion->becerras,
            $inspeccion->becerros,
            $inspeccion->funcion_zootecnica,
            $inspeccion->fecha_inyeccion ? $inspeccion->fecha_inyeccion->format('d/m/Y') : '',
            $inspeccion->fecha_lectura ? $inspeccion->fecha_lectura->format('d/m/Y') : '',
            $totalProbados,
            $negativos,
            $sospechosos,
            $positivos,
            $inspeccion->predio->latitud,
            $inspeccion->predio->longitud,
            $inspeccion->observaciones,
        ];
    }

    /**
     * Apply grey background and bold font to the header row.
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'D9D9D9'],
                ],
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }
}
