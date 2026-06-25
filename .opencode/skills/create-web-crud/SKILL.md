---
name: create-web-crud
description: Generate Laravel WebController + 5 Blade views + web.php routes for CRUD, following SIGDIP conventions
---

## What it does

Scaffolds the web-side CRUD files for an existing entity:
- `app/Http/Controllers/{Entity}Controller.php`
- `resources/views/{entity}/index.blade.php` (list with search)
- `resources/views/{entity}/create.blade.php` (creation form)
- `resources/views/{entity}/edit.blade.php` (edit form)
- `resources/views/{entity}/show.blade.php` (detail view)
- `resources/views/{entity}/_form.blade.php` (shared form partial)
- Updates `routes/web.php` with the resource route

## Prerequisites

The model must already exist (use `create-model` skill first). The skill reads the model's `$table`, `$fillable`, `$casts`, and relation methods to infer fields.

## Instructions

### 1. Ask the user for details

1. **Entity name** (PascalCase singular, e.g. `Categoria`)
2. **Route name** (kebab-case plural, e.g. `categorias`)
3. **Route parameter name** (singular snake_case, e.g. `categoria` — Laravel auto-resolves but confirm)
4. **Controller parameter variable name** (e.g. `$categoria` for edit/update/destroy)
5. **Admin only?** (wrap in `middleware('role:Administrador')` group) or accessible by all roles?
6. **Searchable fields** — which fields/relations appear in the search bar
7. **Index table columns** — which columns to show in the list table, and their labels
8. **Show view fields** — which fields to show in the detail view
9. **Related data for create/edit selects** — which relations need a `<select>` dropdown (load in `create()`/`edit()`)
10. **Validation rules** — per field for `store()` and `update()` (remember unique ignore on update)
11. **Page size** — `paginate(10)` or `paginate(20)` (default 10)
12. **Spanish labels** — singular name, plural name, success messages (created/updated/deleted)
13. **Role-based scoping** — if non-admin users should only see their own records (via `medico_id` or similar)

### 2. Generate the Controller

Use `php artisan make:controller {Entity}Controller --resource` as base, then customize.

#### Import pattern
```php
<?php

namespace App\Http\Controllers;

use App\Models\{Entity};
use App\Models\{RelatedModel1};
use App\Models\{RelatedModel2};
use Illuminate\Http\Request;
```

#### index() — List with search + role scoping
```php
public function index(Request $request)
{
    $user = auth()->user();
    $query = {Entity}::with('relation1', 'relation2');

    // Role-based scoping
    if ($user && !$user->hasRole('Administrador')) {
        // scope by user's records: $query->where('medico_id', $user->id);
        // or via relation: $query->whereHas('relation', fn($q) => $q->where('medico_id', $user->id));
    }

    // Search
    if ($search = $request->get('search')) {
        $query->where(function ($q) use ($search) {
            $q->where('field1', 'like', "%{$search}%")
              ->orWhere('field2', 'like', "%{$search}%")
              ->orWhereHas('relation', fn($rq) => $rq->where('rel_field', 'like', "%{$search}%"));
        });
    }

    // Additional filters (if applicable)
    // if ($request->filled('fk_col')) { $query->where('fk_col', $request->fk_col); }

    ${records} = $query->latest()->paginate(10)->withQueryString();

    return view('{entity}.index', compact('{records}'));
}
```

#### create() — Show creation form
```php
public function create()
{
    // Load relations for <select> dropdowns
    ${relations} = RelatedModel::orderBy('name')->get();
    // or: ${relations} = RelatedModel::pluck('name_column', 'id');

    return view('{entity}.create', compact('{relations}'));
}
```

#### store() — Validate + create
```php
public function store(Request $request)
{
    $validated = $request->validate([
        'field1' => 'required|string|max:255',
        'field2' => 'nullable|email|max:255',
        'unique_field' => 'required|string|max:255|unique:{table},column',
        'fk_id' => 'required|exists:{related_table},id',
    ]);

    // Auto-set fields if needed (e.g. veterinario_id for non-admin)
    // if (!auth()->user()->hasRole('Administrador')) { $validated['veterinario_id'] = auth()->id(); }

    ${model} = {Entity}::create($validated);

    return redirect()->route('{entity}.index')->with('success', '{Entity} creado correctamente.');
}
```

