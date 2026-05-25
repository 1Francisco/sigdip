<?php

namespace App\Exports;

use App\Models\Inspeccion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InspeccionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'OBSERVACIONES'
        ];
    }

    public function map($inspeccion): array
    {
        $negativos = $inspeccion->detalles->where('resultado_prueba', 'Negativo')->count();
        $sospechosos = $inspeccion->detalles->where('resultado_prueba', 'Sospechoso')->count();
        $positivos = $inspeccion->detalles->where('resultado_prueba', 'Positivo')->count();
        $total_probados = $inspeccion->detalles->count();

        return [
            $inspeccion->id,
            'DEFINITIVA', // O el valor que corresponda a "Recurso"
            $inspeccion->folio,
            $inspeccion->predio->nombre_rancho,
            $inspeccion->predio->clave_unidad_produccion,
            $inspeccion->predio->productor->nombre . ' ' . $inspeccion->predio->productor->apellido_paterno . ' ' . $inspeccion->predio->productor->apellido_materno,
            $inspeccion->predio->municipio ?? $inspeccion->predio->localidad,
            $inspeccion->predio->localidad,
            $inspeccion->sementales + $inspeccion->vacas + $inspeccion->vaquillas + $inspeccion->becerras + $inspeccion->becerros,
            $inspeccion->sementales,
            $inspeccion->vacas,
            $inspeccion->vaquillas,
            $inspeccion->becerras,
            $inspeccion->becerros,
            $inspeccion->funcion_zootecnica,
            $inspeccion->fecha_inyeccion ? $inspeccion->fecha_inyeccion->format('d/m/Y') : '',
            $inspeccion->fecha_lectura ? $inspeccion->fecha_lectura->format('d/m/Y') : '',
            $total_probados,
            $negativos,
            $sospechosos,
            $positivos,
            $inspeccion->predio->latitud,
            $inspeccion->predio->longitud,
            $inspeccion->observaciones
        ];
    }
}
