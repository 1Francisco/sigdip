@extends('layouts.app')

@section('title', 'Vista Previa del Dictamen')
@section('header_title', 'Vista Previa Oficial')
@section('header_subtitle', $inspeccion->clave_interna ?: 'Sin Folio')
@section('back_url', request()->headers->get('referer', route('inspecciones.index')))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 rounded-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4 rounded-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4 rounded-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <ul class="mb-0 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('inspecciones.index') }}" class="btn btn-light shadow-sm" onclick="history.back(); return false;">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <div class="d-flex gap-2">
                @if($inspeccion->dictamen_comite_path)
                    <a href="{{ route('inspecciones.download-dictamen-comite', $inspeccion->id) }}" class="btn btn-success shadow-sm px-4">
                        <i class="bi bi-file-earmark-check"></i> Descargar Dictamen del Comité
                    </a>
                @endif
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

        <!-- Dictamen del Comité Upload/Manage section -->
        <div class="card border-0 shadow-sm mb-4 rounded-4">
            <div class="card-body p-4">
                <div class="row align-items-center g-3">
                    <div class="col-md-6">
                        <h5 class="fw-bold mb-1"><i class="bi bi-shield-check text-primary me-2"></i>Dictamen Oficial del Comité</h5>
                        <p class="text-muted small mb-0">
                            @if($inspeccion->dictamen_comite_path)
                                <span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Dictamen cargado correctamente en el servidor.</span>
                            @else
                                <span class="text-warning fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> No se ha cargado el dictamen oficial firmado por el comité.</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        @if($inspeccion->dictamen_comite_path)
                            <div class="d-inline-flex gap-2 align-items-center flex-wrap justify-content-md-end">
                                <button type="button" id="btn-toggle-preview" class="btn btn-outline-primary" onclick="togglePdfPreview()">
                                    <i class="bi bi-eye"></i> Previsualizar Dictamen Comité
                                </button>
                                <form action="{{ route('inspecciones.delete-dictamen-comite', $inspeccion->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar el dictamen del comité cargado?');" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </form>
                            </div>
                        @else
                            <form action="{{ route('inspecciones.upload-dictamen-comite', $inspeccion->id) }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                                @csrf
                                <input type="file" name="dictamen_comite" class="form-control form-control-sm" accept=".pdf" required style="max-width: 250px;">
                                <button type="submit" class="btn btn-primary btn-sm px-3">
                                    <i class="bi bi-upload"></i> Subir PDF
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden" style="height: calc(100vh - 250px);">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span id="preview-title-text" class="small fw-bold text-uppercase tracking-wider">Previsualización del Documento SENASICA</span>
                <span class="badge bg-primary">Modo Lectura</span>
            </div>
            <div class="card-body p-0 h-100 bg-secondary">
                <!-- Iframe para mostrar el PDF en tiempo real -->
                <iframe id="pdf-iframe" src="{{ route('reportes.stream', [$inspeccion->id, 'prototype' => 1]) }}" 
                        width="100%" 
                        height="100%" 
                        frameborder="0"
                        style="border: none;">
                    Tu navegador no soporta la previsualización de PDF. 
                    <a href="{{ route('reportes.stream', [$inspeccion->id, 'prototype' => 1]) }}">Descarga aquí</a>.
                </iframe>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPreview = 'generated'; // 'generated' or 'committee'
    const generatedUrl = "{{ route('reportes.stream', [$inspeccion->id, 'prototype' => 1]) }}";
    const committeeUrl = "{{ $inspeccion->dictamen_comite_path ? Storage::url($inspeccion->dictamen_comite_path) : '' }}";

    function togglePdfPreview() {
        const iframe = document.getElementById('pdf-iframe');
        const btn = document.getElementById('btn-toggle-preview');
        const previewTitle = document.getElementById('preview-title-text');
        
        if (currentPreview === 'generated') {
            iframe.src = committeeUrl;
            currentPreview = 'committee';
            btn.innerHTML = '<i class="bi bi-eye"></i> Previsualizar PDF Oficial (Siniiga)';
            btn.classList.replace('btn-outline-primary', 'btn-outline-success');
            previewTitle.innerText = 'Previsualización del Dictamen Oficial del Comité';
        } else {
            iframe.src = generatedUrl;
            currentPreview = 'generated';
            btn.innerHTML = '<i class="bi bi-eye"></i> Previsualizar Dictamen Comité';
            btn.classList.replace('btn-outline-success', 'btn-outline-primary');
            previewTitle.innerText = 'Previsualización del Documento SENASICA';
        }
    }
</script>
@endsection
