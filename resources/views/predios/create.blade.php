@extends('layouts.app')

@section('title', 'Nuevo Predio SENASICA')
@section('header_title', 'Registrar Unidad de Producción')
@section('header_subtitle', 'Configure la ubicación y datos técnicos del predio')
@section('back_url', route('predios.index'))

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
                <form action="{{ route('predios.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Nombre del Rancho / Predio</label>
                            <input type="text" name="nombre_rancho" class="form-control" required placeholder="Ej. El Salto del Aguacate 1">
                        </div>
                        
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0">Productor Responsable</label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevoProductor">
                                    <i class="bi bi-plus-circle me-1"></i>Nuevo Productor
                                </button>
                            </div>
                            <select name="productor_id" id="productor_id" class="form-select @if(request('productor_id')) bg-light @endif" required>
                                <option value="">Seleccione un productor...</option>
                                @foreach($productores as $p)
                                <option value="{{ $p->id }}" 
                                    data-upp="{{ $p->upp }}" 
                                    data-curp="{{ $p->curp }}" 
                                    data-localidad="{{ $p->localidad }}" 
                                    data-municipio="{{ $p->municipio }}"
                                    {{ (old('productor_id') == $p->id || request('productor_id') == $p->id) ? 'selected' : '' }}>
                                    {{ $p->nombre }} {{ $p->apellido_paterno }} {{ $p->apellido_materno }}
                                </option>
                                @endforeach
                            </select>
                            @if(request('productor_id'))
                                <div class="form-text text-primary small"><i class="bi bi-info-circle me-1"></i> Asignando rancho a este productor</div>
                            @endif
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Clave de Unidad de Producción (UPP)</label>
                            <input type="text" name="clave_unidad_produccion" class="form-control" required placeholder="Ej. 180104330002">
                        </div>

                        <!-- Ubicación -->
                        <div class="col-md-12 mb-2">
                            <button type="button" class="btn btn-outline-primary btn-sm w-100 py-2 rounded-3 shadow-sm border-2 fw-bold" onclick="getLocation()">
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
                            <input type="text" name="domicilio" class="form-control" placeholder="Ej. A 2 km sobre el arroyo">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Municipio</label>
                            <input type="text" name="municipio" class="form-control" placeholder="Rosamorada">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Localidad / Población</label>
                            <input type="text" name="localidad" class="form-control" placeholder="San Juan Corapan">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <a href="{{ route('predios.index') }}" class="btn btn-light px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-5">Guardar Predio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@include('productores.partials.create-modal')
@endsection

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-control { border-radius: 0.5rem !important; padding: 0.6rem !important; }
    .ts-dropdown .option { padding: 8px 12px; }
    .ts-dropdown .option .title { font-weight: bold; display: block; }
    .ts-dropdown .option .subtitle { font-size: 0.75rem; color: #64748b; }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new TomSelect('#productor_id', {
            create: false,
            maxOptions: 1000,
            valueField: 'id',
            labelField: 'text',
            searchField: ['text', 'upp', 'curp', 'localidad', 'municipio'],
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
    });

    function getLocation() {
        const btn = event.currentTarget;
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
