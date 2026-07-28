@extends('layouts.app')

@section('title', 'Detalle del Productor')
@section('header_title', 'Detalle del Productor')
@section('header_subtitle', $productor->nombre . ' ' . $productor->apellido_paterno)
@section('back_url', route('productores.index'))

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Información General</h5>
            </div>
            <div class="card-body p-4">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted ps-0" style="width: 140px;">Nombre</td>
                        <td class="fw-bold">{{ $productor->nombre }} {{ $productor->apellido_paterno }} {{ $productor->apellido_materno }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">CURP</td>
                        <td><code>{{ $productor->curp ?? 'N/A' }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">UPP</td>
                        <td>{{ $productor->upp ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Teléfono</td>
                        <td>{{ $productor->telefono ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Email</td>
                        <td>{{ $productor->email ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Municipio</td>
                        <td>{{ $productor->municipio ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Localidad</td>
                        <td>{{ $productor->localidad ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Estado</td>
                        <td>{{ $productor->estado ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Domicilio</td>
                        <td>{{ $productor->domicilio ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Clave</td>
                        <td>
                            @if ($productor->clave)
                                <span class="badge bg-primary">{{ $productor->clave }}</span>
                                <small class="text-muted ms-1">(Edad mínima de prueba: {{ $productor->edad_minima_prueba }} meses)</small>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Tipo de Actividad</td>
                        <td>
                            @if ($productor->tipo_actividad)
                                <span class="badge bg-secondary">{{ $productor->tipo_actividad }}</span>
                                @if ($productor->sub_tipo_actividad)
                                    <span class="badge bg-info text-dark">{{ $productor->sub_tipo_actividad }}</span>
                                @endif
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Zona / Sector</td>
                        <td>{{ $productor->zona ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Médico Asignado</td>
                        <td>{{ $productor->medico ? $productor->medico->name : 'No Asignado' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Ranchos ({{ $productor->predios->count() }})</h5>
                <a href="{{ route('predios.create', ['productor_id' => $productor->id]) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus"></i> Añadir
                </a>
            </div>
            <div class="card-body p-0">
                @if ($productor->predios->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach ($productor->predios as $predio)
                    <div class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold">{{ $predio->nombre_rancho }}</div>
                                <small class="text-muted">
                                    CUP: {{ $predio->clave_unidad_produccion }}
                                    @if ($predio->municipio) | {{ $predio->municipio }}@endif
                                </small>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('predios.edit', $predio->id) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="{{ route('inspecciones.create', ['predio_id' => $predio->id]) }}" class="btn btn-sm btn-outline-success" title="Nuevo Dictamen">
                                    <i class="bi bi-file-text"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-house-x fs-2 d-block mb-2"></i>
                    Sin ranchos registrados
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
<div class="d-flex gap-2">
    <a href="{{ route('productores.edit', $productor->id) }}" class="btn btn-primary">
        <i class="bi bi-pencil"></i> Editar
    </a>
    <form action="{{ route('productores.destroy', $productor->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este productor y todos sus predios?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger">
            <i class="bi bi-trash"></i> Eliminar
        </button>
    </form>
    <a href="{{ route('productores.index') }}" class="btn btn-outline-secondary ms-auto">Volver</a>
</div>
@endsection
