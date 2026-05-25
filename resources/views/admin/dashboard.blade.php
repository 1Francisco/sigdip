@extends('layouts.app')

@section('title', 'Dashboard Administrativo')
@section('header_title', 'Resumen Administrativo')
@section('header_subtitle', 'Estado actual de las inspecciones pecuarias')

@section('content')
@if($isAdmin)
<!-- ============================================== -->
<!-- VISTA DEL ADMINISTRADOR (ESTADÍSTICAS GLOBALES) -->
<!-- ============================================== -->
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card p-4 bg-primary text-white border-0 overflow-hidden position-relative">
            <div class="position-absolute end-0 bottom-0 opacity-10" style="font-size: 5rem; transform: translate(20%, 20%)">
                <i class="bi bi-clipboard-data"></i>
            </div>
            <h6 class="text-white-50 small text-uppercase fw-bold ls-wide">Total Inspecciones</h6>
            <h2 class="display-5 fw-bold mb-0">{{ $totalInspecciones }}</h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 bg-white border-0 shadow-sm">
            <h6 class="text-secondary small text-uppercase fw-bold ls-wide">Animales Registrados</h6>
            <h2 class="display-5 fw-bold mb-0">{{ $totalAnimales }}</h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 bg-white border-0 shadow-sm position-relative overflow-hidden">
            <div class="position-absolute end-0 bottom-0 opacity-10 text-primary" style="font-size: 5rem; transform: translate(10%, 10%)">
                <i class="bi bi-calendar-event"></i>
            </div>
            <h6 class="text-secondary small text-uppercase fw-bold ls-wide">Visitas en Agenda</h6>
            <h2 class="display-5 fw-bold mb-0 text-primary">{{ $totalVisitasPendientes }}</h2>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Inspecciones por Localidad</span>
                <i class="bi bi-three-dots text-secondary"></i>
            </div>
            <div class="card-body">
                <div style="height: 350px;">
                    <canvas id="chartLocalidades"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">Rendimiento Veterinarios</div>
            <div class="card-body d-flex flex-column justify-content-center">
                <div style="height: 250px;">
                    <canvas id="chartVeterinarios"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold text-primary d-flex justify-content-between align-items-center py-3">
                <span><i class="bi bi-geo-alt me-2"></i> Próximos Despliegues a Campo</span>
                <a href="{{ route('visitas.index') }}" class="btn btn-sm btn-light rounded-pill">Ver Agenda Completa</a>
            </div>
            <div class="card-body p-0">
                @if($proximasVisitasGlobales->isEmpty())
                <div class="text-center p-5 text-muted">
                    <p class="mb-0">No hay visitas pendientes programadas en todo el estado.</p>
                </div>
                @else
                <div class="list-group list-group-flush">
                    @foreach($proximasVisitasGlobales as $visita)
                    <div class="list-group-item p-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="fw-bold fs-6">{{ $visita->predio->productor->nombre ?? 'Sin Productor' }}</div>
                            <span class="badge bg-light text-dark border"><i class="bi bi-calendar3"></i> {{ \Carbon\Carbon::parse($visita->fecha_programada)->format('d/m/Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-secondary"><i class="bi bi-pin-map"></i> {{ $visita->predio->nombre_rancho }} ({{ $visita->predio->localidad }})</small>
                            <small class="fw-semibold text-primary"><i class="bi bi-person-badge"></i> MVZ. {{ $visita->veterinario->name }}</small>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold text-warning d-flex justify-content-between align-items-center py-3">
                <span><i class="bi bi-journal-x me-2"></i> Dictámenes Incompletos</span>
                <span class="badge bg-warning text-dark rounded-pill">{{ $borradoresGlobales->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($borradoresGlobales->isEmpty())
                <div class="text-center p-5 text-muted">
                    <p class="mb-0">Todos los dictámenes están finalizados.</p>
                </div>
                @else
                <div class="list-group list-group-flush">
                    @foreach($borradoresGlobales as $borrador)
                    <div class="list-group-item p-3">
                        <div class="fw-bold text-dark">{{ $borrador->predio->nombre_rancho }}</div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <small class="text-secondary">
                                @if(empty($borrador->folio) || \Illuminate\Support\Str::startsWith($borrador->folio, 'TB-'))
                                    <span class="text-muted fst-italic">Sin Folio (Borrador)</span>
                                @else
                                    Folio: {{ $borrador->folio }}
                                @endif
                            </small>
                            <small class="text-muted fst-italic">{{ $borrador->veterinario->name }}</small>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@else
<!-- ============================================== -->
<!-- VISTA DEL MÉDICO (ESTACIÓN OPERATIVA)          -->
<!-- ============================================== -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <a href="{{ route('inspecciones.create') }}" class="text-decoration-none">
            <div class="card p-4 border-0 shadow-sm text-center bg-primary text-white h-100 hover-lift">
                <i class="bi bi-file-earmark-plus-fill display-4 mb-3"></i>
                <h3 class="fw-bold mb-1">NUEVO DICTAMEN</h3>
                <p class="text-white-50 mb-0">Crear una inspección de prueba desde cero</p>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('visitas.create') }}" class="text-decoration-none">
            <div class="card p-4 border-0 shadow-sm text-center bg-white h-100 hover-lift">
                <i class="bi bi-calendar-plus text-primary display-4 mb-3"></i>
                <h3 class="fw-bold text-dark mb-1">AGENDAR VISITA</h3>
                <p class="text-secondary mb-0">Programar una visita futura a un predio</p>
            </div>
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold text-primary d-flex justify-content-between align-items-center py-3">
                <span><i class="bi bi-calendar-event me-2"></i> Mis Visitas Pendientes</span>
                <span class="badge bg-primary rounded-pill">{{ $visitasPendientes->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($visitasPendientes->isEmpty())
                <div class="text-center p-5 text-muted">
                    <i class="bi bi-check-circle display-4 mb-3"></i>
                    <p>No tienes visitas pendientes programadas.</p>
                </div>
                @else
                <div class="list-group list-group-flush">
                    @foreach($visitasPendientes as $visita)
                    <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">{{ $visita->predio->productor->nombre ?? 'Sin Productor' }}</div>
                            <small class="text-secondary d-block mb-1">
                                {{ $visita->predio->nombre_rancho }} ({{ $visita->predio->localidad }})
                            </small>
                            <small class="text-muted"><i class="bi bi-calendar3"></i> {{ \Carbon\Carbon::parse($visita->fecha_programada)->format('d/m/Y') }}</small>
                        </div>
                        <a href="{{ route('inspecciones.create', ['visita_id' => $visita->id]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            Iniciar Dictamen
                        </a>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold text-warning d-flex justify-content-between align-items-center py-3">
                <span><i class="bi bi-journal-bookmark me-2"></i> Mis Borradores Pausados</span>
                <span class="badge bg-warning text-dark rounded-pill">{{ $dictamenesBorrador->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($dictamenesBorrador->isEmpty())
                <div class="text-center p-5 text-muted">
                    <i class="bi bi-journal-x display-4 mb-3"></i>
                    <p>No tienes dictámenes en estado de borrador.</p>
                </div>
                @else
                <div class="list-group list-group-flush">
                    @foreach($dictamenesBorrador as $borrador)
                    <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">{{ $borrador->predio->nombre_rancho }}</div>
                            <small class="text-secondary">
                                @if(empty($borrador->folio) || \Illuminate\Support\Str::startsWith($borrador->folio, 'TB-'))
                                    <span class="text-muted fst-italic">Sin Folio (Borrador)</span>
                                @else
                                    Folio: {{ $borrador->folio }}
                                @endif
                                 | Incompleto
                            </small>
                        </div>
                        <a href="{{ route('inspecciones.edit', $borrador->id) }}" class="btn btn-sm btn-warning rounded-pill px-3">
                            <i class="bi bi-pencil-square"></i> Continuar
                        </a>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

<style>
    @keyframes pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }
    .ls-wide { letter-spacing: 0.05em; }
</style>
@endsection

@section('scripts')
<script>
    @if($isAdmin)
    // Chart Localidades
    new Chart(document.getElementById('chartLocalidades'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($inspeccionesPorLocalidad->pluck('localidad')) !!},
            datasets: [{
                label: 'Inspecciones',
                data: {!! json_encode($inspeccionesPorLocalidad->pluck('total')) !!},
                backgroundColor: '#2563eb',
                borderRadius: 8,
                barThickness: 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { drawBorder: false, color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Chart Veterinarios
    new Chart(document.getElementById('chartVeterinarios'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($rendimientoVeterinarios->pluck('name')) !!},
            datasets: [{
                data: {!! json_encode($rendimientoVeterinarios->pluck('total')) !!},
                backgroundColor: ['#2563eb', '#60a5fa', '#93c5fd', '#bfdbfe'],
                borderWidth: 0,
                cutout: '70%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
            }
        }
    });
    @endif
</script>
@endsection
