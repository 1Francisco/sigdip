@extends('layouts.app')

@section('title', 'Gestión de Predios')
@section('header_title', 'Predios / Ranchos')
@section('header_subtitle', 'Administre las unidades de producción registradas')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white p-0">
        <ul class="nav nav-tabs border-bottom-0 px-3 pt-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold px-4 {{ $tab === 'predios' ? 'active' : '' }}"
                        data-bs-toggle="tab" data-bs-target="#tab-predios" type="button" role="tab"
                        aria-selected="{{ $tab === 'predios' ? 'true' : 'false' }}">
                    <i class="bi bi-house-door me-1"></i> Predios
                </button>
            </li>
            @auth
                @if(auth()->user()->hasRole('Administrador'))
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold px-4 {{ $tab === 'aretes' ? 'active' : '' }}"
                            data-bs-toggle="tab" data-bs-target="#tab-aretes" type="button" role="tab"
                            aria-selected="{{ $tab === 'aretes' ? 'true' : 'false' }}">
                        <i class="bi bi-upc-scan me-1"></i> Aretes del Censo
                    </button>
                </li>
                @endif
            @endauth
        </ul>
    </div>

    <div class="tab-content">
        {{-- TAB PREDIOS --}}
        <div class="tab-pane fade {{ $tab === 'predios' ? 'show active' : '' }}" id="tab-predios" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center py-3 px-4 border-bottom">
                <h5 class="mb-0 fw-bold">Listado de Predios</h5>
                <a href="{{ route('predios.create') }}" class="btn btn-primary">
                    <i class="bi bi-house-add"></i> Nuevo Predio
                </a>
            </div>
            <div class="card-body bg-light border-bottom py-2 px-4">
                <form method="GET" class="row g-2 align-items-center">
                    <input type="hidden" name="tab" value="predios">
                    <div class="col-md-4 col-sm-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 rounded-end-pill" placeholder="Buscar por rancho, UPP, localidad..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-primary rounded-pill px-3" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                        @if(request('search'))
                            <a href="{{ route('predios.index', ['tab' => 'predios']) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-x-lg"></i> Limpiar</a>
                        @endif
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-mobile-cards">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Nombre del Rancho</th>
                                <th>UPP (Clave de Unidad)</th>
                                <th>Localidad</th>
                                <th>Productor Responsable</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($predios as $predio)
                            <tr>
                                <td class="ps-4" data-label="Rancho">
                                    <div class="fw-bold text-dark">{{ $predio->nombre_rancho }}</div>
                                </td>
                                <td data-label="UPP"><code>{{ $predio->clave_unidad_produccion }}</code></td>
                                <td data-label="Localidad">{{ $predio->localidad }}</td>
                                <td data-label="Productor">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center d-none d-lg-flex" style="width: 24px; height: 24px; font-size: 0.7rem;">
                                            <i class="bi bi-person"></i>
                                        </div>
                                        <span>{{ $predio->productor->nombre }}</span>
                                    </div>
                                </td>
                                <td data-label="Acciones">
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('predios.show', $predio->id) }}" class="btn btn-sm btn-outline-info" title="Ver Detalle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('predios.edit', $predio->id) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('predios.destroy', $predio->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este predio?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white py-3">
                {{ $predios->appends(['tab' => 'predios'])->links() }}
            </div>
        </div>

        {{-- TAB ARETES --}}
        @auth
        @if(auth()->user()->hasRole('Administrador'))
        <div class="tab-pane fade {{ $tab === 'aretes' ? 'show active' : '' }}" id="tab-aretes" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center py-3 px-4 border-bottom">
                <h5 class="mb-0 fw-bold">Listado de Aretes del Censo</h5>
                <a href="{{ route('aretes-censo.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Nuevo Arete
                </a>
            </div>
            <div class="card-body bg-light border-bottom py-2 px-4">
                <form method="GET" class="row g-2 align-items-center">
                    <input type="hidden" name="tab" value="aretes">
                    <div class="col-md-4 col-sm-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search_arete" class="form-control border-start-0 rounded-end-pill" placeholder="Buscar por número de arete, raza o productor..." value="{{ request('search_arete') }}">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-primary rounded-pill px-3" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                        @if(request('search_arete'))
                            <a href="{{ route('predios.index', ['tab' => 'aretes']) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-x-lg"></i> Limpiar</a>
                        @endif
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-mobile-cards">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Núm. de Arete</th>
                                <th>Productor</th>
                                <th>Predio</th>
                                <th>Raza</th>
                                <th>Sexo</th>
                                <th>Edad (meses)</th>
                                <th>Sacrificio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($aretes as $arete)
                            <tr>
                                <td class="ps-4" data-label="Arete">
                                    <div class="fw-bold text-dark"><code>{{ $arete->numero_arete }}</code></div>
                                </td>
                                <td data-label="Productor">{{ $arete->productor?->nombre_completo ?? '—' }}</td>
                                <td data-label="Predio">{{ $arete->predio?->nombre_rancho ?? '—' }}</td>
                                <td data-label="Raza">{{ $arete->raza ?? '—' }}</td>
                                <td data-label="Sexo">{{ $arete->sexo ?? '—' }}</td>
                                <td data-label="Edad">{{ $arete->edad_meses ?? '—' }}</td>
                                <td data-label="Sacrificio">
                                    @if($arete->sacrificio)
                                        <span class="badge bg-danger">SÍ</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td data-label="Acciones">
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('aretes-censo.show', $arete->id) }}" class="btn btn-sm btn-outline-info" title="Ver Detalle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('aretes-censo.edit', $arete->id) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('aretes-censo.destroy', $arete->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este arete?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-upc-scan fs-2 d-block mb-2"></i>
                                    No hay aretes registrados en el censo.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white py-3">
                {{ $aretes->appends(['tab' => 'aretes'])->links() }}
            </div>
        </div>
        @endif
        @endauth
    </div>
</div>
@endsection
