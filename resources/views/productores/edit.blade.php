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
                        
                        @if(auth()->user()->hasRole('Administrador'))
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Clave de Cuarentena</label>
                            <input type="text" name="clave_cuarentena" id="clave_cuarentena" class="form-control text-uppercase rounded-3" placeholder="Ej. BD-123421" value="{{ old('clave_cuarentena', $productor->clave_cuarentena) }}">
                            <div class="form-text small text-muted">Debe iniciar con AD, AP, BD o BP. Ej. BD-123421</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Zona / Sector</label>
                            <select id="zona_select" class="form-select rounded-3 bg-light" disabled>
                                <option value="">-- Sin Zona --</option>
                                <option value="A" {{ old('zona', $productor->zona) == 'A' ? 'selected' : '' }}>Sector A</option>
                                <option value="B" {{ old('zona', $productor->zona) == 'B' ? 'selected' : '' }}>Sector B</option>
                            </select>
                            <input type="hidden" name="zona" id="zona" value="{{ old('zona', $productor->zona) }}">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Médico Veterinario Zootecnista (MVZ) Asignado</label>
                            <select name="medico_id" class="form-select rounded-3">
                                <option value="">-- Seleccionar Médico (Opcional) --</option>
                                @foreach($medicos as $medico)
                                    <option value="{{ $medico->id }}" {{ old('medico_id', $productor->medico_id) == $medico->id ? 'selected' : '' }}>{{ $medico->name }} ({{ $medico->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Médico Veterinario Zootecnista (MVZ) Asignado</label>
                            <input type="text" class="form-control bg-light" value="{{ $productor->medico ? $productor->medico->name : 'No Asignado' }}" readonly disabled>
                        </div>
                        @endif
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

@section('scripts')
<script>
    // Auto-update zona based on clave_cuarentena selection
    const claveSelect = document.getElementById('clave_cuarentena');
    const zonaSelect = document.getElementById('zona_select');
    const zonaHidden = document.getElementById('zona');
    if (claveSelect && (zonaSelect || zonaHidden)) {
        claveSelect.addEventListener('input', function() {
            const val = this.value.toUpperCase();
            let finalZona = '';
            if (val.startsWith('AD') || val.startsWith('AP')) {
                finalZona = 'A';
            } else if (val.startsWith('BD') || val.startsWith('BP')) {
                finalZona = 'B';
            }
            if (zonaSelect) zonaSelect.value = finalZona;
            if (zonaHidden) zonaHidden.value = finalZona;
        });
    }
</script>
@endsection
