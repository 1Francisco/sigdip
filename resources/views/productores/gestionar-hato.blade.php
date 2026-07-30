@extends('layouts.app')

@section('title', 'Gestión de Hato')
@section('header_title', 'Gestión de Hato')
@section('header_subtitle', 'Clave: ' . ($productor->clave ?? 'Sin clave'))
@section('back_url', route('productores.show', $productor->id))

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success border-0 shadow-sm mb-4 rounded-4">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger border-0 shadow-sm mb-4 rounded-4">{{ session('error') }}</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Productores del Hato ({{ $productores->count() }})</h5>
            </div>
            <div class="card-body p-0">
                @if($productores->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Nombre</th>
                                <th>UPP</th>
                                <th>Teléfono</th>
                                <th>MVZ</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productores as $p)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $p->nombre_completo }}</div>
                                    @if($p->clave)
                                    <small class="text-muted"><code>{{ $p->clave }}</code></small>
                                    @endif
                                </td>
                                <td>{{ $p->upp ?? '—' }}</td>
                                <td>{{ $p->telefono ?? '—' }}</td>
                                <td>{{ $p->medico?->name ?? '—' }}</td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('productores.show', $p->id) }}" class="btn btn-sm btn-outline-primary" title="Ver">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('productores.edit', $p->id) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-people fs-2 d-block mb-2"></i>
                    No hay otros productores en este hato.
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-link-45deg me-2"></i>Vincular existente</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Asigna la clave <strong>{{ $productor->clave }}</strong> a otro productor existente:</p>
                <form action="{{ route('productores.vincular-existente', $productor->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <select id="vincular-select" class="form-select" name="productor_id" placeholder="Buscar productor…" autocomplete="off"></select>
                    </div>
                    <button type="submit" class="btn btn-outline-primary rounded-pill w-100" id="btnVincular" disabled>
                        <i class="bi bi-plus-circle me-1"></i> Agregar al hato
                    </button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-person-plus me-2"></i>Agregar nuevos</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Registra nuevos productores en este hato:</p>
                <a href="{{ route('productores.create-multiple', ['prefill_from_productor_id' => $productor->id]) }}" class="btn btn-primary rounded-pill w-100 fw-bold">
                    <i class="bi bi-people-fill me-1"></i> Agregar múltiples
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    var vincularSelect = document.getElementById('vincular-select');
    if (vincularSelect) {
        new TomSelect(vincularSelect, {
            valueField: 'id',
            labelField: 'display',
            searchField: ['nombre', 'apellido_paterno', 'apellido_materno', 'clave'],
            maxOptions: 15,
            placeholder: 'Buscar y seleccionar productor…',
            load: function(query, callback) {
                if (query.length < 1) return callback();
                fetch('{{ route("productores.buscar") }}?q=' + encodeURIComponent(query))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        callback(data.map(function(p) {
                            p.display = (p.clave ? '[' + p.clave + '] ' : '') + p.nombre + ' ' + p.apellido_paterno + (p.apellido_materno ? ' ' + p.apellido_materno : '');
                            return p;
                        }));
                    });
            },
            onChange: function(value) {
                document.getElementById('btnVincular').disabled = !value;
            }
        });
    }
</script>
@endsection
