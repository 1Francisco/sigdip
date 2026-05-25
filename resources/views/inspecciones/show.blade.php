@extends('layouts.app')

@section('title', 'Vista Previa del Dictamen')
@section('header_title', 'Vista Previa Oficial')
@section('header_subtitle', empty($inspeccion->folio) || \Illuminate\Support\Str::startsWith($inspeccion->folio, 'TB-') ? 'Sin Folio (Borrador)' : 'Folio: ' . $inspeccion->folio)
@section('back_url', route('inspecciones.index'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('inspecciones.index') }}" class="btn btn-light shadow-sm">
                <i class="bi bi-arrow-left"></i> Volver al Listado
            </a>
            <div class="d-flex gap-2">
                <a href="{{ route('reportes.pdf', $inspeccion->id) }}" class="btn btn-danger shadow-sm px-4">
                    <i class="bi bi-file-earmark-pdf"></i> Descargar PDF Oficial
                </a>
            </div>
        </div>

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
