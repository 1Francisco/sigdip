@extends('layouts.app')

@section('title', 'Nuevo Productor SENASICA')
@section('header_title', 'Registrar Productor')
@section('header_subtitle', 'Complete los datos según el formato oficial de SENASICA')
@section('back_url', route('productores.index'))

@section('styles')
@endsection

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
                <form action="{{ route('productores.store') }}" method="POST" id="wizardForm">
                    @csrf
                    @include('productores.partials._wizard_productor', [
                        'index' => null,
                        'medicos' => $medicos,
                        'isAdmin' => auth()->user()->hasRole('Administrador'),
                    ])
                </form>
            </div>
        </div>
    </div>
</div>
<style>
    .step-indicator {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #64748b;
        position: relative;
        z-index: 2;
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
    .step-line {
        width: 100px;
        height: 2px;
        background: #e2e8f0;
        align-self: center;
        margin: 0 -5px;
    }
</style>
@endsection

@section('scripts')
<script>
    // ========= Wizard navigation functions (single panel) =========
    function nextStepWizard(index) {
        if (!validateStep1Wizard(index)) return;
        var suf = index !== null && index !== '' ? '-' + index : '';
        document.getElementById('inputRegistrarPredio' + suf).value = "1";
        document.getElementById('step-1' + suf).classList.add('d-none');
        document.getElementById('step-2' + suf).classList.remove('d-none');
        document.getElementById('indicator-1' + suf).classList.add('completed');
        document.getElementById('indicator-1' + suf).innerHTML = '<i class="bi bi-check"></i>';
        document.getElementById('indicator-2' + suf).classList.add('active');
    }

    function prevStepWizard(index) {
        var suf = index !== null && index !== '' ? '-' + index : '';
        document.getElementById('inputRegistrarPredio' + suf).value = "0";
        document.getElementById('step-2' + suf).classList.add('d-none');
        document.getElementById('step-1' + suf).classList.remove('d-none');
        document.getElementById('indicator-1' + suf).classList.remove('completed');
        document.getElementById('indicator-1' + suf).innerHTML = '1';
        document.getElementById('indicator-2' + suf).classList.remove('active');
    }

    function submitOnlyProductorWizard(index) {
        var suf = index !== null && index !== '' ? '-' + index : '';
        document.getElementById('inputRegistrarPredio' + suf).value = "0";
        document.getElementById('wizardForm').submit();
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

    // ========= Sub tipo toggle =========
    var tipoSelect = document.getElementById('tipo_actividad');
    var subContainer = document.getElementById('sub_tipo_actividad_container');
    var subSelect = document.getElementById('sub_tipo_actividad');

    function toggleSubTipo() {
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
    }

    if (tipoSelect) {
        tipoSelect.addEventListener('change', toggleSubTipo);
        toggleSubTipo();
    }

    // ========= Auto-zona based on clave =========
    var claveField = document.getElementById('clave');
    var zonaSel = document.getElementById('zona_select');
    var zonaHid = document.getElementById('zona');
    if (claveField) {
        claveField.addEventListener('input', function() {
            var val = this.value.toUpperCase();
            var first = val.charAt(0);
            var finalZona = (first === 'A' || first === 'B') ? first : '';
            if (zonaSel && !zonaSel.value) zonaSel.value = finalZona;
            if (zonaHid && !zonaHid.value) zonaHid.value = finalZona;
        });
    }

    // ========= Clave autocomplete =========
    var claveInput = document.getElementById('clave');
    var resultadosClave = document.getElementById('resultadosClave');
    var debounceTimer;

    if (claveInput && resultadosClave) {
        claveInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            var val = this.value.trim();
            if (val.length < 1) {
                resultadosClave.innerHTML = '';
                return;
            }
            debounceTimer = setTimeout(function () {
                fetch('{{ route("productores.buscar-por-clave") }}?clave=' + encodeURIComponent(val))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.length === 0) {
                            resultadosClave.innerHTML = '<div class="small text-muted"><i class="bi bi-exclamation-circle me-1"></i> No se encontraron productores con esa clave.</div>';
                        } else {
                            var h = '<div class="small fw-semibold text-muted mb-1">' + data.length + ' productor(es) con esta clave:</div>';
                            h += '<div class="list-group list-group-flush border rounded-3" style="max-height: 200px; overflow-y: auto;">';
                            data.forEach(function (p) {
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
                    .catch(function () {
                        resultadosClave.innerHTML = '<div class="small text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Error al consultar.</div>';
                    });
            }, 300);
        });

        claveInput.addEventListener('blur', function () {
            setTimeout(function () {
                resultadosClave.innerHTML = '';
            }, 200);
        });

        resultadosClave.innerHTML = '';
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

@endsection
