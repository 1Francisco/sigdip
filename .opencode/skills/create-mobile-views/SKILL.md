---
name: create-mobile-views
description: Generate Vue 3 mobile views (list, create, edit, detail) + router entry for a new entity, following SIGDIP mobile conventions
---

## What it does

Scaffolds the mobile-side CRUD views for an existing entity:
- `mobile_app/src/views/{entity}/{Entity}ListView.vue`
- `mobile_app/src/views/{entity}/{Entity}CreateView.vue`
- `mobile_app/src/views/{entity}/{Entity}EditView.vue`
- `mobile_app/src/views/{entity}/{Entity}DetailView.vue`
- Updates `mobile_app/src/services/api.js` with CRUD methods
- Updates `mobile_app/src/services/db.js` with localForage methods
- Updates `mobile_app/src/router/index.js` with 4 new routes

## Prerequisites

The entity API controller must already exist (use `create-api-crud` skill first).

## Instructions

### 1. Ask the user for details

1. **Entity name** (PascalCase singular, e.g. `Categoria`)
2. **Route prefix** (lowercase plural, e.g. `categorias`)
3. **Display fields** — which fields to show in list cards, detail view, and form
4. **Related data** — which relations to load and display (dropdowns in forms, nested data in detail)
5. **Searchable fields** — which fields users can search by in the list
6. **Validation rules** — client-side validation for the form
7. **Spanish labels** — for titles, placeholders, buttons, success/error messages
8. **Offline support?** — cache to IndexedDB and support offline creation?
9. **API endpoints** — base URL path for the entity API

### 2. Update api.js

Add the CRUD methods to `mobile_app/src/services/api.js` following the existing pattern:

```javascript
async get{Entities}(params = {}) {
  const query = new URLSearchParams(params).toString();
  const res = await request('GET', `/api/{entity}${query ? '?' + query : ''}`);
  return res;
},
async get{Entity}(id) {
  const res = await request('GET', `/api/{entity}/${id}`);
  return res;
},
async create{Entity}(data) {
  const res = await request('POST', `/api/{entity}`, data);
  return res;
},
async update{Entity}(id, data) {
  const res = await request('PUT', `/api/{entity}/${id}`, data);
  return res;
},
async delete{Entity}(id) {
  const res = await request('DELETE', `/api/{entity}/${id}`);
  return res;
},
```

Place them alphabetically among the existing methods in the export object.

### 3. Update db.js

Add IndexedDB helpers if offline support is needed:

```javascript
// In the catalogos instance (under the module.exports):
get{Entities}: async function() {
  const data = await catalogo.getItem('{entities}');
  return data || [];
},
save{Entities}: async function(items) {
  await catalogo.setItem('{entities}', clean(items));
},
```

### 4. Generate ListView

File: `mobile_app/src/views/{entity}/{Entity}ListView.vue`

