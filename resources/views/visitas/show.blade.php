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
        @if ($visita->inspecciones->count() > 0)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Dictámenes Asociados ({{ $visita->inspecciones->count() }})</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach ($visita->inspecciones as $ins)
                    <div class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-dark mb-1">
                                    Folio: {{ $ins->folio ?? 'Sin Folio (Borrador)' }}
                                </div>
                                <div class="small text-muted mb-1">
                                    <strong>Productor:</strong> {{ $ins->predio->productor->nombre_completo ?? 'N/A' }} <br>
                                    <strong>Rancho:</strong> {{ $ins->predio->nombre_rancho ?? 'N/A' }}
                                </div>
                                <span class="badge bg-{{ $ins->estado === 'sincronizado' ? 'success' : 'secondary' }} rounded-pill">
                                    {{ ucfirst($ins->estado) }}
                                </span>
                            </div>
                            <div class="d-flex gap-1">
                                <a href="{{ route('inspecciones.show', $ins->id) }}" class="btn btn-sm btn-outline-primary" title="Ver Vista Previa / PDF">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('reportes.pdf', $ins->id) }}" class="btn btn-sm btn-outline-danger" title="Descargar PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                @if($ins->estado === 'borrador' || auth()->user()->hasRole('Administrador'))
                                <a href="{{ route('inspecciones.edit', $ins->id) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
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
