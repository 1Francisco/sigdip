@extends('layouts.app')

@section('title', 'Registro Masivo de Productores')
@section('header_title', 'Registro Masivo de Productores')
@section('header_subtitle', 'Registre múltiples productores de forma secuencial')
@section('back_url', route('productores.index'))

@php $showWizard = $errors->any(); @endphp

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">

        <!-- ==================== PANTALLA INICIAL (CONFIG) ==================== -->
        <div id="config-screen" class="{{ $showWizard ? 'd-none' : '' }}">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <div class="display-6 text-primary mb-3">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h4 class="fw-bold">Registro Múltiple de Productores</h4>
                        <p class="text-muted mb-0">Ingrese la cantidad de productores que desea registrar.</p>
                    </div>

                    @if($prefillProductor)
                    <div class="alert alert-info border-0 shadow-sm rounded-4 mx-auto mb-4 small d-flex align-items-center" style="max-width: 500px;">
                        <i class="bi bi-info-circle-fill me-3 fs-5"></i>
                        <div>
                            <strong>Modo Pre-llenado Activo:</strong> Se copiarán automáticamente los datos de localización y MVZ del productor <strong>{{ $prefillProductor->nombre_completo }}</strong>.
                        </div>
                    </div>
                    @endif

                    <div class="row justify-content-center mt-4">
                        <div class="col-md-4 col-8">
                            <label for="cantidad_productores" class="form-label fw-bold fs-5">Cantidad de productores</label>
                            <input type="number" id="cantidad_productores" class="form-control form-control-lg text-center" value="2" min="1" max="15">
                            <div class="form-text">Mínimo 1, máximo 15</div>
                        </div>
                    </div>

                    <div class="mt-5 d-flex justify-content-center gap-3">
                        <a href="{{ route('productores.index') }}" class="btn btn-outline-secondary px-5 rounded-pill">Cancelar</a>
                        <button type="button" class="btn btn-primary px-5 rounded-pill fw-bold shadow-sm" onclick="startSequential()">
                            <i class="bi bi-arrow-right me-1"></i> Comenzar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== PANTALLA WIZARD (SECUENCIAL) ==================== -->
        <div id="wizard-screen" class="{{ $showWizard ? '' : 'd-none' }}">

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

            @if($prefillProductor)
            <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4 small d-flex align-items-center">
                <i class="bi bi-link-45deg me-3 fs-4 text-primary"></i>
                <div>
                    Vinculando nuevos productores al hato de: <strong>{{ $prefillProductor->nombre_completo }}</strong> (Clave de Hato: <code>{{ $prefillProductor->clave }}</code>). Se copiarán automáticamente sus datos de localización.
                </div>
            </div>
            @endif

            <form action="{{ route('productores.store-multiple') }}" method="POST" id="multipleForm">
                @csrf
                <div id="hidden-data"></div>

                <!-- Barra de Progreso -->
                <div id="progress-bar" class="mb-4"></div>

                <!-- Panel del Wizard -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary bg-opacity-10 py-3 border-0">
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="bi bi-person-fill me-2"></i>Productor <span id="productor-actual-num">1</span> de <span id="productor-total-num">2</span>
                        </h5>
                    </div>
                    <div class="card-body p-4" id="wizard-panel">
                        @include('productores.partials._wizard_productor', [
                            'index' => null,
                            'medicos' => $medicos,
                            'isAdmin' => auth()->user()->hasRole('Administrador'),
                            'sequential' => true,
                        ])
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>

<style>
    .step-indicator {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: #e2e8f0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #64748b;
        transition: all 0.3s;
    }
    .step-indicator.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
    }
    .step-indicator.completed {
        background: #22c55e;
        color: white;
    }
    .step-indicator.completed i {
        font-size: 16px;
    }
    .step-line {
        width: 40px;
        height: 2px;
        background: #e2e8f0;
        display: inline-block;
    }
    .step-line.completed {
        background: #22c55e;
    }
</style>
@endsection

