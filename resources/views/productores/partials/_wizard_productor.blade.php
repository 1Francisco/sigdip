@props([
    'index' => null,
    'medicos' => [],
    'isAdmin' => false,
    'sequential' => false,
])

@php
    $suf = $index !== null ? "-{$index}" : '';
    $prefix = $index !== null ? "productores[{$index}]" : '';
@endphp

<!-- Indicadores de Pasos -->
<div class="d-flex justify-content-center mb-4 step-indicators">
    <div class="step-indicator active" id="indicator-1{{ $suf }}">1</div>
    <div class="step-line"></div>
    <div class="step-indicator" id="indicator-2{{ $suf }}">2</div>
</div>

<!-- PASO 1: DATOS DEL PRODUCTOR -->
<div id="step-1{{ $suf }}" class="wizard-step-1">
    <h5 class="fw-bold mb-3"><i class="bi bi-person-circle me-2 text-primary"></i>Paso 1: Información del Productor</h5>

    <!-- Selector de productor existente (TomSelect) para copiar datos -->
    <div class="mb-4 p-3 bg-light rounded-3 border">
        <label class="form-label fw-semibold small text-muted mb-2">
            <i class="bi bi-people me-1"></i> Copiar datos de otro productor <span class="fw-normal text-muted">(opcional)</span>
        </label>
        <select id="copiar-productor{{ $suf }}" class="form-select" placeholder="Buscar y seleccionar productor…" autocomplete="off"></select>
        <div class="form-text small">Seleccione un productor para copiar sus datos. Si no selecciona, funciona como registro normal con clave consecuente.</div>
    </div>

    @include('productores.partials._productor_fields', [
        'prefix' => $prefix,
        'index' => $index,
        'medicos' => $medicos,
        'isAdmin' => $isAdmin,
    ])

    <div class="d-flex justify-content-end gap-3 mt-5">
        @if($index === null && !$sequential)
        <a href="{{ route('productores.index') }}" class="btn btn-light px-4 rounded-pill">Cancelar</a>
        @endif
        <button type="button" class="btn btn-primary px-5 rounded-pill" onclick="nextStepWizard('{{ $index }}')">
            Continuar al Paso 2 <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
</div>

<!-- PASO 2: DATOS DEL PREDIO -->
<div id="step-2{{ $suf }}" class="d-none wizard-step-2">
    <input type="hidden" name="{{ $prefix ? "{$prefix}[registrar_predio]" : 'registrar_predio' }}" id="inputRegistrarPredio{{ $suf }}" value="0">

    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4">
        <i class="bi bi-info-circle-fill me-2"></i>
        Si el productor no tiene un predio aún, puede hacer clic en <strong>"Solo registrar productor"</strong>.
    </div>

    <h5 class="fw-bold mb-3 text-success"><i class="bi bi-house-add me-2"></i>Paso 2: Información del Rancho / Predio</h5>
    @include('productores.partials._predio_fields', [
        'prefix' => $prefix,
        'index' => $index,
    ])

    <div class="d-flex flex-wrap justify-content-between gap-3 mt-5">
        <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" onclick="prevStepWizard('{{ $index }}')">
            <i class="bi bi-arrow-left me-1"></i> Anterior
        </button>
        <div class="d-flex gap-2">
            @if($sequential)
            <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="saveAndAdvanceProductor('{{ $index }}', 0)">
                No tiene predio (Solo Productor)
            </button>
            <button type="button" class="btn btn-success px-5 rounded-pill shadow btn-next-productor" onclick="saveAndAdvanceProductor('{{ $index }}', 1)">
                <i class="bi bi-arrow-right me-1"></i> Siguiente Productor
            </button>
            <button type="button" class="btn btn-success px-5 rounded-pill shadow btn-submit-final" style="display:none" onclick="saveAndAdvanceProductor('{{ $index }}', 1)">
                <i class="bi bi-check-circle me-1"></i> Finalizar y Guardar Todo
            </button>
            @else
            <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="submitOnlyProductorWizard('{{ $index }}')">
                No tiene predio (Solo Productor)
            </button>
            @if($index === null)
            <button type="submit" class="btn btn-success px-5 rounded-pill shadow">
                <i class="bi bi-check-circle me-1"></i> Finalizar y Guardar Todo
            </button>
            @endif
            @endif
        </div>
    </div>
</div>

<script>
window.llenarFormulario = function(data) {
    function setVal(name, value) {
        if (value) {
            var el = document.querySelector('[name="' + name + '"]');
            if (el) el.value = value;
        }
    }

    setVal('nombre', data.nombre);
    setVal('apellido_paterno', data.apellido_paterno);
    setVal('apellido_materno', data.apellido_materno);
    setVal('curp', data.curp);
    setVal('upp', data.upp);
    setVal('domicilio', data.domicilio);
    setVal('municipio', data.municipio);
    setVal('localidad', data.localidad);
    setVal('estado', data.estado);
    setVal('telefono', data.telefono);
    setVal('email', data.email);
    setVal('tipo_actividad', data.tipo_actividad);
    setVal('sub_tipo_actividad', data.sub_tipo_actividad);
    setVal('medico_id', data.medico_id);

    if (data.clave) {
        var claveEl = document.getElementById('clave{{ $suf }}');
        if (claveEl) {
            claveEl.value = data.clave;
            claveEl.dispatchEvent(new Event('input'));
        }
    }

    if (data.zona) {
        var zonaSel = document.getElementById('zona_select{{ $suf }}');
        var zonaHid = document.getElementById('zona{{ $suf }}');
        if (zonaSel) zonaSel.value = data.zona;
        if (zonaHid) zonaHid.value = data.zona;
    }

    setVal('nombre_rancho', data._predio_nombre_rancho);
    setVal('clave_unidad_produccion', data._predio_clave_unidad_produccion);
    setVal('latitud', data._predio_latitud);
    setVal('longitud', data._predio_longitud);
    setVal('predio_domicilio', data._predio_domicilio);
    setVal('predio_municipio', data._predio_municipio);
    setVal('predio_localidad', data._predio_localidad);

    var tipoSelect = document.getElementById('tipo_actividad{{ $suf }}');
    if (tipoSelect && data.tipo_actividad === 'Seguimiento') {
        var subContainer = document.getElementById('sub_tipo_actividad_container{{ $suf }}');
        if (subContainer) subContainer.style.display = 'block';
    }
};
</script>
