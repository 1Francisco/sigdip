@extends('layouts.app')

@section('title', 'Continuar Dictamen')
@section('header_title', 'Finalizar Dictamen')
@section('header_subtitle', empty($inspeccion->folio) || \Illuminate\Support\Str::startsWith($inspeccion->folio, 'TB-') ? 'Sin Folio (Borrador)' : 'Folio: ' . $inspeccion->folio)
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
    .resultado-select:disabled {
        background-color: #f8f9fa !important;
        opacity: 0.8 !important;
        cursor: not-allowed !important;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: none !important;
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
    $inyeccionConfirmada = $visita ? $visita->inyeccion : true;
    $esDiaDeLectura = false;
    if ($inspeccion->fecha_lectura) {
        $esDiaDeLectura = now()->startOfDay()->gte($inspeccion->fecha_lectura->startOfDay());
    }
    
    $esDiaDeInyeccion = true;
    if ($visita && $visita->fecha_programada) {
        $esDiaDeInyeccion = now()->startOfDay()->gte($visita->fecha_programada->startOfDay());
    }
    
    // El botón se bloquea en cualquiera de estos dos casos:
    // 1. Aún no se ha realizado la inyección Y no hemos llegado a la fecha de la visita programada.
    // 2. La inyección ya se realizó Y todavía no es el día de la lectura.
    $bloquearBotonFinalizar = (!$inyeccionConfirmada && !$esDiaDeInyeccion) || ($inyeccionConfirmada && !$esDiaDeLectura);
    
    // Determinar texto y tooltip para el botón bloqueado
    $textoBotonBloqueado = 'Finalizar (Bloqueado)';
    $tooltipBotonBloqueado = '';
    if (!$inyeccionConfirmada) {
        $textoBotonBloqueado = 'Finalizar Inyección (Bloqueado)';
        $tooltipBotonBloqueado = 'Se habilitará el día de la inyección (' . ($visita && $visita->fecha_programada ? $visita->fecha_programada->format('d/m/Y') : '') . ')';
    } else {
        $textoBotonBloqueado = 'Finalizar Lectura (Bloqueado)';
        $tooltipBotonBloqueado = 'Se habilitará el día de la lectura (' . ($inspeccion->fecha_lectura ? $inspeccion->fecha_lectura->format('d/m/Y') : '') . ')';
    }

    $tipoPruebaValue = old('tipo_prueba', $inspeccion->tipo_prueba ?? 'PPC');
    if (in_array($tipoPruebaValue, ['P.P.C.', 'PPC'], true)) {
        $tipoPruebaValue = 'PPC';
    } elseif (in_array($tipoPruebaValue, ['P.C.C.', 'PCC'], true)) {
        $tipoPruebaValue = 'PCC';
    }
@endphp
<div class="row justify-content-center">
    <div class="col-lg-11">
        <form action="{{ route('inspecciones.update', $inspeccion->id) }}" method="POST" id="formInspeccion">
            @csrf
            @method('PATCH')
            
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
                            <input type="hidden" name="fecha" value="{{ $inspeccion->fecha->format('Y-m-d') }}">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Productor (Persona)</label>
                                <select id="productor_id" class="form-select @if(isset($visita)) bg-light @endif" required @if(isset($visita)) readonly style="pointer-events: none;" tabindex="-1" @endif>
                                    <option value="">Seleccione un productor...</option>
                                    @foreach($productores as $prod)
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
                                                {{ old('productor_id', $inspeccion->predio->productor_id) == $prod->id ? 'selected' : '' }}>
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
                                    <label class="form-label fw-semibold text-muted">Fecha Dictamen</label>
                                    <input type="date" id="fecha_visible" class="form-control bg-light" value="{{ $inspeccion->fecha->format('Y-m-d') }}" readonly style="pointer-events: none;" tabindex="-1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Folio Dictamen</label>
                                    <input type="text" name="folio" class="form-control fw-bold text-primary" placeholder="Opcional (se puede dejar en blanco)" value="{{ empty($inspeccion->folio) || \Illuminate\Support\Str::startsWith($inspeccion->folio, 'TB-') ? '' : $inspeccion->folio }}">
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
                                <label class="form-label fw-semibold">Unidad de Producción (Predio)</label>
                                <select id="predio_id" name="predio_id" class="form-select @if(isset($visita)) bg-light @endif" required @if(isset($visita)) readonly style="pointer-events: none;" tabindex="-1" @endif>
                                    <option value="">Seleccione un predio...</option>
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
                                                    <input type="hidden" name="motivo_prueba" id="motivo_prueba_hidden" value="{{ old('motivo_prueba', $inspeccion->motivo_prueba) }}" required>
                                                    <select id="motivo_prueba_select" class="form-select" onchange="updateMotivoPrueba()">
                                                        <option value="">Seleccione un motivo...</option>
                                                        <option value="Seguimiento">Seguimiento</option>
                                                        <option value="Movilización">Movilización</option>
                                                        <option value="Barrido">Barrido</option>
                                                        <option value="Buffer">Buffer</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-3">
                                                    <label class="form-label text-success fw-bold"><i class="bi bi-geo-fill"></i> F. Inyección</label>
                                                    <input type="date" id="fecha_inyeccion" name="fecha_inyeccion" class="form-control" value="{{ old('fecha_inyeccion', $inspeccion->fecha_inyeccion ? $inspeccion->fecha_inyeccion->format('Y-m-d') : date('Y-m-d')) }}" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label text-success fw-bold"><i class="bi bi-clock"></i> H. Inyección</label>
                                                    <input type="time" name="hora_inyeccion" class="form-control" value="{{ old('hora_inyeccion', $inspeccion->hora_inyeccion ?? date('H:i')) }}" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label text-danger fw-bold"><i class="bi bi-eye-fill"></i> F. Lectura</label>
                                                    <input type="date" id="fecha_lectura" name="fecha_lectura" class="form-control bg-light" value="{{ $inspeccion->fecha_lectura ? $inspeccion->fecha_lectura->format('Y-m-d') : '' }}" readonly style="pointer-events: none;" tabindex="-1" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label text-danger fw-bold"><i class="bi bi-clock"></i> H. Lectura</label>
                                                    <input type="time" name="hora_lectura" class="form-control" value="{{ old('hora_lectura', $inspeccion->hora_lectura ?? date('H:i')) }}" required>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Función Zootécnica</label>
                                                    <select name="funcion_zootecnica" class="form-select">
                                                        <option value="Carne" {{ $inspeccion->funcion_zootecnica == 'Carne' ? 'selected' : '' }}>Carne</option>
                                                        <option value="Leche" {{ $inspeccion->funcion_zootecnica == 'Leche' ? 'selected' : '' }}>Leche</option>
                                                        <option value="Mixto" {{ $inspeccion->funcion_zootecnica == 'Mixto' ? 'selected' : '' }}>Mixto</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Fecha Expiración (Vigencia)</label>
                                                    <input type="date" name="vigencia_fecha" class="form-control" value="{{ $inspeccion->vigencia_fecha ? $inspeccion->vigencia_fecha->format('Y-m-d') : '' }}">
                                                </div>

                                                <!-- Campos Avanzados SENASICA -->
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold small">Fecha Prueba Anterior</label>
                                                    <input type="date" name="fecha_prueba_anterior" id="fecha_prueba_anterior" class="form-control form-control-sm @if($tipoPruebaValue !== 'PCC') bg-light @endif" value="{{ $inspeccion->fecha_prueba_anterior ? $inspeccion->fecha_prueba_anterior->format('Y-m-d') : '' }}" @if($tipoPruebaValue !== 'PCC') disabled @endif>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold small">Dictamen Anterior No.</label>
                                                    <input type="text" name="dictamen_anterior_no" id="dictamen_anterior_no" class="form-control form-control-sm @if($tipoPruebaValue !== 'PCC') bg-light @endif" value="{{ $inspeccion->dictamen_anterior_no }}" @if($tipoPruebaValue !== 'PCC') disabled @endif>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold small">Exención No.</label>
                                                    <input type="text" name="exencion_no" id="exencion_no" class="form-control form-control-sm @if($tipoPruebaValue !== 'PCC') bg-light @endif" value="{{ $inspeccion->exencion_no }}" @if($tipoPruebaValue !== 'PCC') disabled @endif>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold small">Fecha Exención</label>
                                                    <input type="date" name="exencion_fecha" id="exencion_fecha" class="form-control form-control-sm @if($tipoPruebaValue !== 'PCC') bg-light @endif" value="{{ $inspeccion->exencion_fecha ? $inspeccion->exencion_fecha->format('Y-m-d') : '' }}" @if($tipoPruebaValue !== 'PCC') disabled @endif>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold small">Constancia Hato Libre No.</label>
                                                    <input type="text" name="hato_libre_no" id="hato_libre_no" class="form-control form-control-sm @if($tipoPruebaValue !== 'PCC') bg-light @endif" value="{{ $inspeccion->hato_libre_no }}" @if($tipoPruebaValue !== 'PCC') disabled @endif>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold small">Fecha Hato Libre</label>
                                                    <input type="date" name="hato_libre_fecha" id="hato_libre_fecha" class="form-control form-control-sm @if($tipoPruebaValue !== 'PCC') bg-light @endif" value="{{ $inspeccion->hato_libre_fecha ? $inspeccion->hato_libre_fecha->format('Y-m-d') : '' }}" @if($tipoPruebaValue !== 'PCC') disabled @endif>
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
                                                    <input type="number" name="sementales" class="form-control form-control-sm bg-light" value="{{ $inspeccion->sementales ?? 0 }}" readonly style="pointer-events: none;" tabindex="-1">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-1">Vacas</label>
                                                    <input type="number" name="vacas" class="form-control form-control-sm bg-light" value="{{ $inspeccion->vacas ?? 0 }}" readonly style="pointer-events: none;" tabindex="-1">
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small mb-1">Vaquillas</label>
                                                    <input type="number" name="vaquillas" class="form-control form-control-sm bg-light" value="{{ $inspeccion->vaquillas ?? 0 }}" readonly style="pointer-events: none;" tabindex="-1">
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small mb-1">Becerras</label>
                                                    <input type="number" name="becerras" class="form-control form-control-sm bg-light" value="{{ $inspeccion->becerras ?? 0 }}" readonly style="pointer-events: none;" tabindex="-1">
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small mb-1">Becerros</label>
                                                    <input type="number" name="becerros" class="form-control form-control-sm bg-light" value="{{ $inspeccion->becerros ?? 0 }}" readonly style="pointer-events: none;" tabindex="-1">
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
                                <span>
                                    <i class="bi bi-list-columns-reverse text-warning me-2"></i> IV: RESULTADOS INDIVIDUALES
                                    <span class="badge bg-danger-soft text-danger border border-danger ms-2 rounded-pill px-2 py-1 small d-none" id="header-resultados-pendientes">
                                        <strong id="header-pending-count">0</strong> por definir
                                    </span>
                                </span>
                                <span id="status-sec-4" class="badge rounded-pill"></span>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#dictamenAccordion">
                        <div class="accordion-body p-0 bg-white">
                            <div id="seccion-resultados-bloqueada" class="alert alert-warning border-0 rounded-0 mb-0 d-flex align-items-center gap-3 py-3 px-4 shadow-sm {{ $esDiaDeLectura ? 'd-none' : '' }}" style="background-color: #fff9e6; color: #856404; font-size: 0.95rem;">
                                <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
                                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                </div>
                                <div>
                                    <strong class="d-block mb-0.5">⚠️ Sección de resultados bloqueada</strong>
                                    <span>La captura de resultados de la prueba se habilitará el día de la lectura: <strong id="fecha-lectura-warning-text">{{ $inspeccion->fecha_lectura ? $inspeccion->fecha_lectura->format('d/m/Y') : '' }}</strong>.</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light flex-wrap gap-2">
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-primary rounded-pill px-3 py-2" id="counter-total">
                                        <i class="bi bi-hash me-1"></i> Total: <strong>{{ count($inspeccion->detalles) }}</strong>
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
                                        <i class="bi bi-exclamation-circle me-1"></i> Sin definir resultado: <strong>0</strong>
                                    </span>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" onclick="addAnimal()">
                                    <i class="bi bi-plus-lg me-1"></i> Añadir Animal
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0 table-mobile-cards" id="tablaAnimales">
                                    <thead class="bg-light text-center small fw-bold">
                                        <tr>
                                            <th style="width: 220px;">Identificación (Arete)</th>
                                            <th style="width: 180px;">Tipo Arete</th>
                                            <th style="width: 80px;">Edad (m)</th>
                                            <th style="width: 120px;">Raza</th>
                                            <th style="width: 100px;">Sexo</th>
                                            <th style="width: 80px;">Fierro</th>
                                            <th style="width: 140px;">Resultado</th>
                                            <th>Observaciones</th>
                                            <th style="width: 40px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($inspeccion->detalles as $index => $detalle)
                                        <tr>
                                            <td data-label="Identificador">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="animales[{{ $index }}][identificador]" class="form-control arete-input" id="arete_{{ $index }}" value="{{ $detalle->animal->numero_arete_siniiga }}" required onchange="buscarDatosAnimal(this)" inputmode="numeric">
                                                    <button class="btn btn-primary" type="button" onclick="startScanner({{ $index }})">
                                                        <i class="bi bi-camera"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td data-label="Tipo Arete" class="tipo-arete-cell">
                                                <select name="animales[{{ $index }}][tipo_arete]" class="form-select form-select-sm tipo-arete-select {{ ($detalle->tipo_arete == 'SINIIGA' || empty($detalle->tipo_arete)) ? 'd-none' : '' }}" {{ ($detalle->tipo_arete != 'SINIIGA' && !empty($detalle->tipo_arete)) ? 'required' : '' }}>
                                                    <option value="">—</option>
                                                    <option value="IN" {{ $detalle->tipo_arete == 'IN' ? 'selected' : '' }}>IN</option>
                                                    <option value="RA" {{ $detalle->tipo_arete == 'RA' ? 'selected' : '' }}>RA</option>
                                                </select>
                                                <input type="hidden" name="animales[{{ $index }}][tipo_arete_default]" value="SINIIGA" class="tipo-arete-default" {{ ($detalle->tipo_arete != 'SINIIGA' && !empty($detalle->tipo_arete)) ? 'disabled' : '' }}>
                                            </td>
                                            <td data-label="Edad (meses)"><input type="number" name="animales[{{ $index }}][edad_meses]" class="form-control form-control-sm" value="{{ $detalle->edad_meses }}" inputmode="numeric"></td>
                                            <td data-label="Raza"><input type="text" name="animales[{{ $index }}][raza]" class="form-control form-control-sm" value="{{ $detalle->raza }}"></td>
                                            <td data-label="Sexo">
                                                 <select name="animales[{{ $index }}][sexo]" class="form-select form-select-sm sexo-select">
                                                     <option value="H" {{ ($detalle->sexo == 'Hembra' || $detalle->sexo == 'H') ? 'selected' : '' }}>H</option>
                                                     <option value="M" {{ ($detalle->sexo == 'Macho' || $detalle->sexo == 'M') ? 'selected' : '' }}>M</option>
                                                 </select>
                                             </td>
                                             <td data-label="Fierro" class="text-center">
                                                 <input type="checkbox" name="animales[{{ $index }}][fierro]" value="Si" class="form-check-input" {{ $detalle->fierro == 'Si' ? 'checked' : '' }}>
                                             </td>
                                             <td data-label="Resultado" class="resultado-cell">
                                                 <select name="animales[{{ $index }}][resultado]" class="form-select form-select-sm fw-bold resultado-select" required {{ !$esDiaDeLectura ? 'disabled' : '' }}>
                                                     @if(empty($detalle->resultado_prueba) || $detalle->resultado_prueba == 'Pendiente')
                                                         <option value="" disabled selected>-- Seleccione --</option>
                                                     @endif
                                                     <option value="Negativo" class="text-success" {{ $detalle->resultado_prueba == 'Negativo' ? 'selected' : '' }}>Negativo</option>
                                                     <option value="Positivo" class="text-danger" {{ $detalle->resultado_prueba == 'Positivo' ? 'selected' : '' }}>Positivo</option>
                                                     <option value="Sospechoso" class="text-warning" {{ $detalle->resultado_prueba == 'Sospechoso' ? 'selected' : '' }}>Sospechoso</option>
                                                 </select>
                                                 <input type="hidden" name="animales[{{ $index }}][resultado]" class="resultado-hidden" value="{{ $detalle->resultado_prueba ?? 'Pendiente' }}" {{ $esDiaDeLectura ? 'disabled' : '' }}>
                                             </td>
                                            <td data-label="Obs"><input type="text" name="animales[{{ $index }}][observaciones]" class="form-control form-control-sm" value="{{ $detalle->observaciones_animal }}"></td>
                                            <td class="text-center" data-label="Quitar">
                                                <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest('tr').remove(); actualizarCenso(); validateSections();">
                                                    <i class="bi bi-trash fs-5"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td data-label="Identificador">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="animales[0][identificador]" class="form-control arete-input" id="arete_0" placeholder="SINIIGA" required onchange="buscarDatosAnimal(this)" inputmode="numeric">
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
                                             <td data-label="Resultado" class="resultado-cell">
                                                 <select name="animales[0][resultado]" class="form-select form-select-sm fw-bold resultado-select" required {{ !$esDiaDeLectura ? 'disabled' : '' }}>
                                                     <option value="" disabled selected>-- Seleccione --</option>
                                                     <option value="Negativo" class="text-success">Negativo</option>
                                                     <option value="Positivo" class="text-danger">Positivo</option>
                                                     <option value="Sospechoso" class="text-warning">Sospechoso</option>
                                                 </select>
                                                 <input type="hidden" name="animales[0][resultado]" class="resultado-hidden" value="Pendiente" {{ $esDiaDeLectura ? 'disabled' : '' }}>
                                             </td>
                                            <td data-label="Obs"><input type="text" name="animales[0][observaciones]" class="form-control form-control-sm"></td>
                                            <td class="text-center">
                                                <i class="bi bi-lock text-muted"></i>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer bg-light py-3 d-sm-none text-center">
                                <small class="text-muted"><i class="bi bi-arrow-left-right me-1"></i> Deslice para ver todos los campos</small>
                            </div>
                            <!-- Botón Flotante para Añadir Animal en Móvil -->
                            <button type="button" class="btn btn-primary floating-add-btn d-lg-none shadow-lg" onclick="addAnimal()" title="Añadir Animal">
                                <i class="bi bi-plus-lg fs-3"></i>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <div class="d-flex justify-content-end gap-3 mb-5 d-none d-lg-flex">
                <input type="hidden" name="estado" id="form_estado" value="sincronizado">
                <input type="hidden" name="inyeccion_realizada" id="inyeccion_realizada" value="1">
                <a href="{{ route('inspecciones.index') }}" class="btn btn-light px-4 rounded-pill">Cancelar</a>
                <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" onclick="saveAsDraft()">
                    <i class="bi bi-save me-1"></i> Guardar Borrador
                </button>
                <!-- Botón Finalizar Desktop (Activo) -->
                <button type="submit" id="btn-finalizar-desktop-activo" class="btn btn-primary px-5 rounded-pill shadow-sm {{ $bloquearBotonFinalizar ? 'd-none' : '' }}">
                    <i class="bi {{ !$inyeccionConfirmada ? 'bi-box-arrow-in-right' : 'bi-check-circle' }} me-1"></i> 
                    {{ !$inyeccionConfirmada ? 'Finalizar Inyección' : 'Finalizar y Sincronizar' }}
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
                    <i class="bi {{ !$inyeccionConfirmada ? 'bi-box-arrow-in-right' : 'bi-check-circle' }} me-1"></i>
                    {{ !$inyeccionConfirmada ? 'Finalizar Inyección' : 'Finalizar' }}
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
    #reader { background: #000 !important; }
    #reader video { object-fit: cover !important; }
    @media (max-width: 991px) {
        .modal-fullscreen-sm-down .modal-content { height: 100%; border-radius: 0; }
    }
</style>
@endsection

@section('scripts')
<script>
    // 1. Global state variables
    const productores = @json($productores);
    const inyeccionConfirmada = @json($inyeccionConfirmada);
    const esDiaDeInyeccion = @json($esDiaDeInyeccion);
    let animalCount = {{ count($inspeccion->detalles) }};
    let html5QrCode = null;
    let currentInputId = null;

    // 2. Global functions
    function populateProductorFields(productorId) {
        const sel = document.getElementById('productor_id');
        if (!sel) return;
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

    function populatePredios(productorId, selectedPredioId = null) {
        const predioSelect = document.getElementById('predio_id');
        if (!predioSelect) return;
        predioSelect.innerHTML = '';

        if (!productorId) {
            predioSelect.innerHTML = '<option value="">Primero seleccione un productor...</option>';
            predioSelect.disabled = true;
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

            if (selectedProductor.predios.length === 1 && !selectedPredioId) {
                predioSelect.selectedIndex = 1;
            }
        } else {
            predioSelect.innerHTML = '<option value="">Este productor no tiene predios registrados</option>';
            predioSelect.disabled = true;
        }
    }

    function populatePredioFields(predioId, productorId) {
        const productorSelect = document.getElementById('productor_id');
        const gpsBtn = document.getElementById('btn_obtener_coordenadas');
        const saveBtn = document.getElementById('btn_guardar_coordenadas');
        const statusEl = document.getElementById('coordenadas_status');

        if (!predioId || !productorId || !productorSelect) {
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
        const predio = selectedProductor.predios ? selectedProductor.predios.find(pr => pr.id == predioId) : null;
        if (!predio) return;
        
        const nameEl = document.getElementById('up_nombre'); if(nameEl) nameEl.value = predio.nombre_rancho || '';
        const latEl = document.getElementById('up_latitud'); if(latEl) latEl.value = predio.latitud || '';
        const lonEl = document.getElementById('up_longitud'); if(lonEl) lonEl.value = predio.longitud || '';
        const uppEl = document.getElementById('up_upp'); if(uppEl) uppEl.value = predio.clave_unidad_produccion || '';
        const domEl = document.getElementById('up_domicilio'); if(domEl) domEl.value = predio.domicilio || '';
        const munEl = document.getElementById('up_municipio'); if(munEl) munEl.value = predio.municipio || '';
        const locEl = document.getElementById('up_localidad'); if(locEl) locEl.value = predio.localidad || '';
        
        const prodOpt = productorSelect.selectedIndex >= 0 ? productorSelect.options[productorSelect.selectedIndex] : null;
        const estadoEl = document.getElementById('up_estado');
        if (estadoEl) {
            estadoEl.value = (selectedProductor && selectedProductor.estado) || (prodOpt && prodOpt.dataset && prodOpt.dataset.estado) || '';
        }

        if (gpsBtn) gpsBtn.style.display = 'block';
        if (saveBtn) saveBtn.style.display = 'none';
        if (statusEl) statusEl.style.display = 'none';
    }

    function checkLecturaDate() {
        const fLectInput = document.getElementById('fecha_lectura');
        if (!fLectInput) return;
        
        const fLectVal = fLectInput.value;
        if (!fLectVal) {
            setLecturaState(false);
            return;
        }
        
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const todayStr = `${yyyy}-${mm}-${dd}`;
        
        const esDiaDeLectura = todayStr >= fLectVal;
        setLecturaState(esDiaDeLectura);
    }

    function setLecturaState(esDiaDeLectura) {
        // Set global window variable
        window.esDiaDeLectura = esDiaDeLectura;

        // 1. Alert Warning
        const alertEl = document.getElementById('seccion-resultados-bloqueada');
        if (alertEl) {
            if (esDiaDeLectura) {
                alertEl.classList.add('d-none');
            } else {
                alertEl.classList.remove('d-none');
                const fLectInput = document.getElementById('fecha_lectura');
                const warningTextEl = document.getElementById('fecha-lectura-warning-text');
                if (fLectInput && warningTextEl && fLectInput.value) {
                    const parts = fLectInput.value.split('-');
                    warningTextEl.textContent = `${parts[2]}/${parts[1]}/${parts[0]}`;
                }
            }
        }
        
        // 2. Table Rows
        const rows = document.querySelectorAll('#tablaAnimales tbody tr');
        rows.forEach(row => {
            const selectEl = row.querySelector('.resultado-select');
            const hiddenEl = row.querySelector('.resultado-hidden');
            
            if (selectEl) {
                selectEl.disabled = !esDiaDeLectura;
                if (esDiaDeLectura) {
                    selectEl.required = true;
                } else {
                    selectEl.removeAttribute('required');
                    selectEl.classList.remove('is-invalid');
                }
            }
            
            if (hiddenEl) {
                hiddenEl.disabled = esDiaDeLectura;
            }
        });
        
        // 3. Botón de Finalizar Condicional
        const bloquearBoton = (!inyeccionConfirmada && !esDiaDeInyeccion) || (inyeccionConfirmada && !esDiaDeLectura);
        
        // Desktop Buttons
        const btnDesktopActivo = document.getElementById('btn-finalizar-desktop-activo');
        const btnDesktopBloqueado = document.getElementById('btn-finalizar-desktop-bloqueado');
        if (btnDesktopActivo && btnDesktopBloqueado) {
            if (bloquearBoton) {
                btnDesktopActivo.classList.add('d-none');
                btnDesktopBloqueado.classList.remove('d-none');
                
                const txtSpan = document.getElementById('btn-finalizar-desktop-bloqueado-text');
                if (!inyeccionConfirmada) {
                    if (txtSpan) txtSpan.textContent = 'Finalizar Inyección (Bloqueado)';
                    const fechaProg = "{{ $visita && $visita->fecha_programada ? $visita->fecha_programada->format('d/m/Y') : '' }}";
                    btnDesktopBloqueado.setAttribute('title', `Se habilitará el día de la inyección (${fechaProg})`);
                } else {
                    if (txtSpan) txtSpan.textContent = 'Finalizar Lectura (Bloqueado)';
                    const fLectInput = document.getElementById('fecha_lectura');
                    if (fLectInput && fLectInput.value) {
                        const parts = fLectInput.value.split('-');
                        btnDesktopBloqueado.setAttribute('title', `Se habilitará el día de la lectura (${parts[2]}/${parts[1]}/${parts[0]})`);
                    }
                }
            } else {
                btnDesktopActivo.classList.remove('d-none');
                btnDesktopBloqueado.classList.add('d-none');
            }
        }
        
        // 4. Mobile Buttons
        const btnMovilActivo = document.getElementById('btn-finalizar-movil-activo');
        const btnMovilBloqueado = document.getElementById('btn-finalizar-movil-bloqueado');
        if (btnMovilActivo && btnMovilBloqueado) {
            if (bloquearBoton) {
                btnMovilActivo.classList.add('d-none');
                btnMovilBloqueado.classList.remove('d-none');
                
                const txtSpanM = document.getElementById('btn-finalizar-movil-bloqueado-text');
                if (!inyeccionConfirmada) {
                    if (txtSpanM) txtSpanM.textContent = 'Finalizar Inyección (Bloqueado)';
                } else {
                    if (txtSpanM) txtSpanM.textContent = 'Finalizar Lectura (Bloqueado)';
                }
            } else {
                btnMovilActivo.classList.remove('d-none');
                btnMovilBloqueado.classList.add('d-none');
            }
        }
    }

    function calcularFechaLectura() {
        const fInyInput = document.getElementById('fecha_inyeccion');
        const fLectInput = document.getElementById('fecha_lectura');
        const fInyVal = fInyInput ? fInyInput.value : '';
        if (fInyVal && fLectInput) {
            const parts = fInyVal.split('-');
            const date = new Date(parts[0], parts[1] - 1, parts[2]);
            date.setDate(date.getDate() + 3);
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            fLectInput.value = `${y}-${m}-${d}`;
            
            checkLecturaDate();
        }
    }

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

        const headerPending = document.getElementById('header-resultados-pendientes');
        const headerPendingCount = document.getElementById('header-pending-count');
        if (headerPending && headerPendingCount) {
            headerPendingCount.textContent = pendingResults;
            if (pendingResults > 0) {
                headerPending.classList.remove('d-none');
            } else {
                headerPending.classList.add('d-none');
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

        // Section 4: IV RESULTADOS INDIVIDUALES
        const areteInputs = document.querySelectorAll('.arete-input');
        let isS4Complete = areteInputs.length > 0;
        let missingResults = false;
        
        areteInputs.forEach(input => {
            if (!input.value.trim()) {
                isS4Complete = false;
            }
        });
        
        // Sólo validar resultados en el día de la lectura
        if (window.esDiaDeLectura) {
            const rows = document.querySelectorAll('#tablaAnimales tbody tr');
            rows.forEach(row => {
                const resSelect = row.querySelector('select[name*="[resultado]"]');
                if (resSelect && (resSelect.value === '' || resSelect.value === 'Pendiente')) {
                    isS4Complete = false;
                    missingResults = true;
                }
            });
        }
        
        const badge4 = document.getElementById('status-sec-4');
        if (badge4) {
            if (isS4Complete) {
                badge4.className = 'badge bg-success-soft text-success border border-success rounded-pill ms-2 fw-semibold';
                badge4.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Completo';
            } else {
                badge4.className = 'badge bg-danger-soft text-danger border border-danger rounded-pill ms-2 fw-semibold';
                if (missingResults) {
                    badge4.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i> Faltan Resultados';
                } else {
                    badge4.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i> Faltan Aretes';
                }
            }
        }
    }

    function addAnimal() {
        const tbody = document.querySelector('#tablaAnimales tbody');
        if (!tbody) return;
        
        // Use the global state window.esDiaDeLectura safely
        const isLecturaDisabled = !window.esDiaDeLectura ? 'disabled' : '';
        const isLecturaRequired = window.esDiaDeLectura ? 'required' : '';
        const isHiddenDisabled = window.esDiaDeLectura ? 'disabled' : '';

        const row = `
            <tr>
                <td data-label="Identificador">
                    <div class="input-group input-group-sm">
                        <input type="text" name="animales[${animalCount}][identificador]" class="form-control arete-input" id="arete_${animalCount}" placeholder="SINIIGA" required onchange="buscarDatosAnimal(this)" inputmode="numeric">
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
                <td data-label="Resultado" class="resultado-cell">
                    <select name="animales[${animalCount}][resultado]" class="form-select form-select-sm fw-bold resultado-select" ${isLecturaRequired} ${isLecturaDisabled}>
                        <option value="" disabled selected>-- Seleccione --</option>
                        <option value="Negativo" class="text-success">Negativo</option>
                        <option value="Positivo" class="text-danger">Positivo</option>
                        <option value="Sospechoso" class="text-warning">Sospechoso</option>
                    </select>
                    <input type="hidden" name="animales[${animalCount}][resultado]" class="resultado-hidden" value="Pendiente" ${isHiddenDisabled}>
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
        actualizarCenso();
        validateSections();
        checkLecturaDate();

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

                    if (tipoAreteSelect) {
                        tipoAreteSelect.classList.add('d-none');
                        tipoAreteSelect.required = false;
                        tipoAreteSelect.value = '';
                    }
                    if (tipoAreteDefault) tipoAreteDefault.disabled = false;
                } else {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');

                    if (tipoAreteSelect) {
                        tipoAreteSelect.classList.remove('d-none');
                        tipoAreteSelect.required = true;
                    }
                    if (tipoAreteDefault) tipoAreteDefault.disabled = true;
                }
            })
            .catch(error => {
                input.classList.remove('is-valid');
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
                
                const productorId = document.getElementById('productor_id').value;
                const prod = productores.find(p => p.id == productorId);
                if (prod && prod.predios) {
                    const pred = prod.predios.find(pr => pr.id == predioId);
                    if (pred) {
                        pred.latitud = res.latitud;
                        pred.longitud = res.longitud;
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

    // 3. Initialization & Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        const productorSelect = document.getElementById('productor_id');
        const predioSelect = document.getElementById('predio_id');
        const initialPredioId = @json(old('predio_id', $inspeccion->predio_id ?? ''));

        if (productorSelect) {
            productorSelect.addEventListener('change', function() {
                populateProductorFields(this.value);
                populatePredios(this.value);
                populatePredioFields(null, null);
                if (typeof validateSections === 'function') validateSections();
            });
        }

        if (predioSelect) {
            predioSelect.addEventListener('change', function() {
                populatePredioFields(this.value, productorSelect.value);
                if (typeof validateSections === 'function') validateSections();
            });
        }

        if (productorSelect && productorSelect.value) {
            populateProductorFields(productorSelect.value);
            populatePredios(productorSelect.value, initialPredioId);
            populatePredioFields(initialPredioId, productorSelect.value);
        }

        const fInyInput = document.getElementById('fecha_inyeccion');
        if (fInyInput) {
            fInyInput.addEventListener('change', calcularFechaLectura);
            fInyInput.addEventListener('input', calcularFechaLectura);
        }
        
        calcularFechaLectura();
        checkLecturaDate();

        const savedMotivo = @json(old('motivo_prueba', $inspeccion->motivo_prueba ?? ''));
        const motivoSelect = document.getElementById('motivo_prueba_select');
        const motivoHidden = document.getElementById('motivo_prueba_hidden');
        if (savedMotivo && motivoSelect) {
            const normalizedMotivo = ['Seguimiento', 'Movilización', 'Barrido', 'Buffer']
                .find(option => savedMotivo.startsWith(option));
            if (normalizedMotivo) {
                motivoSelect.value = normalizedMotivo;
                updateMotivoPrueba();
            } else if (motivoHidden) {
                motivoHidden.value = savedMotivo;
            }
        }

        updateTipoPruebaFields();

        const tabla = document.getElementById('tablaAnimales');
        if (tabla) {
            tabla.addEventListener('input', function(e) {
                if (e.target.name && (e.target.name.includes('[edad_meses]') || e.target.name.includes('[identificador]'))) {
                    actualizarCenso();
                }
            });
            tabla.addEventListener('change', function(e) {
                if (e.target.name && (e.target.name.includes('[edad_meses]') || e.target.name.includes('[sexo]') || e.target.name.includes('[resultado]') || e.target.name.includes('[tipo_arete]') || e.target.name.includes('[identificador]'))) {
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
                    // Si estamos en la Fase de Inyección (inyección no confirmada)
                    if (!inyeccionConfirmada) {
                        e.preventDefault();
                        if (confirm("Fase de Inyección: El dictamen se guardará como BORRADOR y la visita se marcará con inyección realizada.\n\nPodrá ingresar los resultados en la fase de lectura (72 horas después).\n\n¿Desea continuar?")) {
                            saveAsDraft('1');
                        }
                        return;
                    }

                    // Si estamos en la Fase de Lectura (inyección ya confirmada), validar resultados individuales
                    let pendingCount = 0;
                    let firstPendingSelect = null;
                    const rows = document.querySelectorAll('#tablaAnimales tbody tr');
                    rows.forEach(row => {
                        const resSelect = row.querySelector('select[name*="[resultado]"]');
                        if (resSelect && (resSelect.value === '' || resSelect.value === 'Pendiente')) {
                            pendingCount++;
                            if (!firstPendingSelect) {
                                firstPendingSelect = resSelect;
                            }
                        }
                    });

                    if (pendingCount > 0) {
                        e.preventDefault();
                        const accordionCollapse = document.getElementById('collapseFour');
                        if (accordionCollapse && !accordionCollapse.classList.contains('show')) {
                            const bsCollapse = bootstrap.Collapse.getInstance(accordionCollapse) || new bootstrap.Collapse(accordionCollapse);
                            bsCollapse.show();
                        }
                        
                        alert(`⚠️ No se puede finalizar el dictamen. Faltan definir ${pendingCount} resultados de las pruebas individuales.`);
                        
                        if (firstPendingSelect) {
                            setTimeout(() => {
                                firstPendingSelect.focus();
                                firstPendingSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                firstPendingSelect.classList.add('is-invalid');
                                firstPendingSelect.addEventListener('change', function() {
                                    firstPendingSelect.classList.remove('is-invalid');
                                }, { once: true });
                            }, 300);
                        }
                    }
                }
            });
        }
        setTimeout(validateSections, 300);
    });
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
