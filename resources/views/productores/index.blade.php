@extends('layouts.app')

@section('title', 'Gestión de Productores')
@section('header_title', 'Productores')
@section('header_subtitle', 'Administre la información de los dueños de ganado')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">Listado de Productores</h5>
        <a href="{{ route('productores.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Nuevo Productor
        </a>
    </div>
    <div class="card-body bg-light border-bottom py-2 px-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4 col-sm-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 rounded-end-pill" placeholder="Buscar por nombre, CURP, UPP..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary rounded-pill px-3" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                @if(request('search'))
                    <a href="{{ route('productores.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-x-lg"></i> Limpiar</a>
                @endif
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-mobile-cards">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Nombre</th>
                        <th>CURP</th>
                        <th>UPP</th>
                        @if(auth()->user()->hasRole('Administrador'))
                        <th>Médico Asignado</th>
                        @endif
                        <th>Teléfono</th>
                        <th>Predios</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productores as $p)
                    <tr>
                        <td class="ps-4" data-label="Productor">
                            <div class="fw-bold text-dark">{{ $p->nombre }} {{ $p->apellido_paterno }} {{ $p->apellido_materno }}</div>
                            <small class="text-muted">Productor Registrado</small>
                        </td>
                        <td data-label="CURP"><code>{{ $p->curp }}</code></td>
                        <td data-label="UPP">{{ $p->upp }}</td>
                        @if(auth()->user()->hasRole('Administrador'))
                        <td data-label="Médico Asignado">
                            @if($p->medico)
                                <span class="badge bg-info-soft text-info rounded-pill px-2">{{ $p->medico->name }}</span>
                            @else
                                <span class="text-muted small">Sin Asignar</span>
                            @endif
                        </td>
                        @endif
                        <td data-label="Teléfono">{{ $p->telefono ?? 'N/A' }}</td>
                        <td data-label="Predios">
                            <a href="{{ route('predios.index', ['productor_id' => $p->id]) }}" class="text-decoration-none">
                                <span class="badge bg-primary-soft text-primary rounded-pill px-3">
                                    {{ $p->predios_count }} ranchos
                                </span>
                            </a>
                        </td>
                        <td data-label="Acciones">
                            <div class="d-flex gap-2">
                                <a href="{{ route('predios.create', ['productor_id' => $p->id]) }}" class="btn btn-sm btn-outline-primary" title="Añadir Rancho">
                                    <i class="bi bi-house-add"></i>
                                </a>
                                <a href="{{ route('productores.show', $p->id) }}" class="btn btn-sm btn-outline-info" title="Ver Detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('productores.edit', $p->id) }}" class="btn btn-sm btn-outline-secondary" title="Editar Productor">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('productores.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este productor y todos sus predios?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white py-3">
        {{ $productores->links() }}
    </div>
</div>
@endsection
