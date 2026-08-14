@extends('layouts.app')

@section('title', 'Detalle del Predio')
@section('header_title', $predio->nombre_rancho)
@section('header_subtitle', $predio->clave_unidad_produccion)
@section('back_url', route('predios.index'))

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Información del Predio</h5>
            </div>
            <div class="card-body p-4">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted ps-0" style="width: 140px;">Nombre</td>
                        <td class="fw-bold">{{ $predio->nombre_rancho }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">CUP</td>
                        <td><code>{{ $predio->clave_unidad_produccion }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Productor</td>
                        <td>
                            <a href="{{ route('productores.show', $predio->productor_id) }}">{{ $predio->productor->nombre }} {{ $predio->productor->apellido_paterno }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Municipio</td>
                        <td>{{ $predio->municipio ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Localidad</td>
                        <td>{{ $predio->localidad ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Domicilio</td>
                        <td>{{ $predio->domicilio ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Coordenadas</td>
                        <td>
                            @if ($predio->latitud && $predio->longitud)
                                <code>{{ $predio->latitud }}, {{ $predio->longitud }}</code>
                            @else
                                <span class="text-muted">No registradas</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-people text-primary me-2"></i>Otros Productores del mismo Hato</h5>
            </div>
            <div class="card-body p-4">
                @if($otrosPredios->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($otrosPredios as $otroPredio)
                            @if($otroPredio->productor)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                    <div>
                                        <h6 class="mb-1 fw-bold">
                                            <a href="{{ route('productores.show', $otroPredio->productor_id) }}" class="text-decoration-none">
                                                {{ $otroPredio->productor->nombre }} {{ $otroPredio->productor->apellido_paterno }} {{ $otroPredio->productor->apellido_materno }}
                                            </a>
                                        </h6>
                                        <p class="mb-0 small text-muted">
                                            Rancho: <span class="fw-semibold text-dark">{{ $otroPredio->nombre_rancho }}</span>
                                            @if($otroPredio->productor->clave)
                                                · Clave: <code class="small">{{ $otroPredio->productor->clave }}</code>
                                            @endif
                                        </p>
                                    </div>
                                    @if($otroPredio->productor->medico)
                                        <span class="badge bg-light text-secondary rounded-pill px-3 py-1 fw-normal border">
                                            MVZ: {{ $otroPredio->productor->medico->name }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-person-dash fs-2 d-block mb-2"></i>
                        No hay otros productores registrados con esta misma clave de hato.
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Animales ({{ $animales->total() }})</h5>
                <a href="{{ route('inspecciones.create', ['predio_id' => $predio->id]) }}" class="btn btn-sm btn-success">
                    <i class="bi bi-file-text"></i> Nuevo Dictamen
                </a>
            </div>
        <div class="card-body p-0">
            <form method="GET" class="input-group input-group-sm p-3 pb-0">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="animal_search" class="form-control border-start-0" placeholder="Buscar por arete o raza..." value="{{ request('animal_search') }}">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                @if(request('animal_search'))
                    <a href="{{ route('predios.show', $predio->id) }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>
            @if ($animales->total() > 0)
            <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Arete</th>
                                <th>Raza</th>
                                <th>Sexo</th>
                                <th>Edad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($animales as $animal)
                            <tr>
                                <td><code>{{ $animal->numero_arete_siniiga }}</code></td>
                                <td>{{ $animal->raza }}</td>
                                <td>{{ $animal->sexo }}</td>
                                <td>{{ $animal->edad }} meses</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi bi-archive fs-2 d-block mb-2"></i>
                    Sin animales registrados
                </div>
                @endif
            </div>
            @if ($animales->total() > 0)
            <div class="card-footer bg-white py-3">
                {{ $animales->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
<div class="d-flex gap-2">
    <a href="{{ route('predios.edit', $predio->id) }}" class="btn btn-primary">
        <i class="bi bi-pencil"></i> Editar
    </a>
    <form action="{{ route('predios.destroy', $predio->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este predio y todos sus animales?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger">
            <i class="bi bi-trash"></i> Eliminar
        </button>
    </form>
    <a href="{{ route('predios.index') }}" class="btn btn-outline-secondary ms-auto">Volver</a>
</div>
@endsection
