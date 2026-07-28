# SIGDIP

**Sistema Integral de Gestión de Dictámenes de Inspección Pecuaria**

Plataforma para la gestión de dictámenes de tuberculosis bovina, diseñada para la Campaña Nacional contra la Tuberculosis Bovina (CNTB) de SENASICA.

---

## Stack

| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 10 (PHP 8.1+) |
| Base de datos | MySQL (prod) / SQLite (tests) |
| Web (Blade) | Bootstrap 5, jQuery, TomSelect |
| App móvil | Vue 3 + Vite + Capacitor 8 |
| Estado app móvil | Pinia |
| Offline | localforage (IndexedDB) |
| PDF | DOMPDF (barryvdh/laravel-dompdf) |
| Excel | Laravel Excel / PhpSpreadsheet |

---

## Arquitectura

```
                    ┌──────────────────┐
                    │  Web (Blade)     │  ──  Session Auth
                    │  /login          │
                    └────────┬─────────┘
                             │ routes/web.php
                    ┌────────▼─────────┐
                    │   Laravel 10     │
                    │   (API + Web)    │
                    └────────┬─────────┘
                             │ routes/api.php
                    ┌────────▼─────────┐
                    │  App Móvil       │  ──  Sanctum Tokens
                    │  Vue 3 + Capacitor│
                    │  Offline-first   │
                    └──────────────────┘
```

- **Web** (`routes/web.php`): Blade + session auth. CRUD completo de todas las entidades.
- **API** (`routes/api.php`): Sanctum tokens. Consumida por la app móvil.
- **App móvil** (`mobile_app/`): Vue 3, offline-first (localforage → IndexedDB), sincronización en segundo plano.

---

## Modelos Clave y Relaciones

```
Productor (1) ──→ (N) Predio (1) ──→ (N) Inspeccion (1) ──→ (N) DetalleInspeccion
                    Predio (1) ──→ (N) Visita (1) ──→ (1) Inspeccion
                    Productor (N) ──→ (1) User (médico)
```

| Modelo | Tabla | FK relevantes |
|--------|-------|--------------|
| `Productor` | `productores` | `medico_id → users` |
| `Predio` | `predios` | `productor_id → productores` |
| `Visita` | `visitas` | `predio_id → predios`, `veterinario_id → users` |
| `Inspeccion` | `inspecciones` | `predio_id → predios`, `visita_id → visitas`, `grupo_id` (string) |
| `DetalleInspeccion` | `detalles_inspeccion` | `inspeccion_id → inspecciones`, `animal_id → animales` |

---

## Hato y Múltiples Productores

**No existe tabla `hatos`.** El "hato" es un concepto de agrupación basado en el campo `productores.clave`.

### Cómo funciona

1. Dos o más productores comparten la misma `clave` (ej. `BD-123421`) → pertenecen al mismo hato.
2. Al crear un dictamen para un productor, se pueden añadir **productores extra** del mismo hato.
3. El backend agrupa los animales por `productor_id` y crea **N registros `Inspeccion` separados**, uno por cada productor.
4. Todos comparten el mismo `grupo_id` (ej. `GRP-20260728-123456-abc`).

### Generación de PDFs

**Cada `Inspeccion` genera su propio PDF independiente.** No existe un PDF "grupal". Esto significa:

- Productor A → Inspeccion A → PDF_A (solo animales de A)
- Productor B → Inspeccion B → PDF_B (solo animales de B)
- Ambos PDFs son independientes, vinculados por `grupo_id` en base de datos

El PDF se genera con DOMPDF (`resources/views/reports/inspeccion_pdf.blade.php`) y muestra los datos del productor dueño de esa inspección (`$inspeccion->predio->productor`).

### Roles

- `Administrador`: acceso completo (CRUD, asignación de médicos, clave/zona)
- `Medico_Campo`: solo ve sus productores asignados, sin acceso a clave/zona

---

## Estructura del Proyecto

