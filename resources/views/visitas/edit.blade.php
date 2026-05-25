@extends('layouts.app')

@section('title', 'Editar Visita')
@section('header_title', 'Editar Visita Programada')
@section('header_subtitle', 'Modifique los detalles de la visita')
@section('back_url', route('visitas.index'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('visitas.update', $visita->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Productor (Persona)</label>
                            <select id="productor_id" class="form-select" required>
                                <option value="">Seleccione un productor...</option>
                                @foreach($productores as $prod)
                                <option value="{{ $prod->id }}" {{ old('productor_id', $visita->predio->productor_id) == $prod->id ? 'selected' : '' }}>
                                    {{ $prod->nombre_completo }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Predio (Rancho a Visitar)</label>
                            <select id="predio_id" name="predio_id" class="form-select" required>
                                <option value="">Seleccione un predio...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha Programada</label>
                            <input type="date" name="fecha_programada" class="form-control" value="{{ old('fecha_programada', $visita->fecha_programada->format('Y-m-d')) }}" required>
                        </div>
                        @role('Administrador')
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Médico Veterinario Asignado</label>
                            <select name="veterinario_id" class="form-select" required>
                                <option value="">Seleccione un médico...</option>
                                @foreach($veterinarios as $v)
                                <option value="{{ $v->id }}" {{ old('veterinario_id', $visita->veterinario_id) == $v->id ? 'selected' : '' }}>
                                    {{ $v->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <input type="hidden" name="veterinario_id" value="{{ $visita->veterinario_id }}">
                        @endrole
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Notas u Objetivos de la Visita</label>
                            <textarea name="observaciones" class="form-control" rows="3">{{ old('observaciones', $visita->observaciones) }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <a href="{{ route('visitas.index') }}" class="btn btn-light px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-5">Actualizar Visita</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const productores = @json($productores);
        const productorSelect = document.getElementById('productor_id');
        const predioSelect = document.getElementById('predio_id');
        const initialPredioId = "{{ old('predio_id', $visita->predio_id) }}";

        function populatePredios(productorId, selectedPredioId = null) {
            predioSelect.innerHTML = '';

            if (!productorId) {
                predioSelect.innerHTML = '<option value="">Primero seleccione un productor...</option>';
                predioSelect.disabled = true;
                return;
            }

            const selectedProductor = productores.find(p => p.id == productorId);
            if (selectedProductor && selectedProductor.predios && selectedProductor.predios.length > 0) {
                predioSelect.innerHTML = '<option value="">Seleccione un predio...</option>';
                selectedProductor.predios.forEach(predio => {
                    const option = document.createElement('option');
                    option.value = predio.id;
                    option.textContent = `${predio.nombre_rancho} (${predio.localidad || 'Sin localidad'})`;
                    if (selectedPredioId && predio.id == selectedPredioId) {
                        option.selected = true;
                    }
                    predioSelect.appendChild(option);
                });
                predioSelect.disabled = false;

                // Si solo hay un predio y no hay un predio preseleccionado, seleccionarlo automáticamente
                if (selectedProductor.predios.length === 1 && !selectedPredioId) {
                    predioSelect.selectedIndex = 1;
                }
            } else {
                predioSelect.innerHTML = '<option value="">Este productor no tiene predios registrados</option>';
                predioSelect.disabled = true;
            }
        }

        productorSelect.addEventListener('change', function() {
            populatePredios(this.value);
        });

        // Inicializar con los datos preexistentes
        if (productorSelect.value) {
            populatePredios(productorSelect.value, initialPredioId);
        }
    });
</script>
@endsection
