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
            <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="confirmarGuardarSimple('{{ $index }}', false)">
                No tiene predio (Solo Productor)
            </button>
            @if($index === null)
            <button type="button" class="btn btn-success px-5 rounded-pill shadow" onclick="confirmarGuardarSimple('{{ $index }}', true)">
                <i class="bi bi-check-circle me-1"></i> Finalizar y Guardar Todo
            </button>
            @endif
            @endif
        </div>
    </div>
</div>

<!-- Modal de confirmación de guardado -->
<div class="modal fade" id="modalConfirmarGuardado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white border-0">
                <h6 class="modal-title fw-bold"><i class="bi bi-check-circle me-2"></i>Confirmar registro</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">Se registrará al siguiente productor:</p>
                <div class="bg-light rounded-3 p-3 border">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Nombre:</span>
                        <span class="fw-bold" id="modal-guardar-nombre">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Clave:</span>
                        <span class="fw-bold" id="modal-guardar-clave">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">UPP:</span>
                        <span class="fw-bold" id="modal-guardar-upp">—</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Tipo actividad:</span>
                        <span class="fw-bold" id="modal-guardar-tipo">—</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Con predio:</span>
                        <span class="fw-bold" id="modal-guardar-predio">—</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal" onclick="_cancelarGuardado()">Cancelar</button>
                <button type="button" class="btn btn-success px-5 rounded-pill fw-bold shadow-sm" data-bs-dismiss="modal" onclick="_ejecutarGuardado()">
                    <i class="bi bi-check-circle me-1"></i> Confirmar y Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window._guardarCallback = null;

window._confirmarGuardado = function(callback, data) {
    window._guardarCallback = callback;
    document.getElementById('modal-guardar-nombre').textContent = (data.nombre || '') + ' ' + (data.apellido_paterno || '') + (data.apellido_materno ? ' ' + data.apellido_materno : '');
    document.getElementById('modal-guardar-clave').textContent = data.clave || '—';
    document.getElementById('modal-guardar-upp').textContent = data.upp || '—';
    document.getElementById('modal-guardar-tipo').textContent = data.tipo_actividad || '—';
    document.getElementById('modal-guardar-predio').textContent = data.registrar_predio == '1' ? 'Sí' : 'No';
    new bootstrap.Modal(document.getElementById('modalConfirmarGuardado')).show();
};

window._ejecutarGuardado = function() {
    if (window._guardarCallback) {
        window._guardarCallback();
        window._guardarCallback = null;
    }
};

window._cancelarGuardado = function() {
    window._guardarCallback = null;
};

window.confirmarGuardarSimple = function(index, conPredio) {
    var suf = index !== null && index !== '' ? '-' + index : '';
    if (!validateStep1Wizard(index)) return;

    function getVal(name) {
        var el = document.querySelector('[name="' + name + '"]');
        return el ? el.value : '';
    }

    var data = {
        nombre: getVal('nombre'),
        apellido_paterno: getVal('apellido_paterno'),
        apellido_materno: getVal('apellido_materno'),
        clave: getVal('clave'),
        upp: getVal('upp'),
        tipo_actividad: getVal('tipo_actividad'),
        registrar_predio: conPredio ? '1' : '0',
    };

    window._confirmarGuardado(function() {
        document.getElementById('inputRegistrarPredio' + suf).value = conPredio ? '1' : '0';
        document.getElementById('wizardForm').submit();
    }, data);
};


</script>
