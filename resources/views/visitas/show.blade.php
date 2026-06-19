@extends('layouts.app')

@section('title', 'Detalle de Visita')
@section('header_title', 'Visita: ' . ($visita->codigo ?? 'Sin código'))
@section('header_subtitle', $visita->predio->nombre_rancho ?? '')
@section('back_url', route('visitas.index'))

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Información de la Visita</h5>
            </div>
            <div class="card-body p-4">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted ps-0" style="width: 140px;">Código</td>
                        <td class="fw-bold"><code>{{ $visita->codigo ?? 'N/A' }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Estado</td>
                        <td>
                            <span class="badge bg-{{ $visita->estado === 'completada' ? 'success' : ($visita->estado === 'cancelada' ? 'danger' : 'warning') }}">
                                {{ ucfirst($visita->estado) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Fecha Programada</td>
                        <td>{{ $visita->fecha_programada ? $visita->fecha_programada->format('d/m/Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Inyección</td>
                        <td>{{ $visita->inyeccion ? 'Sí' : 'No' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Veterinario</td>
                        <td>{{ $visita->veterinario->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Observaciones</td>
                        <td>{{ $visita->observaciones ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Predio Asignado</h5>
            </div>
            <div class="card-body p-4">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted ps-0" style="width: 140px;">Rancho</td>
                        <td class="fw-bold">{{ $visita->predio->nombre_rancho ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">CUP</td>
                        <td><code>{{ $visita->predio->clave_unidad_produccion ?? 'N/A' }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Productor</td>
                        <td>
                            @if ($visita->predio->productor)
                            <a href="{{ route('productores.show', $visita->predio->productor_id) }}">
                                {{ $visita->predio->productor->nombre }} {{ $visita->predio->productor->apellido_paterno }}
                            </a>
                            @else
                            N/A
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Municipio</td>
                        <td>{{ $visita->predio->municipio ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        @if ($visita->inspeccion)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Dictamen Asociado</h5>
            </div>
            <div class="card-body p-4">
                <p>
                    <strong>Folio:</strong> {{ $visita->inspeccion->folio ?? 'N/A' }}<br>
                    <strong>Estado:</strong>
                    <span class="badge bg-{{ $visita->inspeccion->estado === 'sincronizado' ? 'success' : 'secondary' }}">
                        {{ $visita->inspeccion->estado }}
                    </span>
                </p>
                <a href="{{ route('inspecciones.show', $visita->inspeccion->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> Ver Dictamen
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
<div class="d-flex gap-2 mt-2">
    <a href="{{ route('visitas.edit', $visita->id) }}" class="btn btn-primary">
        <i class="bi bi-pencil"></i> Editar
    </a>
    <form action="{{ route('visitas.destroy', $visita->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta visita?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger">
            <i class="bi bi-trash"></i> Eliminar
        </button>
    </form>
    <a href="{{ route('visitas.index') }}" class="btn btn-outline-secondary ms-auto">Volver</a>
</div>
@endsection