```
SIGDIP_NEW/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/                   # Controladores API (app móvil)
│   │   ├── InspeccionController   # Web: dictámenes
│   │   ├── ProductorController    # Web: productores
│   │   ├── ReporteController      # Web: PDFs y sábanas
│   │   └── ...
│   ├── Models/
│   │   ├── Productor.php
│   │   ├── Predio.php
│   │   ├── Visita.php
│   │   ├── Inspeccion.php
│   │   └── ...
│   └── ...
├── database/
│   ├── migrations/                # Migraciones con bifurcación SQLite/MySQL
│   └── seeders/
├── mobile_app/                    # App Vue 3 + Capacitor 8
│   ├── src/
│   │   ├── views/                 # 27 vistas Vue
│   │   ├── services/
│   │   │   ├── api.js             # Servicio HTTP (fetch + Bearer token)
│   │   │   └── db.js              # localforage (IndexedDB)
│   │   ├── stores/
│   │   │   └── inspeccion.js      # Pinia store
│   │   └── ...
│   ├── cypress/e2e/               # 23 specs, 126 tests
│   └── ...
├── resources/views/
│   ├── inspecciones/              # CRUD dictámenes (Blade)
│   ├── productores/               # CRUD productores (Blade + wizard)
│   ├── reports/
│   │   └── inspeccion_pdf.blade.php  # Template PDF
│   └── ...
├── routes/
│   ├── web.php                    # Rutas web (session auth)
│   └── api.php                    # Rutas API (Sanctum)
├── tests/
│   └── Feature/
│       └── ProductorTest.php      # 38 tests
└── ...
```

---

## Setup Local

```bash
# 1. Clonar y entrar
git clone <repo>
cd SIGDIP_NEW

# 2. Copiar .env y generar key
cp .env.example .env
php artisan key generate

# 3. Configurar DB en .env (MySQL local, root sin password)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigdip
DB_USERNAME=root
DB_PASSWORD=

# 4. Migrar y seedear
php artisan migrate --seed

# 5. Servir
php artisan serve
```

---

## Testing

### Backend (PHPUnit)

```bash
./vendor/bin/phpunit                       # 433 tests
./vendor/bin/phpunit --filter ProductorTest # 38 tests
```

- DB: SQLite in-memory (configurado en `phpunit.xml`)
- Auth: `Sanctum::actingAs($user)`
- Roles: crear en `setUp()` con `Role::firstOrCreate()` — no existen por defecto
- Usar `RefreshDatabase` (no `DatabaseTransactions` — falla con SQLite anidado)

### App Móvil (Cypress)

```bash
cd mobile_app
npm run cy:run          # 23 specs, 126 tests (headless)
npm run cy:open         # navegador interactivo
```

---

## App Móvil

- Vue 3 + Vite + Capacitor 8
- **Offline-first**: catálogos en localforage (IndexedDB), login sin conexión, auto-sync en segundo plano
- **Layout unificado**: `AppLayout.vue` usado por las 27 vistas (sidebar, header, bottom-nav)
- **Router**: hash history (`createWebHashHistory`), auth guard en `beforeEach`
- **Estado global**: Pinia store (`stores/inspeccion.js`)
- **Toast**: componente `Toast.vue`

Ver `mobile_app/README.md` para más detalles.

---

## Deploy

Railway + Nixpacks:

```bash
php artisan migrate --force
php artisan serve --host=0.0.0.0 --port=$PORT
```

Healthcheck: `GET /login`

Variables de entorno clave:
- `APP_TIMEZONE=America/Mazatlan`
- `APP_LOCALE=es`
- `SESSION_LIFETIME=525600` (1 año)
- `DB_CONNECTION=mysql` (vía Railway add-on)

---

## Convenciones

- **Migraciones**: `renameColumn` necesita `doctrine/dbal`. Bifurcación por driver (`getDriverName() === 'sqlite'`) para compatibilidad.
- **Linting**: `./vendor/bin/pint`
- **Prefijo clave de productor**: debe iniciar con AD, AP, BD o BP. Define la zona (A o B).
- **Tipo de actividad**: `Barrido`, `Buffer`, `Seguimiento`. Si es `Seguimiento`, `sub_tipo_actividad` es requerido (`Cuarentena Precautoria`, `Cuarentena Definitiva`, `Hatos Relacionados y Expuestos`).
