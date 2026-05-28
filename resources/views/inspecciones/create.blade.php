@extends('layouts.app')

@section('title', 'Nuevo Dictamen SENASICA')
@section('header_title', 'Registro de Dictamen')
@section('header_subtitle', 'Complete todos los campos del formato oficial')
@section('back_url', route('inspecciones.index'))

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    #reader {
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
    }
    #scannerModal .modal-body {
        padding: 0;
    }
    .bg-success-soft {
        background-color: rgba(25, 135, 84, 0.1) !important;
    }
    .bg-danger-soft {
        background-color: rgba(220, 53, 69, 0.1) !important;
    }
    .bg-warning-soft {
        background-color: rgba(255, 193, 7, 0.1) !important;
    }
    .accordion-button:not(.collapsed) {
        background-color: rgba(13, 110, 253, 0.05) !important;
        color: #0d6efd !important;
        box-shadow: none !important;
    }
    .accordion-button:focus {
        box-shadow: none !important;
        border-color: rgba(0,0,0,.125) !important;
    }
    .ts-control {
        border-radius: 12px !important;
        padding: 0.6rem 1rem !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: none !important;
        font-size: 0.95rem !important;
    }
    .ts-dropdown .option {
        padding: 8px 12px;
    }
    .ts-dropdown .option .title {
        font-weight: bold;
        display: block;
        color: #1e293b;
    }
    .ts-dropdown .option .subtitle {
        font-size: 0.75rem;
        color: #64748b;
    }
    .sexo-select {
        min-width: 70px !important;
        text-align: center !important;
        text-align-last: center !important;
        padding-left: 0.5rem !important;
        padding-right: 1.5rem !important;
        background-position: right 0.4rem center !important;
    }
    .floating-add-btn {
        position: fixed;
        bottom: 145px;
        right: 20px;
        width: 60px;
        height: 60px;
        border-radius: 50% !important;
        z-index: 1040;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 16px rgba(37, 99, 235, 0.4) !important;
        transition: transform 0.2s, background-color 0.2s;
    }
    .floating-add-btn:active {
        transform: scale(0.9);
    }
</style>
@endsection

@section('content')
<script src="https://unpkg.com/html5-qrcode"></script>
@php
    $esDiaDeInyeccion = true;
    if ($visita && $visita->fecha_programada) {
        $esDiaDeInyeccion = now()->startOfDay()->gte($visita->fecha_programada->startOfDay());
    }
    $bloquearBotonFinalizar = !$esDiaDeInyeccion;

    $tipoPruebaValue = old('tipo_prueba', 'PPC');
    if (in_array($tipoPruebaValue, ['P.P.C.', 'PPC'], true)) {
        $tipoPruebaValue = 'PPC';
    } elseif (in_array($tipoPruebaValue, ['P.C.C.', 'PCC'], true)) {
        $tipoPruebaValue = 'PCC';
    }
    
    $textoBotonBloqueado = 'Finalizar Inyección (Bloqueado)';
    $tooltipBotonBloqueado = 'Se habilitará el día de la inyección (' . ($visita && $visita->fecha_programada ? $visita->fecha_programada->format('d/m/Y') : '') . ')';
