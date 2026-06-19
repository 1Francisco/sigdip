<div class="row g-3">
    <div class="col-md-12">
        <label class="form-label fw-semibold">Arete SINIIGA</label>
        <input type="text" name="numero_arete_siniiga" class="form-control" required
            value="{{ old('numero_arete_siniiga', $animale->numero_arete_siniiga ?? '') }}"
            placeholder="Ej. MX1234567890">
    </div>
    <div class="col-md-12">
        <label class="form-label fw-semibold">Predio</label>
        <select name="predio_id" class="form-select" required>
            <option value="">Seleccione un predio...</option>
            @foreach($predios as $p)
            <option value="{{ $p->id }}" {{ old('predio_id', $animale->predio_id ?? '') == $p->id ? 'selected' : '' }}>
                {{ $p->nombre_rancho }} — {{ $p->productor?->nombre }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Raza</label>
        <input type="text" name="raza" class="form-control"
            value="{{ old('raza', $animale->raza ?? '') }}"
            placeholder="Ej. Suizo Americano">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Sexo</label>
        <select name="sexo" class="form-select">
            <option value="">Seleccione...</option>
            <option value="Macho" {{ old('sexo', $animale->sexo ?? '') == 'Macho' ? 'selected' : '' }}>Macho</option>
            <option value="Hembra" {{ old('sexo', $animale->sexo ?? '') == 'Hembra' ? 'selected' : '' }}>Hembra</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Edad (meses)</label>
        <input type="number" name="edad" class="form-control" min="0"
            value="{{ old('edad', $animale->edad ?? '') }}"
            placeholder="Ej. 24">
    </div>
</div>
