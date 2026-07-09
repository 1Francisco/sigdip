<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resumen de Dictámenes</title>
    <style>
        @page { margin: 2cm; }
        body { font-family: 'Helvetica', Arial, sans-serif; color: #000; font-size: 12px; text-align: center; padding-top: 80px; }
        h1 { font-size: 20px; margin-bottom: 5px; color: #1a237e; }
        .subtitle { font-size: 14px; color: #666; margin-bottom: 40px; }
        table { margin: 0 auto; border-collapse: collapse; }
        td { padding: 8px 20px; border: 1px solid #ccc; font-size: 12px; }
        td.label { background-color: #f5f5f5; font-weight: bold; text-align: right; }
        td.value { font-weight: bold; text-align: center; }
        .big { font-size: 20px; }
        .red { color: #d32f2f; }
        .orange { color: #e65100; }
        .footer { margin-top: 50px; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <h1>RESUMEN DE DICTÁMENES</h1>
    <div class="subtitle">SIGDIP — Sistema Integral de Gestión de Dictámenes de Inspección Pecuaria</div>

    <table>
        <tr><td class="label">Fecha de generación</td><td class="value">{{ now()->timezone('America/Mazatlan')->format('d/m/Y H:i') }}</td></tr>
        @if($fechaDesde)
        <tr><td class="label">Período</td><td class="value">{{ $fechaDesde }} @if($fechaHasta) a {{ $fechaHasta }} @endif</td></tr>
        @endif
        @if($estado)
        <tr><td class="label">Estado</td><td class="value">{{ ucfirst($estado) }}</td></tr>
        @endif
        @if($zona)
        <tr><td class="label">Zona</td><td class="value">{{ $zona }}</td></tr>
        @endif
        <tr><td class="label">Médico</td><td class="value">{{ $medicoNombre }}</td></tr>
        <tr><td class="label">Total dictámenes</td><td class="value big">{{ $inspecciones->count() }}</td></tr>
        <tr><td class="label">Total animales</td><td class="value big">{{ $totalAnimales }}</td></tr>
        <tr><td class="label">Negativos</td><td class="value">{{ $negativos }}</td></tr>
        <tr><td class="label">Positivos</td><td class="value red">{{ $positivos }}</td></tr>
        <tr><td class="label">Sospechosos</td><td class="value orange">{{ $sospechosos }}</td></tr>
    </table>

    <div class="footer">Este documento contiene {{ $inspecciones->count() }} dictamen(es) oficial(es).</div>
</body>
</html>
