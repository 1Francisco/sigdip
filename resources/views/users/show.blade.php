@extends('layouts.app')

@section('title', 'Detalle del Médico')
@section('header_title', $usuario->name)
@section('header_subtitle', $usuario->email . ' — ' . $usuario->productores_count . ' productores asignados')
@section('back_url', route('usuarios.index'))

@section('content')
@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm mb-4 rounded-4">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Información del Médico</h5>
            </div>
            <div class="card-body p-4 text-center">
                <div class="bg-primary-soft text-primary rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-person-badge fs-1"></i>
                </div>
                <h4 class="fw-bold">{{ $usuario->name }}</h4>
                <p class="text-muted mb-4">{{ $usuario->email }}</p>

                <table class="table table-borderless text-start mb-0">
                    <tr>
                        <td class="text-muted ps-0" style="width: 140px;">Rol</td>
                        <td class="fw-bold">
                            @foreach ($usuario->roles as $role)
                                <span class="badge bg-primary">{{ $role->name }}</span>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Productores Asignados</td>
                        <td class="fw-bold">
                            <span class="badge bg-info rounded-pill">{{ $usuario->productores_count }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Registrado</td>
                        <td>{{ $usuario->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Última actualización</td>
                        <td>{{ $usuario->updated_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>
            <div class="card-footer bg-white d-flex gap-2 py-3">
                <a href="{{ route('usuarios.edit', $usuario->id) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                <a href="{{ route('usuarios.asignar-productores', $usuario) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-person-plus"></i> Asignar Productores
                </a>
                <form action="{{ route('usuarios.destroy', $usuario->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este médico permanentemente?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                </form>
                <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary btn-sm ms-auto">Volver</a>
            </div>
        </div>
    </div>

    <div class="col-lg-7 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-people text-primary me-2"></i>
                    Productores Asignados
                </h5>
                <a href="{{ route('usuarios.asignar-productores', $usuario) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Asignar / Desasignar
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Nombre</th>
                                <th>CURP</th>
                                <th>UPP</th>
                                <th class="text-center">Predios</th>
                                <th class="text-end pe-4">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productores as $p)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $p->nombre }} {{ $p->apellido_paterno }} {{ $p->apellido_materno }}</div>
                                </td>
                                <td><code>{{ $p->curp }}</code></td>
                                <td>{{ $p->upp }}</td>
                                <td class="text-center">
                                    <span class="badge bg-primary-soft text-primary rounded-pill px-3">{{ $p->predios_count }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="{{ route('productores.show', $p) }}" class="btn btn-sm btn-outline-info" title="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <form action="{{ route('usuarios.desasignar-productor', [$usuario, $p]) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Desasignar este productor de {{ $usuario->name }}?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Desasignar">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No hay productores asignados a este médico.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($productores->hasPages())
            <div class="card-footer bg-white py-2">
                {{ $productores->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
