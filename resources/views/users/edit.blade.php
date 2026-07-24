@extends('layouts.app')

@section('title', 'Editar Médico')
@section('header_title', 'Editar Médico Verificador')
@section('header_subtitle', 'Modifique los datos del usuario: ' . $usuario->name)
@section('back_url', route('usuarios.index'))

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('usuarios.update', $usuario->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Nombre Completo</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $usuario->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $usuario->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Zona</label>
                            <select name="zona" class="form-select @error('zona') is-invalid @enderror" required>
                                <option value="">Seleccionar zona...</option>
                                <option value="A" {{ old('zona', $usuario->zona) == 'A' ? 'selected' : '' }}>Zona A</option>
                                <option value="B" {{ old('zona', $usuario->zona) == 'B' ? 'selected' : '' }}>Zona B</option>
                            </select>
                            @error('zona')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Actividad</label>
                            <select name="actividad" class="form-select @error('actividad') is-invalid @enderror" required>
                                <option value="">Seleccionar actividad...</option>
                                <option value="Buffer" {{ old('actividad', $usuario->actividad) == 'Buffer' ? 'selected' : '' }}>Buffer</option>
                                <option value="Barrido" {{ old('actividad', $usuario->actividad) == 'Barrido' ? 'selected' : '' }}>Barrido</option>
                                <option value="Seguimiento" {{ old('actividad', $usuario->actividad) == 'Seguimiento' ? 'selected' : '' }}>Seguimiento</option>
                            </select>
                            @error('actividad')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Nueva Contraseña (Opcional)</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Dejar en blanco para no cambiar">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Confirmar Contraseña</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Repita la nueva contraseña">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <a href="{{ route('usuarios.index') }}" class="btn btn-light border">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Actualizar Médico
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
