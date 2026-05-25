@extends('layouts.app')

@section('title', 'Nuevo Médico')
@section('header_title', 'Registrar Médico Verificador')
@section('header_subtitle', 'Dar de alta a un nuevo elemento para la aplicación móvil')
@section('back_url', route('usuarios.index'))

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('usuarios.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Nombre Completo</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Ej. Dr. Juan Pérez" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Correo Electrónico (Para iniciar sesión)</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="juan.perez@sigdip.com" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Contraseña</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Mínimo 8 caracteres" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Confirmar Contraseña</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Mínimo 8 caracteres" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <a href="{{ route('usuarios.index') }}" class="btn btn-light border">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Guardar Médico
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm bg-primary-soft">
            <div class="card-body p-4">
                <h5 class="fw-bold text-primary mb-3"><i class="bi bi-info-circle me-2"></i>Información Importante</h5>
                <p class="text-secondary mb-3">
                    Al crear esta cuenta, el usuario tendrá el rol automático de <strong>Médico de Campo</strong>.
                </p>
                <ul class="text-secondary list-unstyled">
                    <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Podrá iniciar sesión en la Aplicación Móvil.</li>
                    <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Podrá sincronizar datos y crear inspecciones offline.</li>
                    <li class="mb-2"><i class="bi bi-x-circle text-danger me-2"></i><strong>NO</strong> podrá ingresar a este panel web administrativo.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