@endphp
<div class="row justify-content-center">
    <div class="col-lg-11">
        <form action="{{ route('inspecciones.store') }}" method="POST" id="formInspeccion">
            @csrf
            
            <div class="accordion mb-4 shadow-sm" id="dictamenAccordion">
                
                <!-- I: PROPIETARIO -->
                <div class="accordion-item border-0 rounded mb-3 overflow-hidden">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button collapsed bg-white text-dark fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                            <div class="d-flex align-items-center justify-content-between w-100 pe-3">
                                <span><i class="bi bi-person-badge-fill text-primary me-2"></i> I: PROPIETARIO</span>
                                <span id="status-sec-1" class="badge rounded-pill"></span>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#dictamenAccordion">
                        <div class="accordion-body p-4 bg-white">
                            <input type="hidden" name="visita_id" value="{{ $visita->id ?? '' }}">
                            <input type="hidden" name="fecha" id="fecha_hidden" value="{{ date('Y-m-d') }}">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Seleccionar Productor</label>
                                <select id="productor_id" class="form-select @if(isset($visita)) bg-light @endif" required @if(isset($visita)) readonly style="pointer-events: none;" tabindex="-1" @endif>
                                    <option value="">Seleccione un productor...</option>
                                    @foreach($productoresModel as $prod)
                                        <option value="{{ $prod->id }}" 
                                                data-apellido_paterno="{{ $prod->apellido_paterno }}"
                                                data-apellido_materno="{{ $prod->apellido_materno }}"
                                                data-nombre="{{ $prod->nombre }}"
                                                data-telefono="{{ $prod->telefono }}"
                                                data-domicilio="{{ $prod->domicilio }}"
                                                data-municipio="{{ $prod->municipio }}"
                                                data-localidad="{{ $prod->localidad }}"
                                                data-estado="{{ $prod->estado }}"
                                                data-email="{{ $prod->email }}"
                                                data-upp="{{ $prod->upp }}" 
                                                data-curp="{{ $prod->curp }}"
                                                {{ (old('productor_id', $selected_productor_id ?? '') == $prod->id) ? 'selected' : '' }}>
                                            {{ $prod->nombre }} {{ $prod->apellido_paterno }} {{ $prod->apellido_materno }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">APELLIDO PATERNO</label>
                                    <input type="text" id="prop_apellido_paterno" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">APELLIDO MATERNO</label>
                                    <input type="text" id="prop_apellido_materno" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">NOMBRE(S)</label>
                                    <input type="text" id="prop_nombre" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">TELÉFONO</label>
                                    <input type="text" id="prop_telefono" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">DOMICILIO</label>
                                    <input type="text" id="prop_domicilio" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">MUNICIPIO</label>
                                    <input type="text" id="prop_municipio" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">LOCALIDAD/POBLACIÓN</label>
                                    <input type="text" id="prop_localidad" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">ESTADO</label>
                                    <input type="text" id="prop_estado" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">CORREO ELECTRÓNICO</label>
                                    <input type="text" id="prop_email" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold text-muted">Fecha Actual</label>
                                    <input type="date" id="fecha_visible" class="form-control bg-light" value="{{ date('Y-m-d') }}" readonly style="pointer-events: none;" tabindex="-1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Folio Dictamen</label>
                                    <input type="text" name="folio" class="form-control fw-bold text-primary" placeholder="Opcional (se puede dejar en blanco)" value="{{ old('folio') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- II: UNIDAD DE PRODUCCIÓN -->
                <div class="accordion-item border-0 rounded mb-3 overflow-hidden">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed bg-white text-dark fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            <div class="d-flex align-items-center justify-content-between w-100 pe-3">
                                <span><i class="bi bi-geo-alt-fill text-info me-2"></i> II: UNIDAD DE PRODUCCIÓN</span>
                                <span id="status-sec-2" class="badge rounded-pill"></span>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#dictamenAccordion">
                        <div class="accordion-body p-4 bg-white">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Seleccionar Predio</label>
                                <select id="predio_id" name="predio_id" class="form-select @if(isset($visita)) bg-light @endif" required @if(isset($visita)) readonly style="pointer-events: none;" tabindex="-1" @endif>
                                    <option value="">Primero seleccione un productor...</option>
                                </select>
                                @if(isset($visita))
                                    <div class="form-text text-primary small"><i class="bi bi-info-circle me-1"></i> Vinculado a la visita del {{ $visita->fecha_programada->format('d/m/Y') }}</div>
                                @endif
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">NOMBRE DE LA UNIDAD DE PRODUCCIÓN O PREDIO</label>
                                    <input type="text" id="up_nombre" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small text-muted mb-0">COORDENADAS</label>
                                        <button type="button" id="btn_obtener_coordenadas" class="btn btn-link btn-xs p-0 text-decoration-none fw-bold" style="font-size: 0.75rem; display: none;" onclick="obtenerCoordenadasActuales()">
                                            <i class="bi bi-geo-alt-fill text-primary"></i> Obtener GPS
                                        </button>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light text-muted small px-2">Lat</span>
                                        <input type="text" id="up_latitud" class="form-control bg-light" readonly tabindex="-1" style="font-size: 0.85rem;">
                                        <span class="input-group-text bg-light text-muted small px-2">Lon</span>
                                        <input type="text" id="up_longitud" class="form-control bg-light" readonly tabindex="-1" style="font-size: 0.85rem;">
                                        <button type="button" id="btn_guardar_coordenadas" class="btn btn-success" style="display: none;" onclick="guardarCoordenadasPredio()" title="Actualizar coordenadas en la base de datos">
                                            <i class="bi bi-check-circle-fill"></i> Guardar
                                        </button>
                                    </div>
                                    <div id="coordenadas_status" class="small text-muted mt-1" style="font-size: 0.75rem; display: none;"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">CLAVE UPP / PSG</label>
                                    <input type="text" id="up_upp" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">DOMICILIO</label>
                                    <input type="text" id="up_domicilio" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">MUNICIPIO</label>
                                    <input type="text" id="up_municipio" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">LOCALIDAD/POBLACIÓN</label>
                                    <input type="text" id="up_localidad" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">ESTADO</label>
                                    <input type="text" id="up_estado" class="form-control bg-light" readonly tabindex="-1">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- III: DE LA PRUEBA -->
                <div class="accordion-item border-0 rounded mb-3 overflow-hidden">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed bg-white text-dark fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            <div class="d-flex align-items-center justify-content-between w-100 pe-3">
                                <span><i class="bi bi-heartpulse-fill text-success me-2"></i> III: DE LA PRUEBA</span>
                                <span id="status-sec-3" class="badge rounded-pill"></span>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#dictamenAccordion">
                        <div class="accordion-body p-4 bg-white">
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="card border-0 shadow-none mb-4">
                                        <div class="card-header bg-white fw-bold border-bottom px-0">
                                            III: DATOS DE LA PRUEBA
                                        </div>
                                        <div class="card-body px-0">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">TIPO DE PRUEBA REALIZADA</label>
                                                    <select name="tipo_prueba" id="tipo_prueba_select" class="form-select" onchange="updateTipoPruebaFields()">
                                                        <option value="PCC" {{ $tipoPruebaValue === 'PCC' ? 'selected' : '' }}>Prueba Cervical Comparativa (PCC)</option>
                                                        <option value="PPC" {{ $tipoPruebaValue === 'PPC' ? 'selected' : '' }}>Prueba de Pliegue Caudal (PPC)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Motivo de la Prueba</label>
                                                    <input type="hidden" name="motivo_prueba" id="motivo_prueba_hidden" value="{{ old('motivo_prueba') }}" required>
                                                    <select id="motivo_prueba_select" class="form-select" onchange="updateMotivoPrueba()">
                                                        <option value="">Seleccione un motivo...</option>
                                                        <option value="Seguimiento">Seguimiento</option>
                                                        <option value="Movilización">Movilización</option>
                                                        <option value="Barrido">Barrido</option>
                                                        <option value="Buffer">Buffer</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <label class="form-label text-success fw-bold"><i class="bi bi-geo-fill"></i> Inyección</label>
                                                    <div class="input-group">
                                                        <input type="date" id="fecha_inyeccion" name="fecha_inyeccion" class="form-control" value="{{ old('fecha_inyeccion', $visita && $visita->fecha_programada ? $visita->fecha_programada->format('Y-m-d') : date('Y-m-d')) }}" required>
                                                        <input type="time" id="hora_inyeccion" name="hora_inyeccion" class="form-control" value="{{ old('hora_inyeccion', date('H:i')) }}" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-danger fw-bold"><i class="bi bi-eye-fill"></i> Lectura</label>
                                                    <div class="input-group">
                                                        <input type="date" id="fecha_lectura" name="fecha_lectura" class="form-control bg-light" value="{{ old('fecha_lectura') }}" readonly style="pointer-events: none;" tabindex="-1" required>
                                                        <input type="time" id="hora_lectura" name="hora_lectura" class="form-control" value="{{ old('hora_lectura', date('H:i')) }}" required>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Función Zootécnica</label>
                                                    <select name="funcion_zootecnica" class="form-select">
                                                        <option value="Carne">Carne</option>
                                                        <option value="Leche">Leche</option>
                                                        <option value="Mixto">Mixto</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Fecha Expiración (Vigencia)</label>
                                                    <input type="date" name="vigencia_fecha" class="form-control">
                                                </div>

                                                <!-- Campos Avanzados SENASICA -->
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold small">Fecha Prueba Anterior</label>
                                                    <input type="date" name="fecha_prueba_anterior" id="fecha_prueba_anterior" class="form-control form-control-sm @if($tipoPruebaValue !== 'PCC') bg-light @endif" @if($tipoPruebaValue !== 'PCC') disabled @endif>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold small">Dictamen Anterior No.</label>
                                                    <input type="text" name="dictamen_anterior_no" id="dictamen_anterior_no" class="form-control form-control-sm @if($tipoPruebaValue !== 'PCC') bg-light @endif" @if($tipoPruebaValue !== 'PCC') disabled @endif>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold small">Exención No.</label>
                                                    <input type="text" name="exencion_no" id="exencion_no" class="form-control form-control-sm @if($tipoPruebaValue !== 'PCC') bg-light @endif" @if($tipoPruebaValue !== 'PCC') disabled @endif>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold small">Fecha Exención</label>
                                                    <input type="date" name="exencion_fecha" id="exencion_fecha" class="form-control form-control-sm @if($tipoPruebaValue !== 'PCC') bg-light @endif" @if($tipoPruebaValue !== 'PCC') disabled @endif>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold small">Constancia Hato Libre No.</label>
                                                    <input type="text" name="hato_libre_no" id="hato_libre_no" class="form-control form-control-sm @if($tipoPruebaValue !== 'PCC') bg-light @endif" @if($tipoPruebaValue !== 'PCC') disabled @endif>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold small">Fecha Hato Libre</label>
                                                    <input type="date" name="hato_libre_fecha" id="hato_libre_fecha" class="form-control form-control-sm @if($tipoPruebaValue !== 'PCC') bg-light @endif" @if($tipoPruebaValue !== 'PCC') disabled @endif>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="card border-0 shadow-none mb-4">
                                        <div class="card-header bg-white fw-bold border-bottom px-0">
                                            CENSO GANADERO (Población Actual)
                                        </div>
                                        <div class="card-body px-0">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <label class="form-label small mb-1">Sementales</label>
                                                    <input type="number" name="sementales" class="form-control form-control-sm bg-light" value="{{ old('sementales', 0) }}" readonly style="pointer-events: none;" tabindex="-1">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-1">Vacas</label>
                                                    <input type="number" name="vacas" class="form-control form-control-sm bg-light" value="{{ old('vacas', 0) }}" readonly style="pointer-events: none;" tabindex="-1">
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small mb-1">Vaquillas</label>
                                                    <input type="number" name="vaquillas" class="form-control form-control-sm bg-light" value="{{ old('vaquillas', 0) }}" readonly style="pointer-events: none;" tabindex="-1">
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small mb-1">Becerras</label>
                                                    <input type="number" name="becerras" class="form-control form-control-sm bg-light" value="{{ old('becerras', 0) }}" readonly style="pointer-events: none;" tabindex="-1">
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small mb-1">Becerros</label>
                                                    <input type="number" name="becerros" class="form-control form-control-sm bg-light" value="{{ old('becerros', 0) }}" readonly style="pointer-events: none;" tabindex="-1">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- IV: Resultados Individuales -->
                <div class="accordion-item border-0 rounded mb-3 overflow-hidden">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed bg-white text-dark fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            <div class="d-flex align-items-center justify-content-between w-100 pe-3">
                                <span><i class="bi bi-list-columns-reverse text-warning me-2"></i> IV: RESULTADOS INDIVIDUALES</span>
                                <span id="status-sec-4" class="badge rounded-pill"></span>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#dictamenAccordion">
                        <div class="accordion-body p-0 bg-white">
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light flex-wrap gap-2">
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-primary rounded-pill px-3 py-2" id="counter-total">
                                        <i class="bi bi-hash me-1"></i> Total: <strong>1</strong>
                                    </span>
                                    <span class="badge bg-success rounded-pill px-3 py-2" id="counter-con-arete">
                                        <i class="bi bi-check-circle me-1"></i> Con Arete: <strong>0</strong>
                                    </span>
                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2" id="counter-sa-pending">
                                        <i class="bi bi-exclamation-triangle me-1"></i> SA sin definir: <strong>0</strong>
                                    </span>
                                    <span class="badge bg-info rounded-pill px-3 py-2" id="counter-sa-defined">
                                        <i class="bi bi-tag me-1"></i> SA definidos: <strong>0</strong>
                                    </span>
                                    <span class="badge bg-danger rounded-pill px-3 py-2" id="counter-resultados-pendientes">
                                        <i class="bi bi-exclamation-circle me-1"></i> Sin definir resultado: <strong>1</strong>
                                    </span>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" onclick="addAnimal()">
                                    <i class="bi bi-plus-lg me-1"></i> Añadir Animal
                                </button>
                                <!-- Botón Flotante para Añadir Animal en Móvil -->
                                <button type="button" class="btn btn-primary floating-add-btn d-lg-none shadow-lg" onclick="addAnimal()" title="Añadir Animal">
                                    <i class="bi bi-plus-lg fs-3"></i>
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0 table-mobile-cards" id="tablaAnimales">
                                    <thead class="bg-light text-center small fw-bold">
                                        <tr>
                                            <th style="width: 220px;">Identificación (Arete)</th>
                                            <th style="width: 180px;">Tipo Arete</th>
                                            <th style="width: 120px;">Edad (m)</th>
                                            <th style="width: 120px;">Raza</th>
                                            <th style="width: 100px;">Sexo</th>
                                            <th style="width: 80px;">Fierro</th>
                                            <th style="width: 140px;">Resultado</th>
                                            <th>Observaciones</th>
                                            <th style="width: 40px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                             <td data-label="Identificador">
                                                 <div class="input-group input-group-sm">
                                                     <input type="text" name="animales[0][identificador]" class="form-control arete-input" id="arete_0" placeholder="SINIIGA o SA" required onchange="buscarDatosAnimal(this)">
                                                     <button class="btn btn-primary" type="button" onclick="startScanner(0)">
                                                         <i class="bi bi-camera"></i>
                                                     </button>
                                                 </div>
                                             </td>
                                             <td data-label="Tipo Arete" class="tipo-arete-cell">
                                                 <select name="animales[0][tipo_arete]" class="form-select form-select-sm tipo-arete-select d-none" style="min-width: 180px;">
                                                     <option value="">—</option>
                                                     <option value="IN">IN</option>
                                                     <option value="RA">RA</option>
                                                 </select>
                                                 <input type="hidden" name="animales[0][tipo_arete_default]" value="SINIIGA" class="tipo-arete-default">
                                             </td>
                                             <td data-label="Edad (meses)"><input type="number" name="animales[0][edad_meses]" class="form-control form-control-sm" placeholder="Meses" inputmode="numeric"></td>
                                             <td data-label="Raza"><input type="text" name="animales[0][raza]" class="form-control form-control-sm" placeholder="Raza"></td>
                                             <td data-label="Sexo">
                                                 <select name="animales[0][sexo]" class="form-select form-select-sm sexo-select">
                                                     <option value="H">H</option>
                                                     <option value="M">M</option>
                                                 </select>
                                             </td>
                                             <td data-label="Fierro" class="text-center">
                                                 <input type="checkbox" name="animales[0][fierro]" value="Si" class="form-check-input">
                                             </td>
                                             <td data-label="Resultado">
                                                 <select name="animales[0][resultado_disabled]" class="form-select form-select-sm fw-bold text-center" disabled style="-webkit-appearance: none; -moz-appearance: none; appearance: none; background-color: #f8f9fa; opacity: 0.8;">
                                                     <option value="Pendiente" class="text-secondary" selected>Pendiente</option>
                                                     <option value="Negativo" class="text-success">Negativo</option>
                                                     <option value="Positivo" class="text-danger">Positivo</option>
                                                     <option value="Sospechoso" class="text-warning">Sospechoso</option>
                                                 </select>
                                                 <input type="hidden" name="animales[0][resultado]" value="Pendiente">
                                             </td>
                                             <td data-label="Obs"><input type="text" name="animales[0][observaciones]" class="form-control form-control-sm"></td>
                                             <td class="text-center">
                                                 <i class="bi bi-lock text-muted"></i>
                                             </td>
                                         </tr>
                                     </tbody>
                                 </table>
                             </div>
                             <div class="card-footer bg-light py-3 d-sm-none text-center">
                                 <small class="text-muted"><i class="bi bi-arrow-left-right me-1"></i> Deslice para ver todos los campos</small>
                             </div>
                        </div>
                    </div>
                </div>

            </div>

             <div class="d-flex justify-content-end gap-3 mb-5 d-none d-lg-flex">
                 <input type="hidden" name="estado" id="form_estado" value="sincronizado">
                 <input type="hidden" name="inyeccion_realizada" id="inyeccion_realizada" value="0">
                 <a href="{{ route('inspecciones.index') }}" class="btn btn-light px-4 rounded-pill">Cancelar</a>
                 <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" onclick="saveAsDraft()">
                     <i class="bi bi-save me-1"></i> Guardar Borrador
                 </button>
                 <!-- Botón Finalizar Desktop (Activo) -->
                 <button type="submit" id="btn-finalizar-desktop-activo" class="btn btn-primary px-5 rounded-pill shadow-sm {{ $bloquearBotonFinalizar ? 'd-none' : '' }}">
                     <i class="bi bi-check-circle me-1"></i> Finalizar Inyección
                 </button>
                 
                 <!-- Botón Finalizar Desktop (Bloqueado) -->
                 <button type="button" id="btn-finalizar-desktop-bloqueado" class="btn btn-primary px-5 rounded-pill shadow-sm {{ !$bloquearBotonFinalizar ? 'd-none' : '' }}" disabled style="opacity: 0.65; cursor: not-allowed;" title="{{ $tooltipBotonBloqueado }}">
                     <i class="bi bi-lock-fill me-1"></i> <span id="btn-finalizar-desktop-bloqueado-text">{{ $textoBotonBloqueado }}</span>
                 </button>
             </div>

             <!-- Acciones Flotantes para Móvil -->
             <div class="sticky-mobile-actions d-lg-none">
                 <button type="button" class="btn btn-light border flex-grow-1" onclick="saveAsDraft()">
                     <i class="bi bi-save"></i> Borrador
                 </button>
                 <!-- Botón Finalizar Móvil (Activo) -->
                 <button type="submit" id="btn-finalizar-movil-activo" class="btn btn-primary flex-grow-2 w-100 {{ $bloquearBotonFinalizar ? 'd-none' : '' }}">
                     <i class="bi bi-check-circle me-1"></i> Finalizar
                 </button>
                 
                 <!-- Botón Finalizar Móvil (Bloqueado) -->
                 <button type="button" id="btn-finalizar-movil-bloqueado" class="btn btn-secondary flex-grow-2 w-100 {{ !$bloquearBotonFinalizar ? 'd-none' : '' }}" disabled style="opacity: 0.65; cursor: not-allowed;" title="{{ $tooltipBotonBloqueado }}">
                     <i class="bi bi-lock-fill me-1"></i> <span id="btn-finalizar-movil-bloqueado-text">{{ $textoBotonBloqueado }}</span>
                 </button>
             </div>
         </form>
     </div>
 </div>

 <!-- Modal para el Escáner -->
 <div class="modal fade" id="scannerModal" tabindex="-1" aria-hidden="true">
     <div class="modal-dialog modal-fullscreen-sm-down modal-dialog-centered">
         <div class="modal-content border-0">
             <div class="modal-header bg-dark text-white border-0">
                 <h5 class="modal-title"><i class="bi bi-qr-code-scan me-2"></i>Escanear Arete</h5>
                 <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="stopScanner()"></button>
             </div>
             <div class="modal-body p-0 bg-dark position-relative" style="min-height: 400px;">
                 <div id="reader" style="width: 100%;"></div>
                 <div class="position-absolute bottom-0 start-0 end-0 p-3 text-center bg-dark bg-opacity-50 text-white">
                     <p class="small mb-0">Encuadre el código de barras del arete</p>
                 </div>
             </div>
             <div class="modal-footer bg-dark border-0">
                 <button type="button" class="btn btn-outline-light w-100" data-bs-dismiss="modal" onclick="stopScanner()">Cancelar</button>
             </div>
         </div>
     </div>
 </div>

 <style>
    .is-loading { opacity: 0.5; pointer-events: none; }
    .arete-input.is-valid { border-color: #22c55e !important; background-color: #f0fdf4 !important; }
    @media (max-width: 991px) {
        .modal-fullscreen-sm-down .modal-content { height: 100%; border-radius: 0; }
    }
 </style>
@endsection

 @section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set local device date and time on first load (if not set by old() or server)
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const localDate = `${year}-${month}-${day}`;
        
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const localTime = `${hours}:${minutes}`;

        const fechaInput = document.getElementById('fecha_visible');
        const fechaHidden = document.getElementById('fecha_hidden');
        const fechaInyeccionInput = document.getElementById('fecha_inyeccion');
        const horaInyeccionInput = document.getElementById('hora_inyeccion');
        const horaLecturaInput = document.getElementById('hora_lectura');
        const fechaLecturaInput = document.getElementById('fecha_lectura');

        const isFreshLoad = @json(!old('_token'));
        const fechaProgramadaVal = @json($visita && $visita->fecha_programada ? $visita->fecha_programada->format('Y-m-d') : null);
        if (isFreshLoad) {
            if (fechaInput) fechaInput.value = localDate;
            if (fechaHidden) fechaHidden.value = localDate;
            if (fechaInyeccionInput) {
                fechaInyeccionInput.value = fechaProgramadaVal ? fechaProgramadaVal : localDate;
            }
            if (horaInyeccionInput) horaInyeccionInput.value = localTime;
            if (horaLecturaInput) horaLecturaInput.value = localTime;
        }

        function calcularFechaLectura() {
            const fInyVal = fechaInyeccionInput ? fechaInyeccionInput.value : '';
            if (fInyVal && fechaLecturaInput) {
                const parts = fInyVal.split('-');
                const date = new Date(parts[0], parts[1] - 1, parts[2]);
                date.setDate(date.getDate() + 3);
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                fechaLecturaInput.value = `${y}-${m}-${d}`;
            }
        }

        if (fechaInyeccionInput) {
            fechaInyeccionInput.addEventListener('change', calcularFechaLectura);
            fechaInyeccionInput.addEventListener('input', calcularFechaLectura);
        }
        calcularFechaLectura();

        const productores = @json($productores);
        const productorSelect = document.getElementById('productor_id');
        const predioSelect = document.getElementById('predio_id');
        const initialPredioId = "{{ old('predio_id', $selected_predio_id ?? '') }}";

        function populatePredios(productorId, selectedPredioId = null) {
            predioSelect.innerHTML = '';

            if (!productorId) {
                predioSelect.innerHTML = '<option value="">Primero seleccione un productor...</option>';
                predioSelect.disabled = true;
                populatePredioFields(null, null);
                return;
            }

            const selectedProductor = productores.find(p => p.id == productorId);
            if (selectedProductor && selectedProductor.predios && selectedProductor.predios.length > 0) {
                predioSelect.innerHTML = '<option value="">Seleccione un predio...</option>';
                selectedProductor.predios.forEach(predio => {
                    const option = document.createElement('option');
                    option.value = predio.id;
                    option.textContent = `${predio.nombre_rancho} (${predio.localidad || 'Sin localidad'})`;
                    if (selectedPredioId && predio.id == selectedPredioId) {
                        option.selected = true;
                    }
                    predioSelect.appendChild(option);
                });
                predioSelect.disabled = false;

                // Si solo hay un predio y no hay un predio preseleccionado, seleccionarlo automáticamente
                if (selectedProductor.predios.length === 1 && !selectedPredioId) {
                    predioSelect.selectedIndex = 1;
                }
            } else {
                predioSelect.innerHTML = '<option value="">Este productor no tiene predios registrados</option>';
                predioSelect.disabled = true;
            }

            // Auto-populate predio detail fields whenever a predio ends up selected
            if (predioSelect.value) {
                populatePredioFields(predioSelect.value, productorId);
            } else {
                populatePredioFields(null, null);
            }
        }

        function populateProductorFields(productorId) {
            const sel = productorSelect;
            const opt = sel.options[sel.selectedIndex];
            if (!productorId || !opt || !opt.value) {
                ['prop_apellido_paterno','prop_apellido_materno','prop_nombre','prop_telefono','prop_domicilio','prop_municipio','prop_localidad','prop_estado','prop_email'].forEach(id => {
                    const el = document.getElementById(id); if(el) el.value = '';
                });
                return;
            }
            document.getElementById('prop_apellido_paterno').value = opt.dataset.apellido_paterno || '';
            document.getElementById('prop_apellido_materno').value = opt.dataset.apellido_materno || '';
            document.getElementById('prop_nombre').value = opt.dataset.nombre || '';
            document.getElementById('prop_telefono').value = opt.dataset.telefono || 'S/D';
            document.getElementById('prop_domicilio').value = opt.dataset.domicilio || '';
            document.getElementById('prop_municipio').value = opt.dataset.municipio || '';
            document.getElementById('prop_localidad').value = opt.dataset.localidad || '';
            document.getElementById('prop_estado').value = opt.dataset.estado || '';
            document.getElementById('prop_email').value = opt.dataset.email || 'S/D';
        }

        function populatePredioFields(predioId, productorId) {
            const gpsBtn = document.getElementById('btn_obtener_coordenadas');
            const saveBtn = document.getElementById('btn_guardar_coordenadas');
            const statusEl = document.getElementById('coordenadas_status');

            if (!predioId || !productorId) {
                ['up_nombre','up_latitud','up_longitud','up_upp','up_domicilio','up_municipio','up_localidad','up_estado'].forEach(id => {
                    const el = document.getElementById(id); if(el) el.value = '';
                });
                if (gpsBtn) gpsBtn.style.display = 'none';
                if (saveBtn) saveBtn.style.display = 'none';
                if (statusEl) statusEl.style.display = 'none';
                return;
            }
            const selectedProductor = productores.find(p => p.id == productorId);
            if (!selectedProductor) return;
            const predio = selectedProductor.predios.find(pr => pr.id == predioId);
            if (!predio) return;
            document.getElementById('up_nombre').value = predio.nombre_rancho || '';
            document.getElementById('up_latitud').value = predio.latitud || '';
            document.getElementById('up_longitud').value = predio.longitud || '';
            document.getElementById('up_upp').value = predio.clave_unidad_produccion || '';
            document.getElementById('up_domicilio').value = predio.domicilio || '';
            document.getElementById('up_municipio').value = predio.municipio || '';
            document.getElementById('up_localidad').value = predio.localidad || '';
            // Predio doesn't have estado - use productor's estado
            const prodOpt = productorSelect.options[productorSelect.selectedIndex];
            document.getElementById('up_estado').value = (prodOpt && prodOpt.dataset.estado) ? prodOpt.dataset.estado : '';

            // Mostrar el botón de GPS al seleccionar el predio
            if (gpsBtn) gpsBtn.style.display = 'block';
            if (saveBtn) saveBtn.style.display = 'none';
            if (statusEl) statusEl.style.display = 'none';
        }

        productorSelect.addEventListener('change', function() {
            populateProductorFields(this.value);
            populatePredios(this.value);
            if (typeof validateSections === 'function') validateSections();
        });

        predioSelect.addEventListener('change', function() {
            populatePredioFields(this.value, productorSelect.value);
            if (typeof validateSections === 'function') validateSections();
        });

        // Inicializar con datos preexistentes
        if (productorSelect.value) {
            populateProductorFields(productorSelect.value);
            populatePredios(productorSelect.value, initialPredioId);
        }
    });