@section('scripts')
<script>
    const isAdmin = {{ auth()->user()->hasRole('Administrador') ? 'true' : 'false' }};
    const medicos = @json($medicos);

    const prefill = {
        @if($prefillProductor)
        domicilio: "{{ $prefillProductor->domicilio ?? '' }}",
        municipio: "{{ $prefillProductor->municipio ?? '' }}",
        localidad: "{{ $prefillProductor->localidad ?? '' }}",
        estado: "{{ $prefillProductor->estado ?? 'Nayarit' }}",
        medico_id: "{{ $prefillProductor->medico_id ?? '' }}",
        tipo_actividad: "{{ $prefillProductor->tipo_actividad ?? '' }}",
        sub_tipo_actividad: "{{ $prefillProductor->sub_tipo_actividad ?? '' }}",
        telefono: "{{ $prefillProductor->telefono ?? '' }}",
        email: "{{ $prefillProductor->email ?? '' }}",
        clave: "{{ $prefillProductor->clave ?? '' }}",
        zona: "{{ $prefillProductor->zona ?? '' }}",
        @if($prefillPredio)
        nombre_rancho: "{{ $prefillPredio->nombre_rancho ?? '' }}",
        clave_unidad_produccion: "{{ $prefillPredio->clave_unidad_produccion ?? '' }}",
        predio_domicilio: "{{ $prefillPredio->domicilio ?? '' }}",
        predio_municipio: "{{ $prefillPredio->municipio ?? '' }}",
        predio_localidad: "{{ $prefillPredio->localidad ?? '' }}",
        latitud: "{{ $prefillPredio->latitud ?? '' }}",
        longitud: "{{ $prefillPredio->longitud ?? '' }}",
        @endif
        @endif
    };

    let totalCount = 2;
    let currentIndex = 0;
    let productors = [];

    @if($showWizard)
    document.addEventListener('DOMContentLoaded', function() {
        generateForms();
    });
    @endif

    function startSequential() {
        const cantidadInput = document.getElementById('cantidad_productores');
        let count = parseInt(cantidadInput.value);
        if (isNaN(count) || count < 1) count = 1;
        if (count > 15) count = 15;
        cantidadInput.value = count;

        document.getElementById('config-screen').classList.add('d-none');
        document.getElementById('wizard-screen').classList.remove('d-none');

        generateForms();
    }

    function generateForms() {
        totalCount = parseInt(document.getElementById('cantidad_productores').value) || 2;
        currentIndex = 0;
        productors = new Array(totalCount).fill(null);

        renderProgress();
        resetWizard();
        applyPrefill();
        initPanelEvents();
        updateUI();
    }

    function renderProgress() {
        var html = '<div class="d-flex justify-content-center gap-2 align-items-center">';
        for (var i = 0; i < totalCount; i++) {
            var cls = i === currentIndex ? 'active' : (productors[i] ? 'completed' : '');
            var num = i + 1;
            var content = productors[i] ? '<i class="bi bi-check"></i>' : num;
            html += '<div class="step-indicator ' + cls + '">' + content + '</div>';
            if (i < totalCount - 1) {
                var lineCls = productors[i] ? 'completed' : '';
                html += '<div class="step-line ' + lineCls + '"></div>';
            }
        }
        html += '</div>';
        document.getElementById('progress-bar').innerHTML = html;
    }

    function updateUI() {
        document.getElementById('productor-actual-num').textContent = currentIndex + 1;
        document.getElementById('productor-total-num').textContent = totalCount;

        var isLast = currentIndex >= totalCount - 1;
        var nextBtn = document.querySelector('.btn-next-productor');
        var submitBtn = document.querySelector('.btn-submit-final');
        if (nextBtn) nextBtn.style.display = isLast ? 'none' : '';
        if (submitBtn) submitBtn.style.display = isLast ? '' : 'none';
    }

    function resetWizard() {
        document.getElementById('step-1').classList.remove('d-none');
        document.getElementById('step-2').classList.add('d-none');
        document.getElementById('indicator-1').classList.remove('completed');
        document.getElementById('indicator-1').innerHTML = '1';
        document.getElementById('indicator-2').classList.remove('active');

        var formElements = document.querySelectorAll('#step-1 input, #step-1 select, #step-2 input, #step-2 select');
        formElements.forEach(function(el) {
            if (el.type !== 'hidden') {
                if (el.type === 'checkbox') {
                    el.checked = false;
                } else {
                    el.value = '';
                }
            }
        });
        document.getElementById('inputRegistrarPredio').value = '0';

        var subContainer = document.getElementById('sub_tipo_actividad_container');
        if (subContainer) subContainer.style.display = 'none';

        var resultadosClave = document.getElementById('resultadosClave');
        if (resultadosClave) resultadosClave.innerHTML = '';

        var switchEl = document.getElementById('vincular_hato_switch');
        if (switchEl) {
            switchEl.checked = false;
            if (typeof toggleVincularHato === 'function') toggleVincularHato('');
        }
    }

    function applyPrefill() {
        if (prefill.domicilio) document.querySelector('[name="domicilio"]').value = prefill.domicilio;
        if (prefill.municipio) document.querySelector('[name="municipio"]').value = prefill.municipio;
        if (prefill.localidad) document.querySelector('[name="localidad"]').value = prefill.localidad;
        if (prefill.estado) document.querySelector('[name="estado"]').value = prefill.estado;
        if (prefill.medico_id) document.querySelector('[name="medico_id"]').value = prefill.medico_id;
        if (prefill.tipo_actividad) document.querySelector('[name="tipo_actividad"]').value = prefill.tipo_actividad;
        if (prefill.sub_tipo_actividad) document.querySelector('[name="sub_tipo_actividad"]').value = prefill.sub_tipo_actividad;
        if (prefill.nombre_rancho) document.querySelector('[name="nombre_rancho"]').value = prefill.nombre_rancho;
        if (prefill.predio_domicilio) document.querySelector('[name="predio_domicilio"]').value = prefill.predio_domicilio;
        if (prefill.predio_municipio) document.querySelector('[name="predio_municipio"]').value = prefill.predio_municipio;
        if (prefill.predio_localidad) document.querySelector('[name="predio_localidad"]').value = prefill.predio_localidad;
        if (prefill.clave_unidad_produccion) document.querySelector('[name="clave_unidad_produccion"]').value = prefill.clave_unidad_produccion;
        if (prefill.latitud) document.querySelector('[name="latitud"]').value = prefill.latitud;
        if (prefill.longitud) document.querySelector('[name="longitud"]').value = prefill.longitud;

        if (prefill.telefono) document.querySelector('[name="telefono"]').value = prefill.telefono;
        if (prefill.email) document.querySelector('[name="email"]').value = prefill.email;

        if (prefill.clave) {
            document.getElementById('clave').value = prefill.clave;
            document.getElementById('clave').dispatchEvent(new Event('input'));
        }
        if (prefill.zona) {
            if (document.getElementById('zona_select')) document.getElementById('zona_select').value = prefill.zona;
            if (document.getElementById('zona')) document.getElementById('zona').value = prefill.zona;
        }

        if (prefill.tipo_actividad && typeof toggleSubTipo === 'function') toggleSubTipo();
    }

    function saveAndAdvanceProductor(index, conPredio) {
        if (!validateStep1Wizard(index)) return;

        function getVal(name) {
            var el = document.querySelector('[name="' + name + '"]');
            return el ? el.value : '';
        }

        var data = {
            nombre: getVal('nombre'),
            apellido_paterno: getVal('apellido_paterno'),
            apellido_materno: getVal('apellido_materno'),
            curp: getVal('curp'),
            upp: getVal('upp'),
            domicilio: getVal('domicilio'),
            municipio: getVal('municipio'),
            localidad: getVal('localidad'),
            estado: getVal('estado'),
            telefono: getVal('telefono'),
            email: getVal('email'),
            tipo_actividad: getVal('tipo_actividad'),
            sub_tipo_actividad: getVal('sub_tipo_actividad'),
            clave: getVal('clave'),
            zona: document.getElementById('zona') ? document.getElementById('zona').value : '',
            medico_id: getVal('medico_id'),
            registrar_predio: conPredio ? '1' : '0',
        };

        if (conPredio) {
            data.nombre_rancho = getVal('nombre_rancho');
            data.clave_unidad_produccion = getVal('clave_unidad_produccion');
            data.latitud = getVal('latitud');
            data.longitud = getVal('longitud');
            data.predio_domicilio = getVal('predio_domicilio');
            data.predio_municipio = getVal('predio_municipio');
            data.predio_localidad = getVal('predio_localidad');
        }

        productors[currentIndex] = data;

        // Inherit common fields for the next productor in sequence
        prefill.domicilio = data.domicilio;
        prefill.municipio = data.municipio;
        prefill.localidad = data.localidad;
        prefill.estado = data.estado;
        prefill.telefono = data.telefono;
        prefill.email = data.email;
        prefill.medico_id = data.medico_id;
        prefill.tipo_actividad = data.tipo_actividad;
        prefill.sub_tipo_actividad = data.sub_tipo_actividad;
        prefill.clave = data.clave;
        prefill.zona = data.zona;
        prefill.nombre_rancho = data.nombre_rancho || '';
        prefill.clave_unidad_produccion = data.clave_unidad_produccion || '';
        prefill.predio_domicilio = data.predio_domicilio || '';
        prefill.predio_municipio = data.predio_municipio || '';
        prefill.predio_localidad = data.predio_localidad || '';
        prefill.latitud = data.latitud || '';
        prefill.longitud = data.longitud || '';

        if (currentIndex < totalCount - 1) {
            currentIndex++;
            rebuildHiddenInputs();
            resetWizard();
            applyPrefill();
            renderProgress();
            updateUI();
            initPanelEvents();
        } else {
            rebuildHiddenInputs();
            document.getElementById('multipleForm').submit();
        }
    }

    function rebuildHiddenInputs() {
        var container = document.getElementById('hidden-data');
        container.innerHTML = '';
        for (var i = 0; i < productors.length; i++) {
            var data = productors[i];
            if (!data) continue;
            for (var key in data) {
                if (data[key]) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'productores[' + i + '][' + key + ']';
                    input.value = data[key];
                    container.appendChild(input);
                }
            }
        }
    }

    // ========= Wizard navigation =========
    function nextStepWizard(index) {
        if (!validateStep1Wizard(index)) return;
        var suf = index !== null && index !== '' ? '-' + index : '';
        document.getElementById('inputRegistrarPredio' + suf).value = '1';
        document.getElementById('step-1' + suf).classList.add('d-none');
        document.getElementById('step-2' + suf).classList.remove('d-none');
        document.getElementById('indicator-1' + suf).classList.add('completed');
        document.getElementById('indicator-1' + suf).innerHTML = '<i class="bi bi-check"></i>';
        document.getElementById('indicator-2' + suf).classList.add('active');
    }

    function prevStepWizard(index) {
        var suf = index !== null && index !== '' ? '-' + index : '';
        document.getElementById('inputRegistrarPredio' + suf).value = '0';
        document.getElementById('step-2' + suf).classList.add('d-none');
        document.getElementById('step-1' + suf).classList.remove('d-none');
        document.getElementById('indicator-1' + suf).classList.remove('completed');
        document.getElementById('indicator-1' + suf).innerHTML = '1';
        document.getElementById('indicator-2' + suf).classList.remove('active');
    }

    function validateStep1Wizard(index) {
        var suf = index !== null && index !== '' ? '-' + index : '';
        var inputs = document.getElementById('step-1' + suf).querySelectorAll('input[required]');
        var valid = true;
        inputs.forEach(function(input) {
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                valid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        if (!valid) alert('Por favor, complete los campos obligatorios del productor.');
        return valid;
    }

    // ========= Panel events (single panel, no suffix) =========
    function initPanelEvents() {
        var tipoSelect = document.getElementById('tipo_actividad');
        var subContainer = document.getElementById('sub_tipo_actividad_container');
        var subSelect = document.getElementById('sub_tipo_actividad');

        window.toggleSubTipo = function() {
            if (tipoSelect && subContainer && subSelect) {
                if (tipoSelect.value === 'Seguimiento') {
                    subContainer.style.display = 'block';
                    subSelect.setAttribute('required', 'required');
                } else {
                    subContainer.style.display = 'none';
                    subSelect.removeAttribute('required');
                    subSelect.value = '';
                }
            }
        };

        if (tipoSelect) {
            tipoSelect.removeEventListener('change', window.toggleSubTipo);
            tipoSelect.addEventListener('change', window.toggleSubTipo);
            window.toggleSubTipo();
        }

        // Auto zona
        var claveField = document.getElementById('clave');
        var zonaSel = document.getElementById('zona_select');
        var zonaHid = document.getElementById('zona');
        if (claveField) {
            claveField.removeEventListener('input', window.autoZonaHandler);
            window.autoZonaHandler = function() {
                var val = this.value.toUpperCase();
                var finalZona = '';
                if (val.startsWith('AD') || val.startsWith('AP')) {
                    finalZona = 'A';
                } else if (val.startsWith('BD') || val.startsWith('BP')) {
                    finalZona = 'B';
                }
                if (zonaSel) zonaSel.value = finalZona;
                if (zonaHid) zonaHid.value = finalZona;
            };
            claveField.addEventListener('input', window.autoZonaHandler);
        }

        // Clave autocomplete
        var claveInput = document.getElementById('clave');
        var resultadosClave = document.getElementById('resultadosClave');
        if (claveInput && resultadosClave) {
            if (window.claveHandler) {
                claveInput.removeEventListener('input', window.claveHandler);
            }
            window.claveHandler = function() {
                clearTimeout(window.debounceTimer);
                var val = this.value.trim();
                if (val.length < 1) {
                    resultadosClave.innerHTML = '';
                    return;
                }
                window.debounceTimer = setTimeout(function() {
                    fetch('{{ route("productores.buscar-por-clave") }}?clave=' + encodeURIComponent(val))
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.length === 0) {
                                resultadosClave.innerHTML = '<div class="small text-muted"><i class="bi bi-exclamation-circle me-1"></i> No se encontraron productores con esa clave.</div>';
                            } else {
                                var h = '<div class="small fw-semibold text-muted mb-1">' + data.length + ' productor(es) con esta clave:</div>';
                                h += '<div class="list-group list-group-flush border rounded-3" style="max-height: 200px; overflow-y: auto;">';
                                data.forEach(function(p) {
                                    var nom = p.nombre + ' ' + p.apellido_paterno + (p.apellido_materno ? ' ' + p.apellido_materno : '');
                                    var cl = p.clave || '';
                                    h += '<div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 small" style="cursor:pointer;" onclick="document.getElementById(\'clave\').value=\'' + cl + '\'; document.getElementById(\'clave\').dispatchEvent(new Event(\'input\')); document.getElementById(\'resultadosClave\').innerHTML=\'\';">' +
                                        '<div><span class="badge bg-secondary rounded-pill me-2">' + cl + '</span>' + nom + '</div>' +
                                        '<div><span class="text-muted">' + (p.tipo_actividad || '') + (p.zona ? ' | ' + p.zona : '') + '</span></div>' +
                                        '</div>';
                                });
                                h += '</div>';
                                resultadosClave.innerHTML = h;
                            }
                        })
                        .catch(function() {
                            resultadosClave.innerHTML = '<div class="small text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Error al consultar.</div>';
                        });
                }, 300);
            };
            claveInput.addEventListener('input', window.claveHandler);
        }
    }

    // ========= GPS =========
    function getLocationGps(btn, index) {
        var suf = index !== null && index !== '' ? '-' + index : '';
        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Obteniendo ubicación...';
        btn.disabled = true;

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    document.getElementById('latitud' + suf).value = position.coords.latitude.toFixed(6);
                    document.getElementById('longitud' + suf).value = position.coords.longitude.toFixed(6);
                    btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Ubicación Capturada';
                    btn.classList.replace('btn-outline-primary', 'btn-success');
                    btn.disabled = false;
                    setTimeout(function() {
                        btn.innerHTML = originalHtml;
                        btn.classList.replace('btn-success', 'btn-outline-primary');
                    }, 3000);
                },
                function(error) {
                    console.error('Error GPS:', error);
                    alert('Error al obtener ubicación: ' + error.message);
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        } else {
            alert('Tu navegador no soporta geolocalización.');
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    // ========= TomSelect: copiar datos de productor existente =========
    var copiarSelect = document.getElementById('copiar-productor');
    if (copiarSelect) {
        new TomSelect(copiarSelect, {
            valueField: 'id',
            labelField: 'display',
            searchField: ['nombre', 'apellido_paterno', 'apellido_materno', 'clave'],
            maxOptions: 15,
            placeholder: 'Buscar y seleccionar productor…',
            load: function(query, callback) {
                if (query.length < 2) return callback();
                fetch('{{ route("productores.buscar") }}?q=' + encodeURIComponent(query))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        callback(data.map(function(p) {
                            p.display = (p.clave ? '[' + p.clave + '] ' : '') + p.nombre + ' ' + p.apellido_paterno + (p.apellido_materno ? ' ' + p.apellido_materno : '');
                            return p;
                        }));
                    });
            },
            onChange: function(value) {
                if (value) {
                    var option = this.options[value];
                    if (option) window.llenarFormulario(option);
                }
            }
        });
    }
</script>
@endsection
