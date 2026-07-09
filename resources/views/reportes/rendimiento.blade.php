@extends('layouts.app')

@section('title', 'Rendimiento de Médicos')
@section('header_title', 'Rendimiento de Médicos')
@section('header_subtitle', 'Reportes de actividad y productividad del personal de campo')

@section('content')
<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm p-3 text-center h-100">
            <div class="text-primary fs-5 fw-bold">{{ $kpiInspecciones }}</div>
            <small class="text-secondary text-uppercase fw-semibold small">Inspecciones</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm p-3 text-center h-100">
            <div class="text-success fs-5 fw-bold">{{ $kpiVisitas }}</div>
            <small class="text-secondary text-uppercase fw-semibold small">Visitas</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm p-3 text-center h-100">
            <div class="text-info fs-5 fw-bold">{{ $kpiMedicosActivos }}</div>
            <small class="text-secondary text-uppercase fw-semibold small">Médicos Activos</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm p-3 text-center h-100">
            <div class="text-dark fs-5 fw-bold">{{ $kpiAnimales }}</div>
            <small class="text-secondary text-uppercase fw-semibold small">Animales Probados</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm p-3 text-center h-100">
            <div class="text-danger fs-5 fw-bold">{{ $kpiReactores }}</div>
            <small class="text-secondary text-uppercase fw-semibold small">Reactores</small>
        </div>
    </div>
</div>

