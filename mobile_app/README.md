# SIGDIP — App Móvil

Aplicación móvil de SIGDIP construida con Vue 3 + Vite + Capacitor 8.

---

## Stack

| Tecnología | Uso |
|-----------|-----|
| Vue 3 (Options API) | Framework UI |
| Vite | Bundler |
| Capacitor 8 | Entorno nativo (Android/iOS) |
| Pinia | Estado global |
| Vue Router (hash history) | Navegación |
| localforage (IndexedDB) | Almacenamiento offline |
| Bootstrap Icons | Iconografía |
| Cypress 15 | Tests E2E |
| Google Maps API | Mapas en dashboard |

---

## Scripts

```bash
npm run dev          # Servidor de desarrollo Vite
npm run build        # Build para producción
npm run preview      # Vista previa del build
npm run cy:open      # Cypress interactivo
npm run cy:run       # Cypress headless (CI)
```

---

## Arquitectura

```
src/
├── views/           # 27 vistas (cada una es una ruta)
├── services/
│   ├── api.js       # HTTP client (fetch + Bearer Sanctum token)
│   └── db.js        # localforage wrapper (IndexedDB)
├── stores/
│   └── inspeccion.js # Pinia store (lecturas: scanner, borradores)
├── components/
│   ├── AppLayout.vue    # Layout único (sidebar + header + bottom-nav)
│   └── Toast.vue        # Notificaciones toast
├── router/
│   └── index.js    # Hash history + auth guard
└── config.js       # API_BASE_URL
```

### Offline-first

Tres stores IndexedDB:

| Store | Contenido |
|-------|-----------|
| `catalogos` | Predios, Productores, Médicos, Visitas, Dashboard, Animales, AretesCenso |
| `inspecciones_pendientes` | Lecturas (dictámenes) offline pendientes de sincronizar |
| `visitas_pendientes` | Visitas offline pendientes de sincronizar |

Flujo: API primero si hay conexión → cachea en localforage → fallback a localforage si offline.

### Vistas principales

| Ruta | Vista | Descripción |
|------|-------|-------------|
| `/` | DashboardView | Estadísticas, mapa, resumen |
| `/productores` | ProductoresView | Lista de productores |
| `/productores/nuevo` | ProductoresFormView | Crear productor (2 pasos) |
| `/productores/editar/:id` | ProductoresFormView | Editar productor |
| `/predios` | PrediosView | Lista de predios |
| `/predios/nuevo` | PredioCreateView | Crear predio |
| `/visitas` | VisitasView | Lista de visitas |
| `/visitas/nueva` | VisitaCreateView | Crear visita |
| `/inspecciones` | InspeccionesView | Lista de lecturas / dictámenes |
| `/inspecciones/nueva` | InspeccionFormView | Crear lectura / dictamen |
| `/medicos` | MedicosView | Lista de médicos (admin) |
| `/sync` | SyncView | Descarga/sincronización de catálogos |
| `/animales` | AnimalesView | CRUD animales (admin) |
| `/aretes-censo` | AretesCensoView | CRUD aretes (admin) |

### Conexión con API Backend

Endpoint base: `CONFIG.API_BASE_URL` (configurado en `config.js`)

Autenticación: Bearer token vía `auth:sanctum`.

Métodos principales en `api.js`:
- `getProductores()`, `getProductor(id)`, `searchProductor(q)`, `storeProductor()`, `updateProductor()`
- `getPredios()`, `getPredio(id)`, `storeRancho()`, `updateRancho()`
- `getVisitas()`, `createVisita()`, `updateVisita()`
- `getInspecciones()`, `updateInspeccion()` — gestión de lecturas / dictámenes
- `getMedicos()`, `storeMedico()`, `updateMedico()`
- `downloadCatalogos()` — sincronización masiva

---

## Testing (Cypress)

```bash
npm run cy:run        # Todos los tests (headless)
npm run cy:run -- --spec "cypress/e2e/productor*.cy.js"  # Específico
npm run cy:open       # Interactivo
```

23 specs, 126 tests E2E. Los tests usan `cy.intercept()` para mockear la API y `cy.resetAppState()` para limpiar IndexedDB antes de cada suite.

---

## Notas

- **Hash history**: `createWebHashHistory` — las rutas usan `/#/` (requerido para Capacitor)
- **Auth guard**: `router.beforeEach` verifica token en localStorage
- **Login offline**: si no hay conexión, intenta autenticar contra credenciales cacheadas (hash SHA-256 de contraseña en localStorage)
- **Layout**: `AppLayout.vue` envuelve todas las vistas con sidebar, header y bottom-nav