#### show() — Detail view
```php
public function show({Entity} ${model})
{
    ${model}->load('relation1', 'relation2');
    return view('{entity}.show', compact('{model}'));
}
```

#### edit() — Show edit form
```php
public function edit({Entity} ${model})
{
    // Same relations as create()
    ${relations} = RelatedModel::orderBy('name')->get();
    return view('{entity}.edit', compact('{model}', '{relations}'));
}
```

#### update() — Validate + update
```php
public function update(Request $request, {Entity} ${model})
{
    $validated = $request->validate([
        'field1' => 'required|string|max:255',
        'unique_field' => 'required|string|max:255|unique:{table},column,'.${model}->id,
        'fk_id' => 'required|exists:{related_table},id',
    ]);

    ${model}->update($validated);

    return redirect()->route('{entity}.index')->with('success', '{Entity} actualizado correctamente.');
}
```

#### destroy() — Delete
```php
public function destroy({Entity} ${model})
{
    ${model}->delete();
    return redirect()->route('{entity}.index')->with('success', '{Entity} eliminado correctamente.');
}
```

**Use PUT or PATCH?** Follow the project's pattern:
- PUT: admin-only controllers (Animal, AreteCenso)
- PATCH: mixed-role controllers (Predio, Visita, Productor)
Default to PATCH unless admin-only.

### 3. Generate the _form.blade.php partial

File: `resources/views/{entity}/_form.blade.php`

Pattern (no layout, just HTML):
```blade
<div class="row g-3">
    <div class="col-md-6">
        <label for="field_name" class="form-label fw-semibold">{Label}</label>
        <input type="text" name="field_name" id="field_name"
            class="form-control @error('field_name') is-invalid @enderror"
            value="{{ old('field_name', ${model}->field_name ?? '') }}"
            placeholder="{Placeholder}" {{-- required? --}}>
        @error('field_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- For selects (FK) --}}
    <div class="col-md-6">
        <label for="fk_id" class="form-label fw-semibold">{Label}</label>
        <select name="fk_id" id="fk_id"
            class="form-select @error('fk_id') is-invalid @enderror">
            <option value="">-- Seleccionar --</option>
            @foreach(${relations} as $option)
                <option value="{{ $option->id }}" {{ old('fk_id', ${model}->fk_id ?? '') == $option->id ? 'selected' : '' }}>
                    {{ $option->name_column }}
                </option>
            @endforeach
        </select>
        @error('fk_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- For textareas --}}
    <div class="col-md-12">
        <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
        <textarea name="observaciones" id="observaciones" rows="3"
            class="form-control @error('observaciones') is-invalid @enderror">{{ old('observaciones', ${model}->observaciones ?? '') }}</textarea>
        @error('observaciones')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- For booleans --}}
    <div class="col-md-6">
        <div class="form-check form-switch pt-4">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" id="activo" class="form-check-input" value="1"
                {{ old('activo', ${model}->activo ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="activo">¿Activo?</label>
        </div>
    </div>
</div>
```

**IMPORTANT field value pattern**: Always use `old('field', ${model}->field ?? '')` so the same partial works for create (model is null → empty string) and edit (model has values → populated).

### 4. Generate index.blade.php

