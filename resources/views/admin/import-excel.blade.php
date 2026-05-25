@extends('layouts.app')

@section('title', 'Importar Expedientes Excel')
@section('header_title', 'Importar Expedientes Excel')
@section('header_subtitle', 'Arrastra los archivos .xlsx de cada médico para unificar los datos')
@section('back_url', route('admin.dashboard'))

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">


            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>✅ ¡Éxito!</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>❌ Error:</strong> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Formulario de Importación -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="mb-0">📁 Subir Archivo Excel</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('import.excel.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                        @csrf

                        <!-- Zona de Drag & Drop -->
                        <div id="dropZone" class="border border-3 border-dashed rounded-4 p-5 text-center mb-4"
                             style="border-color: #ccc !important; background: #fafafa; cursor: pointer; transition: all 0.3s;">
                            <div id="dropContent">
                                <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                                <h5 class="text-muted">Arrastra tu archivo .xlsx o .zip aquí</h5>
                                <p class="text-muted small">Puedes subir un archivo ZIP con muchos expedientes a la vez</p>
                                <input type="file" name="archivo" id="fileInput" accept=".xlsx,.xls,.zip"
                                       class="d-none" required>
                            </div>
                            <div id="fileSelected" class="d-none">
                                <div style="font-size: 2.5rem;">✅</div>
                                <h5 class="text-success" id="fileName"></h5>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="clearFile()">✕ Quitar archivo</button>
                            </div>
                        </div>

                        <!-- Pestañas (Ocultas porque siempre son las mismas) -->
                        <input type="hidden" name="pestana_base" value="BASE">
                        <input type="hidden" name="pestana_aretes" value="ARETE">

                        <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            Los datos de <strong>Productores</strong> y <strong>Aretes</strong> se extraerán automáticamente de las pestañas <strong>BASE</strong> y <strong>ARETE</strong>.
                        </div>

                        <!-- Preview -->
                        <div id="previewSection" class="d-none mb-4">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h6 class="fw-bold">👁️ Vista Previa de las Pestañas Encontradas:</h6>
                                    <div id="previewContent"></div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="button" class="btn btn-outline-primary btn-lg" onclick="previewFile()">
                                👁️ Previsualizar
                            </button>
                            <button type="submit" class="btn btn-success btn-lg" id="btnImport">
                                ⬆️ Importar Datos
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Historial de Importaciones -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0">📜 Historial de Importaciones</h5>
                </div>
                <div class="card-body p-0">
                    @if($importaciones->count())
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>📁 Archivo</th>
                                    <th>🏷️ Aretes Importados</th>
                                    <th>📅 Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($importaciones as $imp)
                                <tr>
                                    <td class="fw-bold">{{ $imp->archivo_origen }}</td>
                                    <td><span class="badge bg-success">{{ number_format($imp->total_aretes) }}</span></td>
                                    <td>{{ \Carbon\Carbon::parse($imp->fecha_importacion)->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-5 text-muted">
                            <div style="font-size: 2rem;">📭</div>
                            <p>No hay importaciones registradas aún.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .border-dashed { border-style: dashed !important; }
    #dropZone.dragover {
        border-color: #198754 !important;
        background: #e8f5e9 !important;
    }
</style>

<script>
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const dropContent = document.getElementById('dropContent');
    const fileSelected = document.getElementById('fileSelected');
    const fileName = document.getElementById('fileName');

    // Clic para seleccionar
    dropZone.addEventListener('click', () => fileInput.click());

    // Drag & Drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            showFile(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length) showFile(e.target.files[0]);
    });

    function showFile(file) {
        fileName.textContent = file.name;
        dropContent.classList.add('d-none');
        fileSelected.classList.remove('d-none');
    }

    function clearFile() {
        fileInput.value = '';
        dropContent.classList.remove('d-none');
        fileSelected.classList.add('d-none');
        document.getElementById('previewSection').classList.add('d-none');
    }

    function previewFile() {
        if (!fileInput.files.length) {
            alert('Selecciona un archivo primero');
            return;
        }
        const formData = new FormData();
        formData.append('archivo', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');

        const previewContent = document.getElementById('previewContent');
        previewContent.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-success" role="status"></div><p class="mt-2">Leyendo archivo...</p></div>';
        document.getElementById('previewSection').classList.remove('d-none');

        fetch('{{ route("import.excel.preview") }}', { 
            method: 'POST', 
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(async r => {
            if (!r.ok) {
                const errData = await r.json();
                throw new Error(errData.error || errData.message || 'Error en el servidor');
            }
            return r.json();
        })
            .then(res => {
                if (res.status === 'success') {
                    let html = '';
                    
                    if (res.is_zip) {
                        // Caso: Archivo ZIP con múltiples expedientes
                        html = `
                            <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="fs-1 me-3">📦</div>
                                    <div>
                                        <h5 class="alert-heading fw-bold mb-1">¡Paquete ZIP Detectado!</h5>
                                        <p class="mb-0">Se han encontrado <strong>${res.file_count} archivos Excel</strong> listos para ser unificados.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                                <div class="card-header bg-light fw-bold">Lista de archivos encontrados en el paquete:</div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush small" style="max-height: 150px; overflow-y: auto;">
                                        ${res.files.map(f => `<div class="list-group-item"><i class="bi bi-file-earmark-excel text-success me-2"></i>${f}</div>`).join('')}
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">
                        `;

                        // Mostrar muestra de cada archivo
                        for (const [fileName, fileData] of Object.entries(res.samples)) {
                            html += `<h6 class="fw-bold text-primary mb-3 mt-4"><i class="bi bi-file-earmark-excel me-2"></i>📄 ${fileName}</h6>`;
                            
                            for (const [sheetName, sheetData] of Object.entries(fileData)) {
                                const headers = sheetData.headers;
                                html += `
                                    <div class="card shadow-sm border-0 mb-3" style="border-radius: 15px; overflow: hidden;">
                                        <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 fw-bold" style="color: #2c3e50;">
                                                <i class="fas fa-file-excel me-2 text-success"></i> Pestaña: ${sheetName}
                                            </h6>
                                            <span class="badge rounded-pill bg-success text-white px-3 py-2">${sheetData.total} registros totales</span>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive" style="max-height: 300px;">
                                                <table class="table table-hover table-sm mb-0">
                                                    <thead class="sticky-top">
                                                        <tr class="bg-light" style="font-size: 0.7rem;">
                                                            ${headers.map(h => {
                                                                const isSpecial = h.includes('PREDIO') || h.includes('ARETE');
                                                                return `<th class="px-3 py-2 border-bottom ${isSpecial ? 'bg-primary-subtle text-primary' : ''}">${h}</th>`;
                                                            }).join('')}
                                                        </tr>
                                                    </thead>
                                                    <tbody style="font-size: 0.8rem;">
                                                        ${sheetData.rows.map(row => `
                                                            <tr>
                                                                ${headers.map((h, i) => {
                                                                    const val = row[i];
                                                                    const isSpecial = h.includes('PREDIO') || h.includes('ARETE');
                                                                    return `<td class="px-3 py-2 border-bottom ${isSpecial ? 'fw-bold text-primary' : ''}">${val || '<span class="text-muted opacity-50">---</span>'}</td>`;
                                                                }).join('')}
                                                            </tr>`).join('')}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>`;
                            }
                        }
                    } else if (res.data) {
                        // Caso: Un solo archivo Excel (Vista previa original)
                        for (const [sheetName, sheetData] of Object.entries(res.data)) {
                            const headers = sheetData.headers;
                            html += `
                                <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px; overflow: hidden;">
                                    <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 fw-bold" style="color: #2c3e50;">
                                            <i class="fas fa-file-excel me-2 text-success"></i> Pestaña: ${sheetName}
                                        </h6>
                                        <span class="badge rounded-pill bg-success text-white px-3 py-2">${sheetData.total} registros totales</span>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive" style="max-height: 350px;">
                                            <table class="table table-hover table-sm mb-0">
                                                <thead class="sticky-top">
                                                    <tr class="bg-light" style="font-size: 0.7rem; letter-spacing: 1px; color: #7f8c8d;">
                                                        ${headers.map(h => `
                                                            <th class="px-3 py-2 border-bottom ${h.includes('PREDIO') || h.includes('ARETE') ? 'bg-primary-subtle text-primary' : ''}">${h}</th>
                                                        `).join('')}
                                                    </tr>
                                                </thead>
                                                <tbody style="font-size: 0.85rem;">
                                                    ${sheetData.rows.map(row => `
                                                        <tr>
                                                            ${headers.map((h, i) => {
                                                                const val = row[i];
                                                                const isSpecial = h.includes('PREDIO') || h.includes('ARETE');
                                                                return `<td class="px-3 py-2 border-bottom ${isSpecial ? 'fw-bold text-primary bg-light-subtle' : ''}">${val || '<span class="text-muted opacity-50">---</span>'}</td>`;
                                                            }).join('')}
                                                        </tr>`).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>`;
                        }
                    }
                    
                    if (html === '') {
                        html = '<div class="alert alert-warning border-0 shadow-sm">No se pudo leer el contenido del archivo.</div>';
                    }
                    
                    previewContent.innerHTML = html;
                }
            })
            .catch(err => {
                previewContent.innerHTML = `<div class="alert alert-danger">Error: ${err.message}</div>`;
            });
    }

    // Confirmación antes de importar
    document.getElementById('importForm').addEventListener('submit', function(e) {
        if (!confirm('¿Seguro que deseas importar estos datos? Se agregarán a la base de datos.')) {
            e.preventDefault();
        }
    });
</script>
@endsection
