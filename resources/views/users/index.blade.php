@extends('layouts.app')

@section('title', 'Gestión de Médicos')
@section('header_title', 'Médicos Verificadores')
@section('header_subtitle', 'Administra el personal autorizado para realizar inspecciones')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-people text-primary me-2"></i>Personal en Campo</h5>
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Médico
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nombre Completo</th>
                        <th>Correo de Acceso</th>
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
                        <td>{{ $medico->created_at->format('d/m/Y') }}</td>
                        <td class="text-end pe-4">
                            <form action="{{ route('usuarios.destroy', $medico) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar a este médico? No podrá volver a iniciar sesión.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-secondary">
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
