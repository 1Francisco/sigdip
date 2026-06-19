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
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Animales ({{ $predio->animales->count() }})</h5>
                <a href="{{ route('inspecciones.create', ['predio_id' => $predio->id]) }}" class="btn btn-sm btn-success">
                    <i class="bi bi-file-text"></i> Nuevo Dictamen
                </a>
            </div>
            <div class="card-body p-0">
                @if ($predio->animales->count() > 0)
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
                            @foreach ($predio->animales as $animal)
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
