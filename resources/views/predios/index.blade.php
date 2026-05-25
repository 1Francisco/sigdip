@extends('layouts.app')

@section('title', 'Gestión de Predios')
@section('header_title', 'Predios / Ranchos')
@section('header_subtitle', 'Administre las unidades de producción registradas')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">Listado de Predios</h5>
        <a href="{{ route('predios.create') }}" class="btn btn-primary">
            <i class="bi bi-house-add"></i> Nuevo Predio
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-mobile-cards">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Nombre del Rancho</th>
                        <th>UPP (Clave de Unidad)</th>
                        <th>Localidad</th>
                        <th>Productor Responsable</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($predios as $predio)
                    <tr>
                        <td class="ps-4" data-label="Rancho">
                            <div class="fw-bold text-dark">{{ $predio->nombre_rancho }}</div>
                        </td>
                        <td data-label="UPP"><code>{{ $predio->clave_unidad_produccion }}</code></td>
                        <td data-label="Localidad">{{ $predio->localidad }}</td>
                        <td data-label="Productor">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center d-none d-lg-flex" style="width: 24px; height: 24px; font-size: 0.7rem;">
                                    <i class="bi bi-person"></i>
                                </div>
                                <span>{{ $predio->productor->nombre }}</span>
                            </div>
                        </td>
                        <td data-label="Acciones">
                            <div class="d-flex gap-2">
                                <a href="{{ route('predios.edit', $predio->id) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
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
        {{ $predios->links() }}
    </div>
</div>
@endsection
