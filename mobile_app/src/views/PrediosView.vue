<template>
  <AppLayout>
    <PullToRefresh @refresh="onRefresh" :loading="refreshing">
      <div class="page-header">
        <h2 class="page-title">Predios / Ranchos</h2>
        <p class="page-subtitle">Administre las unidades de producción registradas</p>
      </div>

      <div class="mobile-panel shadow-sm">
        <div class="mobile-panel-title">Listado de Predios</div>
        <button class="btn-new-predio-mobile" @click="$router.push('/predios/nuevo')">
          <i class="bi bi-house-add me-2"></i> Nuevo Predio
        </button>
      </div>

      <div class="p-3 bg-light-subtle border-bottom border-slate-100 mx-3 mb-2 rounded-3 d-lg-none">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
          <input 
            v-model="search" 
            type="text" 
            class="form-control border-start-0 px-2 py-2 fs-6-5" 
            placeholder="Buscar por nombre, UPP, localidad o productor..."
            style="outline: none; box-shadow: none; border-color: #dee2e6;"
            @input="currentPage = 1"
          >
        </div>
      </div>

      <div v-if="errorMsg" class="alert alert-danger shadow-sm border-0 rounded-4">{{ errorMsg }}</div>

      <div class="mobile-cards d-lg-none">
        <div v-if="paginatedPredios.length === 0" class="empty-state-card shadow-sm">
          <i class="bi bi-house-x display-6 d-block mb-2 text-muted"></i>
          <div class="text-muted">No hay predios para mostrar</div>
        </div>

        <div class="mobile-cards-grid">
          <article v-for="predio in paginatedPredios" :key="predio.id" class="predio-mobile-card shadow-sm">
            <div class="predio-content">
              <div class="field-block">
                <span class="field-label">RANCHO</span>
                <span class="field-value">{{ predio.nombre_rancho || predio.nombre || 'Sin nombre' }}</span>
              </div>

              <div class="field-block">
                <span class="field-label">UPP</span>
                <span class="field-value predio-upp">{{ predio.clave_unidad_produccion || predio.upp || 'N/A' }}</span>
              </div>

              <div class="field-block">
                <span class="field-label">LOCALIDAD</span>
                <span class="field-value">{{ predio.localidad || 'General' }}</span>
              </div>

              <div class="field-block">
                <span class="field-label">PRODUCTOR</span>
                <span class="field-value">{{ formatProductorName(predio.productor) }}</span>
              </div>
            </div>

            <div class="predio-mobile-footer">
              <span class="footer-actions-label">Acciones</span>
              <button class="btn-icon-square-gray" @click="$router.push('/predios/' + predio.id)" title="Ver predio">
                <i class="bi bi-eye"></i>
              </button>
              <button class="btn-icon-square-gray" @click="editPredio(predio)" title="Editar predio">
                <i class="bi bi-pencil"></i>
              </button>
            </div>
          </article>
        </div>
      </div>

      <div class="desktop-card card border-0 shadow-sm overflow-hidden predios-card d-none d-lg-block">
        <div class="card-header bg-white d-flex justify-content-between align-items-center p-3 p-md-4 border-bottom border-slate-100">
          <h5 class="mb-0 fw-bold fs-5 text-dark">Listado de Predios</h5>
          <button class="btn btn-primary btn-sm-custom d-flex align-items-center gap-1-5 px-3 py-2" @click="$router.push('/predios/nuevo')">
            <i class="bi bi-house-add"></i> Nuevo Predio
          </button>
        </div>

        <div class="p-3 bg-light-subtle border-bottom border-slate-100">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input 
              v-model="search" 
              type="text" 
              class="form-control border-start-0 px-2 py-2 fs-6-5" 
              placeholder="Buscar por nombre, UPP, localidad o productor..."
              style="outline: none; box-shadow: none; border-color: #dee2e6;"
              @input="currentPage = 1"
            >
          </div>
        </div>

        <div class="table-responsive">
          <table class="table align-middle mb-0 predios-table">
            <thead>
              <tr>
                <th class="ps-4">Nombre del Rancho</th>
                <th>UPP (Clave de Unidad)</th>
                <th>Localidad</th>
                <th>Productor Responsable</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="paginatedPredios.length === 0">
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="bi bi-house-x display-6 d-block mb-2"></i>
                  No hay predios para mostrar
                </td>
              </tr>
              <tr v-for="predio in paginatedPredios" :key="predio.id">
                <td class="ps-4">
                  <div class="fw-bold text-dark">{{ predio.nombre_rancho || predio.nombre || 'Sin nombre' }}</div>
                </td>
                <td>
                  <span class="predio-upp">{{ predio.clave_unidad_produccion || predio.upp || 'N/A' }}</span>
                </td>
                <td class="text-dark">{{ predio.localidad || 'General' }}</td>
                <td>
                  <span class="d-inline-flex align-items-center gap-2">
                    <i class="bi bi-person text-primary"></i>
                    {{ formatProductorName(predio.productor) }}
                  </span>
                </td>
                <td class="text-center">
                  <button class="btn-edit" @click="$router.push('/predios/' + predio.id)" title="Ver predio">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="btn-edit" @click="editPredio(predio)" title="Editar predio">
                    <i class="bi bi-pencil"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-4 border-top">
          <div class="text-secondary small">
            Showing <strong class="text-dark">{{ startResult }}</strong> to <strong class="text-dark">{{ endResult }}</strong> of <strong class="text-dark">{{ totalResults }}</strong> results
          </div>
          <div class="pagination-box">
            <button class="pagination-btn" :disabled="currentPage === 1" @click="currentPage--">
              <i class="bi bi-chevron-left"></i>
            </button>
            <button
              v-for="page in totalPages"
              :key="page"
              class="pagination-btn"
              :class="{ active: currentPage === page }"
              @click="currentPage = page"
            >
              {{ page }}
            </button>
            <button class="pagination-btn" :disabled="currentPage === totalPages" @click="currentPage++">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </PullToRefresh>
  </AppLayout>
