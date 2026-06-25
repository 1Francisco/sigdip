@extends('layouts.app')

@section('title', 'Gestión de Médicos')
@section('header_title', 'Médicos Verificadores')
@section('header_subtitle', 'Administra el personal autorizado para realizar inspecciones')

@section('content')
@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm mb-4 rounded-4">{{ session('success') }}</div>
@endif
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-people text-primary me-2"></i>Personal en Campo</h5>
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Médico
        </a>
    </div>
    <div class="card-body bg-light border-bottom py-2 px-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4 col-sm-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 rounded-end-pill" placeholder="Buscar por nombre o email..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary rounded-pill px-3" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                @if(request('search'))
                    <a href="{{ route('usuarios.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-x-lg"></i> Limpiar</a>
                @endif
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nombre Completo</th>
                        <th>Correo de Acceso</th>
                        <th class="text-center">Productores Asignados</th>
                        <th>Fecha de Registro</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($medicos as $medico)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    <i class="bi bi-person-badge fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ $medico->name }}</h6>
                                    <small class="text-secondary">Rol: Médico de Campo</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $medico->email }}</td>
                        <td class="text-center">
                            <a href="{{ route('usuarios.show', $medico) }}" class="text-decoration-none">
                                <span class="badge bg-info-soft text-info rounded-pill px-3 py-1">
                                    {{ $medico->productores_count }} productores
                                </span>
                            </a>
                        </td>
                        <td>{{ $medico->created_at->format('d/m/Y') }}</td>
                        <td class="text-end pe-4">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('usuarios.show', $medico) }}" class="btn btn-sm btn-outline-info" title="Ver Detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('usuarios.asignar-productores', $medico) }}" class="btn btn-sm btn-outline-primary" title="Asignar Productores">
                                    <i class="bi bi-person-plus"></i>
                                </a>
                                <a href="{{ route('usuarios.edit', $medico) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('usuarios.destroy', $medico) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar a este médico? No podrá volver a iniciar sesión.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-secondary">
                            <i class="bi bi-inbox fs-1 mb-3 d-block text-light"></i>
                            No hay médicos verificadores registrados en el sistema.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
