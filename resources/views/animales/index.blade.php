@extends('layouts.app')

@section('title', 'Gestión de Animales')
@section('header_title', 'Animales')
@section('header_subtitle', 'Registro de animales del inventario pecuario')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">Listado de Animales</h5>
        <a href="{{ route('animales.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo Animal
        </a>
    </div>
    <div class="card-body bg-light border-bottom py-2 px-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4 col-sm-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 rounded-end-pill" placeholder="Buscar por arete SINIIGA, raza, predio..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary rounded-pill px-3" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                @if(request('search'))
                    <a href="{{ route('animales.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-x-lg"></i> Limpiar</a>
                @endif
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Arete SINIIGA</th>
                        <th>Predio</th>
                        <th>Raza</th>
                        <th>Sexo</th>
                        <th>Edad (meses)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($animales as $animal)
                    <tr>
                        <td class="ps-4" data-label="Arete">
                            <code class="fw-bold">{{ $animal->numero_arete_siniiga }}</code>
                        </td>
                        <td data-label="Predio">
                            <span class="fw-semibold">{{ $animal->predio?->nombre_rancho ?? 'N/A' }}</span>
                            <small class="d-block text-muted">{{ $animal->predio?->productor?->nombre }}</small>
                        </td>
                        <td data-label="Raza">{{ $animal->raza ?? 'N/A' }}</td>
                        <td data-label="Sexo">{{ $animal->sexo ?? 'N/A' }}</td>
                        <td data-label="Edad">{{ $animal->edad ?? 'N/A' }}</td>
                        <td data-label="Acciones">
                            <div class="d-flex gap-2">
                                <a href="{{ route('animales.show', $animal->id) }}" class="btn btn-sm btn-outline-info" title="Ver Detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('animales.edit', $animal->id) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('animales.destroy', $animal->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este animal?')">
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
        {{ $animales->links() }}
    </div>
</div>
@endsection