</template>

<script>
import AppLayout from '../components/AppLayout.vue';
import PullToRefresh from '../components/PullToRefresh.vue';
import api from '../services/api.js';
import db from '../services/db.js';

export default {
  name: 'PrediosView',
  components: { AppLayout, PullToRefresh },
  data() {
    return {
      userName: '',
      isAdmin: false,
      isOnline: true,
      refreshing: false,
      search: '',
      predios: [],
      currentPage: 1,
      errorMsg: ''
    };
  },
  computed: {
    filteredPredios() {
      const q = this.search.trim().toLowerCase();
      if (!q) return this.predios;
      return this.predios.filter(predio => {
        const values = [
          predio.nombre_rancho,
          predio.nombre,
          predio.clave_unidad_produccion,
          predio.upp,
          predio.localidad,
          predio.municipio,
          predio.productor?.nombre,
          predio.productor?.apellido_paterno,
          predio.productor?.apellido_materno
        ].filter(Boolean).join(' ').toLowerCase();
        return values.includes(q);
      });
    },
    totalResults() {
      return this.filteredPredios.length;
    },
    totalPages() {
      return Math.max(1, Math.ceil(this.totalResults / 10));
    },
    startResult() {
      return this.totalResults === 0 ? 0 : ((this.currentPage - 1) * 10) + 1;
    },
    endResult() {
      return Math.min(this.currentPage * 10, this.totalResults);
    },
    paginatedPredios() {
      return this.filteredPredios.slice((this.currentPage - 1) * 10, this.currentPage * 10);
    }
  },
  watch: {
    search() {
      this.currentPage = 1;
    }
  },
  async mounted() {
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Administrador Central';
    this.isAdmin = user?.roles && user.roles.includes('Administrador');
    this.isOnline = navigator.onLine;

    await this.loadPredios();
    window.addEventListener('online', this.handleOnline);
    window.addEventListener('offline', this.handleOffline);
  },
  beforeUnmount() {
    window.removeEventListener('online', this.handleOnline);
    window.removeEventListener('offline', this.handleOffline);
  },
  methods: {
    handleOnline() {
      this.isOnline = true;
    },
    handleOffline() {
      this.isOnline = false;
    },
    async loadPredios() {
      // 1. Mostrar datos locales inmediatamente (offline-first)
      this.predios = await db.getPredios();

      // 2. Intentar actualizar desde el servidor en segundo plano
      try {
        const isReachable = await api.checkRealConnectivity();
        if (!isReachable) {
          if (this.predios.length > 0) {
            this.errorMsg = '';
          } else {
            this.errorMsg = 'Sin conexión al servidor. Sincroniza datos cuando tengas conexión.';
          }
          return;
        }

        const res = await api.getPredios();
        if (res.data && res.data.length > 0) {
          this.predios = res.data;
          await db.savePredios(this.predios);
          this.errorMsg = '';
        }
      } catch (e) {
        // Si ya tenemos datos locales, no mostrar error alarmante
        if (this.predios.length > 0) {
          console.warn('No se pudo actualizar predios desde el servidor, mostrando datos locales:', e.message);
        } else {
          this.errorMsg = 'No se pudo leer el servidor. Sincroniza datos cuando tengas conexión.';
        }
      }
    },
    async onRefresh() {
      this.refreshing = true;
      try {
        await this.loadPredios();
      } catch (e) {
        console.warn('Refresh error:', e);
      } finally {
        this.refreshing = false;
      }
    },
    formatProductorName(productor) {
      if (!productor) return 'Sin productor';
      return [
        productor.nombre,
        productor.apellido_paterno,
        productor.apellido_materno
      ].filter(Boolean).join(' ') || 'Sin productor';
    },
    editPredio(predio) {
      this.$router.push(`/predios/editar/${predio.id}`);
    }
  }
};
</script>

