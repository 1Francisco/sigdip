---
name: inspect-data
description: Run Laravel Tinker or artisan commands to inspect database records, relationships, and schemas
---

## What it does

Helps explore data in the SIGDIP database using Tinker and artisan commands — useful for debugging sync issues, checking record counts, and inspecting relationships.

## Instructions

### 1. Ask the user

1. **What to inspect**: records, schema, relations, or custom query?
2. **Model/table name** to inspect
3. **Filters** (optional) — e.g. `id=5`, `estado=borrador`, `recent`

### 2. Commands by use case

#### Count records
```bash
php artisan tinker --execute="echo \App\Models\{Model}::count();"
```

#### List recent records
```bash
php artisan tinker --execute="\App\Models\{Model}::latest()->take(10)->get();"
```

#### Show table schema
```bash
php artisan db:table --table={table}
```

#### Show all table names
```bash
php artisan db:show
```

#### Eager load relations
```bash
php artisan tinker --execute="\App\Models\{Model}::with(['relation1', 'relation2'])->find({id});"
```

#### Search by field
```bash
php artisan tinker --execute="\App\Models\{Model}::where('{field}', 'like', '%{value}%')->get();"
```

#### Check migration status
```bash
php artisan migrate:status
```

#### List pending sync items (offline debugging)
```php
// In Tinker:
\App\Models\Inspeccion::where('estado', 'borrador')->count();
\App\Models\Inspeccion::where('estado', 'borrador')->with(['predio.productor', 'veterinario'])->get();
```

#### Find orphaned records (FK pointing to non-existent parent)
```bash
php artisan tinker --execute="\App\Models\{Child}::whereDoesntHave('{relation}')->get();"
```

### 3. Notes

- Run `php artisan tinker` for interactive mode (multi-line queries)
- Use `--execute=` flag for one-liners
- Use `refresh()` and `fresh()` on model instances to check DB state after mutations
- For production Railway DB, connect via `php artisan tinker --env=production` (if .env file configured)
