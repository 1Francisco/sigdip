@extends('layouts.app')

@section('title', 'Planificación de Visitas')
@section('header_title', 'Agenda de Campo')
@section('header_subtitle', 'Programe y gestione las visitas a los predios')

@section('content')
<style>
    .productor-link {
        color: inherit;
        text-decoration: none;
        display: block;
        transition: all 0.2s ease;
    }
    .productor-link:hover .productor-name {
        color: #0d6efd !important;
        text-decoration: underline;
    }
</style>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">Visitas Programadas</h5>
        <a href="{{ route('visitas.create') }}" class="btn btn-primary">
            <i class="bi bi-calendar-plus"></i> Programar Visita
        </a>
    </div>
    <div class="card-body bg-light border-bottom py-3 px-4">
        <form action="{{ route('visitas.index') }}" method="GET" class="row g-3 justify-content-end align-items-end">
            <div class="col-md-4 col-sm-6">
                <label class="form-label fw-semibold small text-muted mb-1"><i class="bi bi-funnel"></i> Filtrar por Fecha</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="fecha" class="form-control rounded-start-pill" value="{{ request('fecha') }}">
                    @if(request('fecha'))
                        <a href="{{ route('visitas.index') }}" class="btn btn-outline-secondary" title="Limpiar Filtro">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <button type="submit" class="btn btn-sm btn-primary w-100 rounded-pill px-3">
                    <i class="bi bi-search me-1"></i> Buscar
                </button>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-mobile-cards">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Fecha</th>
                        <th>Productor</th>
                        <th>Médico Veterinario</th>
                        <th class="text-center">Inyección</th>
                        <th class="text-center">Lectura</th>
                        <th>Estado Visita</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($visitas as $v)
                    <tr>
                        <td class="ps-4" data-label="Fecha">
                            <div class="fw-bold text-dark">{{ $v->fecha_programada->format('d/m/Y') }}</div>
                            <small class="text-secondary">{{ $v->fecha_programada->locale('es')->diffForHumans() }}</small>
                        </td>
                        <td data-label="Productor">
                            @php
                                $targetUrl = '#';
                                if ($v->inspeccion) {
                                    $targetUrl = $v->inspeccion->estado != 'borrador' 
                                        ? route('inspecciones.show', $v->inspeccion->id) 
                                        : route('inspecciones.edit', $v->inspeccion->id);
                                } else {
                                    $targetUrl = route('inspecciones.create', ['visita_id' => $v->id]);
                                }
                            @endphp
                            <a href="{{ $targetUrl }}" class="productor-link">
                                <div class="fw-semibold text-dark productor-name">{{ $v->predio->productor->nombre ?? 'Sin Productor' }}</div>
                                <small class="text-secondary d-block">{{ $v->predio->nombre_rancho }} ({{ $v->predio->localidad }})</small>
                            </a>
                        </td>
                        <td data-label="Médico">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center d-none d-lg-flex" style="width: 28px; height: 28px;">
                                    <i class="bi bi-person"></i>
                                </div>
                                <span>{{ $v->veterinario->name }}</span>
                            </div>
                        </td>
                        <td class="text-center" data-label="Inyección">
                            @if($v->inyeccion)
                                <span class="badge bg-success rounded-pill px-3 py-1.5 fw-semibold">
                                    <i class="bi bi-check-circle-fill me-1"></i> Realizada
                                </span>
                                @if($v->inspeccion && $v->inspeccion->fecha_inyeccion)
                                <div class="small text-secondary mt-1" style="font-size: 0.75rem;">
                                    {{ $v->inspeccion->fecha_inyeccion->format('d/m/Y') }}
                                    @if($v->inspeccion->hora_inyeccion)
                                        {{ \Carbon\Carbon::parse($v->inspeccion->hora_inyeccion)->format('g:i A') }}
                                    @endif
                                </div>
                                @endif
                            @else
                                <span class="badge bg-danger rounded-pill px-3 py-1.5 fw-semibold">
                                    <i class="bi bi-x-circle-fill me-1"></i> Pendiente
                                </span>
                            @endif
                        </td>
                        <td class="text-center" data-label="Lectura">
                            @if($v->inspeccion && $v->inspeccion->estado != 'borrador')
                                <span class="badge bg-success rounded-pill px-3 py-1.5 fw-semibold">
                                    <i class="bi bi-check-circle-fill me-1"></i> Realizada
                                </span>
                                <div class="small text-secondary mt-1" style="font-size: 0.75rem;">
                                    {{ $v->inspeccion->fecha_lectura ? $v->inspeccion->fecha_lectura->format('d/m/Y') : '' }}
                                    @if($v->inspeccion->hora_lectura)
                                        {{ \Carbon\Carbon::parse($v->inspeccion->hora_lectura)->format('g:i A') }}
                                    @endif
                                </div>
                            @else
                                <span class="badge bg-danger rounded-pill px-3 py-1.5 fw-semibold">
                                    <i class="bi bi-x-circle-fill me-1"></i> Pendiente
                                </span>
                                @if($v->inyeccion && $v->inspeccion && $v->inspeccion->fecha_inyeccion)
                                    <div class="small text-muted mt-1" style="font-size: 0.75rem; font-weight: 500;">
                                        Estimada: {{ $v->inspeccion->fecha_inyeccion->copy()->addDays(3)->format('d/m/Y') }}
                                    </div>
                                @elseif($v->fecha_programada)
                                    <div class="small text-muted mt-1" style="font-size: 0.75rem; font-weight: 500;">
                                        Estimada: {{ $v->fecha_programada->copy()->addDays(3)->format('d/m/Y') }}
                                    </div>
                                @endif
                            @endif
                        </td>
                        <td data-label="Estado Visita">
                            @php
                                $badgeClass = match($v->estado) {
                                    'pendiente' => 'bg-warning text-dark',
                                    'completada' => 'bg-success',
                                    'cancelada' => 'bg-danger',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }} rounded-pill px-3 text-capitalize fw-semibold">
                                {{ $v->estado }}
                            </span>
                        </td>
                        <td data-label="Acciones">
                            <div class="d-flex flex-wrap gap-2 justify-content-center">
                                @if($v->inspeccion)
                                    @if($v->inspeccion->estado != 'borrador')
                                        <a href="{{ route('inspecciones.show', $v->inspeccion->id) }}" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-eye"></i> <span class="d-none d-lg-inline">Ver Dictamen</span>
                                        </a>
                                    @else
                                        <a href="{{ route('inspecciones.edit', $v->inspeccion->id) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil-square"></i> <span class="d-none d-lg-inline">Continuar Dictamen</span>
                                        </a>
                                    @endif
                                @else
                                    @if($v->estado == 'pendiente')
                                        <a href="{{ route('inspecciones.create', ['visita_id' => $v->id]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-file-earmark-plus"></i> <span class="d-none d-lg-inline">Dictamen</span>
                                        </a>
                                    @endif
                                @endif
                                
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" data-bs-toggle="dropdown">
                                        Acciones
                                    </button>
                                    <ul class="dropdown-menu border-0 shadow-sm">
                                        <li>
                                            <button type="button" class="dropdown-item text-primary" onclick="openReprogramarModal('{{ route('visitas.reprogramar', $v->id) }}', '{{ $v->fecha_programada->format('Y-m-d') }}')">
                                                <i class="bi bi-calendar-week me-1"></i> Reprogramar
                                            </button>
                                        </li>
                                        @if($v->estado != 'cancelada')
                                            <li>
                                                <form action="{{ route('visitas.updateEstado', $v->id) }}" method="POST" onsubmit="return confirm('¿Está seguro de que desea cancelar esta visita?');">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="estado" value="cancelada">
                                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-x-circle me-1"></i> Cancelar</button>
                                                </form>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($visitas->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $visitas->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal para Reprogramar Visita -->
<div class="modal fade" id="reprogramarModal" tabindex="-1" aria-labelledby="reprogramarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="reprogramarModalLabel"><i class="bi bi-calendar-event me-2"></i>Reprogramar Visita</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reprogramarForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nueva Fecha Programada</label>
                        <input type="date" name="fecha_programada" id="nueva_fecha" class="form-control" required>
                        <div class="form-text text-muted mt-1">Seleccione la nueva fecha para realizar la visita.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Reprogramar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openReprogramarModal(url, fechaActual) {
        const form = document.getElementById('reprogramarForm');
        form.action = url;
        const fechaInput = document.getElementById('nueva_fecha');
        fechaInput.value = fechaActual;
        
        const modal = new bootstrap.Modal(document.getElementById('reprogramarModal'));
        modal.show();
    }
</script>
@endsection
