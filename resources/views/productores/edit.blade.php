@extends('layouts.app')

@section('title', 'Editar Productor')
@section('header_title', 'Editar Productor')
@section('header_subtitle', 'Actualice la información del productor: ' . $productor->nombre)
@section('back_url', route('productores.index'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm mb-4 rounded-4">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-2"></i>Por favor corrige los siguientes errores:</div>
                        <ul class="mb-0 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('productores.update', $productor->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="row g-3">
                        <!-- Nombres -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nombre(s)</label>
                            <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $productor->nombre) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Apellido Paterno</label>
                            <input type="text" name="apellido_paterno" class="form-control" value="{{ old('apellido_paterno', $productor->apellido_paterno) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Apellido Materno</label>
                            <input type="text" name="apellido_materno" class="form-control" value="{{ old('apellido_materno', $productor->apellido_materno) }}">
                        </div>

                        <!-- Identificación -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">CURP</label>
                            <input type="text" name="curp" class="form-control" value="{{ old('curp', $productor->curp) }}" maxlength="18">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">UPP (Unidad de Producción Pecuaria)</label>
                            <input type="text" name="upp" class="form-control" value="{{ old('upp', $productor->upp) }}">
                        </div>

                        <!-- Domicilio -->
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Domicilio Completo</label>
                            <input type="text" name="domicilio" class="form-control" value="{{ old('domicilio', $productor->domicilio) }}" placeholder="Calle, Número, Colonia">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Municipio</label>
                            <input type="text" name="municipio" class="form-control" value="{{ old('municipio', $productor->municipio) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Localidad</label>
                            <input type="text" name="localidad" class="form-control" value="{{ old('localidad', $productor->localidad) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado</label>
                            <input type="text" name="estado" class="form-control" value="{{ old('estado', $productor->estado) }}">
                        </div>

                        <!-- Contacto -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $productor->telefono) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $productor->email) }}">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <a href="{{ route('productores.index') }}" class="btn btn-light px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-5">Actualizar Productor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
