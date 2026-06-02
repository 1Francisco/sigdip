<template>
  <div class="app-container bg-light-page">
    <div class="sidebar-overlay" :class="{ active: sidebarActive }" @click="sidebarActive = false"></div>

    <aside class="sidebar" :class="{ active: sidebarActive }">
      <div class="sidebar-brand">
        <img src="/icon_png.png" alt="SIGDIP" class="sidebar-logo">
        <span>SIGDIP</span>
        <button class="btn-close-sidebar" @click="sidebarActive = false">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <nav class="nav flex-column">
        <template v-if="isAdmin">
          <a class="nav-link" @click.prevent="$router.push('/dashboard')">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
          </a>
          <a class="nav-link" @click.prevent="$router.push('/productores')">
            <i class="bi bi-people"></i> Productores
          </a>
          <a class="nav-link active" @click.prevent="sidebarActive = false">
            <i class="bi bi-house-door"></i> Predios
          </a>
          <a class="nav-link" @click.prevent="$router.push('/inspecciones')">
            <i class="bi bi-clipboard-check"></i> Inspecciones
          </a>
          <a class="nav-link" @click.prevent="$router.push('/visitas')">
            <i class="bi bi-calendar-event"></i> Agenda / Visitas
          </a>
          <a class="nav-link" @click.prevent="$router.push('/medicos')">
            <i class="bi bi-person-badge"></i> Médicos
          </a>
          <a class="nav-link" @click.prevent="alertWebOnly('Importar Excel')">
            <i class="bi bi-file-earmark-arrow-up"></i> Importar Excel
          </a>
          <hr class="mx-3 text-slate-200">
          <a class="nav-link" @click.prevent="$router.push('/inspecciones?downloadExcel=true')">
            <i class="bi bi-file-earmark-excel"></i> Sábana Excel
          </a>
        </template>
        <template v-else>
          <a class="nav-link" @click.prevent="$router.push('/dashboard')">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
          </a>
          <a class="nav-link" @click.prevent="$router.push('/productores')">
            <i class="bi bi-people"></i> Productores
          </a>
          <a class="nav-link active" @click.prevent="sidebarActive = false">
            <i class="bi bi-house-door"></i> Predios
          </a>
          <a class="nav-link" @click.prevent="$router.push('/visitas')">
            <i class="bi bi-calendar-event"></i> Agenda / Visitas
          </a>
          <a class="nav-link" @click.prevent="$router.push('/inspecciones')">
            <i class="bi bi-clipboard-check"></i> Inspecciones
          </a>
          <a class="nav-link" @click.prevent="$router.push('/inspeccion')">
            <i class="bi bi-file-earmark-plus"></i> Nuevo Dictamen
          </a>
          <a class="nav-link" @click.prevent="$router.push('/sync')">
            <i class="bi bi-arrow-repeat"></i> Sincronizar
          </a>
        </template>

        <hr class="mx-3 text-slate-200">
        <a class="nav-link text-danger logout-btn" @click.prevent="doLogout">
          <i class="bi bi-box-arrow-left"></i> Salir
        </a>
      </nav>
    </aside>

    <header class="mobile-header shadow-sm">
      <button class="header-hamburger-btn rounded-circle" @click="sidebarActive = true">
        <i class="bi bi-list fs-4"></i>
      </button>

      <div class="brand-title flex-grow-1 text-center">
        <img src="/icon_png.png" alt="SIGDIP" class="brand-icon">
        <span class="fw-bold">SIGDIP</span>
      </div>

      <div
        class="badge rounded-pill px-2-5 py-1-5 d-flex align-items-center gap-1-5 fw-semibold me-2 border connectivity-badge shadow-sm"
        :class="isOnline ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle'"
      >
        <span class="pulse-dot" :class="isOnline ? 'bg-success' : 'bg-danger'"></span>
        <span class="badge-text d-none d-sm-inline">{{ isOnline ? 'Online' : 'Offline' }}</span>
      </div>

      <button class="avatar-circle rounded-circle" @click="sidebarActive = true" title="Más opciones">
        <i class="bi bi-person"></i>
      </button>
    </header>

    <main class="app-content main-content bg-light">
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

      <div v-if="errorMsg" class="alert alert-danger shadow-sm border-0 rounded-4">{{ errorMsg }}</div>

      <div class="mobile-cards d-md-none">
        <div v-if="paginatedPredios.length === 0" class="empty-state-card shadow-sm">
          <i class="bi bi-house-x display-6 d-block mb-2 text-muted"></i>
          <div class="text-muted">No hay predios para mostrar</div>
        </div>

        <article v-for="predio in paginatedPredios" :key="predio.id" class="predio-mobile-card shadow-sm">
          <div class="predio-accent"></div>
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

          <div class="predio-actions">
            <span class="actions-label">ACCIONES</span>
            <button class="btn-edit-mobile" @click="editPredio(predio)" title="Editar predio">
              <i class="bi bi-pencil"></i>
            </button>
          </div>
        </article>
      </div>

      <div class="desktop-card card border-0 shadow-sm overflow-hidden predios-card d-none d-md-block">
        <div class="card-header bg-white d-flex justify-content-between align-items-center p-3 p-md-4 border-bottom border-slate-100">
          <h5 class="mb-0 fw-bold fs-5 text-dark">Listado de Predios</h5>
          <button class="btn btn-primary btn-sm-custom d-flex align-items-center gap-1-5 px-3 py-2" @click="$router.push('/predios/nuevo')">
            <i class="bi bi-house-add"></i> Nuevo Predio
          </button>
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
    </main>

    <nav class="bottom-nav">
      <a class="bottom-nav-link" @click.prevent="$router.push('/dashboard')">
        <i class="bi bi-grid-1x2"></i>
        <span>Inicio</span>
      </a>
      <a class="bottom-nav-link" @click.prevent="$router.push('/inspecciones')">
        <i class="bi bi-journal-text"></i>
        <span>Dictámenes</span>
      </a>
      <a class="bottom-nav-link" @click.prevent="$router.push('/visitas')">
        <i class="bi bi-calendar-event"></i>
        <span>Agenda</span>
      </a>
      <a class="bottom-nav-link" @click.prevent="sidebarActive = true">
        <i class="bi bi-three-dots"></i>
        <span>Más</span>
      </a>
    </nav>
  </div>
