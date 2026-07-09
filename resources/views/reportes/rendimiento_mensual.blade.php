@extends('layouts.app')

@section('title', 'Rendimiento Mensual')
@section('header_title', 'Rendimiento Mensual')
@section('header_subtitle', 'Desempeño mensual detallado por médico')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body bg-light border-bottom py-3 px-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold text-secondary mb-1">Año</label>
                <select name="year" class="form-select form-select-sm">
                    @foreach($years as $y)
                        <option value="{{ $y }}" @selected((int) $year === (int) $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-sm-4">
                <label class="form-label small fw-semibold text-secondary mb-1">Estado</label>
                <select name="estado" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="borrador" @selected($estado === 'borrador')>Borrador</option>
                    <option value="sincronizado" @selected($estado === 'sincronizado')>Sincronizado</option>
                </select>
            </div>
            <div class="col-md-2 col-sm-4">
                <label class="form-label small fw-semibold text-secondary mb-1">Zona</label>
                <select name="zona" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <option value="A" @selected($zona === 'A')>Zona A</option>
                    <option value="B" @selected($zona === 'B')>Zona B</option>
                </select>
            </div>
            <div class="col-md-3 col-sm-4">
                <label class="form-label small fw-semibold text-secondary mb-1">Médico</label>
                <select name="medico_id" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach($medicos as $medico)
                        <option value="{{ $medico->id }}" @selected($medicoId == $medico->id)>{{ $medico->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-sm-6 d-flex gap-2">
                <button class="btn btn-sm btn-primary rounded-pill px-3 flex-fill" type="submit">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
                @if(request()->anyFilled(['year', 'estado', 'zona', 'medico_id']))
                    <a href="{{ route('reportes.rendimiento.mensual') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<style>
.table-mensual { table-layout: fixed; }
.table-mensual th,
.table-mensual td { overflow: hidden; text-overflow: ellipsis; }
.table-mensual .col-medico { width: 22%; min-width: 170px; }
.table-mensual .col-mes { width: 14%; min-width: 110px; }
.table-mensual .col-num { width: 10%; }
.table-mensual .col-download { width: 12%; min-width: 100px; white-space: nowrap; }
</style>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar-month me-2 text-primary"></i> Detalle Mensual por Médico</span>
        <a href="{{ route('reportes.rendimiento.mensual.excel', request()->query()) }}" class="btn btn-sm btn-success rounded-pill px-3">
            <i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-mensual">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 col-medico">Médico</th>
                        <th class="col-mes">Mes</th>
                        <th class="text-center col-num">Inspecciones</th>
                        <th class="text-center col-num">Predios</th>
                        <th class="text-center col-num">Visitas</th>
                        <th class="text-center col-num">Animales</th>
                        <th class="text-center col-num">Reactores</th>
                        <th class="text-center pe-4 col-download">Descargar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $parts = explode('-', $row->mes);
                            $mesNombre = \Carbon\Carbon::createFromFormat('Y-m', $row->mes)->locale('es')->translatedFormat('F');
                            $firstDay = $parts[0] . '-' . $parts[1] . '-01';
                            $lastDay = \Carbon\Carbon::createFromDate($parts[0], (int) $parts[1], 1)->endOfMonth()->toDateString();
                        @endphp

                        <tr>
                            <td class="ps-4 fw-semibold">{{ $row->medico_nombre }}</td>
                            <td>{{ ucfirst($mesNombre) }}</td>
                            <td class="text-center"><span class="badge bg-primary rounded-pill">{{ $row->total_inspecciones }}</span></td>
                            <td class="text-center">{{ $row->predios }}</td>
                            <td class="text-center">{{ $row->total_visitas }}</td>
                            <td class="text-center">{{ $row->total_animales }}</td>
                            <td class="text-center">
                                @if($row->total_reactores > 0)
                                    <span class="badge bg-danger rounded-pill">{{ $row->total_reactores }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center pe-4 col-download">
                                <a href="{{ route('reportes.rendimiento.pdf', ['medico_id' => $row->veterinario_id, 'fecha_desde' => $firstDay, 'fecha_hasta' => $lastDay]) }}"
                                   class="btn btn-sm px-1 py-0 text-danger" title="Descargar PDF este mes">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <a href="{{ route('reportes.rendimiento.excel', ['medico_id' => $row->veterinario_id, 'fecha_desde' => $firstDay, 'fecha_hasta' => $lastDay]) }}"
                                   class="btn btn-sm px-1 py-0 text-success" title="Descargar Excel este mes">
                                    <i class="bi bi-file-earmark-excel"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                No hay datos para el período seleccionado.
                            </td>
                        </tr>
                    @endforelse

                    @if($rows->isNotEmpty())
                        @php
                            $totalInspecciones = $rows->sum('total_inspecciones');
                            $totalPredios = $rows->sum('predios');
                            $totalVisitas = $rows->sum('total_visitas');
                            $totalAnimales = $rows->sum('total_animales');
                            $totalReactores = $rows->sum('total_reactores');
                        @endphp
                        <tr class="table-primary fw-bold">
                            <td class="ps-4">Total General</td>
                            <td class="text-muted fst-italic">——</td>
                            <td class="text-center">{{ $totalInspecciones }}</td>
                            <td class="text-center">{{ $totalPredios }}</td>
                            <td class="text-center">{{ $totalVisitas }}</td>
                            <td class="text-center">{{ $totalAnimales }}</td>
                            <td class="text-center">{{ $totalReactores }}</td>
                            <td class="text-center pe-4"></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
