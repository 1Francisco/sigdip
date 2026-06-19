# SIGDIP — Repo Guide

## Project
Sistema Integral de Gestión de Dictámenes de Inspección Pecuaria.  
Backend Laravel 10 (PHP 8.1+) + app móvil Vue 3 / Capacitor 8 en `mobile_app/`.

## Testing
- **Run**: `./vendor/bin/phpunit` (no alias en composer.json). Actual: **121 tests, 225 assertions**.
- **DB**: SQLite in-memory ya configurado en `phpunit.xml` (`DB_CONNECTION=sqlite`).
- **Trait**: usar `RefreshDatabase` — `DatabaseTransactions` falla con SQLite anidado.
- **Roles en tests**: crearlos manualmente en `setUp()` con `Role::firstOrCreate(['name' => '...'])`; no existen por defecto.
- **API auth**: `Sanctum::actingAs($user)`.
- **Cypress** (app móvil): `npm run cy:open` / `npm run cy:run` dentro de `mobile_app/`.

## Migraciones
- `renameColumn` necesita `doctrine/dbal` (ya en `composer.json`).
- Varias migraciones tienen bifurcación por driver: `DB::connection()->getDriverName() === 'sqlite'` para usar `renameColumn`, vs `ALTER TABLE ... CHANGE` raw en MySQL.
- Prod ejecuta `php artisan migrate --force` (ver Procfile, railway.json).

## Arquitectura
- Dos interfaces: web (`routes/web.php` — Blade + session auth) y API (`routes/api.php` — Sanctum tokens).
- Roles Spatie: `Administrador`, `Medico_Campo` — usados vía middleware `role:` y en lógica de controladores.
- Modelos clave: `Visita`, `Inspeccion`, `Productor`, `Predio`, `Animal`, `DetalleInspeccion`.
- Exportaciones: PDF (DOMPDF) y Excel (Laravel Excel / PhpSpreadsheet).

## Dev commands
- `./vendor/bin/pint` — linting PHP.
- Servir local: `php artisan serve`.
- `composer run post-root-package-install` copia `.env.example` → `.env`.

## Env quirks
- `APP_TIMEZONE=America/Mazatlan`, `APP_LOCALE=es`.
- `SESSION_LIFETIME=525600` (1 año).
- `.env` local: MySQL root@localhost sin password.

## Scratch scripts
Herramientas auxiliares movidas a `scratch/` (ignorado por git) — `read_ini.php`, `search_data.php`, `locate_curp.php`, etc. Fuera del flujo Laravel.

## Web CRUD
- CRUD completo para: `Productores`, `Predios`, `Visitas`, `Usuarios`, `Inspecciones` (sin destroy hasta F9), `Animales` (F9), `Aretes del Censo` (F9).
- `AnimalController` y `AreteCensoController` solo accesibles por `Administrador` vía middleware `role:`.

## App Móvil
- Rutas de edición explícitas: `/predios/editar/:id` (PredioCreateView), `/inspeccion/editar/:id` (InspeccionFormView), `/medicos/editar/:id` (MedicoEditView), `/visitas/editar/:id` (VisitaCreateView).
- `api.js` expone: `getMedico(id)`, `updateMedico(id, data)`, `getPredios()`, `getInspeccion(id)`, etc.
- **Layout unificado**: `AppLayout.vue` usado por las 12 vistas principales (sidebar, header, bottom-nav ya no duplicados).
- **Estado global**: Pinia store (`stores/inspeccion.js`) reemplaza `sessionStorage` para datos de scanner/borradores.
- **Toast**: Componente `Toast.vue` para notificaciones (disponible, pendiente integración total).

## Deploy
Railway + Nixpacks: `php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT`. Healthcheck en `/login`.
