<?php

namespace App\Exports;

use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\User;
use App\Models\Visita;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RendimientoMensualExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private int $year;

    private ?string $medicoId;

    private ?string $zona;

    private ?string $estado;

    public function __construct(
        int $year,
        ?string $medicoId = null,
        ?string $zona = null,
        ?string $estado = null,
    ) {
        $this->year = $year;
        $this->medicoId = $medicoId;
        $this->zona = $zona;
        $this->estado = $estado;
    }

    public function collection()
    {
        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite' ? "strftime('%Y-%m', inspecciones.fecha)" : "DATE_FORMAT(inspecciones.fecha, '%Y-%m')";
        $mesExprVisitas = $driver === 'sqlite' ? "strftime('%Y-%m', visitas.fecha_programada)" : "DATE_FORMAT(visitas.fecha_programada, '%Y-%m')";
        $mesExprDetalles = $driver === 'sqlite' ? "strftime('%Y-%m', inspecciones.fecha)" : "DATE_FORMAT(inspecciones.fecha, '%Y-%m')";

        $medicos = User::role('Medico_Campo')->orderBy('name')->get();
        $medicoNames = $medicos->pluck('name', 'id');

        $rawRows = Inspeccion::select(
            'inspecciones.veterinario_id',
            'inspecciones.tipo_prueba',
            DB::raw("{$monthExpr} as mes"),
            DB::raw('COUNT(*) as total_inspecciones'),
            DB::raw('COUNT(DISTINCT inspecciones.predio_id) as predios')
        )
            ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id')
            ->whereYear('inspecciones.fecha', $this->year)
            ->groupBy('inspecciones.veterinario_id', 'inspecciones.tipo_prueba', DB::raw($monthExpr))
            ->orderBy('mes')
            ->when($this->medicoId, fn ($q) => $q->where('inspecciones.veterinario_id', $this->medicoId))
            ->when($this->zona, fn ($q) => $q->where('productores.zona', $this->zona))
            ->when($this->estado, fn ($q) => $q->where('inspecciones.estado', $this->estado))
            ->get();

        $grouped = collect();
        foreach ($rawRows as $row) {
            $key = $row->veterinario_id.'|'.$row->mes;
            if (! $grouped->has($key)) {
                $row->medico_nombre = $medicoNames[$row->veterinario_id] ?? 'Desconocido';
                $row->ppc = 0;
                $row->pcc = 0;
                $row->total_inspecciones = 0;
                $row->predios = 0;
                $grouped[$key] = $row;
            }
            $existing = $grouped[$key];
            $existing->total_inspecciones += $row->total_inspecciones;
            $existing->predios = max($existing->predios, $row->predios);
            $tipo = in_array($row->tipo_prueba, ['P.P.C.', 'PPC']) ? 'ppc' : 'pcc';
            $existing->{$tipo} += $row->total_inspecciones;
        }
        $rows = $grouped->values();

        $visitasData = Visita::select(
            'veterinario_id',
            DB::raw("{$mesExprVisitas} as mes"),
            DB::raw('COUNT(*) as total_visitas')
        )
            ->whereYear('fecha_programada', $this->year)
            ->groupBy('veterinario_id', DB::raw($mesExprVisitas))
            ->when($this->medicoId, fn ($q) => $q->where('veterinario_id', $this->medicoId))
            ->get()
            ->keyBy(fn ($v) => $v->veterinario_id.'|'.$v->mes);

        $detallesData = DetalleInspeccion::select(
            'inspecciones.veterinario_id',
            'inspecciones.tipo_prueba',
            DB::raw("{$mesExprDetalles} as mes"),
            DB::raw('COUNT(*) as total_animales'),
            DB::raw("COALESCE(SUM(CASE WHEN detalles_inspeccion.resultado_prueba IN ('Positivo','Sospechoso') THEN 1 ELSE 0 END), 0) as total_reactores")
        )
            ->join('inspecciones', 'detalles_inspeccion.inspeccion_id', '=', 'inspecciones.id')
            ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
            ->join('productores', 'predios.productor_id', '=', 'productores.id')
            ->whereYear('inspecciones.fecha', $this->year)
            ->groupBy('inspecciones.veterinario_id', 'inspecciones.tipo_prueba', DB::raw($mesExprDetalles))
            ->when($this->medicoId, fn ($q) => $q->where('inspecciones.veterinario_id', $this->medicoId))
            ->when($this->zona, fn ($q) => $q->where('productores.zona', $this->zona))
            ->when($this->estado, fn ($q) => $q->where('inspecciones.estado', $this->estado))
            ->get()
            ->groupBy(fn ($d) => $d->veterinario_id.'|'.$d->mes);

        foreach ($rows as $row) {
            $key = $row->veterinario_id.'|'.$row->mes;
            $row->total_visitas = $visitasData->get($key)?->total_visitas ?? 0;
            $row->total_animales = 0;
            $row->total_reactores = 0;
            $row->reactores_ppc = 0;
            $row->reactores_pcc = 0;

            $detalles = $detallesData->get($key, collect());
            foreach ($detalles as $d) {
                $row->total_animales += $d->total_animales;
                $row->total_reactores += $d->total_reactores;
                $tipo = in_array($d->tipo_prueba, ['P.P.C.', 'PPC']) ? 'reactores_ppc' : 'reactores_pcc';
                $row->{$tipo} += $d->total_reactores;
            }
        }

        $export = collect();
        foreach ($rows as $row) {
            $parts = explode('-', $row->mes);
            $mesNombre = Carbon::createFromFormat('Y-m', $row->mes)->locale('es')->translatedFormat('F Y');

            $export->push([
                'medico' => $row->medico_nombre,
                'mes' => ucfirst($mesNombre),
                'inspecciones' => $row->total_inspecciones,
                'ppc' => $row->ppc ?? 0,
                'pcc' => $row->pcc ?? 0,
                'predios' => $row->predios,
                'visitas' => $row->total_visitas,
                'animales' => $row->total_animales,
                'reactores' => $row->total_reactores,
                'reactores_ppc' => $row->reactores_ppc ?? 0,
                'reactores_pcc' => $row->reactores_pcc ?? 0,
            ]);
        }

        return $export;
    }

    public function headings(): array
    {
        return [
            'Médico',
            'Mes',
            'Inspecciones',
            'PPC',
            'PCC',
            'Predios',
            'Visitas',
            'Animales Probados',
            'Reactores',
            'Reactores PPC',
            'Reactores PCC',
        ];
    }

    public function map($row): array
    {
        return [
            $row['medico'],
            $row['mes'],
            $row['inspecciones'],
            $row['ppc'],
            $row['pcc'],
            $row['predios'],
            $row['visitas'],
            $row['animales'],
            $row['reactores'],
            $row['reactores_ppc'],
            $row['reactores_pcc'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial', 'size' => 11],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('A2:K'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
