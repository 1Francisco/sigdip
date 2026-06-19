@extends('layouts.app')

@section('title', 'Detalle del Animal')
@section('header_title', 'Detalle del Animal')
@section('header_subtitle', 'Información completa del registro')
@section('back_url', route('animales.index'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h5 class="fw-bold mb-1">Arete: {{ $animale->numero_arete_siniiga }}</h5>
                        <p class="text-muted small mb-0">Registrado el {{ $animale->created_at->format('d/m/Y') }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('animales.edit', $animale->id) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <form action="{{ route('animales.destroy', $animale->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este animal?')">
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
                        <th class="bg-light" style="width: 200px;">Arete SINIIGA</th>
                        <td><code>{{ $animale->numero_arete_siniiga }}</code></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Predio</th>
                        <td>{{ $animale->predio?->nombre_rancho ?? 'N/A' }} <small class="text-muted">({{ $animale->predio?->productor?->nombre }})</small></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Raza</th>
                        <td>{{ $animale->raza ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Sexo</th>
                        <td>{{ $animale->sexo ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Edad (meses)</th>
                        <td>{{ $animale->edad ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
