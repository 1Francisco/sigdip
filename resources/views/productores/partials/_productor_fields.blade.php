@props([
    'prefix' => '',
    'index' => null,
    'medicos' => [],
    'isAdmin' => false,
])

@php
    $idSuffix = $index !== null ? "-{$index}" : '';
    $nameFn = function ($field) use ($prefix) {
        return $prefix ? "{$prefix}[{$field}]" : $field;
    };
    $oldFn = function ($field, $default = '') use ($index) {
        return old($index !== null ? "productores.{$index}.{$field}" : $field, $default);
    };
    $selected = function ($field, $value) use ($oldFn) {
        return $oldFn($field) == $value ? 'selected' : '';
    };
@endphp

<div class="row g-3" data-index="{{ $index }}">
    <div class="col-md-4">
        <label class="form-label fw-semibold">Nombre(s) <span class="text-danger">*</span></label>
        <input type="text" name="{{ $nameFn('nombre') }}" class="form-control" value="{{ $oldFn('nombre') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Apellido Paterno <span class="text-danger">*</span></label>
        <input type="text" name="{{ $nameFn('apellido_paterno') }}" class="form-control" value="{{ $oldFn('apellido_paterno') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Apellido Materno</label>
        <input type="text" name="{{ $nameFn('apellido_materno') }}" class="form-control" value="{{ $oldFn('apellido_materno') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">CURP</label>
        <input type="text" name="{{ $nameFn('curp') }}" class="form-control text-uppercase" maxlength="18" value="{{ $oldFn('curp') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">UPP (Productor)</label>
        <input type="text" name="{{ $nameFn('upp') }}" class="form-control" value="{{ $oldFn('upp') }}">
    </div>

    <div class="col-md-12">
        <label class="form-label fw-semibold">Domicilio Completo</label>
        <input type="text" name="{{ $nameFn('domicilio') }}" class="form-control" placeholder="Calle, Número, Colonia" value="{{ $oldFn('domicilio') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Municipio</label>
        <input type="text" name="{{ $nameFn('municipio') }}" class="form-control" value="{{ $oldFn('municipio') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Localidad</label>
        <input type="text" name="{{ $nameFn('localidad') }}" class="form-control" value="{{ $oldFn('localidad') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Estado</label>
        <input type="text" name="{{ $nameFn('estado') }}" class="form-control" value="{{ $oldFn('estado', 'Nayarit') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Teléfono</label>
        <input type="text" name="{{ $nameFn('telefono') }}" class="form-control" value="{{ $oldFn('telefono') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Correo Electrónico</label>
        <input type="email" name="{{ $nameFn('email') }}" class="form-control" value="{{ $oldFn('email') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Tipo de Actividad</label>
        <select name="{{ $nameFn('tipo_actividad') }}" id="tipo_actividad{{ $idSuffix }}" class="form-select rounded-3">
            <option value="">-- Seleccionar Actividad --</option>
            <option value="Barrido" {{ $selected('tipo_actividad', 'Barrido') }}>Barrido</option>
            <option value="Buffer" {{ $selected('tipo_actividad', 'Buffer') }}>Buffer</option>
            <option value="Seguimiento" {{ $selected('tipo_actividad', 'Seguimiento') }}>Seguimiento</option>
        </select>
    </div>
    <div class="col-md-6" id="sub_tipo_actividad_container{{ $idSuffix }}" style="display: none;">
        <label class="form-label fw-semibold">Subtipo de Actividad <span class="text-danger">*</span></label>
        <select name="{{ $nameFn('sub_tipo_actividad') }}" id="sub_tipo_actividad{{ $idSuffix }}" class="form-select rounded-3">
            <option value="">-- Seleccionar Subtipo --</option>
            <option value="Cuarentena Precautoria" {{ $selected('sub_tipo_actividad', 'Cuarentena Precautoria') }}>Cuarentena Precautoria</option>
            <option value="Cuarentena Definitiva" {{ $selected('sub_tipo_actividad', 'Cuarentena Definitiva') }}>Cuarentena Definitiva</option>
            <option value="Hatos Relacionados y Expuestos" {{ $selected('sub_tipo_actividad', 'Hatos Relacionados y Expuestos') }}>Hatos Relacionados y Expuestos</option>
        </select>
    </div>

    @if($isAdmin)
    <div class="col-md-6">
        <label class="form-label fw-semibold">Clave</label>
        <input type="text" name="{{ $nameFn('clave') }}" id="clave{{ $idSuffix }}" class="form-control text-uppercase rounded-3" placeholder="Ej. BD-123421" value="{{ $oldFn('clave') }}" autocomplete="off">
        <div class="form-text small text-muted">Clave del productor. Debe iniciar con AD, AP, BD o BP. Ej. BD-123421</div>
        <div id="resultadosClave{{ $idSuffix }}" class="mt-2"></div>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Zona / Sector</label>
        <select id="zona_select{{ $idSuffix }}" class="form-select rounded-3 bg-light">
            <option value="">-- Sin Zona --</option>
            <option value="A" {{ $selected('zona', 'A') }}>Sector A</option>
            <option value="B" {{ $selected('zona', 'B') }}>Sector B</option>
        </select>
        <input type="hidden" name="{{ $nameFn('zona') }}" id="zona{{ $idSuffix }}" value="{{ $oldFn('zona') }}">
        <div class="form-text small text-muted">Se sugiere automáticamente según la clave; puedes modificarla.</div>
    </div>

    <div class="col-md-12">
        <label class="form-label fw-semibold">Médico Veterinario Zootecnista (MVZ) Asignado</label>
        <select name="{{ $nameFn('medico_id') }}" id="medico_id{{ $idSuffix }}" class="form-select rounded-3">
            <option value="">-- Seleccionar Médico (Opcional) --</option>
            @foreach($medicos as $medico)
                <option value="{{ $medico->id }}" {{ $selected('medico_id', $medico->id) }}>{{ $medico->name }} ({{ $medico->email }})</option>
            @endforeach
        </select>
    </div>
    @else
    <div class="col-md-12">
        <label class="form-label fw-semibold">Médico Veterinario Zootecnista (MVZ) Asignado</label>
        <input type="text" class="form-control bg-light" value="{{ auth()->user()->name }}" readonly disabled>
    </div>
    @endif

    <!-- Marcador de clave visible para todos -->
    <div id="clave-marker{{ $idSuffix }}" class="col-12 mt-1" style="display:none;"></div>

    <script>
    (function() {
        var suf = '{{ $idSuffix }}';
        var input = document.getElementById('clave' + suf);
        var marker = document.getElementById('clave-marker' + suf);
        if (!marker) return;

        var zonaSelect = document.getElementById('zona_select' + suf);
        var zonaHidden = document.getElementById('zona' + suf);

        function updateClaveMarker(val) {
            if (val === undefined) {
                if (!input) return;
                val = input.value.toUpperCase().trim();
            }
            var first = val.charAt(0);
            if (first === 'A' || first === 'B') {
                var sector = first;
                marker.innerHTML = '<span class="badge bg-primary rounded-pill px-3 py-1 fs-6">' + val + '  ·  Zona ' + sector + '</span>';
                marker.style.display = '';
                if (zonaSelect && !zonaSelect.value) {
                    zonaSelect.value = sector;
                }
                if (zonaHidden && !zonaHidden.value) {
                    zonaHidden.value = sector;
                }
            } else {
                marker.style.display = 'none';
            }
        }

        if (input) {
            input.addEventListener('input', function() {
                if (!this.value.trim() && typeof previewClave === 'function') {
                    previewClave();
                } else {
                    updateClaveMarker();
                }
            });
            updateClaveMarker();
        }

        // ========= Vista previa de clave auto-generada =========
        var tipoSelect = document.getElementById('tipo_actividad' + suf);
        var subSelect = document.getElementById('sub_tipo_actividad' + suf);
        var medicoSelect = document.getElementById('medico_id' + suf);

        function previewClave() {
            if (!tipoSelect || !tipoSelect.value) {
                marker.style.display = 'none';
                return;
            }
            if (input && input.value.trim()) {
                updateClaveMarker();
                return;
            }
            var params = new URLSearchParams();
            params.set('tipo_actividad', tipoSelect.value);
            if (subSelect && subSelect.value) params.set('sub_tipo_actividad', subSelect.value);
            if (zonaHidden && zonaHidden.value) params.set('zona', zonaHidden.value);
            if (medicoSelect && medicoSelect.value) params.set('medico_id', medicoSelect.value);
            fetch('{{ route("productores.preview-clave") }}?' + params.toString())
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data || !data.clave) return;
                    if (zonaHidden && !zonaHidden.value && data.zona) zonaHidden.value = data.zona;
                    if (zonaSelect && !zonaSelect.value && data.zona) zonaSelect.value = data.zona;
                    marker.innerHTML = '<span class="badge bg-success rounded-pill px-3 py-1 fs-6"><i class="bi bi-magic me-1"></i>Se generará: ' + data.clave + (data.zona ? ' · Zona ' + data.zona : '') + '</span>';
                    marker.style.display = '';
                })
                .catch(function() {});
        }

        if (tipoSelect) tipoSelect.addEventListener('change', previewClave);
        if (subSelect) subSelect.addEventListener('change', previewClave);
        if (medicoSelect) medicoSelect.addEventListener('change', previewClave);
        if (zonaSelect) {
            zonaSelect.addEventListener('change', function() {
                if (zonaHidden) zonaHidden.value = zonaSelect.value;
                previewClave();
            });
        }

        window['previewClave' + (suf ? suf : '')] = previewClave;
        marker._previewClave = previewClave;
    })();
    </script>
</div>
