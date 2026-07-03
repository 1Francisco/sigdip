<template>
  <div class="app-container bg-light-page">
    <header class="form-view-header shadow-sm">
        <div class="d-flex align-items-center gap-2 gap-sm-3 header-left-container">
          <button class="btn-back-circle shadow-sm" @click="$router.push('/medicos')">
            <i class="bi bi-arrow-left fs-5"></i>
          </button>
          <div class="text-start title-text-wrapper">
            <h1 class="h5 fw-bold mb-0 text-dark header-title">Asignar Productores</h1>
            <div class="subtitle text-secondary small d-none d-sm-block">
              {{ medicoName ? 'Asignando productores a: ' + medicoName : 'Cargando...' }}
            </div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-1 gap-sm-2 header-badges">
          <span class="web-connectivity-pill shadow-sm">
            <span class="dot" :class="isOnline ? 'bg-success' : 'bg-danger'"></span>
            <span 
            class="d-none d-sm-inline">{{ isOnline ? 'Conectado' : 'Offline' }}</span>
          </span>
        </div>
      </header>

      <main class="app-content main-content p-4">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary mb-3" role="status"></div>
          <p class="text-muted">Cargando productores...</p>
        </div>

        <div v-else-if="errorMsg" class="alert alert-danger border-0 shadow-sm rounded-4">{{ errorMsg }}</div>

        <template v-else>
          <div class="row g-3">
            <!-- PANEL: ASIGNADOS -->
            <div class="col-12 col-md-5">
              <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                  <h5 class="mb-0 fw-bold fs-6">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    Asignados Actualmente
                  </h5>
                  <span class="badge bg-success rounded-pill">{{ asignados.length }}</span>
                </div>
                <div class="card-body p-0" style="max-height: 450px; overflow-y: auto;">
                  <div v-if="asignados.length === 0" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    <small>No hay productores asignados a este médico.</small>
                  </div>
                  <div v-for="prod in asignados" :key="prod.id" class="d-flex justify-content-between align-items-center p-3 border-bottom">
                    <div class="text-start">
                      <div class="fw-bold small">{{ prod.nombre }} {{ prod.apellido_paterno }} {{ prod.apellido_materno }}</div>
                      <small class="text-muted">UPP: {{ prod.upp || 'N/A' }} | Predios: {{ prod.predios_count }}</small>
                    </div>
                    <button
                      class="btn btn-sm btn-outline-danger rounded-circle"
                      title="Desasignar"
                      @click="desasignar(prod)"
                      :disabled="saving"
                    >
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- PANEL: DISPONIBLES -->
            <div class="col-12 col-md-7">
              <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                  <h5 class="mb-0 fw-bold fs-6">
                    <i class="bi bi-person-plus-fill text-primary me-2"></i>
                    Productores Disponibles
                  </h5>
                  <span class="badge bg-primary rounded-pill">{{ disponiblesFiltrados.length }}</span>
                </div>
                <div class="card-body p-0">
                  <div class="p-3 bg-light border-bottom">
                    <div class="input-group input-group-sm">
                      <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                      <input
                        v-model="search"
                        type="text"
                        class="form-control border-start-0"
                        placeholder="Filtrar por nombre, CURP o UPP..."
                      >
                    </div>
                  </div>
                  <div style="max-height: 400px; overflow-y: auto;">
                    <div v-if="disponiblesFiltrados.length === 0" class="text-center py-5 text-muted">
                      <i class="bi bi-people fs-1 d-block mb-2"></i>
                      <small v-if="search">Sin resultados para "{{ search }}"</small>
                      <small v-else>Todos los productores están asignados a algún médico.</small>
                    </div>
                    <label
                      v-for="prod in disponiblesFiltrados"
                      :key="prod.id"
                      class="d-flex align-items-center p-3 border-bottom item-disponible"
                      :class="{ 'bg-light': selectedIds.has(prod.id) }"
                    >
                      <div class="form-check me-3">
                        <input
                          class="form-check-input"
                          type="checkbox"
                          :value="prod.id"
                          :checked="selectedIds.has(prod.id)"
                          @change="toggleProductor(prod.id)"
                        >
                      </div>
                      <div class="flex-grow-1" style="cursor: pointer;" @click="toggleProductor(prod.id)">
                        <div class="fw-bold small">{{ prod.nombre }} {{ prod.apellido_paterno }} {{ prod.apellido_materno }}</div>
                        <small class="text-muted">CURP: {{ prod.curp || 'N/A' }} | UPP: {{ prod.upp || 'N/A' }} | Predios: {{ prod.predios_count }}</small>
                      </div>
                    </label>
                  </div>
                </div>
                <div v-if="disponibles.length > 0" class="card-footer bg-white py-2 px-4 d-flex justify-content-end border-top">
                  <button
                    class="btn btn-sm btn-outline-secondary"
                    @click="selectAllToggle"
                  >
                    {{ selectAll ? 'Deseleccionar todos' : 'Seleccionar todos' }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <button class="btn btn-light border px-4 rounded-pill" @click="$router.push('/medicos')">Cancelar</button>
            <button
              class="btn btn-primary px-5 rounded-pill"
              @click="guardarAsignacion"
              :disabled="saving"
            >
              <i class="bi bi-save me-1"></i> {{ saving ? 'Guardando...' : 'Guardar Asignación' }}
            </button>
          </div>
        </template>
      </main>
    </div>
</template>

<script>
import api from '../services/api.js';
import db from '../services/db.js';

export default {
  name: 'AsignarProductoresView',
  data() {
    return {
      medicoName: '',
      isOnline: navigator.onLine,
      loading: true,
      saving: false,
      errorMsg: '',
      asignados: [],
      disponibles: [],
      selectedIds: new Set(),
      search: '',
      selectAll: false,
    };
  },
  computed: {
    disponiblesFiltrados() {
      if (!this.search) return this.disponibles;
      const q = this.search.toLowerCase().trim();
      return this.disponibles.filter(p =>
        (p.nombre && p.nombre.toLowerCase().includes(q)) ||
        (p.apellido_paterno && p.apellido_paterno.toLowerCase().includes(q)) ||
        (p.apellido_materno && p.apellido_materno.toLowerCase().includes(q)) ||
        (p.curp && p.curp.toLowerCase().includes(q)) ||
        (p.upp && p.upp.toLowerCase().includes(q))
      );
    },
  },
  async mounted() {
    const user = api.getCurrentUser();
    if (!user || !user.roles || !user.roles.includes('Administrador')) {
      alert('Acceso restringido. Solo administradores.');
      this.$router.push('/medicos');
      return;
    }

    window.addEventListener('online', this._onOnline);
    window.addEventListener('offline', this._onOffline);

    await this.loadData();
  },
  beforeUnmount() {
    window.removeEventListener('online', this._onOnline);
    window.removeEventListener('offline', this._onOffline);
  },
  methods: {
    _onOnline() { this.isOnline = true; },
    _onOffline() { this.isOnline = false; },

    async loadData() {
      this.loading = true;
      this.errorMsg = '';
      const usuarioId = this.$route.params.id;

      try {
        const res = await api.getProductoresAsignables(usuarioId);
        if (res.success) {
          this.medicoName = res.medico.name;
          this.asignados = res.asignados || [];
          this.disponibles = res.disponibles || [];
        } else {
          this.errorMsg = res.message || 'Error al cargar datos.';
        }
      } catch (e) {
        this.errorMsg = 'Error de conexión al cargar productores. Necesitas conexión para asignar productores.';
      } finally {
        this.loading = false;
      }
    },

    toggleProductor(id) {
      if (this.selectedIds.has(id)) {
        this.selectedIds.delete(id);
      } else {
        this.selectedIds.add(id);
      }
      this.selectAll = this.disponibles.length > 0 && this.selectedIds.size === this.disponibles.length;
    },

    selectAllToggle() {
      if (this.selectAll) {
        this.selectedIds.clear();
        this.selectAll = false;
      } else {
        this.disponibles.forEach(p => this.selectedIds.add(p.id));
        this.selectAll = true;
      }
    },

    async desasignar(prod) {
      if (!confirm(`¿Desasignar a ${prod.nombre} ${prod.apellido_paterno} de ${this.medicoName}?`)) return;

      this.saving = true;
      const usuarioId = this.$route.params.id;

      try {
        const res = await api.desasignarProductor(usuarioId, prod.id);
        if (res.success) {
          this.asignados = this.asignados.filter(a => a.id !== prod.id);
          this.disponibles.push(prod);
          this.disponibles.sort((a, b) => (a.nombre || '').localeCompare(b.nombre || ''));
        } else {
          alert(res.message || 'Error al desasignar.');
        }
      } catch (e) {
        alert(e.message || 'Error de conexión.');
      } finally {
        this.saving = false;
      }
    },

    async guardarAsignacion() {
      this.saving = true;
      const usuarioId = this.$route.params.id;

      try {
        const res = await api.asignarProductores(usuarioId, Array.from(this.selectedIds));
        if (res.success) {
          this.selectedIds.clear();
          this.selectAll = false;
          await this.loadData();
          alert(res.message || 'Asignación guardada correctamente.');
        } else {
          alert(res.message || 'Error al guardar asignación.');
        }
      } catch (e) {
        alert(e.message || 'Error de conexión al guardar.');
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
.app-container {
  min-height: 100dvh;
}

.form-view-header {
  background: white;
  padding: 16px 24px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 100;
  gap: 12px;
}

.header-left-container {
  min-width: 0;
  flex: 1;
}

.title-text-wrapper {
  min-width: 0;
}

.header-title {
  font-size: 1.1rem;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin: 0;
}

@media (min-width: 576px) {
  .header-title {
    font-size: 1.25rem;
  }
}

@media (max-width: 576px) {
  .form-view-header {
    padding: 12px 14px;
  }
  .btn-back-circle {
    width: 34px !important;
    height: 34px !important;
  }
  .btn-back-circle i {
    font-size: 0.95rem !important;
  }
}

.header-badges {
  flex-shrink: 0;
}

.btn-back-circle {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  border: 1px solid #e2e8f0;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
}

.item-disponible:hover {
  background-color: #f8fafc;
}

.web-connectivity-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 100px;
  background: white;
  border: 1px solid #e2e8f0;
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}
</style>
