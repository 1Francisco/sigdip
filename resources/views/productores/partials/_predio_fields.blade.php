@props([
    'prefix' => '',
    'index' => null,
])

@php
    $idSuffix = $index !== null ? "-{$index}" : '';
    $nameFn = function ($field) use ($prefix) {
        return $prefix ? "{$prefix}[{$field}]" : $field;
    };
    $oldFn = function ($field, $default = '') use ($index) {
        return old($index !== null ? "productores.{$index}.{$field}" : $field, $default);
    };
@endphp

<div class="row g-3">
    <div class="col-md-12">
        <label class="form-label fw-semibold text-primary">Nombre del Rancho</label>
        <input type="text" name="{{ $nameFn('nombre_rancho') }}" id="nombre_rancho{{ $idSuffix }}" class="form-control border-primary" value="{{ $oldFn('nombre_rancho') }}" placeholder="Ej. El Mirador">
    </div>
    <div class="col-md-12">
        <label class="form-label fw-semibold">Clave UPP del Predio</label>
        <input type="text" name="{{ $nameFn('clave_unidad_produccion') }}" id="clave_upp{{ $idSuffix }}" class="form-control" value="{{ $oldFn('clave_unidad_produccion') }}" placeholder="Ej. 180104330002">
    </div>

    <div class="col-md-12 mb-2">
        <button type="button" class="btn btn-outline-primary btn-sm w-100 py-2 rounded-3 shadow-sm border-2 fw-bold" onclick="getLocationGps(this, '{{ $index }}')">
            <i class="bi bi-geo-alt-fill me-1"></i> Detectar Ubicación Actual (GPS)
        </button>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Latitud</label>
        <input type="text" name="{{ $nameFn('latitud') }}" id="latitud{{ $idSuffix }}" class="form-control" placeholder="Ej. 21.948694">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Longitud</label>
        <input type="text" name="{{ $nameFn('longitud') }}" id="longitud{{ $idSuffix }}" class="form-control" placeholder="Ej. -105.298320">
    </div>

    <div class="col-md-12">
        <label class="form-label fw-semibold">Domicilio del Predio</label>
        <input type="text" name="{{ $nameFn('predio_domicilio') }}" class="form-control" placeholder="Ej. A 2 km sobre el arroyo">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Municipio del Predio</label>
        <input type="text" name="{{ $nameFn('predio_municipio') }}" id="predio_municipio{{ $idSuffix }}" class="form-control" value="{{ $oldFn('predio_municipio') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Localidad del Predio</label>
        <input type="text" name="{{ $nameFn('predio_localidad') }}" id="predio_localidad{{ $idSuffix }}" class="form-control" value="{{ $oldFn('predio_localidad') }}">
    </div>
</div>
