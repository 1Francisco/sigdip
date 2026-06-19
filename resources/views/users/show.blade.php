@extends('layouts.app')

@section('title', 'Detalle del Médico')
@section('header_title', $usuario->name)
@section('header_subtitle', $usuario->email)
@section('back_url', route('usuarios.index'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
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
                        <td class="text-muted ps-0">Registrado</td>
                        <td>{{ $usuario->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Última actualización</td>
                        <td>{{ $usuario->updated_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <a href="{{ route('usuarios.edit', $usuario->id) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <form action="{{ route('usuarios.destroy', $usuario->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este médico permanentemente?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-trash"></i> Eliminar
                </button>
            </form>
            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary ms-auto">Volver</a>
        </div>
    </div>
</div>
@endsection