```vue
<template>
  <AppLayout>
    <PullToRefresh @refresh="onRefresh" :loading="refreshing">
      <div class="welcome-header">
        <h2>{Entities}</h2>
        <p>Administración de {entities}</p>
      </div>

      <div v-if="errorMsg" class="alert alert-danger">{{ errorMsg }}</div>

      <div class="card border-0 shadow-sm card-outer-mobile-flat">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
          <h5 class="mb-0 fw-bold">Listado</h5>
          <button class="btn btn-primary btn-sm rounded-pill px-3" @click="$router.push('/{entity}/nuevo')">
            <i class="bi bi-plus-lg"></i> Nuevo
          </button>
        </div>

        <!-- Search -->
        <div class="card-body bg-light border-bottom py-2 px-4">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control border-start-0 rounded-end-pill"
              placeholder="Buscar por {field}..." v-model="search">
          </div>
        </div>

        <!-- Desktop table -->
        <div class="table-responsive d-none d-lg-block">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4">{Column1}</th>
                <th>{Column2}</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in paginatedItems" :key="item.id">
                <td class="ps-4">{{ item.field1 }}</td>
                <td>{{ item.rel?.field ?? 'N/A' }}</td>
                <td>
                  <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-info" @click="$router.push('/{entity}/' + item.id)" title="Ver">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" @click="$router.push('/{entity}/editar/' + item.id)" title="Editar">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" @click="deleteItem(item)" title="Eliminar">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile cards -->
        <div class="d-block d-lg-none p-3">
          <div v-for="item in paginatedItems" :key="item.id" class="mobile-card mb-3">
            <div class="card-fields-box">
              <div class="card-field">
                <span class="field-label">{Label1}</span>
                <span class="field-value">{{ item.field1 }}</span>
              </div>
              <div class="card-field">
                <span class="field-label">{Label2}</span>
                <span class="field-value">{{ item.rel?.field ?? 'N/A' }}</span>
              </div>
            </div>
            <div class="card-footer-actions">
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-info" @click="$router.push('/{entity}/' + item.id)">
                  <i class="bi bi-eye"></i> Ver
                </button>
                <button class="btn btn-sm btn-outline-secondary" @click="$router.push('/{entity}/editar/' + item.id)">
                  <i class="bi bi-pencil"></i> Editar
                </button>
                <button class="btn btn-sm btn-outline-danger" @click="deleteItem(item)">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
          </div>
          <div v-if="paginatedItems.length === 0" class="text-center py-5 text-muted">
            <i class="bi bi-archive fs-2 d-block mb-2"></i>
            Sin {entities} registrados
          </div>

          <!-- Pagination footer -->
          <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">Mostrando {{ startResult }}-{{ endResult }} de {{ filteredItems.length }}</small>
            <div class="d-flex gap-1">
              <button class="btn btn-sm btn-outline-secondary prev-btn" @click="prevPage" :disabled="currentPage === 1">
                <i class="bi bi-chevron-left"></i>
              </button>
              <button class="btn btn-sm btn-outline-secondary next-btn" @click="nextPage" :disabled="currentPage >= totalPages">
                <i class="bi bi-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </PullToRefresh>
  </AppLayout>
</template>

<script>
import AppLayout from '../../components/AppLayout.vue';
import PullToRefresh from '../../components/PullToRefresh.vue';
import api from '../../services/api.js';
import db from '../../services/db.js';

export default {
  name: '{Entity}ListView',
  components: { AppLayout, PullToRefresh },
  data() {
    return {
      items: [],
      loading: false,
      refreshing: false,
      errorMsg: '',
      search: '',
      currentPage: 1,
      perPage: 10,
    };
  },
  computed: {
    isOnline() { return navigator.onLine; },
    filteredItems() {
      if (!this.search) return this.items;
      const q = this.search.toLowerCase();
      return this.items.filter(i =>
        (i.field1 && i.field1.toLowerCase().includes(q)) ||
        (i.field2 && String(i.field2).toLowerCase().includes(q))
      );
    },
    paginatedItems() {
      const start = (this.currentPage - 1) * this.perPage;
      return this.filteredItems.slice(start, start + this.perPage);
    },
    totalPages() {
      return Math.ceil(this.filteredItems.length / this.perPage) || 1;
    },
    startResult() {
      return this.filteredItems.length === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
    },
    endResult() {
      return Math.min(this.currentPage * this.perPage, this.filteredItems.length);
    },
  },
  async mounted() {
    await this.loadAll();
    window.addEventListener('sigdip-sync-complete', this.refreshOnSync);
  },
  beforeUnmount() {
    window.removeEventListener('sigdip-sync-complete', this.refreshOnSync);
  },
  methods: {
    async loadAll() {
      this.loading = true;
      this.errorMsg = '';

      // Phase 1: load from cache
      const cached = await db.get{Entities}();
      if (cached && cached.length) this.items = cached;

      // Phase 2: try API
      try {
        const res = await api.get{Entities}();
        this.items = res.data || res;
        await db.save{Entities}(this.items);
      } catch (e) {
        if (this.items.length === 0) {
          this.errorMsg = 'No se pudieron cargar los {entities}.';
        }
      } finally {
        this.loading = false;
      }
    },
    async onRefresh() {
      this.refreshing = true;
      try {
        const res = await api.get{Entities}();
        this.items = res.data || res;
        await db.save{Entities}(this.items);
      } catch (e) {
        this.errorMsg = 'Error al actualizar.';
      } finally {
        this.refreshing = false;
      }
    },
    refreshOnSync() { this.loadAll(); },
    prevPage() { if (this.currentPage > 1) this.currentPage--; },
    nextPage() { if (this.currentPage < this.totalPages) this.currentPage++; },
    async deleteItem(item) {
      if (!confirm('¿Eliminar este {entity}?')) return;
      try {
        await api.delete{Entity}(item.id);
        this.items = this.items.filter(i => i.id !== item.id);
        await db.save{Entities}(this.items);
      } catch (e) {
        this.errorMsg = e.message || 'Error al eliminar.';
      }
    },
  },
};
</script>

<style scoped>
.mobile-card {
  background: #fff;
  border-radius: 14px;
  padding: 1rem;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.card-fields-box { margin-bottom: 0.75rem; }
.card-field {
  display: flex;
  justify-content: space-between;
  padding: 0.2rem 0;
  border-bottom: 1px solid #f0f0f0;
}
.field-label { color: #6c757d; font-size: 0.8rem; }
.field-value { font-weight: 600; font-size: 0.9rem; text-align: right; }
.card-footer-actions {
  padding-top: 0.5rem;
  border-top: 1px solid #eee;
}
</style>
```

