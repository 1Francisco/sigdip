<div class="row g-3">
    <div class="col-md-12">
        <label class="form-label fw-semibold">Número de Arete</label>
        <input type="text" name="numero_arete" class="form-control" required
            value="{{ old('numero_arete', $aretes_censo->numero_arete ?? '') }}"
            placeholder="Ej. MX1234567890">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Productor</label>
        <select name="productor_id" class="form-select" required>
            <option value="">Seleccione un productor...</option>
            @foreach($productores as $p)
            <option value="{{ $p->id }}" {{ old('productor_id', $aretes_censo->productor_id ?? '') == $p->id ? 'selected' : '' }}>
                {{ $p->nombre }} {{ $p->apellido_paterno }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Predio</label>
        <select name="predio_id" class="form-select" required>
            <option value="">Seleccione un predio...</option>
            @foreach($predios as $p)
            <option value="{{ $p->id }}" {{ old('predio_id', $aretes_censo->predio_id ?? '') == $p->id ? 'selected' : '' }}
                data-productor="{{ $p->productor_id }}">
                {{ $p->nombre_rancho }} — {{ $p->productor?->nombre }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Raza</label>
        <input type="text" name="raza" class="form-control"
            value="{{ old('raza', $aretes_censo->raza ?? '') }}"
            placeholder="Ej. Suizo Americano">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Sexo</label>
        <select name="sexo" class="form-select">
            <option value="">Seleccione...</option>
            <option value="Macho" {{ old('sexo', $aretes_censo->sexo ?? '') == 'Macho' ? 'selected' : '' }}>Macho</option>
            <option value="Hembra" {{ old('sexo', $aretes_censo->sexo ?? '') == 'Hembra' ? 'selected' : '' }}>Hembra</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Edad (meses)</label>
        <input type="number" name="edad_meses" class="form-control" min="0"
            value="{{ old('edad_meses', $aretes_censo->edad_meses ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Fecha de Nacimiento</label>
        <input type="date" name="fecha_nacimiento" class="form-control"
            value="{{ old('fecha_nacimiento', $aretes_censo->fecha_nacimiento ?? '') }}">
    </div>
    <div class="col-md-6">
        <div class="form-check mt-4">
            <input type="checkbox" name="sacrificio" class="form-check-input" value="1" id="sacrificio"
                {{ old('sacrificio', $aretes_censo->sacrificio ?? false) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="sacrificio">Sacrificio</label>
        </div>
    </div>
</div>
