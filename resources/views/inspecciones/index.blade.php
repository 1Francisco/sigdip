@extends('layouts.app')

@section('title', 'Listado de Lecturas')
@section('header_title', 'Lecturas Pecuarias')
@section('header_subtitle', 'Historial de registros y seguimiento')

@section('content')
<div id="offline-global-sync-alert" class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-none">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                <i class="bi bi-cloud-arrow-up-fill fs-5"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold">Tienes dictámenes modificados en modo offline sin sincronizar</h6>
                <p class="mb-0 small text-secondary">Se han detectado cambios guardados en este dispositivo. Conéctate a internet y presiona el botón para subirlos todos al servidor.</p>
            </div>
        </div>
        <div>
            <button id="btn-sync-all-offline" class="btn btn-warning fw-bold d-flex align-items-center gap-2 px-4 shadow-sm" onclick="syncAllPendingDrafts()">
                <span id="sync-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                <i id="sync-icon" class="bi bi-arrow-repeat"></i> Sincronizar Cambios
            </button>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">Dictámenes Registrados</h5>
        <div class="d-flex gap-2">
            @hasanyrole('Administrador|Medico_Campo')
            <a href="{{ route('reportes.excel') }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel"></i> Descargar Sábana
            </a>
            @endhasanyrole
            <a href="{{ route('inspecciones.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nuevo Dictamen
            </a>
        </div>
    </div>
    <div class="card-body bg-light border-bottom py-2 px-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3 col-sm-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 rounded-end-pill" placeholder="Buscar por folio, predio, veterinario..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-2 col-sm-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar"></i></span>
                    <input type="date" name="fecha_desde" class="form-control border-start-0" placeholder="Desde" value="{{ request('fecha_desde') }}">
                </div>
            </div>
            <div class="col-md-2 col-sm-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar"></i></span>
                    <input type="date" name="fecha_hasta" class="form-control border-start-0" placeholder="Hasta" value="{{ request('fecha_hasta') }}">
                </div>
            </div>
            <div class="col-md-2 col-sm-4">
                <select name="estado" class="form-select form-select-sm rounded-pill">
                    <option value="">Todos los estados</option>
                    <option value="borrador" @selected(request('estado') === 'borrador')>Borrador</option>
                    <option value="finalizado" @selected(request('estado') === 'finalizado')>Finalizado</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary rounded-pill px-3" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                @if(request()->anyFilled(['search', 'fecha_desde', 'fecha_hasta', 'estado']))
                    <a href="{{ route('inspecciones.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-x-lg"></i> Limpiar</a>
                @endif
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-mobile-cards">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Folio</th>
                        <th>Fecha</th>
                        <th>Predio / Localidad</th>
                        <th>Veterinario</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inspecciones as $inspeccion)
                    <tr>
                        <td class="ps-4" data-label="Folio">
                            @if(empty($inspeccion->folio) || $inspeccion->folio === $inspeccion->clave_interna)
                                <span class="text-muted fst-italic fw-bold">{{ $inspeccion->clave_interna ?: 'Sin Folio' }}</span>
                            @else
                                <a href="{{ route('inspecciones.show', $inspeccion->id) }}" class="text-decoration-none text-primary fw-bold">
                                    {{ $inspeccion->folio }}
                                </a>
                            @endif
                        </td>
                        <td data-label="Fecha">{{ \Carbon\Carbon::parse($inspeccion->fecha)->format('d/m/Y') }}</td>
                        <td data-label="Predio">
                            <div class="fw-semibold">{{ $inspeccion->predio->nombre_rancho }}</div>
                            <small class="text-secondary">{{ $inspeccion->predio->productor->nombre }} {{ $inspeccion->predio->productor->apellido_paterno }}</small>
                        </td>
                        <td data-label="Veterinario">{{ $inspeccion->veterinario->name }}</td>
                        <td data-label="Estado">
                            @if($inspeccion->estado === 'borrador')
                                <span class="badge bg-warning text-dark"><i class="bi bi-pencil-square me-1"></i> Borrador</span>
                            @else
                                <span class="badge bg-success"><i class="bi bi-check-all me-1"></i> Finalizado</span>
                            @endif
                            @if($inspeccion->modified_at)
                                @if(in_array($inspeccion->estado, ['finalizado', 'sincronizado']))
                                    <span class="badge bg-info text-dark mt-1 d-block">
                                        <i class="bi bi-arrow-repeat me-1"></i> Modificado {{ $inspeccion->modified_at->format('d/m H:i') }}
                                    </span>
                                @else
                                    <small class="text-muted d-block mt-1" style="font-size:0.7rem;">
                                        <i class="bi bi-clock me-1"></i> {{ $inspeccion->modified_at->format('d/m H:i') }}
                                    </small>
                                @endif
                            @endif
                        </td>
                        <td data-label="Acciones">
                            <div class="d-flex gap-2">
                                @if($inspeccion->estado === 'borrador' || auth()->user()->hasRole('Administrador'))
                                <a href="{{ route('inspecciones.edit', $inspeccion->id) }}" class="btn btn-sm btn-primary" title="Editar / Finalizar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                <a href="{{ route('reportes.pdf', $inspeccion->id) }}" class="btn btn-sm btn-outline-danger" title="Ver PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <a href="{{ route('inspecciones.show', $inspeccion->id) }}" class="btn btn-sm btn-outline-primary" title="Detalles">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @role('Administrador')
                                <form action="{{ route('inspecciones.destroy', $inspeccion->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este dictamen? Esta acción no se puede deshacer.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endrole
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white py-3">
        {{ $inspecciones->links() }}
    </div>