### 5. Generate CreateView

File: `mobile_app/src/views/{entity}/{Entity}CreateView.vue`

```vue
<template>
  <AppLayout>
    <div class="welcome-header">
      <h2>Registrar {Entity}</h2>
      <p>Complete los datos del nuevo {entity}</p>
    </div>

    <div v-if="errorMsg" class="alert alert-danger">{{ errorMsg }}</div>
    <div v-if="successMsg" class="alert alert-success">{{ successMsg }}</div>

    <div class="card border-0 shadow-sm card-outer-mobile-flat">
      <div class="card-body p-4">
        <form @submit.prevent="save">
          <div v-for="field in formFields" :key="field.name" class="form-group-custom">
            <label class="form-label-custom">{{ field.label }}</label>

            <!-- Text input -->
            <input v-if="field.type === 'text' || field.type === 'email' || field.type === 'number'"
              :type="field.type" v-model="form[field.name]"
              class="form-control-custom" :placeholder="field.placeholder" :required="field.required">

            <!-- Select -->
            <select v-else-if="field.type === 'select'" v-model="form[field.name]"
              class="form-control-custom form-select-custom" :required="field.required">
              <option value="">-- Seleccionar --</option>
              <option v-for="opt in field.options" :key="opt.id" :value="opt.id">{{ opt.label }}</option>
            </select>

            <!-- Textarea -->
            <textarea v-else-if="field.type === 'textarea'" v-model="form[field.name]"
              class="form-control-custom" rows="3" :placeholder="field.placeholder"></textarea>
          </div>

          <button type="submit" class="btn btn-primary w-100 btn-save-custom mt-3" :disabled="saving">
            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
            {{ saving ? 'Guardando...' : 'Guardar' }}
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '../../components/AppLayout.vue';
import api from '../../services/api.js';
import db from '../../services/db.js';

export default {
  name: '{Entity}CreateView',
  components: { AppLayout },
  data() {
    return {
      form: {
        // Initialize each field: field1: '', field2: '',
      },
      formFields: [
        // { name: 'field1', type: 'text', label: 'Field 1', placeholder: '...', required: true },
        // { name: 'fk_id', type: 'select', label: 'Relation', placeholder: '...', required: true, options: [] },
      ],
      loading: false,
      saving: false,
      errorMsg: '',
      successMsg: '',
      isOnline: navigator.onLine,
    };
  },
  async mounted() {
    // Load relation data for selects
    // try { const res = await api.getRelations(); this.relationOptions = res.data; } catch(e) {}
  },
  methods: {
    async save() {
      // Client validation
      for (const field of this.formFields) {
        if (field.required && !this.form[field.name]) {
          this.errorMsg = `El campo ${field.label} es obligatorio.`;
          return;
        }
      }

      this.saving = true;
      this.errorMsg = '';
      this.successMsg = '';

      try {
        if (this.isOnline) {
          const res = await api.create{Entity}(this.form);
          this.successMsg = '{Entity} creado correctamente.';
          setTimeout(() => this.$router.push('/{entity}'), 1500);
        } else {
          // Offline: save to pending store
          await db.save{Entity}Pendiente({
            ...this.form,
            _pendiente: true,
            _created_at: new Date().toISOString(),
          });
          this.successMsg = 'Guardado offline. Se sincronizará automáticamente.';
          setTimeout(() => this.$router.push('/{entity}'), 1500);
        }
      } catch (e) {
        this.errorMsg = e.message || 'Error al guardar.';
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
/* Reuse existing mobile form styles */
.form-group-custom { margin-bottom: 1.5rem; }
.form-label-custom { font-weight: 600; color: #2c3e50; margin-bottom: 0.4rem; display: block; }
.form-control-custom {
  width: 100%; padding: 0.75rem 1rem; border: 1px solid #ddd;
  border-radius: 14px; font-size: 0.95rem; transition: border-color 0.2s;
}
.form-control-custom:focus { border-color: #0d6efd; outline: none; box-shadow: 0 0 0 3px rgba(13,110,253,.15); }
.form-select-custom {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 1rem center; padding-right: 2.5rem;
}
.btn-save-custom { border-radius: 14px; padding: 0.8rem; font-weight: 600; }
</style>
```

### 6. Generate EditView

File: `mobile_app/src/views/{entity}/{Entity}EditView.vue`

Same structure as CreateView but:
- Detect edit mode from `this.$route.params.id`
- Load existing data in `mounted()`: `const res = await api.get{Entity}(this.entityId); this.form = res.data;`
- Call `api.update{Entity}()` instead of `create{Entity}()`
- Title says "Editar {Entity}" instead of "Registrar {Entity}"