<style scoped>
.app-container {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
}

.mobile-header {
  height: 72px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #fff;
  padding: 0 0.9rem;
  position: sticky;
  top: 0;
  z-index: 50;
  border-bottom: 1px solid #eef2f7;
}

.header-hamburger-btn {
  width: 42px;
  height: 42px;
  border: 0;
  background: #f5f6f8;
  color: #111827;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.brand-title {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  color: #2563eb;
  font-size: 1.02rem;
}

.brand-icon,
.sidebar-logo {
  width: 22px;
  height: 22px;
  object-fit: contain;
}

.avatar-circle {
  width: 34px;
  height: 34px;
  border: 0;
  background: transparent;
  color: #2563eb;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.page-header {
  padding: 0.95rem 0.95rem 0.35rem;
}

.page-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.page-subtitle {
  color: #64748b;
  font-size: 0.92rem;
  margin: 0.1rem 0 0;
}

.mobile-panel {
  margin: 0.6rem 0.95rem 0.85rem;
  background: #fff;
  border-radius: 0.85rem;
  padding: 0.95rem;
  text-align: center;
}

.mobile-panel-title {
  font-weight: 800;
  font-size: 1.05rem;
  margin-bottom: 0.85rem;
  color: #111827;
}

.btn-new-predio-mobile {
  width: 100%;
  border: 0;
  background: linear-gradient(180deg, #3575f3 0%, #2d66e6 100%);
  color: white;
  font-weight: 700;
  padding: 0.95rem 1rem;
  border-radius: 1rem;
  box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);
}

.mobile-cards {
  padding: 0 0.95rem 5.8rem;
}

.predio-mobile-card {
  background: #fff;
  border-radius: 1rem;
  border: 1px solid #e2e8f0;
  border-left: 5px solid #2563eb;
  padding: 20px 20px 16px 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
  margin-bottom: 0.95rem;
}

.predio-content {
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.field-block {
  margin-bottom: 0;
  text-align: left;
}

.field-label {
  display: block;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.8px;
  color: #64748b;
  text-transform: uppercase;
  margin-bottom: 4px;
}

.field-value {
  display: block;
  font-size: 0.95rem;
  color: #1e293b;
  line-height: 1.3;
}

.predio-upp {
  color: #ff2e83;
  font-weight: 700;
}

.predio-mobile-footer {
  margin-left: -20px;
  margin-right: -20px;
  margin-bottom: -16px;
  padding: 12px 20px;
  background-color: #f8fafc;
  border-top: 1px solid #e2e8f0;
  border-bottom-left-radius: 16px;
  border-bottom-right-radius: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.actions-label {
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}

.btn-icon-square-gray {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  border: 1.5px solid #cbd5e1;
  background-color: #ffffff;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  padding: 0;
}

.btn-icon-square-gray:active {
  background-color: #f8fafc;
  transform: scale(0.95);
}

.empty-state-card {
  background: #fff;
  padding: 2rem 1rem;
  border-radius: 1rem;
  text-align: center;
}

.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.38);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease;
  z-index: 90;
}