</script>
<script>
    let animalCount = 1;
    let html5QrCode;
    let currentInputId = null;

    function actualizarCenso() {
        let sementales = 0;
        let vacas = 0;
        let vaquillas = 0;
        let becerras = 0;
        let becerros = 0;

        const rows = document.querySelectorAll('#tablaAnimales tbody tr');
        rows.forEach(row => {
            const areteInput = row.querySelector('.arete-input');
            if (!areteInput || !areteInput.value.trim()) return;

            const edadInput = row.querySelector('input[name*="[edad_meses]"]');
            const sexoSelect = row.querySelector('select[name*="[sexo]"]');
            
            if (!edadInput || !sexoSelect) return;

            const edad = parseInt(edadInput.value) || 0;
            const sexo = sexoSelect.value;

            if (sexo === 'M') {
                if (edad < 12) {
                    becerros++;
                } else {
                    sementales++;
                }
            } else if (sexo === 'H') {
                if (edad < 12) {
                    becerras++;
                } else if (edad >= 12 && edad < 24) {
                    vaquillas++;
                } else {
                    vacas++;
                }
            }
        });

        document.querySelector('input[name="sementales"]').value = sementales;
        document.querySelector('input[name="vacas"]').value = vacas;
        document.querySelector('input[name="vaquillas"]').value = vaquillas;
        document.querySelector('input[name="becerras"]').value = becerras;
        document.querySelector('input[name="becerros"]').value = becerros;

        actualizarContadores();
    }

    function actualizarContadores() {
        let total = 0;
        let conArete = 0;
        let saPending = 0;
        let saDefined = 0;
        let pendingResults = 0;

        const rows = document.querySelectorAll('#tablaAnimales tbody tr');
        rows.forEach(row => {
            total++;
            const areteInput = row.querySelector('.arete-input');
            const areteVal = areteInput ? areteInput.value.trim() : '';

            if (areteVal !== '') {
                if (areteVal.toUpperCase() === 'SA') {
                    const tipoAreteSelect = row.querySelector('.tipo-arete-select');
                    if (tipoAreteSelect && tipoAreteSelect.value !== '') {
                        saDefined++;
                    } else {
                        saPending++;
                    }
                } else {
                    conArete++;
                }
            }

            const resSelect = row.querySelector('select[name*="[resultado_disabled]"]') || row.querySelector('select[name*="[resultado]"]');
            const resHidden = row.querySelector('input[type="hidden"][name*="[resultado]"]');
            const resVal = resSelect ? resSelect.value : (resHidden ? resHidden.value : '');
            if (resVal === 'Pendiente' || resVal === '') {
                pendingResults++;
            }
        });

        const counterTotal = document.getElementById('counter-total');
        if (counterTotal) counterTotal.querySelector('strong').textContent = total;

        const counterConArete = document.getElementById('counter-con-arete');
        if (counterConArete) counterConArete.querySelector('strong').textContent = conArete;

        const counterSaPending = document.getElementById('counter-sa-pending');
        if (counterSaPending) counterSaPending.querySelector('strong').textContent = saPending;

        const counterSaDefined = document.getElementById('counter-sa-defined');
        if (counterSaDefined) counterSaDefined.querySelector('strong').textContent = saDefined;

        const counterResults = document.getElementById('counter-resultados-pendientes');
        if (counterResults) {
            counterResults.querySelector('strong').textContent = pendingResults;
            if (pendingResults > 0) {
                counterResults.className = 'badge bg-danger rounded-pill px-3 py-2';
            } else {
                counterResults.className = 'badge bg-success rounded-pill px-3 py-2';
            }
        }
    }

    function updateMotivoPrueba() {
        const select = document.getElementById('motivo_prueba_select').value;
        document.getElementById('motivo_prueba_hidden').value = select;
        validateSections();
    }

    function updateTipoPruebaFields() {
        const tipo = document.getElementById('tipo_prueba_select')?.value || 'PPC';
        const isPcc = tipo === 'PCC';

        const fieldNames = [
            'fecha_prueba_anterior',
            'dictamen_anterior_no',
            'exencion_no',
            'exencion_fecha',
            'hato_libre_no',
            'hato_libre_fecha'
        ];

        fieldNames.forEach((name) => {
            const field = document.querySelector(`[name="${name}"]`);
            if (!field) return;

            field.disabled = !isPcc;
            field.readOnly = !isPcc && field.tagName === 'INPUT';
            field.classList.toggle('bg-light', !isPcc);

            if (!isPcc) {
                field.value = '';
            }
        });

        validateSections();
    }

    function validateSections() {
        // Section 1: I PROPIETARIO
        const s1Productor = document.getElementById('productor_id') ? document.getElementById('productor_id').value : '';
        const s1FolioInput = document.querySelector('input[name="folio"]');
        const s1Folio = s1FolioInput ? s1FolioInput.value.trim() : '';
        const isS1Complete = !!s1Productor;
        
        const badge1 = document.getElementById('status-sec-1');
        if (badge1) {
            if (isS1Complete) {
                badge1.className = 'badge bg-success-soft text-success border border-success rounded-pill ms-2 fw-semibold';
                badge1.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Completo';
            } else {
                badge1.className = 'badge bg-danger-soft text-danger border border-danger rounded-pill ms-2 fw-semibold';
                badge1.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i> Faltan Datos';
            }
        }

        // Section 2: II UNIDAD DE PRODUCCIÓN
        const s2Predio = document.getElementById('predio_id') ? document.getElementById('predio_id').value : '';
        const isS2Complete = s1Productor && s2Predio;
        
        const badge2 = document.getElementById('status-sec-2');
        if (badge2) {
            if (isS2Complete) {
                badge2.className = 'badge bg-success-soft text-success border border-success rounded-pill ms-2 fw-semibold';
                badge2.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Completo';
            } else {
                badge2.className = 'badge bg-danger-soft text-danger border border-danger rounded-pill ms-2 fw-semibold';
                badge2.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i> Faltan Datos';
            }
        }
        
        // Section 3: III DE LA PRUEBA
        const s3MotivoInput = document.querySelector('input[name="motivo_prueba"]');
        const s3Motivo = s3MotivoInput ? s3MotivoInput.value.trim() : '';
        const s3HoraInyInput = document.querySelector('input[name="hora_inyeccion"]');
        const s3HoraIny = s3HoraInyInput ? s3HoraInyInput.value : '';
        const s3FechaLectInput = document.querySelector('input[name="fecha_lectura"]');
        const s3FechaLect = s3FechaLectInput ? s3FechaLectInput.value : '';
        const s3HoraLectInput = document.querySelector('input[name="hora_lectura"]');
        const s3HoraLect = s3HoraLectInput ? s3HoraLectInput.value : '';
        const s3VigenciaInput = document.querySelector('input[name="vigencia_fecha"]');
        const s3Vigencia = s3VigenciaInput ? s3VigenciaInput.value : '';
        const isS3Complete = s3Motivo && s3HoraIny && s3FechaLect && s3HoraLect;
        
        const badge3 = document.getElementById('status-sec-3');
        if (badge3) {
            if (isS3Complete) {
                badge3.className = 'badge bg-success-soft text-success border border-success rounded-pill ms-2 fw-semibold';
                badge3.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Completo';
            } else {
                badge3.className = 'badge bg-danger-soft text-danger border border-danger rounded-pill ms-2 fw-semibold';
                badge3.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i> Faltan Datos';
            }
        }
        
        // Section 4: IV RESULTADOS
        const areteInputs = document.querySelectorAll('.arete-input');
        let isS4Complete = areteInputs.length > 0;
        areteInputs.forEach(input => {
            if (!input.value.trim()) {
                isS4Complete = false;
            }
        });
        
        const badge4 = document.getElementById('status-sec-4');
        if (badge4) {
            if (isS4Complete) {
                badge4.className = 'badge bg-success-soft text-success border border-success rounded-pill ms-2 fw-semibold';
                badge4.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Completo';
            } else {
                badge4.className = 'badge bg-danger-soft text-danger border border-danger rounded-pill ms-2 fw-semibold';
                badge4.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i> Faltan Aretes';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateTipoPruebaFields();
        updateMotivoPrueba();

        const tabla = document.getElementById('tablaAnimales');
        if (tabla) {
            tabla.addEventListener('input', function(e) {
                if (e.target.name && (e.target.name.includes('[edad_meses]') || e.target.name.includes('[identificador]'))) {
                    actualizarCenso();
                }
            });
            tabla.addEventListener('change', function(e) {
                if (e.target.name && (e.target.name.includes('[edad_meses]') || e.target.name.includes('[sexo]') || e.target.name.includes('[tipo_arete]') || e.target.name.includes('[identificador]'))) {
                    actualizarCenso();
                }
            });
        }
        actualizarCenso();

        const form = document.getElementById('formInspeccion');
        if (form) {
            form.addEventListener('input', validateSections);
            form.addEventListener('change', validateSections);
            
            form.addEventListener('submit', function(e) {
                const estado = document.getElementById('form_estado').value;
                if (estado === 'sincronizado') {
                    e.preventDefault();
                    if (confirm("Fase de Inyección: El dictamen se guardará como BORRADOR y la visita se marcará con inyección realizada.\n\nPodrá ingresar los resultados en la fase de lectura (72 horas después).\n\n¿Desea continuar?")) {
                        saveAsDraft('1');
                    }
                }
            });
        }
        setTimeout(validateSections, 500);

        // Also listen for folio changes specifically
        const folioInput = document.querySelector('input[name="folio"]');
        if (folioInput) {
            folioInput.addEventListener('input', validateSections);
        }
    });

     function addAnimal() {
         const tbody = document.querySelector('#tablaAnimales tbody');
         const row = `
             <tr>
                 <td data-label="Identificador">
                     <div class="input-group input-group-sm">
                         <input type="text" name="animales[${animalCount}][identificador]" class="form-control arete-input" id="arete_${animalCount}" placeholder="SINIIGA o SA" required onchange="buscarDatosAnimal(this)">
                         <button class="btn btn-primary" type="button" onclick="startScanner(${animalCount})">
                             <i class="bi bi-camera"></i>
                         </button>
                     </div>
                 </td>
                 <td data-label="Tipo Arete" class="tipo-arete-cell">
                    <select name="animales[${animalCount}][tipo_arete]" class="form-select form-select-sm tipo-arete-select d-none" style="min-width: 180px;">
                        <option value="">—</option>
                        <option value="IN">IN</option>
                        <option value="RA">RA</option>
                    </select>
                     <input type="hidden" name="animales[${animalCount}][tipo_arete_default]" value="SINIIGA" class="tipo-arete-default">
                 </td>
                 <td data-label="Edad (meses)"><input type="number" name="animales[${animalCount}][edad_meses]" class="form-control form-control-sm" placeholder="Meses" inputmode="numeric"></td>
                 <td data-label="Raza"><input type="text" name="animales[${animalCount}][raza]" class="form-control form-control-sm" placeholder="Raza"></td>
                 <td data-label="Sexo">
                     <select name="animales[${animalCount}][sexo]" class="form-select form-select-sm sexo-select">
                         <option value="H">H</option>
                         <option value="M">M</option>
                     </select>
                 </td>
                 <td data-label="Fierro" class="text-center">
                     <input type="checkbox" name="animales[${animalCount}][fierro]" value="Si" class="form-check-input">
                 </td>
                 <td data-label="Resultado">
                      <select name="animales[${animalCount}][resultado_disabled]" class="form-select form-select-sm fw-bold text-center" disabled style="-webkit-appearance: none; -moz-appearance: none; appearance: none; background-color: #f8f9fa; opacity: 0.8;">
                          <option value="Pendiente" class="text-secondary" selected>Pendiente</option>
                          <option value="Negativo" class="text-success">Negativo</option>
                          <option value="Positivo" class="text-danger">Positivo</option>
                          <option value="Sospechoso" class="text-warning">Sospechoso</option>
                      </select>
                      <input type="hidden" name="animales[${animalCount}][resultado]" value="Pendiente">
                 </td>
                 <td data-label="Obs"><input type="text" name="animales[${animalCount}][observaciones]" class="form-control form-control-sm"></td>
                 <td class="text-center" data-label="Quitar">
                     <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest('tr').remove(); actualizarCenso(); validateSections();">
                         <i class="bi bi-trash fs-5"></i>
                     </button>
                 </td>
             </tr>
         `;
         tbody.insertAdjacentHTML('beforeend', row);
         animalCount++;
         validateSections();
         
         // Scroll to the new animal on mobile
         if (window.innerWidth < 992) {
             const rows = tbody.querySelectorAll('tr');
             rows[rows.length - 1].scrollIntoView({ behavior: 'smooth' });
         }
     }

     function startScanner(inputIdSuffix) {
         currentInputId = `arete_${inputIdSuffix}`;
         const modal = new bootstrap.Modal(document.getElementById('scannerModal'));
         modal.show();

         html5QrCode = new Html5Qrcode("reader");
         const config = { fps: 20, qrbox: { width: 280, height: 180 } };

         html5QrCode.start(
             { facingMode: "environment" }, 
             config, 
             onScanSuccess
         ).catch(err => {
             console.error("Error al iniciar cámara:", err);
             alert("No se pudo acceder a la cámara. Verifique los permisos.");
         });
     }

     function onScanSuccess(decodedText, decodedResult) {
         if (currentInputId) {
             const input = document.getElementById(currentInputId);
             input.value = decodedText;
             
             // Feedback de éxito
             if (navigator.vibrate) navigator.vibrate(100);
             
             buscarDatosAnimal(input);
             stopScanner();
             const modal = bootstrap.Modal.getInstance(document.getElementById('scannerModal'));
             modal.hide();
         }
     }

     function saveAsDraft(inyeccionVal = '0') {
         document.getElementById('form_estado').value = 'borrador';
         document.getElementById('inyeccion_realizada').value = inyeccionVal;
         const form = document.getElementById('formInspeccion');
         const requiredElements = form.querySelectorAll('[required]');
         requiredElements.forEach(el => {
             if (el.name !== 'predio_id' && el.name !== 'folio') {
                 el.removeAttribute('required');
             }
         });
         form.submit();
     }

     function stopScanner() {
         if (html5QrCode && html5QrCode.isScanning) {
             html5QrCode.stop();
         }
     }

     function buscarDatosAnimal(input) {
          const numero = input.value.trim();
          const row = input.closest('tr');
          const tipoAreteSelect = row.querySelector('.tipo-arete-select');
          const tipoAreteDefault = row.querySelector('.tipo-arete-default');

          // Blank state -> hide tipo arete select and reset
          if (numero === '') {
              if (tipoAreteSelect) {
                  tipoAreteSelect.classList.add('d-none');
                  tipoAreteSelect.required = false;
                  tipoAreteSelect.value = '';
              }
              if (tipoAreteDefault) tipoAreteDefault.disabled = false;
              input.classList.remove('is-valid', 'is-invalid');
              actualizarCenso();
              return;
          }

          // Auto-detect "SA" (Sin Arete) → show tipo arete dropdown
          if (numero.toUpperCase() === 'SA' || numero.toUpperCase() === 'S/A') {
              input.value = 'SA';
              if (tipoAreteSelect) {
                  tipoAreteSelect.classList.remove('d-none');
                  tipoAreteSelect.required = true;
              }
              if (tipoAreteDefault) tipoAreteDefault.disabled = true;
              input.classList.remove('is-invalid');
              input.classList.add('is-valid');
              actualizarCenso();
              return;
          }

          // Default: hide dropdown until API call completes or fails
          if (tipoAreteSelect) {
              tipoAreteSelect.classList.add('d-none');
              tipoAreteSelect.required = false;
              tipoAreteSelect.value = '';
          }
          if (tipoAreteDefault) tipoAreteDefault.disabled = false;

          if (numero.length < 5) return;

          const edadInput = row.querySelector('input[name*="[edad_meses]"]');
          const razaInput = row.querySelector('input[name*="[raza]"]');
          const sexoSelect = row.querySelector('select[name*="[sexo]"]');

          input.classList.add('is-loading');

          fetch(`/api/buscar-arete/${numero}`)
              .then(response => response.json())
              .then(res => {
                  if (res.success) {
                      const data = res.data;
                      if (data.edad_meses) edadInput.value = data.edad_meses;
                      if (data.raza) razaInput.value = data.raza;
                      if (data.sexo) {
                          const s = data.sexo.charAt(0).toUpperCase();
                          sexoSelect.value = (s === 'H' || s === 'F') ? 'H' : 'M';
                      }
                      input.classList.remove('is-invalid');
                      input.classList.add('is-valid');

                      // Known tag -> keep hidden
                      if (tipoAreteSelect) {
                          tipoAreteSelect.classList.add('d-none');
                          tipoAreteSelect.required = false;
                          tipoAreteSelect.value = '';
                      }
                      if (tipoAreteDefault) tipoAreteDefault.disabled = false;
                  } else {
                      input.classList.remove('is-valid');
                      input.classList.add('is-invalid');

                      // Unknown tag -> show dropdown and make required
                      if (tipoAreteSelect) {
                          tipoAreteSelect.classList.remove('d-none');
                          tipoAreteSelect.required = true;
                      }
                      if (tipoAreteDefault) tipoAreteDefault.disabled = true;
                  }
              })
              .catch(error => {
                  input.classList.remove('is-valid');
                  // Fallback to unknown tag behavior on network/server error
                  if (tipoAreteSelect) {
                      tipoAreteSelect.classList.remove('d-none');
                      tipoAreteSelect.required = true;
                  }
                  if (tipoAreteDefault) tipoAreteDefault.disabled = true;
              })
              .finally(() => {
                  input.classList.remove('is-loading');
                  actualizarCenso();
              });
      }

    function obtenerCoordenadasActuales() {
        const btn = document.getElementById('btn_obtener_coordenadas');
        const statusEl = document.getElementById('coordenadas_status');
        const originalHtml = btn.innerHTML;

        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Buscando...';
        btn.disabled = true;
        
        statusEl.className = 'small text-muted mt-1';
        statusEl.innerHTML = '<i class="bi bi-info-circle-fill text-info me-1"></i> Solicitando ubicación GPS...';
        statusEl.style.display = 'block';

        const successCallback = (position) => {
            const lat = position.coords.latitude.toFixed(6);
            const lon = position.coords.longitude.toFixed(6);
            
            document.getElementById('up_latitud').value = lat;
            document.getElementById('up_longitud').value = lon;
            
            btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Ubicación obtenida';
            btn.className = 'btn btn-link btn-xs p-0 text-decoration-none fw-bold text-success';
            btn.disabled = false;

            const guardarBtn = document.getElementById('btn_guardar_coordenadas');
            if (guardarBtn) {
                guardarBtn.style.display = 'block';
            }

            statusEl.className = 'small text-success mt-1 fw-bold';
            statusEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Coordenadas capturadas. Presiona "Guardar" para guardarlas en el predio.';
            
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.className = 'btn btn-link btn-xs p-0 text-decoration-none fw-bold text-primary';
            }, 3500);
        };

        const errorCallback = (error) => {
            console.error('Error GPS:', error);
            let errorMsg = 'No se pudo obtener la ubicación: ' + error.message;
            if (error.code === 1) {
                errorMsg = 'Permiso denegado para acceder al GPS. Por favor activa los permisos de ubicación en tu navegador.';
            } else if (error.code === 2) {
                errorMsg = 'Ubicación no disponible. Inténtalo de nuevo en una zona despejada.';
            } else if (error.code === 3) {
                errorMsg = 'Tiempo de espera agotado al buscar señal GPS.';
            }
            statusEl.className = 'small text-danger mt-1';
            statusEl.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1"></i> ' + errorMsg;
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        };

        // Soporte nativo de GPS para la APK de Capacitor (evita bloqueos de origen no seguro)
        if (window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.Geolocation) {
            window.Capacitor.Plugins.Geolocation.getCurrentPosition({ enableHighAccuracy: true, timeout: 10000 })
                .then(successCallback)
                .catch(errorCallback);
        } else if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(successCallback, errorCallback, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
        } else {
            statusEl.className = 'small text-danger mt-1';
            statusEl.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1"></i> Este navegador o dispositivo no soporta geolocalización.';
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    }

    function guardarCoordenadasPredio() {
        const predioId = document.getElementById('predio_id').value;
        const lat = document.getElementById('up_latitud').value;
        const lon = document.getElementById('up_longitud').value;
        const statusEl = document.getElementById('coordenadas_status');
        const guardarBtn = document.getElementById('btn_guardar_coordenadas');

        if (!predioId) {
            alert('Por favor selecciona un predio primero.');
            return;
        }

        if (!lat || !lon) {
            alert('Por favor obtén o captura las coordenadas primero.');
            return;
        }

        const originalBtnHtml = guardarBtn.innerHTML;
        guardarBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        guardarBtn.disabled = true;

        statusEl.className = 'small text-muted mt-1';
        statusEl.innerHTML = '<i class="bi bi-info-circle-fill text-info me-1"></i> Guardando en base de datos...';

        fetch(`/predios/${predioId}/coordenadas`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({
                latitud: lat,
                longitud: lon
            })
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                statusEl.className = 'small text-success mt-1 fw-bold';
                statusEl.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> ¡Guardado con éxito en el predio!';
                
                // Actualizar el array local 'productores' en memoria JS para mantener la coherencia
                const productorId = document.getElementById('productor_id').value;
                if (typeof productores !== 'undefined') {
                    const prod = productores.find(p => p.id == productorId);
                    if (prod && prod.predios) {
                        const pred = prod.predios.find(pr => pr.id == predioId);
                        if (pred) {
                            pred.latitud = res.latitud;
                            pred.longitud = res.longitud;
                        }
                    }
                }
                
                guardarBtn.className = 'btn btn-success';
                guardarBtn.innerHTML = '<i class="bi bi-check2"></i>';
                
                setTimeout(() => {
                    guardarBtn.style.display = 'none';
                    guardarBtn.className = 'btn btn-success';
                    guardarBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Guardar';
                    guardarBtn.disabled = false;
                }, 3000);
            } else {
                throw new Error(res.message || 'Error desconocido.');
            }
        })
        .catch(error => {
            console.error('Error al guardar:', error);
            statusEl.className = 'small text-danger mt-1';
            statusEl.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1"></i> Error al guardar: ' + error.message;
            guardarBtn.innerHTML = originalBtnHtml;
            guardarBtn.disabled = false;
        });
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const productorSelectEl = document.getElementById('productor_id');
        if (productorSelectEl) {
            const ts = new TomSelect('#productor_id', {
                create: false,
                maxOptions: 1000,
                valueField: 'id',
                labelField: 'text',
                searchField: ['text', 'upp', 'curp', 'localidad', 'municipio'],
                dropdownParent: 'body',
                render: {
                    option: function(data, escape) {
                        return `<div>
                            <span class="title">${escape(data.text)}</span>
                        </div>`;
                    },
                    item: function(data, escape) {
                        return `<div>${escape(data.text)}</div>`;
                    }
                }
            });
            if (productorSelectEl.hasAttribute('readonly') || productorSelectEl.style.pointerEvents === 'none') {
                ts.disable();
            }
        }
    });
</script>
@include('inspecciones.partials.offline-draft-js')
@endsection
