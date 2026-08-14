@extends('layouts.app')

@section('title', 'Aretes del Censo')
@section('header_title', 'Aretes del Censo')
@section('header_subtitle', 'Registro de aretes del censo (importación SENASICA)')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">Listado de Aretes</h5>
        <a href="{{ route('aretes-censo.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo Arete
        </a>
    </div>
    <div class="card-body bg-light border-bottom py-2 px-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4 col-sm-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 rounded-end-pill" placeholder="Buscar por arete, raza, productor..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary rounded-pill px-3" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                @if(request('search'))
                    <a href="{{ route('aretes-censo.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-x-lg"></i> Limpiar</a>
                @endif
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Número de Arete</th>
                        <th>Productor</th>
                        <th>Predio</th>
                        <th>Raza</th>
                        <th>Sexo</th>
                        <th>Edad (meses)</th>
                        <th>Sacrificio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($aretes as $arete)
                    <tr>
                        <td class="ps-4" data-label="Arete">
                            <code class="fw-bold">{{ $arete->numero_arete }}</code>
                        </td>
                        <td data-label="Productor">{{ $arete->productor?->nombre }} {{ $arete->productor?->apellido_paterno }}</td>
                        <td data-label="Predio">{{ $arete->predio?->nombre_rancho ?? 'N/A' }}</td>
                        <td data-label="Raza">{{ $arete->raza ?? 'N/A' }}</td>
                        <td data-label="Sexo">{{ $arete->sexo ?? 'N/A' }}</td>
                        <td data-label="Edad">{{ $arete->edad_meses ?? 'N/A' }}</td>
                        <td data-label="Sacrificio">
                            @if($arete->sacrificio)
                                <span class="badge bg-danger">Sí</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td data-label="Acciones">
                            <div class="d-flex gap-2">
                                <a href="{{ route('aretes-censo.show', $arete->id) }}" class="btn btn-sm btn-outline-info" title="Ver Detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('aretes-censo.edit', $arete->id) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('aretes-censo.destroy', $arete->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este arete?')">
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
        {{ $aretes->links() }}
    </div>
</div>
@endsection
