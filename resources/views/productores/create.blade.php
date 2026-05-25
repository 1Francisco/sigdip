@extends('layouts.app')

@section('title', 'Nuevo Productor SENASICA')
@section('header_title', 'Registrar Productor')
@section('header_subtitle', 'Complete los datos según el formato oficial de SENASICA')
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
                <form action="{{ route('productores.store') }}" method="POST" id="wizardForm">
                    @csrf
                    
                    <!-- Indicadores de Pasos -->
                    <div class="d-flex justify-content-center mb-4">
                        <div class="step-indicator active" id="indicator-1">1</div>
                        <div class="step-line"></div>
                        <div class="step-indicator" id="indicator-2">2</div>
                    </div>

                    <!-- PASO 1: DATOS DEL PRODUCTOR -->
                    <div id="step-1">
                        <h5 class="fw-bold mb-3"><i class="bi bi-person-circle me-2 text-primary"></i>Paso 1: Información del Productor</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Nombre(s)</label>
                                <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Apellido Paterno</label>
                                <input type="text" name="apellido_paterno" class="form-control" value="{{ old('apellido_paterno') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Apellido Materno</label>
                                <input type="text" name="apellido_materno" class="form-control" value="{{ old('apellido_materno') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">CURP</label>
                                <input type="text" name="curp" class="form-control" maxlength="18" value="{{ old('curp') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">UPP (Productor)</label>
                                <input type="text" name="upp" class="form-control" value="{{ old('upp') }}">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Domicilio Completo</label>
                                <input type="text" name="domicilio" class="form-control" placeholder="Calle, Número, Colonia" value="{{ old('domicilio') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Municipio</label>
                                <input type="text" name="municipio" class="form-control" value="{{ old('municipio') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Localidad</label>
                                <input type="text" name="localidad" class="form-control" value="{{ old('localidad') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Estado</label>
                                <input type="text" name="estado" class="form-control" value="{{ old('estado', 'Nayarit') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Teléfono</label>
                                <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-3 mt-5">
                            <a href="{{ route('productores.index') }}" class="btn btn-light px-4 rounded-pill">Cancelar</a>
                            <button type="button" class="btn btn-primary px-5 rounded-pill" onclick="nextStep()">
                                Continuar al Paso 2 <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- PASO 2: DATOS DEL PREDIO -->
                    <div id="step-2" class="d-none">
                        <input type="hidden" name="registrar_predio" id="inputRegistrarPredio" value="1">
                        
                        <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            Si el productor no tiene un predio aún, puede hacer clic en <strong>"Solo registrar productor"</strong>.
                        </div>

                        <h5 class="fw-bold mb-3 text-success"><i class="bi bi-house-add me-2"></i>Paso 2: Información del Rancho / Predio</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold text-primary">Nombre del Rancho</label>
                                <input type="text" name="nombre_rancho" id="nombre_rancho" class="form-control border-primary" value="{{ old('nombre_rancho') }}" placeholder="Ej. El Mirador">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Clave UPP del Predio</label>
                                <input type="text" name="clave_unidad_produccion" id="clave_upp" class="form-control" value="{{ old('clave_unidad_produccion') }}" placeholder="Ej. 180104330002">
                            </div>

                            <!-- Ubicación GPS -->
                            <div class="col-md-12 mb-2">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 py-2 rounded-3 shadow-sm border-2 fw-bold" onclick="getLocation(this)">
                                    <i class="bi bi-geo-alt-fill me-1"></i> Detectar Ubicación Actual (GPS)
                                </button>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Latitud</label>
                                <input type="text" name="latitud" id="latitud" class="form-control" placeholder="Ej. 21.948694">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Longitud</label>
                                <input type="text" name="longitud" id="longitud" class="form-control" placeholder="Ej. -105.298320">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Domicilio del Predio</label>
                                <input type="text" name="predio_domicilio" class="form-control" placeholder="Ej. A 2 km sobre el arroyo">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Municipio del Predio</label>
                                <input type="text" name="predio_municipio" id="p_muni" class="form-control" value="{{ old('predio_municipio') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Localidad del Predio</label>
                                <input type="text" name="predio_localidad" id="p_loc" class="form-control" value="{{ old('predio_localidad') }}">
                            </div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between gap-3 mt-5">
                            <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" onclick="prevStep()">
                                <i class="bi bi-arrow-left me-1"></i> Anterior
                            </button>
                            
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="submitOnlyProductor()">
                                    No tiene predio (Solo Productor)
                                </button>
                                <button type="submit" class="btn btn-success px-5 rounded-pill shadow">
                                    <i class="bi bi-check-circle me-1"></i> Finalizar y Guardar Todo
                                </button>
                            </div>
                        </div>
                    </div>
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
    const step1 = document.getElementById('step-1');
    const step2 = document.getElementById('step-2');
    const indicator1 = document.getElementById('indicator-1');
    const indicator2 = document.getElementById('indicator-2');
    const inputRegistrar = document.getElementById('inputRegistrarPredio');
    const form = document.getElementById('wizardForm');

    function nextStep() {
        if (!validateStep1()) return;
        
        step1.classList.add('d-none');
        step2.classList.remove('d-none');
        indicator1.classList.add('completed');
        indicator1.innerHTML = '<i class="bi bi-check"></i>';
        indicator2.classList.add('active');
    }

    function prevStep() {
        step2.classList.add('d-none');
        step1.classList.remove('d-none');
        indicator1.classList.remove('completed');
        indicator1.innerHTML = '1';
        indicator2.classList.remove('active');
    }

    function submitOnlyProductor() {
        inputRegistrar.value = "0";
        form.submit();
    }

    function validateStep1() {
        const inputs = step1.querySelectorAll('input[required]');
        let valid = true;
        inputs.forEach(input => {
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

    // Función GPS
    function getLocation(btn) {
        const originalHtml = btn.innerHTML;
        
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Obteniendo ubicación...';
        btn.disabled = true;

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    document.getElementById('latitud').value = position.coords.latitude.toFixed(6);
                    document.getElementById('longitud').value = position.coords.longitude.toFixed(6);
                    
                    btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Ubicación Capturada';
                    btn.classList.replace('btn-outline-primary', 'btn-success');
                    btn.disabled = false;
                    
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.replace('btn-success', 'btn-outline-primary');
                    }, 3000);
                },
                (error) => {
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
