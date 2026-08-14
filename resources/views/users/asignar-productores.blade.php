@extends('layouts.app')

@section('title', 'Asignar Productores')
@section('header_title', 'Asignar Productores a: ' . $usuario->name)
@section('header_subtitle', 'Administre los productores asignados al médico verificador')
@section('back_url', route('usuarios.show', $usuario))

@section('content')
<div class="row">
    <div class="col-lg-12">
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm mb-4 rounded-4">{{ session('success') }}</div>
        @endif

        <div class="row">
            <!-- COLUMNA: ASIGNADOS ACTUALMENTE -->
            <div class="col-md-5 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Asignados Actualmente
                        </h5>
                        <span class="badge bg-success rounded-pill">{{ $asignados->count() }}</span>
                    </div>
                    <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                        @forelse($asignados as $productor)
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                            <div>
                                <div class="fw-bold">{{ $productor->nombre }} {{ $productor->apellido_paterno }} {{ $productor->apellido_materno }}
                                    @if($productor->clave)
                                        <span class="badge bg-secondary rounded-pill">Hato: {{ $productor->clave }}</span>
                                    @endif
                                </div>
                                <small class="text-muted">UPP: {{ $productor->upp ?? 'N/A' }} | Predios: {{ $productor->predios_count }}</small>
                            </div>
                            <form action="{{ route('usuarios.desasignar-productor', [$usuario, $productor]) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Desasignar este productor de {{ $usuario->name }}?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Desasignar">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        </div>
                        @empty
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            <small>No hay productores asignados a este médico.</small>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- COLUMNA: DISPONIBLES PARA ASIGNAR -->
            <form action="{{ route('usuarios.guardar-asignacion', $usuario) }}" method="POST" class="col-md-7 mb-4">
                @csrf
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-person-plus-fill text-primary me-2"></i>
                            Productores Disponibles
                        </h5>
                        <span class="badge bg-primary rounded-pill">{{ $disponibles->count() }}</span>
                    </div>
                    <div class="card-body p-0">
                        @if($disponibles->count() > 0)
                        <div class="p-3 bg-light border-bottom">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" id="filtroDisponibles" class="form-control border-start-0" placeholder="Filtrar por nombre, CURP o UPP...">
                            </div>
                        </div>
                        @endif
                        <div style="max-height: 450px; overflow-y: auto;">
                            @forelse($disponibles as $productor)
                            <div class="d-flex align-items-center p-3 border-bottom item-disponible" data-search="{{ strtolower($productor->nombre . ' ' . $productor->apellido_paterno . ' ' . $productor->apellido_materno . ' ' . $productor->curp . ' ' . $productor->upp) }}">
                                <div class="form-check me-3">
                                    <input 
                                        class="form-check-input productor-checkbox" 
                                        type="checkbox" 
                                        name="productor_ids[]" 
                                        value="{{ $productor->id }}" 
                                        id="prod_{{ $productor->id }}"
                                        data-clave="{{ $productor->clave ?? '' }}"
                                    >
                                </div>
                                <label class="form-check-label flex-grow-1" for="prod_{{ $productor->id }}" style="cursor: pointer;">
                                    <div class="fw-bold">{{ $productor->nombre }} {{ $productor->apellido_paterno }} {{ $productor->apellido_materno }}
                                        @if($productor->clave)
                                            <span class="badge bg-secondary rounded-pill">Hato: {{ $productor->clave }}</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">CURP: {{ $productor->curp ?? 'N/A' }} | UPP: {{ $productor->upp ?? 'N/A' }} | Predios: {{ $productor->predios_count }}</small>
                                </label>
                            </div>
                            @empty
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2"></i>
                                <small>Todos los productores están asignados a algún médico.</small>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-2">
                    <a href="{{ route('usuarios.show', $usuario) }}" class="btn btn-light border px-4">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="bi bi-save me-1"></i> Guardar Asignación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.productor-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
}
.item-disponible:hover {
    background-color: #f8fafc;
}
</style>

@endsection

@section('scripts')
<script>
    document.getElementById('filtroDisponibles')?.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.item-disponible').forEach(el => {
            const search = el.dataset.search || '';
            el.style.display = !q || search.includes(q) ? '' : 'none';
        });
    });

    // Seleccionar/deseleccionar todos con doble clic en el header
    let selectAll = false;
    document.querySelector('.card-header .bi-person-plus-fill')?.closest('.card-header')?.addEventListener('dblclick', function() {
        selectAll = !selectAll;
        document.querySelectorAll('.productor-checkbox').forEach(cb => {
            cb.checked = selectAll;
        });
    });

    // Un hato (misma clave) siempre se asigna completo:
    // al marcar un productor, se marcan todos los del mismo hato.
    document.querySelectorAll('.productor-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const clave = this.dataset.clave;
            if (!clave) return;
            document.querySelectorAll('.productor-checkbox').forEach(other => {
                if (other.dataset.clave === clave) {
                    other.checked = this.checked;
                }
            });
        });
    });
</script>
@endsection