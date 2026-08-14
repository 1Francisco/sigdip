@extends('layouts.app')

@section('title', 'Sábana General de Dictámenes Pecuarios')

@section('content')
<div class="container-fluid px-0 px-md-3">
    <div class="mb-4">
        <h1 class="h3 fw-bold text-dark mb-1">
            <i class="bi bi-file-earmark-excel-fill text-success me-2"></i>Sábana General de Dictámenes Pecuarios
        </h1>
        <p class="text-secondary small mb-0">Filtra, consulta y exporta la información consolidada de dictámenes pecuarios a Excel.</p>
    </div>

    <!-- Tarjeta de Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex align-items-center justify-content-between">
            <span class="fw-semibold text-secondary">
                <i class="bi bi-funnel-fill text-primary me-2"></i>Filtros de Búsqueda y Generación
            </span>
            @if(request()->filled('zona') || request()->filled('tipo_actividad') || request()->filled('medico_id') || request()->filled('tipo_prueba'))
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-semibold">Filtros Activos</span>
            @endif
        </div>
        <div class="card-body bg-light-subtle py-3">
            <form method="GET" action="{{ route('reportes.sabana') }}" class="row g-3 align-items-end">
                <!-- Filtro: Zona -->
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        <i class="bi bi-geo-alt me-1"></i>Zona
                    </label>
                    <select name="zona" class="form-select form-select-sm">
                        <option value="">Todas las Zonas</option>
                        @foreach($zonas as $z)
                            <option value="{{ $z }}" {{ $zona == $z ? 'selected' : '' }}>Zona {{ $z }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro: Tipo de Actividad / Prueba -->
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        <i class="bi bi-activity me-1"></i>Tipo de Actividad / Prueba
                    </label>
                    <select name="tipo_actividad" class="form-select form-select-sm">
                        <option value="">Todas las Actividades</option>
                        @foreach($tiposActividad as $tipo)
                            <option value="{{ $tipo }}" {{ $tipoActividad == $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>

                @hasanyrole('Administrador')
                <!-- Filtro: Médico -->
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        <i class="bi bi-person-badge me-1"></i>Médico Veterinario
                    </label>
                    <select name="medico_id" class="form-select form-select-sm">
                        <option value="">Todos los Médicos</option>
                        @foreach($medicos as $medico)
                            <option value="{{ $medico->id }}" {{ $medicoId == $medico->id ? 'selected' : '' }}>
                                {{ $medico->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endhasanyrole

                <!-- Botones -->
                <div class="col-12 d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                    @if(request()->filled('zona') || request()->filled('tipo_actividad') || request()->filled('medico_id') || request()->filled('tipo_prueba'))
                        <a href="{{ route('reportes.sabana') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                            <i class="bi bi-x-lg me-1"></i> Limpiar Filtros
                        </a>
                    @endif
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="bi bi-search me-1"></i> Filtrar Sábana
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tarjetas de Resumen KPI -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 bg-primary-subtle text-primary p-3 me-3">
                        <i class="bi bi-journal-text fs-3"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase tracking-wider">Dictámenes</div>
                        <div class="h3 fw-bold mb-0 text-dark">{{ number_format($totalInspecciones) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 bg-info-subtle text-info p-3 me-3">
                        <i class="bi bi-border-all fs-3"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase tracking-wider">Animales Probados</div>
                        <div class="h3 fw-bold mb-0 text-dark">{{ number_format($totalProbados) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 bg-success-subtle text-success p-3 me-3">
                        <i class="bi bi-check-circle-fill fs-3"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase tracking-wider">Negativos</div>
                        <div class="h3 fw-bold mb-0 text-success">{{ number_format($totalNegativos) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 bg-danger-subtle text-danger p-3 me-3">
                        <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase tracking-wider">Reactores</div>
                        <div class="h3 fw-bold mb-0 text-danger">{{ number_format($totalReactores) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Vista Previa -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom border-light d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
            <span class="fw-bold text-dark">
                <i class="bi bi-table text-primary me-2"></i>Vista Previa de la Sábana
            </span>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-secondary border rounded-pill px-3 py-1">
                    Mostrando {{ $inspecciones->firstItem() ?? 0 }} - {{ $inspecciones->lastItem() ?? 0 }} de {{ $inspecciones->total() }} registros
                </span>
                <a href="{{ route('reportes.excel.download', request()->all()) }}" class="btn btn-success rounded-pill px-4 shadow-sm fw-semibold">
                    <i class="bi bi-download me-1"></i> Descargar Excel (.xlsx)
                </a>
                <a href="{{ route('reportes.sabana.pdf', request()->all()) }}" class="btn btn-danger rounded-pill px-4 shadow-sm fw-semibold">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Descargar PDFs (.pdf)
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            @if($inspecciones->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                    <h5 class="fw-semibold text-secondary">No se encontraron dictámenes con los filtros seleccionados</h5>
                    <p class="text-muted small">Intenta ajustando los criterios del filtro arriba.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase text-secondary">
                            <tr>
                                <th class="ps-3">Clave / Folio</th>
                                <th>Predio</th>
                                <th>UPP</th>
                                <th>Beneficiario</th>
                                <th>Localidad / Mpio.</th>
                                <th>Prueba / Actividad</th>
                                <th>Fecha</th>
                                <th class="text-center">Probados</th>
                                <th class="text-center">Neg.</th>
                                <th class="text-center">React.</th>
                                <th class="pe-3">Médico</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @foreach($inspecciones as $inspeccion)
                                @php
                                    $negativos = $inspeccion->detalles->where('resultado_prueba', 'Negativo')->count();
                                    $reactores = $inspeccion->detalles->whereIn('resultado_prueba', ['Positivo', 'Sospechoso'])->count();
                                    $probados = $inspeccion->detalles->count();
                                    $productor = $inspeccion->predio?->productor;
                                @endphp
                                <tr style="cursor: pointer;" onclick="window.location.href='{{ route('inspecciones.show', $inspeccion->id) }}'">
                                    <td class="ps-3 fw-bold text-primary">
                                        {{ $inspeccion->clave_interna ?: ($inspeccion->folio ?: 'Sin Folio') }}
                                    </td>
                                    <td class="fw-semibold">{{ $inspeccion->predio?->nombre_rancho ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $inspeccion->predio?->clave_unidad_produccion ?? 'N/A' }}</span>
                                    </td>
                                    <td>{{ $productor ? $productor->nombre_completo : 'N/A' }}</td>
                                    <td>
                                        <div>{{ $inspeccion->predio?->localidad ?? '-' }}</div>
                                        <small class="text-muted">{{ $inspeccion->predio?->municipio ?? '' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                                            {{ $inspeccion->motivo_prueba ?? $inspeccion->tipo_prueba ?? $inspeccion->tipo_inspeccion ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>{{ $inspeccion->fecha ? $inspeccion->fecha->format('d/m/Y') : 'N/A' }}</td>
                                    <td class="text-center fw-bold">{{ number_format($probados) }}</td>
                                    <td class="text-center text-success fw-bold">{{ number_format($negativos) }}</td>
                                    <td class="text-center">
                                        @if($reactores > 0)
                                            <span class="badge bg-danger rounded-pill fw-bold">{{ $reactores }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="pe-3">
                                        <small class="text-secondary fw-semibold">{{ $inspeccion->veterinario?->name ?? 'N/A' }}</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @if($inspecciones->hasPages())
            <div class="card-footer bg-white border-top border-light py-3">
                {{ $inspecciones->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
