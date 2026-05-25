@extends('layouts.app')

@section('title', 'Listado de Inspecciones')
@section('header_title', 'Inspecciones Pecuarias')
@section('header_subtitle', 'Historial de registros y seguimiento')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">Dictámenes Registrados</h5>
        <div class="d-flex gap-2">
            @role('Administrador')
            <a href="{{ route('reportes.excel') }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel"></i> Descargar Sábana
            </a>
            @endrole
            <a href="{{ route('inspecciones.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nuevo Dictamen
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-mobile-cards">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Folio</th>
                        <th>Fecha</th>
                        <th>Predio / Localidad</th>
                        <th>Veterinario</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inspecciones as $inspeccion)
                    <tr>
                        <td class="ps-4 fw-bold text-primary" data-label="Folio">
                            @if(empty($inspeccion->folio) || \Illuminate\Support\Str::startsWith($inspeccion->folio, 'TB-'))
                                <span class="text-muted fst-italic">Sin Folio (Borrador)</span>
                            @else
                                {{ $inspeccion->folio }}
                            @endif
                        </td>
                        <td data-label="Fecha">{{ \Carbon\Carbon::parse($inspeccion->fecha)->format('d/m/Y') }}</td>
                        <td data-label="Predio">
                            <div class="fw-semibold">{{ $inspeccion->predio->nombre_rancho }}</div>
                            <small class="text-secondary">{{ $inspeccion->predio->productor->nombre }} {{ $inspeccion->predio->productor->apellido_paterno }}</small>
                        </td>
                        <td data-label="Veterinario">{{ $inspeccion->veterinario->name }}</td>
                        <td data-label="Estado">
                            @if($inspeccion->estado === 'borrador')
                                <span class="badge bg-warning text-dark"><i class="bi bi-pencil-square me-1"></i> Borrador</span>
                            @else
                                <span class="badge bg-success"><i class="bi bi-check-all me-1"></i> Finalizado</span>
                            @endif
                        </td>
                        <td data-label="Acciones">
                            <div class="d-flex gap-2">
                                @if($inspeccion->estado === 'borrador' || auth()->user()->hasRole('Administrador'))
                                <a href="{{ route('inspecciones.edit', $inspeccion->id) }}" class="btn btn-sm btn-primary" title="Editar / Finalizar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                <a href="{{ route('reportes.pdf', $inspeccion->id) }}" class="btn btn-sm btn-outline-danger" title="Ver PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <a href="{{ route('inspecciones.show', $inspeccion->id) }}" class="btn btn-sm btn-outline-primary" title="Detalles">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white py-3">
        {{ $inspecciones->links() }}
    </div>
</div>
@endsection