</div>

<script>
    (function() {
        const syncAlert = document.getElementById('offline-global-sync-alert');
        const syncButton = document.getElementById('btn-sync-all-offline');
        
        // Scan localStorage for pending drafts
        const pendingDrafts = [];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key.startsWith('sigdip:borrador:')) {
                try {
                    const record = JSON.parse(localStorage.getItem(key));
                    if (record && record.status === 'pendiente') {
                        // Extract pathname to match against table edit links
                        const pathname = key.replace('sigdip:borrador:', '').split('?')[0];
                        pendingDrafts.push({ key, pathname, record });
                    }
                } catch(e) {}
            }
        }

        if (pendingDrafts.length > 0) {
            // Show global alert banner
            if (syncAlert) {
                syncAlert.classList.remove('d-none');
            }

            // Find matching rows in table and inject warning badges
            pendingDrafts.forEach(function(draft) {
                // Find edit link targeting the pathname
                const editLinks = document.querySelectorAll('a[href*="' + draft.pathname + '"]');
                editLinks.forEach(function(link) {
                    const row = link.closest('tr');
                    if (row) {
                        row.classList.add('table-warning-soft');
                        row.style.backgroundColor = 'rgba(255, 193, 7, 0.08)';
                        
                        // Add warning badge next to the status cell
                        const statusCell = row.querySelector('td[data-label="Estado"]');
                        if (statusCell) {
                            const badge = document.createElement('span');
                            badge.className = 'badge bg-warning text-dark border border-warning border-opacity-20 d-block mt-1 small';
                            badge.innerHTML = '<i class="bi bi-cloud-arrow-up-fill me-1"></i> Cambios locales';
                            statusCell.appendChild(badge);
                        }
                    }
                });
            });
        }

        // Global synchronization function
        window.syncAllPendingDrafts = async function() {
            const spinner = document.getElementById('sync-spinner');
            const icon = document.getElementById('sync-icon');
            
            if (spinner) spinner.classList.remove('d-none');
            if (icon) icon.classList.add('d-none');
            if (syncButton) syncButton.disabled = true;

            let successCount = 0;
            let failCount = 0;
            let lastErrorMessage = '';

            for (const draft of pendingDrafts) {
                try {
                    // Refresh token in payload to prevent 419 Page Expired
                    draft.record.payload._token = '{{ csrf_token() }}';

                    // Prepare body payload as FormData
                    const formData = new FormData();
                    Object.entries(draft.record.payload || {}).forEach(([k, v]) => {
                        if (Array.isArray(v)) {
                            v.forEach(val => formData.append(k, val));
                        } else {
                            formData.append(k, v);
                        }
                    });

                    // Perform POST request
                    const response = await fetch(draft.record.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    const contentType = response.headers.get('content-type') || '';
                    const body = contentType.includes('application/json') ? await response.json() : {};

                    if (!response.ok || body.success === false) {
                        if (response.status === 422 && body.errors) {
                            const firstError = Object.values(body.errors).flat()[0];
                            throw new Error(firstError || 'Fallo de validación en el servidor.');
                        }
                        throw new Error(body.message || 'Error desconocido del servidor.');
                    }

                    // Succeeded! Mark as synchronized
                    draft.record.status = 'sincronizado';
                    localStorage.setItem(draft.key, JSON.stringify(draft.record));
                    successCount++;
                } catch (error) {
                    console.error('Fallo al sincronizar draft:', draft.key, error);
                    failCount++;
                    lastErrorMessage = error.message;
                }
            }

            if (spinner) spinner.classList.add('d-none');
            if (icon) icon.classList.remove('d-none');
            if (syncButton) syncButton.disabled = false;

            if (successCount > 0 && failCount === 0) {
                alert('¡Éxito! Se sincronizaron ' + successCount + ' dictamen(es) correctamente con la base de datos.');
                window.location.reload();
            } else if (successCount > 0 && failCount > 0) {
                alert('Sincronización parcial: se sincronizaron ' + successCount + ' dictamen(es), pero ' + failCount + ' fallaron. Detalle: ' + lastErrorMessage);
                window.location.reload();
            } else {
                alert('Fallo de sincronización: ' + (lastErrorMessage || 'Por favor revisa tu conexión a internet o inicia sesión de nuevo.'));
            }
        };
    })();
</script>
@endsection