```blade
@extends('layouts.app')

@section('title', 'Gestión de {Entities}')
@section('header_title', '{Entities}')
@section('header_subtitle', 'Administre los {entities} del sistema')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">Listado de {Entities}</h5>
        <a href="{{ route('{entity}.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo {Entity}
        </a>
    </div>

    {{-- SEARCH --}}
    <div class="card-body bg-light border-bottom py-2 px-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4 col-sm-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 rounded-end-pill"
                        placeholder="Buscar por {field}..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary rounded-pill px-3" type="submit">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                @if(request('search'))
                    <a href="{{ route('{entity}.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-x-lg"></i> Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-mobile-cards">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">{Column1}</th>
                        <th>{Column2}</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(${records} as ${record})
                    <tr>
                        <td class="ps-4" data-label="{Label}">{{ ${record}->field1 }}</td>
                        <td data-label="{Label}">{{ ${record}->relation->field ?? 'N/A' }}</td>
                        <td data-label="Acciones">
                            <div class="d-flex gap-2">
                                <a href="{{ route('{entity}.show', ${record}->id) }}"
                                    class="btn btn-sm btn-outline-info" title="Ver Detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('{entity}.edit', ${record}->id) }}"
                                    class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('{entity}.destroy', ${record}->id) }}" method="POST"
                                    class="d-inline" onsubmit="return confirm('¿Eliminar este {entity}?')">
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
                        <td colspan="3" class="text-center py-5 text-muted">
                            <i class="bi bi-archive fs-2 d-block mb-2"></i>
                            Sin {entities} registrados
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer bg-white py-3">
        {{ ${records}->links() }}
    </div>
</div>
@endsection
```

### 5. Generate create.blade.php

```blade
@extends('layouts.app')

@section('title', 'Registrar {Entity}')
@section('header_title', 'Registrar {Entity}')
@section('header_subtitle', 'Complete los datos del nuevo {entity}')
@section('back_url', route('{entity}.index'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('{entity}.store') }}" method="POST">
                    @csrf
                    @include('{entity}._form', ['{model}' => null])
                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <a href="{{ route('{entity}.index') }}" class="btn btn-light px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-5">Guardar {Entity}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
```

### 6. Generate edit.blade.php

```blade
@extends('layouts.app')

@section('title', 'Editar {Entity}')
@section('header_title', 'Editar {Entity}')
@section('header_subtitle', 'Modifique los datos del {entity}')
@section('back_url', route('{entity}.index'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('{entity}.update', ${model}->id) }}" method="POST">
                    @csrf
                    @method('PATCH') {{-- or @method('PUT') --}}
                    @include('{entity}._form', ['{model}' => ${model}])
                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <a href="{{ route('{entity}.index') }}" class="btn btn-light px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-5">Actualizar {Entity}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
```

### 7. Generate show.blade.php

```blade
@extends('layouts.app')

@section('title', 'Detalle del {Entity}')
@section('header_title', ${model}->{display_field})
@section('header_subtitle', 'Información detallada del {entity}')
@section('back_url', route('{entity}.index'))

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Información del {Entity}</h5>
            </div>
            <div class="card-body p-4">
                <table class="table table-borderless mb-0">
                    @foreach($fields as $label => $valueExpr)
                    <tr>
                        <td class="text-muted ps-0" style="width: 140px;">{{ $label }}</td>
                        <td class="fw-bold">{{ $valueExpr }}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
    {{-- optional second column for related data --}}
</div>
<div class="d-flex gap-2">
    <a href="{{ route('{entity}.edit', ${model}->id) }}" class="btn btn-primary">
        <i class="bi bi-pencil"></i> Editar
    </a>
    <form action="{{ route('{entity}.destroy', ${model}->id) }}" method="POST"
        class="d-inline" onsubmit="return confirm('¿Eliminar este {entity}?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger">
            <i class="bi bi-trash"></i> Eliminar
        </button>
    </form>
    <a href="{{ route('{entity}.index') }}" class="btn btn-outline-secondary ms-auto">Volver</a>
</div>
@endsection
```

### 8. Update routes/web.php

Find the appropriate auth group and add:

```php
// Within Route::middleware(['auth'])->group(function () { ... })

// If admin-only:
Route::middleware('role:Administrador')->group(function () {
    Route::resource('{entity}', {Entity}Controller::class);
    // optionally with explicit parameter: ->parameters(['{entity}' => '{param}']);
});

// If accessible by all roles:
Route::resource('{entity}', {Entity}Controller::class);
```

Make sure to add the import at the top of `web.php`:
```php
use App\Http\Controllers\{Entity}Controller;
```

### 9. Verify

Run `./vendor/bin/pint` to lint the controller code.