```vue
<template>
  <AppLayout>
    <div class="welcome-header">
      <h2>Editar {Entity}</h2>
      <p>Modifique los datos del {entity}</p>
    </div>

    <div v-if="errorMsg" class="alert alert-danger">{{ errorMsg }}</div>
    <div v-if="successMsg" class="alert alert-success">{{ successMsg }}</div>

    <div class="card border-0 shadow-sm card-outer-mobile-flat">
      <div class="card-body p-4">
        <form @submit.prevent="save">
          <!-- Same form fields as create, using v-model="form.field" -->
          <button type="submit" class="btn btn-primary w-100 btn-save-custom mt-3" :disabled="saving">
            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
            {{ saving ? 'Actualizando...' : 'Actualizar' }}
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '../../components/AppLayout.vue';
import api from '../../services/api.js';

export default {
  name: '{Entity}EditView',
  components: { AppLayout },
  data() {
    return {
      entityId: null,
      form: {},
      formFields: [],
      saving: false,
      errorMsg: '',
      successMsg: '',
    };
  },
  async mounted() {
    this.entityId = this.$route.params.id;
    await this.loadEntity();
  },
  methods: {
    async loadEntity() {
      try {
        const res = await api.get{Entity}(this.entityId);
        const data = res.data || res;
        // Map fields to form
        for (const key of Object.keys(this.form)) {
          if (data[key] !== undefined) this.form[key] = data[key];
        }
      } catch (e) {
        this.errorMsg = 'No se pudo cargar el {entity}.';
      }
    },
    async save() {
      this.saving = true;
      this.errorMsg = '';
      try {
        await api.update{Entity}(this.entityId, this.form);
        this.successMsg = '{Entity} actualizado correctamente.';
        setTimeout(() => this.$router.push('/{entity}'), 1500);
      } catch (e) {
        this.errorMsg = e.message || 'Error al actualizar.';
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
```

### 7. Generate DetailView

File: `mobile_app/src/views/{entity}/{Entity}DetailView.vue`

```vue
<template>
  <AppLayout>
    <div class="welcome-header">
      <h2>{{ entity.field1 }}</h2>
      <p>Detalle del {entity}</p>
    </div>

    <div v-if="errorMsg" class="alert alert-danger">{{ errorMsg }}</div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <template v-if="!loading && entity">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-3">
          <h5 class="mb-0 fw-bold">Información del {Entity}</h5>
        </div>
        <div class="card-body p-4">
          <div class="row g-3">
            <div v-for="field in detailFields" :key="field.name" class="col-6">
              <div class="text-muted small">{{ field.label }}</div>
              <div class="fw-semibold">{{ entity[field.name] ?? 'N/A' }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Related data card (optional) -->

      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary flex-fill" @click="$router.push('/{entity}/editar/' + entity.id)">
          <i class="bi bi-pencil"></i> Editar
        </button>
        <button class="btn btn-outline-primary flex-fill" @click="$router.push('/{entity}')">
          Volver
        </button>
      </div>
    </template>
  </AppLayout>
</template>

<script>
import AppLayout from '../../components/AppLayout.vue';
import api from '../../services/api.js';

export default {
  name: '{Entity}DetailView',
  components: { AppLayout },
  data() {
    return {
      entity: null,
      loading: false,
      errorMsg: '',
      detailFields: [
        // { name: 'field1', label: 'Field 1' },
      ],
    };
  },
  async mounted() {
    await this.loadDetail();
  },
  methods: {
    async loadDetail() {
      this.loading = true;
      this.errorMsg = '';
      try {
        const res = await api.get{Entity}(this.$route.params.id);
        this.entity = res.data || res;
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo cargar.';
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
```

### 8. Update the Router

Add 4 routes to `mobile_app/src/router/index.js` in the appropriate section (alphabetically among existing entity routes):

```javascript
{
  path: '/{entity}',
  name: '{Entities}',
  component: () => import('../views/{entity}/{Entity}ListView.vue'),
  meta: { requiresAuth: true },
},
{
  path: '/{entity}/nuevo',
  name: 'Nuevo{Entity}',
  component: () => import('../views/{entity}/{Entity}CreateView.vue'),
  meta: { requiresAuth: true },
},
{
  path: '/{entity}/editar/:id',
  name: 'Editar{Entity}',
  component: () => import('../views/{entity}/{Entity}EditView.vue'),
  meta: { requiresAuth: true },
},
{
  path: '/{entity}/:id',
  name: '{Entity}Detail',
  component: () => import('../views/{entity}/{Entity}DetailView.vue'),
  meta: { requiresAuth: true },
},
```

### 9. Verify

Run `cd mobile_app && npm run build` to check for compilation errors.
