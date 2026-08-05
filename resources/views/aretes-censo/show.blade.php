@extends('layouts.app')

@section('title', 'Detalle del Arete')
@section('header_title', 'Detalle del Arete del Censo')
@section('header_subtitle', 'Información completa del registro')
@section('back_url', route('predios.index', ['tab' => 'aretes']))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h5 class="fw-bold mb-1">Arete: {{ $aretes_censo->numero_arete }}</h5>
                        <p class="text-muted small mb-0">Registrado el {{ $aretes_censo->created_at->format('d/m/Y') }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('aretes-censo.edit', $aretes_censo->id) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <form action="{{ route('aretes-censo.destroy', $aretes_censo->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este arete?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i> Eliminar
                            </button>
                        </form>
                    </div>
                </div>

                <table class="table table-bordered">
                    <tr>
                        <th class="bg-light" style="width: 200px;">Número de Arete</th>
                        <td><code>{{ $aretes_censo->numero_arete }}</code></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Productor</th>
                        <td>{{ $aretes_censo->productor?->nombre }} {{ $aretes_censo->productor?->apellido_paterno }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Predio</th>
                        <td>{{ $aretes_censo->predio?->nombre_rancho ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Raza</th>
                        <td>{{ $aretes_censo->raza ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Sexo</th>
                        <td>{{ $aretes_censo->sexo ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Fecha de Nacimiento</th>
                        <td>{{ $aretes_censo->fecha_nacimiento ? \Carbon\Carbon::parse($aretes_censo->fecha_nacimiento)->format('d/m/Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Edad (meses)</th>
                        <td>{{ $aretes_censo->edad_meses ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Sacrificio</th>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge {{ $aretes_censo->sacrificio ? 'bg-danger' : 'bg-secondary' }} fs-6 px-3 py-2">
                                    {{ $aretes_censo->sacrificio ? 'Sacrificado' : 'Activo' }}
                                </span>
                                <form action="{{ route('aretes-censo.toggle-sacrificio', $aretes_censo->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $aretes_censo->sacrificio ? 'btn-outline-success' : 'btn-outline-danger' }} rounded-pill">
                                        <i class="bi {{ $aretes_censo->sacrificio ? 'bi-arrow-counterclockwise' : 'bi-x-circle' }} me-1"></i>
                                        {{ $aretes_censo->sacrificio ? 'Reactivar' : 'Marcar como sacrificado' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
