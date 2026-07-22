<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rendimiento Mensual {{ $year }}</title>
    <style>
        body { font-family: sans-serif; font-size: 9px; color: #1e293b; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; color: #1e3a8a; }
        .subtitle { text-align: center; font-size: 10px; color: #64748b; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background-color: #1e3a8a; color: #fff; font-weight: 600; padding: 4px 5px; text-align: center; font-size: 8px; }
        td { padding: 3px 5px; border-bottom: 1px solid #e2e8f0; text-align: center; font-size: 8px; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: 700; }
        .totals-row { background: #f8fafc; font-weight: 700; }
        .header-logo { text-align: center; margin-bottom: 10px; }
        .header-logo img { max-height: 50px; }
    </style>
</head>
<body>
    @php
        $rows = collect($rows);
    @endphp
    <div class="header-logo">
        <img src="{{ public_path('img/logo_sigdip.png') }}" alt="SIGDIP" onerror="this.style.display='none'">
    </div>

    <h1>Rendimiento Mensual {{ $year }}</h1>
    <p class="subtitle">Sistema Integral de Gestión de Dictámenes de Lectura Pecuaria</p>

    <table>
        <thead>
            <tr>
                <th class="text-left">Médico</th>
                <th>Mes</th>
                <th>Lecturas</th>
                <th>PPC</th>
                <th>PCC</th>
                <th>Predios</th>
                <th>Visitas</th>
                <th>Animales</th>
                <th>Reactores</th>
                <th>React. PPC</th>
                <th>React. PCC</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                @php
                    $parts = explode('-', $row->mes);
                    $mesNombre = \Carbon\Carbon::createFromFormat('Y-m-d', $row->mes . '-01')->locale('es')->translatedFormat('F');
                @endphp
                <tr>
                    <td class="text-left fw-bold">{{ $row->medico_nombre }}</td>
                    <td>{{ ucfirst($mesNombre) }}</td>
                    <td>{{ $row->total_inspecciones }}</td>
                    <td>{{ $row->ppc ?? 0 }}</td>
                    <td>{{ $row->pcc ?? 0 }}</td>
                    <td>{{ $row->predios }}</td>
                    <td>{{ $row->total_visitas }}</td>
                    <td>{{ $row->total_animales }}</td>
                    <td>{{ $row->total_reactores }}</td>
                    <td>{{ $row->reactores_ppc ?? 0 }}</td>
                    <td>{{ $row->reactores_pcc ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center;color:#94a3b8;padding:20px;">No hay datos para el período seleccionado.</td>
                </tr>
            @endforelse

            @if($rows->isNotEmpty())
                @php
                    $totalInspecciones = $rows->sum('total_inspecciones');
                    $totalPPC = $rows->sum(fn($r) => $r->ppc ?? 0);
                    $totalPCC = $rows->sum(fn($r) => $r->pcc ?? 0);
                    $totalPredios = $rows->sum('predios');
                    $totalVisitas = $rows->sum('total_visitas');
                    $totalAnimales = $rows->sum('total_animales');
                    $totalReactores = $rows->sum('total_reactores');
                    $totalReactoresPPC = $rows->sum(fn($r) => $r->reactores_ppc ?? 0);
                    $totalReactoresPCC = $rows->sum(fn($r) => $r->reactores_pcc ?? 0);
                @endphp
                <tr class="totals-row">
                    <td class="text-left fw-bold">Total General</td>
                    <td>——</td>
                    <td>{{ $totalInspecciones }}</td>
                    <td>{{ $totalPPC }}</td>
                    <td>{{ $totalPCC }}</td>
                    <td>{{ $totalPredios }}</td>
                    <td>{{ $totalVisitas }}</td>
                    <td>{{ $totalAnimales }}</td>
                    <td>{{ $totalReactores }}</td>
                    <td>{{ $totalReactoresPPC }}</td>
                    <td>{{ $totalReactoresPCC }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="subtitle" style="margin-top:20px;border-top:1px solid #e2e8f0;padding-top:8px;">
        Generado el {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY [a las] h:mm a') }}
    </div>
</body>
</html>
