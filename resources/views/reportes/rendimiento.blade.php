@extends('layouts.app')

@section('title', 'Rendimiento de Médicos')
@section('header_title', 'Rendimiento de Médicos')
@section('header_subtitle', 'Reportes de actividad y productividad del personal de campo')

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



<!-- Filtros Globales -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body bg-light border-bottom py-3 px-4">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold text-secondary mb-1">Año</label>
                <select name="year" class="form-select form-select-sm">
                    <option value="">Todos</option>
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
            <div class="col-md-2 col-sm-4">
                <label class="form-label small fw-semibold text-secondary mb-1">Localidad</label>
                <select name="localidad" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    @foreach($localidades as $loc)
                        <option value="{{ $loc }}" @selected($localidad === $loc)>{{ $loc }}</option>
                    @endforeach
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
                @if(request()->anyFilled(['estado', 'zona', 'medico_id', 'year', 'localidad']))
                    <a href="{{ route('reportes.rendimiento') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>



<!-- Pestañas / Tabs -->
<ul class="nav nav-pills nav-pills-premium mb-4" id="reportTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <a href="{{ route('reportes.rendimiento', array_merge(request()->query(), ['tab' => 'medicos'])) }}"
           class="nav-link {{ $tab === 'medicos' ? 'active' : '' }}">
            <i class="bi bi-person-badge"></i> Médicos
        </a>
    </li>

    <li class="nav-item" role="presentation">
        <a href="{{ route('reportes.rendimiento.mensual', request()->query()) }}"
           class="nav-link {{ $tab === 'mensual' ? 'active' : '' }}"
           role="tab">
            <i class="bi bi-table"></i> Detalle Mensual
        </a>
    </li>
</ul>

<div class="tab-content" id="reportTabContent">
    <!-- ========== TAB: MÉDICOS ========== -->
    <div class="tab-pane fade {{ $tab === 'medicos' ? 'show active' : '' }}" id="panel-medicos" role="tabpanel">
        <!-- Gráfica de barras -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold py-3">
                <span><i class="bi bi-bar-chart-fill me-2 text-primary"></i> Lecturas por Médico</span>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto; overflow-y: hidden;">
                    <div style="height: 350px; min-width: {{ max($medicosRendimiento->count() * 60, 300) }}px;">
                        <canvas id="chartMedicos"></canvas>
                    </div>
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
                                <th class="text-center">Lecturas</th>
                                <th class="text-center">Visitas</th>
                                <th class="text-center">Completadas</th>
                                <th class="text-center">Predios</th>
                                <th class="text-center">Animales</th>
                                <th class="text-center">Anim/Insp</th>
                                <th class="text-center">% Reactores</th>
                                <th class="text-center">% Finalización</th>
                                <th class="text-center">Eficiencia</th>
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
                                <td class="text-center">{{ $m->total_animales }}</td>
                                <td class="text-center">{{ $m->promedio_animales }}</td>
                                <td class="text-center">
                                    @if($m->tasa_reactores > 0)
                                        <span class="badge bg-danger rounded-pill">{{ $m->tasa_reactores }}%</span>
                                    @else
                                        <span class="text-muted">0%</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($m->total_visitas > 0)
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <div class="progress" style="height:6px;width:60px">
                                                <div class="progress-bar bg-success" style="width:{{ $m->tasa_finalizacion }}%"></div>
                                            </div>
                                            <small>{{ $m->tasa_finalizacion }}%</small>
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $efClass = $m->eficiencia_score >= 70 ? 'success' : ($m->eficiencia_score >= 40 ? 'warning text-dark' : 'danger');
                                        $efWidth = min($m->eficiencia_score, 100);
                                    @endphp
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <div class="progress" style="height:8px;width:60px">
                                            <div class="progress-bar bg-{{ $efClass }}" style="width:{{ $efWidth }}%"></div>
                                        </div>
                                        <small class="fw-bold text-{{ $efClass }}">{{ $m->eficiencia_score }}</small>
                                    </div>
                                </td>
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
                                <td colspan="12" class="text-center text-muted py-4">
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
                                <i class="bi bi-list-ul me-2 text-primary"></i> Últimas lecturas
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
                            <tr style="cursor: pointer;" onclick="window.location.href='{{ route('inspecciones.show', $ins->id) }}'">
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


</div>
@endsection

@section('scripts')
<script>
// ===== CHART: Médicos =====
const medicosData = @json($medicosRendimiento->map(fn($m) => ['name' => $m->name, 'total' => $m->total_inspecciones, 'eficiencia' => $m->eficiencia_score]));
new Chart(document.getElementById('chartMedicos'), {
    type: 'bar',
    data: {
        labels: medicosData.map(m => m.name),
        datasets: [{
            label: 'Lecturas',
            data: medicosData.map(m => m.total),
            backgroundColor: medicosData.map(m => {
                const ef = m.eficiencia;
                return ef >= 70 ? '#16a34a' : (ef >= 40 ? '#f59e0b' : '#dc2626');
            }),
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
                        return 'Lecturas: ' + ctx.raw;
                    },
                    afterLabel: function(ctx) {
                        const m = medicosData[ctx.dataIndex];
                        return 'Eficiencia: ' + m.eficiencia + '/100';
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