.sidebar-overlay.active {
  opacity: 1;
  pointer-events: auto;
}

.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 270px;
  height: 100dvh;
  background: #fff;
  z-index: 100;
  transform: translateX(-100%);
  transition: transform 0.22s ease;
  box-shadow: 20px 0 40px rgba(15, 23, 42, 0.15);
  display: flex;
  flex-direction: column;
}

.sidebar.active {
  transform: translateX(0);
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 1rem 1rem 0.8rem;
  font-weight: 800;
  color: #2563eb;
  border-bottom: 1px solid #eef2f7;
}

.btn-close-sidebar {
  margin-left: auto;
  border: 0;
  background: transparent;
  color: #334155;
}

.nav {
  padding: 0.8rem 0.4rem;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.logout-btn {
  margin-top: auto;
}

.nav-link {
  padding: 0.85rem 1rem;
  border-radius: 0.8rem;
  color: #334155;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.7rem;
}

.nav-link.active {
  background: #2563eb;
  color: white;
}



.desktop-card {
  max-width: 1180px;
  margin: 0 auto 1rem;
}

.search-input {
  border: 1px solid #dbe3ef;
  padding: 0.8rem 0.95rem;
  border-radius: 0.9rem;
}

/* Search input style (matching ProductoresView) */
.input-group {
  display: flex;
  align-items: stretch;
  width: 100%;
}

.input-group-text {
  display: flex;
  align-items: center;
  padding: 8px 12px;
  font-size: 0.9rem;
  font-weight: 400;
  line-height: 1.5;
  color: #6c757d;
  text-align: center;
  white-space: nowrap;
  background-color: #fff;
  border: 1px solid #dee2e6;
  border-radius: var(--radius-sm) 0 0 var(--radius-sm);
}

.form-control {
  display: block;
  width: 100%;
  padding: 8px 12px;
  font-size: 0.88rem;
  font-weight: 400;
  line-height: 1.5;
  color: #212529;
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid #dee2e6;
  border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
  transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
  box-shadow: none !important;
}

.form-control:focus {
  border-color: var(--color-primary) !important;
}

.predios-table thead th {
  font-size: 1rem;
  font-weight: 700;
  color: #2b2f36;
  background: #fff;
  padding-top: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.predios-table tbody td {
  font-size: 0.98rem;
  padding-top: 1rem;
  padding-bottom: 1rem;
  border-top: 1px solid #edf0f5;
}

.btn-edit {
  width: 42px;
  height: 42px;
  border-radius: 0.45rem;
  border: 1px solid #aab2bf;
  background: #fff;
  color: #6b7280;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.pagination-box {
  display: inline-flex;
  align-items: center;
  gap: 0;
  border: 1px solid #d9dee7;
  border-radius: 0.65rem;
  overflow: hidden;
}

.pagination-btn {
  min-width: 42px;
  height: 42px;
  border: 0;
  background: #fff;
  color: #2563eb;
  border-right: 1px solid #d9dee7;
}

.pagination-btn:last-child {
  border-right: 0;
}

.pagination-btn.active {
  background: #2563eb;
  color: #fff;
}

.pagination-btn:disabled {
  background: #f3f5f8;
  color: #a0a8b7;
}

.mobile-cards-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  margin-bottom: 16px;
}

@media (min-width: 576px) and (max-width: 991.98px) {
  .mobile-cards-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 992px) {
  .mobile-header,
  .mobile-panel,
  .mobile-cards,
  .bottom-nav {
    display: none;
  }

  .page-header {
    padding: 1.2rem 1rem 0.8rem;
    max-width: 1180px;
    margin: 0 auto;
  }

  .page-title {
    font-size: 2.05rem;
  }

  .page-subtitle {
    font-size: 1.03rem;
  }
}

@media (max-width: 991.98px) {
  .page-header {
    padding-top: 0.8rem;
  }

  .predio-mobile-card {
    margin-bottom: 0 !important;
  }

  .bg-light-subtle {
    border-radius: 12px !important;
    margin-bottom: 16px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: var(--shadow-sm) !important;
    background-color: #ffffff !important;
  }
}
</style>