<!-- Filtros Globales -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body bg-light border-bottom py-3 px-4">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold text-secondary mb-1">Fecha inicio</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ $fechaDesde }}">
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold text-secondary mb-1">Fecha fin</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ $fechaHasta }}">
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
            <div class="col-md-2 col-sm-4">
                <label class="form-label small fw-semibold text-secondary mb-1">Médico</label>
                <select name="medico_id" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach($medicos as $medico)
                        <option value="{{ $medico->id }}" @selected($medicoId == $medico->id)>{{ $medico->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-sm-6 d-flex gap-2">
                <button class="btn btn-sm btn-primary rounded-pill px-3 flex-fill" type="submit">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
                @if(request()->anyFilled(['fecha_desde', 'fecha_hasta', 'estado', 'zona', 'medico_id']))
                    <a href="{{ route('reportes.rendimiento') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mb-3">
    <a href="{{ route('reportes.rendimiento.excel', request()->except('tab', 'secciones', 'page')) }}" class="btn btn-sm btn-success rounded-pill px-3">
        <i class="bi bi-file-earmark-excel me-1"></i> Exportar Todo (Excel)
    </a>
</div>

<!-- Pestañas / Tabs -->
<ul class="nav nav-tabs border-0 mb-4" id="reportTabs" role="tablist">
    <li class="nav-item d-flex align-items-center" role="presentation">
        <button class="nav-link border-0 fw-semibold px-4 {{ $tab === 'medicos' ? 'active bg-white shadow-sm' : 'text-secondary' }}"
                id="tab-medicos" data-bs-toggle="tab" data-bs-target="#panel-medicos"
                type="button" role="tab" onclick="switchTab('medicos')">
            <i class="bi bi-person-badge me-1"></i> Médicos
        </button>
        <a href="{{ route('reportes.rendimiento.pdf', request()->except('tab', 'secciones', 'page')) }}" class="btn btn-sm px-1 py-0 text-danger" title="Descargar PDF consolidado">
            <i class="bi bi-file-earmark-pdf"></i>
        </a>
    </li>
    <li class="nav-item d-flex align-items-center" role="presentation">
        <button class="nav-link border-0 fw-semibold px-4 {{ $tab === 'actividades' ? 'active bg-white shadow-sm' : 'text-secondary' }}"
                id="tab-actividades" data-bs-toggle="tab" data-bs-target="#panel-actividades"
                type="button" role="tab" onclick="switchTab('actividades')">
            <i class="bi bi-activity me-1"></i> Actividades
        </button>
        <a href="{{ route('reportes.rendimiento.pdf', request()->except('tab', 'secciones', 'page')) }}" class="btn btn-sm px-1 py-0 text-danger" title="Descargar PDF consolidado">
            <i class="bi bi-file-earmark-pdf"></i>
        </a>
    </li>
    <li class="nav-item d-flex align-items-center" role="presentation">
        <button class="nav-link border-0 fw-semibold px-4 {{ $tab === 'zona' ? 'active bg-white shadow-sm' : 'text-secondary' }}"
                id="tab-zona" data-bs-toggle="tab" data-bs-target="#panel-zona"
                type="button" role="tab" onclick="switchTab('zona')">
            <i class="bi bi-geo-alt me-1"></i> Zona
        </button>
        <a href="{{ route('reportes.rendimiento.pdf', request()->except('tab', 'secciones', 'page')) }}" class="btn btn-sm px-1 py-0 text-danger" title="Descargar PDF consolidado">
            <i class="bi bi-file-earmark-pdf"></i>
        </a>
    </li>
    <li class="nav-item d-flex align-items-center" role="presentation">
        <button class="nav-link border-0 fw-semibold px-4 {{ $tab === 'cuarentena' ? 'active bg-white shadow-sm' : 'text-secondary' }}"
                id="tab-cuarentena" data-bs-toggle="tab" data-bs-target="#panel-cuarentena"
                type="button" role="tab" onclick="switchTab('cuarentena')">
            <i class="bi bi-shield-exclamation me-1"></i> Cuarentena
        </button>
        <a href="{{ route('reportes.rendimiento.pdf', request()->except('tab', 'secciones', 'page')) }}" class="btn btn-sm px-1 py-0 text-danger" title="Descargar PDF consolidado">
            <i class="bi bi-file-earmark-pdf"></i>
        </a>
    </li>
    <li class="nav-item d-flex align-items-center" role="presentation">
        <button class="nav-link border-0 fw-semibold px-4 {{ $tab === 'mes' ? 'active bg-white shadow-sm' : 'text-secondary' }}"
                id="tab-mes" data-bs-toggle="tab" data-bs-target="#panel-mes"
                type="button" role="tab" onclick="switchTab('mes')">
            <i class="bi bi-calendar-month me-1"></i> Mes
        </button>
        <a href="{{ route('reportes.rendimiento.pdf', request()->except('tab', 'secciones', 'page')) }}" class="btn btn-sm px-1 py-0 text-danger" title="Descargar PDF consolidado">
            <i class="bi bi-file-earmark-pdf"></i>
        </a>
    </li>
    <li class="nav-item d-flex align-items-center" role="presentation">
        <a href="{{ route('reportes.rendimiento.mensual') }}"
           class="nav-link border-0 fw-semibold px-4 {{ $tab === 'mensual' ? 'active bg-white shadow-sm' : 'text-secondary' }}"
           role="tab">
            <i class="bi bi-table me-1"></i> Detalle Mensual
        </a>
    </li>
</ul>

<div class="tab-content" id="reportTabContent">
    <!-- ========== TAB: MÉDICOS ========== -->
    <div class="tab-pane fade {{ $tab === 'medicos' ? 'show active' : '' }}" id="panel-medicos" role="tabpanel">
        <!-- Gráfica de barras -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bar-chart-fill me-2 text-primary"></i> Inspecciones por Médico</span>
                <div class="d-flex gap-2">
                    <a href="{{ route('reportes.rendimiento.pdf', request()->except('tab')) }}" class="btn btn-sm btn-danger rounded-pill px-3">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Descargar Dictámenes (PDF)
                    </a>
                    <a href="{{ route('reportes.rendimiento.excel', request()->except('tab')) }}" class="btn btn-sm btn-success rounded-pill px-3">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div style="height: 350px;">
                    <canvas id="chartMedicos"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabla de rendimiento -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-table me-2 text-primary"></i> Detalle de Rendimiento</span>
                <span class="badge bg-primary rounded-pill">{{ $medicosRendimiento->count() }} médicos</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Médico</th>
                                <th class="text-center">Inspecciones</th>
                                <th class="text-center">Visitas</th>
                                <th class="text-center">Completadas</th>
                                <th class="text-center">Predios</th>
                                <th>Última actividad</th>
                                <th class="text-center">Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($medicosRendimiento as $m)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $m->name }}</td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill">{{ $m->total_inspecciones }}</span>
                                </td>
                                <td class="text-center">{{ $m->total_visitas }}</td>
                                <td class="text-center">
                                    @if($m->total_visitas > 0)
                                        <span class="badge bg-success rounded-pill">{{ $m->visitas_completadas }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $m->predios_atendidos }}</td>
                                <td>
                                    @if($m->ultima_inspeccion)
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($m->ultima_inspeccion)->format('d/m/Y') }}</small>
                                    @else
                                        <small class="text-muted fst-italic">Sin actividad</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ request()->fullUrlWithQuery(['medico_id' => $m->id, 'tab' => 'medicos']) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 {{ $medicoId == $m->id ? 'active' : '' }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                    No hay datos para el período seleccionado.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detalle por médico seleccionado -->
        @if($medicoId && $detalleMedico)
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-bold py-3">
                <i class="bi bi-list-ul me-2 text-primary"></i> Últimas inspecciones
                @php $medicoSel = $medicos->firstWhere('id', $medicoId); @endphp
                @if($medicoSel)
                    <span class="text-secondary fw-normal"> — {{ $medicoSel->name }}</span>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-mobile-cards">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Folio</th>
                                <th>Productor</th>
                                <th>Predio</th>
                                <th>Tipo Prueba</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($detalleMedico as $ins)
                            <tr>
                                <td data-label="Fecha">{{ $ins->fecha?->format('d/m/Y') }}</td>
                                <td data-label="Folio">
                                    @if(empty($ins->folio) || $ins->folio === $ins->clave_interna)
                                        <span class="text-muted fst-italic">{{ $ins->clave_interna ?: '—' }}</span>
                                    @else
                                        {{ $ins->folio }}
                                    @endif
                                </td>
                                <td data-label="Productor">{{ $ins->predio?->productor?->nombre_completo ?? '—' }}</td>
                                <td data-label="Predio">{{ $ins->predio?->nombre_rancho ?? '—' }}</td>
                                <td data-label="Prueba">{{ $ins->tipo_prueba }}</td>
                                <td data-label="Estado">
                                    <span class="badge {{ $ins->estado === 'sincronizado' ? 'bg-success' : 'bg-warning text-dark' }}">
                                        {{ ucfirst($ins->estado) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- ========== TAB: ACTIVIDADES ========== -->
    <div class="tab-pane fade {{ $tab === 'actividades' ? 'show active' : '' }}" id="panel-actividades" role="tabpanel">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pie-chart-fill me-2 text-primary"></i> Distribución por Tipo de Prueba</span>
            </div>
            <div class="card-body">
                <div style="height: 350px;">
                    <canvas id="chartActividades"></canvas>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Tipo de Prueba</th>
                                <th class="text-center">Total</th>
                                <th class="pe-4">Porcentaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalAct = $actividades->sum('total'); @endphp
                            @forelse($actividades as $act)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $nombresPruebas[$act->tipo] ?? $act->tipo }}</td>
                                <td class="text-center"><span class="badge bg-primary rounded-pill">{{ $act->total }}</span></td>
                                <td class="pe-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px; max-width: 200px;">
                                            <div class="progress-bar bg-primary" style="width: {{ $totalAct > 0 ? round($act->total / $totalAct * 100) : 0 }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $totalAct > 0 ? round($act->total / $totalAct * 100) : 0 }}%</small>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Sin datos</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== TAB: ZONA ========== -->
    <div class="tab-pane fade {{ $tab === 'zona' ? 'show active' : '' }}" id="panel-zona" role="tabpanel">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-map me-2 text-primary"></i> Distribución por Zona</span>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <div style="height: 300px; width: 100%; max-width: 400px;">
                            <canvas id="chartZona"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Zona</th>
                                    <th class="text-center">Inspecciones</th>
                                    <th class="pe-4">Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalZonas = $zonas->sum('total'); @endphp
                                @forelse($zonas as $z)
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge {{ $z->zona === 'A' ? 'bg-info' : 'bg-warning text-dark' }} fs-6 px-3 py-2">
                                            Zona {{ $z->zona ?: 'Sin zona' }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold fs-5">{{ $z->total }}</td>
                                    <td class="pe-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 10px; max-width: 200px;">
                                                <div class="progress-bar {{ $z->zona === 'A' ? 'bg-info' : 'bg-warning' }}" 
                                                     style="width: {{ $totalZonas > 0 ? round($z->total / $totalZonas * 100) : 0 }}%">
                                                </div>
                                            </div>
                                            <small class="text-muted">{{ $totalZonas > 0 ? round($z->total / $totalZonas * 100) : 0 }}%</small>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Sin datos</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== TAB: CUARENTENA ========== -->
    <div class="tab-pane fade {{ $tab === 'cuarentena' ? 'show active' : '' }}" id="panel-cuarentena" role="tabpanel">
        @php
            $totalCuarentenas = $cuarentenasD->sum('total') + $cuarentenasP->sum('total') + $totalSinCuarentena;
        @endphp
        <div class="row g-4">
            <!-- Columna: Definitivas -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-bold py-3 d-flex align-items-center">
                        <span class="badge bg-danger fs-6 me-2">D</span>
                        <span>Definitivas</span>
                        <span class="ms-auto badge bg-secondary rounded-pill">{{ $cuarentenasD->sum('total') }}</span>
                    </div>
                    <div class="card-body p-0">
                        @forelse($cuarentenasD as $grupo)
                        <div class="border-bottom p-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-danger fs-6 px-3 py-1">{{ $grupo->tipo }}</span>
                                <span class="fw-bold">{{ $grupo->total }} inspecciones</span>
                                @php $zonaLetra = substr($grupo->tipo, 0, 1); @endphp
                                <span class="badge {{ $zonaLetra === 'A' ? 'bg-info' : 'bg-warning text-dark' }} ms-auto">Zona {{ $zonaLetra }}</span>
                            </div>
                            <div class="ps-2">
                                @foreach($grupo->detalle as $det)
                                <div class="d-flex align-items-center gap-2 py-1 small">
                                    <span class="text-muted" style="min-width: 100px; font-family: monospace;">{{ $det->clave_cuarentena }}</span>
                                    <div class="progress flex-grow-1" style="height: 6px; max-width: 150px;">
                                        <div class="progress-bar bg-danger" style="width: {{ $grupo->total > 0 ? round($det->total / $grupo->total * 100) : 0 }}%"></div>
                                    </div>
                                    <span class="fw-semibold">{{ $det->total }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-shield-exclamation display-5 d-block mb-2"></i>
                            <p class="mb-0 small">Sin cuarentenas definitivas</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
            <!-- Columna: Precautorias -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-bold py-3 d-flex align-items-center">
                        <span class="badge bg-warning text-dark fs-6 me-2">P</span>
                        <span>Precautorias</span>
                        <span class="ms-auto badge bg-secondary rounded-pill">{{ $cuarentenasP->sum('total') }}</span>
                    </div>
                    <div class="card-body p-0">
                        @forelse($cuarentenasP as $grupo)
                        <div class="border-bottom p-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-warning text-dark fs-6 px-3 py-1">{{ $grupo->tipo }}</span>
                                <span class="fw-bold">{{ $grupo->total }} inspecciones</span>
                                @php $zonaLetra = substr($grupo->tipo, 0, 1); @endphp
                                <span class="badge {{ $zonaLetra === 'A' ? 'bg-info' : 'bg-warning text-dark' }} ms-auto">Zona {{ $zonaLetra }}</span>
                            </div>
                            <div class="ps-2">
                                @foreach($grupo->detalle as $det)
                                <div class="d-flex align-items-center gap-2 py-1 small">
                                    <span class="text-muted" style="min-width: 100px; font-family: monospace;">{{ $det->clave_cuarentena }}</span>
                                    <div class="progress flex-grow-1" style="height: 6px; max-width: 150px;">
                                        <div class="progress-bar bg-warning" style="width: {{ $grupo->total > 0 ? round($det->total / $grupo->total * 100) : 0 }}%"></div>
                                    </div>
                                    <span class="fw-semibold">{{ $det->total }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-shield-exclamation display-5 d-block mb-2"></i>
                            <p class="mb-0 small">Sin cuarentenas precautorias</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        @if($totalSinCuarentena > 0)
        <div class="alert alert-secondary border-0 shadow-sm rounded-4 mt-4 mb-0 d-flex align-items-center gap-3">
            <i class="bi bi-info-circle"></i>
            <span><strong>{{ $totalSinCuarentena }}</strong> inspecciones sin clave de cuarentena asignada.</span>
        </div>
        @endif
    </div>

    <!-- ========== TAB: MES ========== -->
    <div class="tab-pane fade {{ $tab === 'mes' ? 'show active' : '' }}" id="panel-mes" role="tabpanel">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up me-2 text-primary"></i> Tendencia Mensual</span>
            </div>
            <div class="card-body">
                <div style="height: 350px;">
                    <canvas id="chartMes"></canvas>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Mes</th>
                                <th class="text-center">Inspecciones</th>
                                <th class="pe-4">Variación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $prevTotal = null; @endphp
                            @forelse($meses as $mes)
                            @php
                                $var = null;
                                if (!is_null($prevTotal) && $prevTotal > 0) {
                                    $var = round(($mes->total - $prevTotal) / $prevTotal * 100, 1);
                                }
                                $prevTotal = $mes->total;
                            @endphp
                            <tr>
                                <td class="ps-4 fw-semibold">
                                    @php
                                        $parts = explode('-', $mes->mes);
                                        $mesNombre = \Carbon\Carbon::createFromFormat('Y-m', $mes->mes)->locale('es')->translatedFormat('F Y');
                                    @endphp
                                    {{ ucfirst($mesNombre) }}
                                </td>
                                <td class="text-center"><span class="badge bg-primary rounded-pill fs-6">{{ $mes->total }}</span></td>
                                <td class="pe-4">
                                    @if(!is_null($var))
                                        <span class="badge {{ $var >= 0 ? 'bg-success' : 'bg-danger' }} rounded-pill">
                                            <i class="bi {{ $var >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }} me-1"></i>
                                            {{ $var >= 0 ? '+' : '' }}{{ $var }}%
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Sin datos</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Tab switching via URL
function switchTab(tab) {
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.location.href = url.toString();
}

// Color palette
const colors = ['#2563eb', '#60a5fa', '#93c5fd', '#3b82f6', '#1d4ed8', '#7c3aed', '#a78bfa', '#6366f1', '#4f46e5', '#0ea5e9'];

// ===== CHART: Médicos =====
const medicosData = @json($medicosRendimiento->map(fn($m) => ['name' => $m->name, 'total' => $m->total_inspecciones]));
new Chart(document.getElementById('chartMedicos'), {
    type: 'bar',
    data: {
        labels: medicosData.map(m => m.name),
        datasets: [{
            label: 'Inspecciones',
            data: medicosData.map(m => m.total),
            backgroundColor: colors.slice(0, medicosData.length),
            borderRadius: 8,
            barThickness: 35
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return 'Inspecciones: ' + ctx.raw;
                    },
                    afterLabel: function(ctx) {
                        const m = medicosData[ctx.dataIndex];
                        return 'Médico: ' + m.name;
                    }
                }
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { drawBorder: false, color: '#f1f5f9' }, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        }
    }
});

// ===== CHART: Actividades =====
const actData = @json($actividades);
const nombresPruebas = @json($nombresPruebas);
const totalActChart = actData.reduce((s, a) => s + a.total, 0);
new Chart(document.getElementById('chartActividades'), {
    type: 'bar',
    data: {
        labels: actData.map(a => nombresPruebas[a.tipo] || a.tipo),
        datasets: [{
            label: 'Inspecciones',
            data: actData.map(a => a.total),
            backgroundColor: ['#2563eb', '#60a5fa', '#93c5fd', '#3b82f6', '#1d4ed8'],
            borderRadius: 8,
            barThickness: 45
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return 'Inspecciones: ' + ctx.raw;
                    },
                    afterLabel: function(ctx) {
                        const pct = totalActChart > 0 ? (ctx.raw / totalActChart * 100).toFixed(1) : 0;
                        return '% del total: ' + pct + '%';
                    }
                }
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { drawBorder: false, color: '#f1f5f9' }, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        }
    }
});

// ===== CHART: Zona =====
const zonaData = @json($zonas);
const zonaLabels = zonaData.map(z => 'Zona ' + (z.zona || 'Sin zona'));
const zonaColors = zonaData.map(z => z.zona === 'A' ? '#0ea5e9' : (z.zona === 'B' ? '#f59e0b' : '#94a3b8'));
new Chart(document.getElementById('chartZona'), {
    type: 'doughnut',
    data: {
        labels: zonaLabels,
        datasets: [{
            data: zonaData.map(z => z.total),
            backgroundColor: zonaColors,
            borderWidth: 0,
            cutout: '70%'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { size: 14 } } },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return 'Inspecciones: ' + ctx.raw;
                    },
                    afterLabel: function(ctx) {
                        const total = ctx.dataset.data.reduce((s, v) => s + v, 0);
                        const pct = total > 0 ? (ctx.raw / total * 100).toFixed(1) : 0;
                        return '% del total: ' + pct + '%';
                    }
                }
            }
        }
    }
});

