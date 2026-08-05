@extends('layouts.app')

@section('title', 'Vista Previa del Dictamen')
@section('header_title', 'Vista Previa Oficial')
@section('header_subtitle', $inspeccion->clave_interna ?: 'Sin Folio')
@section('back_url', request()->headers->get('referer', route('inspecciones.index')))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('inspecciones.index') }}" class="btn btn-light shadow-sm" onclick="history.back(); return false;">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <div class="d-flex gap-2">
                <a href="{{ route('reportes.pdf', $inspeccion->id) }}" class="btn btn-danger shadow-sm px-4">
                    <i class="bi bi-file-earmark-pdf"></i> Descargar PDF Oficial
                </a>
            </div>
        </div>

        @if($inspeccionesGrupo->count() > 1)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3 px-4 bg-light d-flex align-items-center flex-wrap gap-2">
                <span class="fw-bold small text-muted text-uppercase me-2"><i class="bi bi-people-fill"></i> Productores en este Hato:</span>
                @foreach($inspeccionesGrupo as $insGrupo)
                    <a href="{{ route('inspecciones.show', $insGrupo->id) }}" 
                       class="btn btn-sm {{ $insGrupo->id === $inspeccion->id ? 'btn-primary fw-bold' : 'btn-outline-secondary' }} rounded-pill px-3">
                        <i class="bi bi-person-badge"></i> 
                        {{ $insGrupo->predio->productor->nombre_completo ?? 'N/A' }} 
                        ({{ $insGrupo->folio ?? 'Borrador' }})
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        <div class="card border-0 shadow-sm overflow-hidden" style="height: calc(100vh - 250px);">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span class="small fw-bold text-uppercase tracking-wider">Previsualización del Documento SENASICA</span>
                <span class="badge bg-primary">Modo Lectura</span>
            </div>
            <div class="card-body p-0 h-100 bg-secondary">
                <!-- Iframe para mostrar el PDF en tiempo real -->
                <iframe src="{{ route('reportes.stream', $inspeccion->id) }}" 
                        width="100%" 
                        height="100%" 
                        frameborder="0"
                        style="border: none;">
                    Tu navegador no soporta la previsualización de PDF. 
                    <a href="{{ route('reportes.stream', $inspeccion->id) }}">Descarga aquí</a>.
                </iframe>
            </div>
        </div>
    </div>
</div>
@endsection
