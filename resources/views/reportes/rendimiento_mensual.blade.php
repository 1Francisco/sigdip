@extends('layouts.app')

@section('title', 'Rendimiento Mensual')
@section('header_title', 'Rendimiento Mensual')
@section('header_subtitle', 'Desempeño mensual detallado por médico')

@section('styles')
<style>
    .nav-pills-premium {
        background: #f1f5f9;
        padding: 4px;
        border-radius: 12px;
        display: inline-flex;
        border: none;
    }
    .nav-pills-premium .nav-item {
        margin: 0;
    }
    .nav-pills-premium .nav-link {
        border: none;
        color: #64748b !important;
        font-weight: 600;
        padding: 0.6rem 1.25rem;
        transition: all 0.2s ease;
        border-radius: 10px;
        margin: 0;
        background: transparent !important;
        box-shadow: none !important;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .nav-pills-premium .nav-link:hover {
        color: #1e293b !important;
    }
    .nav-pills-premium .nav-link.active {
        color: #2563eb !important;
        background: white !important;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05) !important;
    }
</style>
@endsection

@section('content')
@php
    $rows = collect($rows);
    $yearsArray = $years->values()->toArray();
    $currentIdx = array_search((int) $year, $yearsArray);
    $prevYear = $currentIdx !== false && $currentIdx < count($yearsArray) - 1 ? $yearsArray[$currentIdx + 1] : null;
    $nextYear = $currentIdx !== false && $currentIdx > 0 ? $yearsArray[$currentIdx - 1] : null;
@endphp
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

<ul class="nav nav-pills nav-pills-premium mb-4" id="reportTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <a href="{{ route('reportes.rendimiento', array_merge(request()->query(), ['tab' => 'medicos'])) }}"
           class="nav-link">
            <i class="bi bi-person-badge"></i> Médicos
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a href="{{ route('reportes.rendimiento.mensual', request()->query()) }}"
           class="nav-link active"
           role="tab">
            <i class="bi bi-table"></i> Detalle Mensual
        </a>
    </li>
</ul>

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
    <div class="card-header bg-white fw-bold py-3">
        <span><i class="bi bi-calendar-month me-2 text-primary"></i> Detalle Mensual por Médico</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-mensual">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 col-medico">Médico</th>
                        <th class="col-mes">Mes</th>
                        <th class="text-center col-num">Inspecciones</th>
                        <th class="text-center col-num">PPC</th>
                        <th class="text-center col-num">PCC</th>
                        <th class="text-center col-num">Predios</th>
                        <th class="text-center col-num">Visitas</th>
                        <th class="text-center col-num">Animales</th>
                        <th class="text-center col-num">Reactores</th>
                        <th class="text-center col-num">React. PPC</th>
                        <th class="text-center col-num">React. PCC</th>
                        <th class="text-center pe-4 col-download">Descargar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $parts = explode('-', $row->mes);
                            $mesNombre = \Carbon\Carbon::createFromFormat('Y-m-d', $row->mes . '-01')->locale('es')->translatedFormat('F');
                            $firstDay = $parts[0] . '-' . $parts[1] . '-01';
                            $lastDay = \Carbon\Carbon::createFromDate($parts[0], (int) $parts[1], 1)->endOfMonth()->toDateString();
                        @endphp

                        <tr>
                            <td class="ps-4 fw-semibold">{{ $row->medico_nombre }}</td>
                            <td>{{ ucfirst($mesNombre) }}</td>
                            <td class="text-center"><span class="badge bg-primary rounded-pill">{{ $row->total_inspecciones }}</span></td>
                            <td class="text-center">{{ $row->ppc ?? 0 }}</td>
                            <td class="text-center">{{ $row->pcc ?? 0 }}</td>
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
                            <td class="text-center">
                                @if(($row->reactores_ppc ?? 0) > 0)
                                    <span class="badge bg-danger rounded-pill">{{ $row->reactores_ppc }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(($row->reactores_pcc ?? 0) > 0)
                                    <span class="badge bg-danger rounded-pill">{{ $row->reactores_pcc }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center pe-4 col-download">
                                <a href="{{ route('reportes.rendimiento.pdf', ['medico_id' => $row->veterinario_id, 'fecha_desde' => $firstDay, 'fecha_hasta' => $lastDay]) }}"
                                   class="btn btn-sm px-1 py-0 text-danger" title="Descargar PDF este mes">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">
                                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                No hay datos para el período seleccionado.
                            </td>
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
                        <tr class="table-primary fw-bold">
                            <td class="ps-4">Total General</td>
                            <td class="text-muted fst-italic">——</td>
                            <td class="text-center">{{ $totalInspecciones }}</td>
                            <td class="text-center">{{ $totalPPC }}</td>
                            <td class="text-center">{{ $totalPCC }}</td>
                            <td class="text-center">{{ $totalPredios }}</td>
                            <td class="text-center">{{ $totalVisitas }}</td>
                            <td class="text-center">{{ $totalAnimales }}</td>
                            <td class="text-center">{{ $totalReactores }}</td>
                            <td class="text-center">{{ $totalReactoresPPC }}</td>
                            <td class="text-center">{{ $totalReactoresPCC }}</td>
                            <td class="text-center pe-4"></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
        <a href="{{ $prevYear ? route('reportes.rendimiento.mensual', array_merge(request()->query(), ['year' => $prevYear])) : '#' }}"
           class="btn btn-outline-primary rounded-3 px-3 d-flex align-items-center gap-2 {{ !$prevYear ? 'disabled' : '' }}">
            <i class="bi bi-chevron-left"></i> {{ $prevYear ?? '' }}
        </a>
        <div class="text-center">
            <div class="fw-bold fs-6 text-dark">{{ $year }}</div>
            <small class="text-secondary">{{ count($yearsArray) }} años disponibles</small>
        </div>
        <a href="{{ $nextYear ? route('reportes.rendimiento.mensual', array_merge(request()->query(), ['year' => $nextYear])) : '#' }}"
           class="btn btn-outline-primary rounded-3 px-3 d-flex align-items-center gap-2 {{ !$nextYear ? 'disabled' : '' }}">
            {{ $nextYear ?? '' }} <i class="bi bi-chevron-right"></i>
        </a>
    </div>
</div>
@endsection
