---
name: create-api-crud
description: Generate Laravel API controller + api.php routes for CRUD, following SIGDIP API conventions
---

## What it does

Scaffolds a RESTful API controller for an existing entity:
- `app/Http/Controllers/Api/{Entity}ApiController.php`
- Updates `routes/api.php` with the resource routes

## Prerequisites

The model must already exist (use `create-model` skill first).

## Instructions

### 1. Ask the user for details

1. **Entity name** (PascalCase singular, e.g. `Categoria`)
2. **Route prefix** (kebab-case plural, e.g. `categorias`)
3. **Admin-only?** (requires `Administrador` role) or accessible by all roles?
4. **Pagination** — paginated (default 20) or all records (`.get()`)?
5. **Searchable fields** — which fields/relations appear in search
6. **Filters** — any exact-match filters (e.g. `?predio_id=5`)
7. **Fields for validation** — per field rules for store/update
8. **Relations to eager-load** — for index, show (list vs detail)
9. **Field mapping to JSON** — which fields to expose in the API response (the `toArray()` method)
10. **Nested relations in JSON** — which relations to nest and which fields of each
11. **Include timestamps** — include `created_at`/`updated_at` in JSON?
12. **Spanish labels** — for success messages

### 2. Generate the Controller

Create `app/Http/Controllers/Api/{Entity}ApiController.php`.

#### Imports
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Entity};
use App\Models\{RelatedModel1};
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
```

#### index() — List with search, filters, role scoping, pagination
```php
public function index(Request $request)
{
    $user = $request->user();
    $query = {Entity}::with(['relation1', 'relation2']);

    // Role-based scoping (if not admin-only)
    if (!$user->hasRole('Administrador')) {
        $query->where('user_id', $user->id);
        // or: $query->whereHas('relation', fn($q) => $q->where('user_id', $user->id));
    }

    // Search
    if ($search = $request->get('search')) {
        $query->where(function ($q) use ($search) {
            $q->where('field1', 'like', "%{$search}%")
              ->orWhere('field2', 'like', "%{$search}%")
              ->orWhereHas('relation', fn($rq) => $rq->where('rel_field', 'like', "%{$search}%"));
        });
    }

    // Filters (exact match)
    if ($request->filled('fk_col')) {
        $query->where('fk_col', $request->fk_col);
    }

    // Paginated or all
    if (${paginated}) {
        $perPage = min((int) ($request->get('perPage', 20)), 100);
        $results = $query->latest()->paginate($perPage);
        $data = collect($results->items())->map(fn ($item) => $this->toArray($item));

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    // Non-paginated
    $data = $query->latest()->get()->map(fn ($item) => $this->toArray($item));

    return response()->json([
        'success' => true,
        'data' => $data,
    ]);
}
```

#### show() — Single record detail
```php
public function show($id)
{
    try {
        ${model} = {Entity}::with(['relation1', 'relation2'])->findOrFail($id);

        // Ownership check (if not admin)
        // $user = $request->user();
        // if (!$user->hasRole('Administrador') && ${model}->user_id !== $user->id) {
        //     return response()->json(['success' => false, 'message' => 'No tienes permiso.'], 403);
        // }

        return response()->json(['success' => true, 'data' => $this->toDetailArray(${model})]);
    } catch (ModelNotFoundException $e) {
        return response()->json(['success' => false, 'message' => '{Entity} no encontrado.'], 404);
    }
}
```

#### store() — Create
```php
public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'field1' => 'required|string|max:255',
            'field2' => 'nullable|email',
            'unique_field' => 'required|string|max:255|unique:{table},column',
            'fk_id' => 'required|exists:{related_table},id',
        ]);

        // Auto-set fields (e.g. user_id for non-admin)
        // if (!$request->user()->hasRole('Administrador')) { $validated['user_id'] = $request->user()->id; }

        ${model} = {Entity}::create($validated);
        ${model}->load(['relation1']);

        return response()->json([
            'success' => true,
            'message' => '{Entity} creado con éxito.',
            'data' => $this->toArray(${model}),
        ], 201);

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error de validación.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error al crear {entity}: '.$e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error crítico al crear {entity}.',
        ], 500);
    }
}
```

#### update() — Update
```php
public function update(Request $request, $id)
{
    try {
        ${model} = {Entity}::findOrFail($id);

        $validated = $request->validate([
            'field1' => 'required|string|max:255',
            'unique_field' => 'required|string|max:255|unique:{table},column,'.${model}->id,
            'fk_id' => 'required|exists:{related_table},id',
        ]);

        ${model}->update($validated);
        ${model}->load(['relation1']);

        return response()->json([
            'success' => true,
            'message' => '{Entity} actualizado con éxito.',
            'data' => $this->toArray(${model}),
        ]);

    } catch (ModelNotFoundException $e) {
        return response()->json(['success' => false, 'message' => '{Entity} no encontrado.'], 404);
    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error de validación.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error al actualizar {entity}: '.$e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error crítico al actualizar {entity}.',
        ], 500);
    }
}
```

#### destroy() — Delete
```php
public function destroy($id)
{
    try {
        ${model} = {Entity}::findOrFail($id);
        ${model}->delete();

        return response()->json([
            'success' => true,
            'message' => '{Entity} eliminado con éxito.',
        ]);

    } catch (ModelNotFoundException $e) {
        return response()->json(['success' => false, 'message' => '{Entity} no encontrado.'], 404);
    } catch (\Exception $e) {
        Log::error('Error al eliminar {entity}: '.$e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error crítico al eliminar {entity}.',
        ], 500);
    }
}
```

#### toArray() / toDetailArray() — Serialization
```php
private function toArray($model): array
{
    return [
        'id' => $model->id,
        'field1' => $model->field1,
        'fk_id' => $model->fk_id,
        'relation' => $model->relation ? [
            'id' => $model->relation->id,
            'name' => $model->relation->name,
        ] : null,
        'created_at' => optional($model->created_at)->format('Y-m-d H:i:s'),
        'updated_at' => optional($model->updated_at)->format('Y-m-d H:i:s'),
    ];
}

// For show() — more detailed, with nested relations
private function toDetailArray($model): array
{
    $data = $this->toArray($model);
    $data['nested_relation'] = $model->nestedRelation->map(fn ($item) => [
        'id' => $item->id,
        'field' => $item->field,
    ]);
    return $data;
}
```

**Important**: Use `optional($value)->format('Y-m-d')` for date fields (as the project does) to avoid null errors.

### 3. Update routes/api.php

Add the routes with proper authentication:

```php
// Within Route::middleware('auth:sanctum')->group(function () { ... })

// Standard RESTful routes:
Route::get('/{entity}', [{Entity}ApiController::class, 'index']);
Route::get('/{entity}/{id}', [{Entity}ApiController::class, 'show']);
Route::post('/{entity}', [{Entity}ApiController::class, 'store']);
Route::put('/{entity}/{id}', [{Entity}ApiController::class, 'update']);
Route::delete('/{entity}/{id}', [{Entity}ApiController::class, 'destroy']);
```

Add the import at the top of `routes/api.php`:
```php
use App\Http\Controllers\Api\{Entity}ApiController;
```

### 4. Verify

Run `./vendor/bin/pint` to lint the controller.