// ===== CHART: Mes =====
const mesData = @json($meses);
const monthNames = {
    '01': 'Enero', '02': 'Febrero', '03': 'Marzo', '04': 'Abril',
    '05': 'Mayo', '06': 'Junio', '07': 'Julio', '08': 'Agosto',
    '09': 'Septiembre', '10': 'Octubre', '11': 'Noviembre', '12': 'Diciembre'
};
const mesLabels = mesData.map(m => {
    const parts = m.mes.split('-');
    return monthNames[parts[1]] + ' ' + parts[0];
});
new Chart(document.getElementById('chartMes'), {
    type: 'line',
    data: {
        labels: mesLabels,
        datasets: [{
            label: 'Inspecciones',
            data: mesData.map(m => m.total),
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            fill: true,
            tension: 0.3,
            pointBackgroundColor: '#2563eb',
            pointRadius: 5,
            pointHoverRadius: 8,
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return 'Inspecciones: ' + ctx.raw;
                    },
                    afterLabel: function(ctx) {
                        const prev = ctx.dataIndex > 0 ? mesData[ctx.dataIndex - 1].total : null;
                        if (prev && prev > 0) {
                            const varPct = ((ctx.raw - prev) / prev * 100).toFixed(1);
                            return 'Variación vs mes ant.: ' + (varPct >= 0 ? '+' : '') + varPct + '%';
                        }
                        return '';
                    }
                }
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { drawBorder: false, color: '#f1f5f9' }, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        }
    }
});
</script>
@endsection