</template>

<script>
import api from '../services/api.js';
import db from '../services/db.js';

export default {
  name: 'PrediosView',
  data() {
    return {
      userName: '',
      isAdmin: false,
      isOnline: true,
      sidebarActive: false,
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
    alertWebOnly(seccion) {
      alert(`La sección de ${seccion} es una función administrativa disponible en la web de escritorio.`);
      this.sidebarActive = false;
    },
    async doLogout() {
      try { 
        await api.logout(); 
      } catch (e) { 
        // Silenciar errores en offline
      }
      localStorage.removeItem('sigdip_token');
      localStorage.removeItem('sigdip_user');
      sessionStorage.clear();
      this.$router.push('/login');
    },
    handleOnline() {
      this.isOnline = true;
    },
    handleOffline() {
      this.isOnline = false;
    },
    async loadPredios() {
      try {
        const res = await api.getPredios();
        this.predios = res.data || [];
        await db.savePredios(this.predios);
      } catch (e) {
        this.errorMsg = 'No se pudo leer el servidor. Mostrando datos locales.';
        this.predios = await db.getPredios();
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
      this.$router.push({
        path: '/predios/nuevo',
        query: { predio_id: predio.id, productor_id: predio.productor_id }
      });
    }
  }
};
</script>

<style scoped>
.app-container {
  min-height: 100vh;
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
  position: relative;
  display: flex;
  background: #fff;
  border-radius: 1rem;
  overflow: hidden;
  margin-bottom: 0.95rem;
  min-height: 290px;
}

.predio-accent {
  width: 4px;
  background: #2563eb;
  flex: 0 0 auto;
}

.predio-content {
  flex: 1;
  padding: 1.15rem 0.95rem 0.95rem;
}

.field-block {
  margin-bottom: 1.35rem;
}

.field-label {
  display: block;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.03em;
  color: #64748b;
  margin-bottom: 0.35rem;
}

.field-value {
  display: block;
  font-size: 1.03rem;
  color: #0f172a;
  line-height: 1.2;
}

.predio-upp {
  color: #ff2e83;
  font-weight: 700;
}

.predio-actions {
  min-width: 116px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.95rem 0.85rem;
  background: #f8fafc;
  flex-direction: column;
  gap: 0.7rem;
}

.actions-label {
  font-size: 0.78rem;
  font-weight: 800;
  color: #64748b;
}

.btn-edit-mobile {
  width: 64px;
  height: 52px;
  border-radius: 0.8rem;
  border: 1.5px solid #9aa4b2;
  background: #fff;
  color: #6b7280;
  display: inline-flex;
  align-items: center;
  justify-content: center;
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
  height: 100vh;
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

.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 70px;
  background: #fff;
  border-top: 1px solid #e9eef5;
  display: flex;
  z-index: 80;
  padding-bottom: env(safe-area-inset-bottom);
}

.bottom-nav-link {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #64748b;
  text-decoration: none;
  font-size: 0.78rem;
  gap: 0.2rem;
}

.bottom-nav-link i {
  font-size: 1.15rem;
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

@media (min-width: 769px) {
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

@media (max-width: 768px) {
  .page-header {
    padding-top: 0.8rem;
  }

  .predio-mobile-card {
    min-height: 300px;
  }

  .field-value {
    font-size: 1.02rem;
  }

  .predio-actions {
    min-width: 96px;
  }
}
</style>
