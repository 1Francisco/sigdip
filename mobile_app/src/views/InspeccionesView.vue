<template>
  <div class="app-container bg-light-page">
    <!-- Sidebar (Drawer) -->
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
          <a class="nav-link" @click.prevent="$router.push('/predios')">
            <i class="bi bi-house-door"></i> Predios
          </a>
          <a class="nav-link active" @click.prevent="sidebarActive = false">
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
          <a class="nav-link" @click.prevent="downloadSábana()">
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
          <a class="nav-link" @click.prevent="$router.push('/predios')">
            <i class="bi bi-house-door"></i> Predios
          </a>
          <a class="nav-link" @click.prevent="$router.push('/visitas')">
            <i class="bi bi-calendar-event"></i> Agenda / Visitas
          </a>
          <a class="nav-link active" @click.prevent="sidebarActive = false">
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

    <!-- Mobile Header -->
    <header class="mobile-header shadow-sm">
      <button class="header-hamburger-btn rounded-circle" @click="sidebarActive = true">
        <i class="bi bi-list fs-4"></i>
      </button>
      
      <div class="brand-title flex-grow-1 text-center">
        <img src="/icon_png.png" alt="SIGDIP" class="brand-icon">
        <span class="fw-bold">SIGDIP</span>
      </div>

      <!-- Connectivity Badge Mobile -->
      <div 
        class="badge rounded-pill px-2-5 py-1-5 d-flex align-items-center gap-1.5 fw-semibold me-2 border connectivity-badge shadow-sm"
        :class="isOnline ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle'"
      >
        <span class="pulse-dot" :class="isOnline ? 'bg-success' : 'bg-danger'"></span>
        <span class="badge-text d-none d-sm-inline">{{ isOnline ? 'Online' : 'Offline' }}</span>
      </div>

      <button class="avatar-circle rounded-circle" @click="sidebarActive = true">
        <i class="bi bi-person"></i>
      </button>
    </header>

    <!-- Main Content -->
    <main class="app-content main-content bg-light">
      
      <!-- Top Action Bar (Premium Web Replica) -->
      <div class="welcome-header mb-4 text-start d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
          <h2 class="h4 fw-bold mb-1 text-dark">Inspecciones Pecuarias</h2>
          <p class="text-secondary small mb-0">Historial de registros y seguimiento</p>
        </div>
        
        <!-- Web Badges (Conectado / Administrador Central) -->
        <div class="d-none d-md-flex align-items-center gap-2">
          <span class="web-connectivity-pill">
            <span class="dot" :class="isOnline ? 'bg-success' : 'bg-danger'"></span>
            {{ isOnline ? 'Conectado' : 'Desconectado' }}
          </span>
          <span class="web-role-pill">
            <i class="bi bi-person-fill text-primary"></i>
            {{ userName }}
          </span>
        </div>
      </div>

      <!-- Alerts -->
      <div v-if="errorMsg" class="alert alert-danger shadow-sm rounded-4 border-0 text-start">{{ errorMsg }}</div>
      <div v-if="successMsg" class="alert alert-success shadow-sm rounded-4 border-0 text-start">{{ successMsg }}</div>

      <!-- Card Container for Inspections List -->
      <div class="card border-0 shadow-sm p-0 overflow-hidden card-outer-mobile-flat bg-white rounded-4">
        <div class="card-header bg-white p-3 p-md-4 border-bottom border-slate-100">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 w-100">
            <h5 class="mb-0 fw-bold fs-5 text-dark text-center text-md-start w-100 w-md-auto">Dictámenes Registrados</h5>
            <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
              <button v-if="isAdmin" @click="downloadSábana()" class="btn btn-outline-success btn-sm-custom d-flex align-items-center justify-content-center gap-1.5 px-3 py-2 fw-semibold border-success text-success bg-transparent rounded-3 w-100 w-md-auto">
                <i class="bi bi-file-earmark-excel"></i> Descargar Sábana
              </button>
              <button @click="$router.push('/inspeccion')" class="btn btn-primary btn-sm-custom d-flex align-items-center justify-content-center gap-1-5 px-3 py-2 bg-primary text-white border-0 rounded-3 w-100 w-md-auto">
                <i class="bi bi-plus-lg"></i> Nuevo Dictamen
              </button>
            </div>
          </div>
        </div>

        <div class="card-body p-0 text-start">
          <!-- Empty State when filtered results are 0 -->
          <div v-if="!loading && inspecciones.length === 0" class="text-center p-5 text-muted">
            <i class="bi bi-clipboard-x display-6 d-block mb-2 text-muted"></i>
            <p class="mb-0 small text-secondary">No hay inspecciones para mostrar</p>
          </div>

          <div v-else>
            <!-- 1. DESKTOP VIEW: Beautiful and precise Table replica matching screenshot exactly -->
            <div class="table-responsive d-none d-md-block">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr>
                    <th class="ps-4 text-secondary fw-bold text-uppercase fs-7 tracking-wider">Folio</th>
                    <th class="text-secondary fw-bold text-uppercase fs-7 tracking-wider">Fecha</th>
                    <th class="text-secondary fw-bold text-uppercase fs-7 tracking-wider">Predio / Localidad</th>
                    <th class="text-secondary fw-bold text-uppercase fs-7 tracking-wider">Veterinario</th>
                    <th class="text-secondary fw-bold text-uppercase fs-7 tracking-wider">Estado</th>
                    <th class="text-center text-secondary fw-bold text-uppercase fs-7 tracking-wider">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="inspeccion in paginatedInspecciones" :key="inspeccion.id" class="border-bottom">
                    <td class="ps-4">
                      <span v-if="isBorrador(inspeccion)" class="text-muted fst-italic fw-bold fs-6">
                        Sin Folio (Borrador)
                      </span>
                      <a v-else @click.prevent="viewInspeccion(inspeccion)" class="text-decoration-none text-primary fw-bold fs-6" style="cursor: pointer;">
                        {{ inspeccion.folio }}
                      </a>
                    </td>
                    <td class="text-dark">{{ formatDate(inspeccion.fecha) }}</td>
                    <td>
                      <div class="fw-bold text-dark">{{ inspeccion.predio?.nombre_rancho || 'Rancho General' }}</div>
                      <small class="text-secondary fs-7">{{ getProductorName(inspeccion.predio?.productor) }}</small>
                    </td>
                    <td class="text-secondary">{{ inspeccion.veterinario?.name || 'Administrador Central' }}</td>
                    <td>
                      <span class="badge rounded-3 px-2-5 py-1-5 d-inline-flex align-items-center gap-1 fw-bold fs-7.5" :class="isBorrador(inspeccion) ? 'bg-warning text-dark' : 'bg-success text-white'">
                        <i class="bi" :class="isBorrador(inspeccion) ? 'bi-pencil-square' : 'bi-check-circle-fill'"></i>
                        {{ isBorrador(inspeccion) ? 'Borrador' : 'Finalizado' }}
                      </span>
                    </td>
                    <td>
                      <div class="d-flex justify-content-center gap-2">
                        <button 
                          v-if="isBorrador(inspeccion) || isAdmin"
                          @click="editInspeccion(inspeccion)" 
                          class="btn btn-sm btn-primary d-flex align-items-center justify-content-center" 
                          title="Editar / Finalizar"
                          style="width: 32px; height: 32px; background: #2563eb;"
                        >
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button 
                          v-if="!inspeccion.__localDraft"
                          @click="openPdf(inspeccion)" 
                          class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center bg-transparent border-danger text-danger" 
                          title="Ver PDF"
                          style="width: 32px; height: 32px;"
                        >
                          <i class="bi bi-file-earmark-pdf"></i>
                        </button>
                        <button 
                          v-if="!inspeccion.__localDraft"
                          @click="viewInspeccion(inspeccion)" 
                          class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center bg-transparent border-primary text-primary" 
                          title="Detalles"
                          style="width: 32px; height: 32px;"
                        >
                          <i class="bi bi-eye"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- 2. MOBILE VIEW: Modern responsive high-fidelity cards matching screenshots exactly -->
            <div class="d-block d-md-none px-3 py-2">
              <div 
                v-for="inspeccion in paginatedInspecciones" 
                :key="inspeccion.id" 
                class="producer-mobile-card shadow-sm mb-3 position-relative"
                style="border-left-color: #2563eb;"
              >
                <div class="card-fields-box">
                  <!-- Folio Field -->
                  <div class="card-field">
                    <span class="field-label">FOLIO</span>
                    <span class="field-value producer-name-bold text-secondary fst-italic" v-if="isBorrador(inspeccion)">Sin Folio (Borrador)</span>
                    <span class="field-value producer-name-bold text-primary" v-else @click="viewInspeccion(inspeccion)">{{ inspeccion.folio }}</span>
                  </div>

                  <!-- Fecha Field -->
                  <div class="card-field">
                    <span class="field-label">FECHA</span>
                    <span class="field-value text-dark fs-6">{{ formatDate(inspeccion.fecha) }}</span>
                  </div>

                  <!-- Rancho Field -->
                  <div class="card-field">
                    <span class="field-label">PREDIO</span>
                    <span class="field-value fw-bold text-dark fs-5">{{ inspeccion.predio?.nombre_rancho || 'Rancho General' }}</span>
                    <span class="field-subtitle text-secondary fs-7">{{ getProductorName(inspeccion.predio?.productor) }}</span>
                  </div>

                  <!-- Veterinario Field -->
                  <div class="card-field">
                    <span class="field-label">VETERINARIO</span>
                    <span class="field-value text-dark">{{ inspeccion.veterinario?.name || 'Administrador Central' }}</span>
                  </div>

                  <!-- Estado Field -->
                  <div class="card-field">
                    <span class="field-label">ESTADO</span>
                    <div class="badge rounded-3 py-2 px-3 fs-7.5 w-100 d-flex align-items-center justify-content-center gap-1.5" :class="isBorrador(inspeccion) ? 'bg-warning text-dark' : 'bg-success text-white'">
                      <i class="bi" :class="isBorrador(inspeccion) ? 'bi-pencil-square' : 'bi-check-lg'"></i>
                      {{ isBorrador(inspeccion) ? 'Borrador' : 'Finalizado' }}
                    </div>
                  </div>
                </div>

                <!-- Actions Footer -->
                <div class="producer-mobile-footer d-flex align-items-center justify-content-between p-3" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
                  <div class="footer-actions-label fw-bold text-secondary mb-0">ACCIONES</div>
                  <div class="d-flex gap-2">
                    <button 
                      v-if="isBorrador(inspeccion) || isAdmin"
                      @click="editInspeccion(inspeccion)" 
                      class="btn btn-sm btn-primary d-flex align-items-center justify-content-center" 
                      title="Editar / Finalizar"
                      style="width: 44px; height: 44px; border-radius: 12px; background: #2563eb;"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button 
                      v-if="!inspeccion.__localDraft"
                      @click="openPdf(inspeccion)" 
                      class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center bg-transparent border-danger text-danger" 
                      title="Ver PDF"
                      style="width: 44px; height: 44px; border-radius: 12px;"
                    >
                      <i class="bi bi-file-earmark-pdf"></i>
                    </button>
                    <button 
                      v-if="!inspeccion.__localDraft"
                      @click="viewInspeccion(inspeccion)" 
                      class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center bg-transparent border-primary text-primary" 
                      title="Detalles"
                      style="width: 44px; height: 44px; border-radius: 12px;"
                    >
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- PAGINATION FOOTER: Precise design match to screenshot -->
            <div class="pagination-footer-custom d-flex justify-content-between align-items-center flex-wrap gap-3 p-4 bg-white">
              <!-- Left: Pagination Info (Desktop only or responsive) -->
              <div class="text-secondary small d-none d-md-block">
                Mostrando <strong class="text-dark">{{ startResult }}</strong> a <strong class="text-dark">{{ endResult }}</strong> de <strong class="text-dark">{{ totalResults }}</strong> registros
              </div>
              
              <!-- Center/Right: Beautiful Custom Chevrons Pagination -->
              <div class="pagination-custom-wrapper d-flex align-items-center justify-content-center w-100 w-md-auto gap-4 py-2">
                <button 
                  class="pagination-custom-btn prev-btn" 
                  :disabled="currentPage === 1" 
                  @click="prevPage"
                >
                  <i class="bi bi-chevron-left"></i>
                </button>
                <div class="pagination-custom-text text-center">
                  <div class="fw-bold text-dark fs-6" style="line-height: 1.2;">Pág. {{ currentPage }} de {{ totalPages }}</div>
                  <small class="text-secondary" style="font-size: 0.78rem;">({{ totalResults }} registros)</small>
                </div>
                <button 
                  class="pagination-custom-btn next-btn" 
                  :disabled="currentPage === totalPages" 
                  @click="nextPage"
                >
                  <i class="bi bi-chevron-right"></i>
                </button>
              </div>
            </div>

          </div>
        </div>
      </div>
    </main>

    <!-- Bottom Nav -->
    <nav class="bottom-nav">
      <a class="bottom-nav-link" @click.prevent="$router.push('/dashboard')">
        <i class="bi bi-grid-1x2"></i>
        <span>Inicio</span>
      </a>
      <a class="bottom-nav-link" @click.prevent="$router.push('/visitas')">
        <i class="bi bi-calendar-event"></i>
        <span>Visitas</span>
      </a>
      <a class="bottom-nav-link active" @click.prevent>
        <i class="bi bi-clipboard-check-fill"></i>
        <span>Dictámenes</span>
      </a>
      <a class="bottom-nav-link" @click.prevent="$router.push('/sync')">
        <i class="bi bi-arrow-repeat"></i>
        <span>Sincronizar</span>
      </a>
    </nav>
  </div>
</template>

<script>
import { Network } from '@capacitor/network';
import api from '../services/api.js';
import db from '../services/db.js';

export default {
  name: 'InspeccionesView',
  data() {
    return {
      userName: '',
      isAdmin: false,
      isOnline: true,
      sidebarActive: false,
      networkListener: null,
      loading: false,
      inspecciones: [],
      localDrafts: [],
      prediosCatalog: [],
      errorMsg: '',
      successMsg: '',
      currentPage: 1
    };
  },
  computed: {
    totalPages() {
      return Math.ceil(this.totalResults / 10) || 1;
    },
    totalResults() {
      return this.inspecciones.length;
    },
    startResult() {
      return this.totalResults === 0 ? 0 : ((this.currentPage - 1) * 10) + 1;
    },
    endResult() {
      return Math.min(this.currentPage * 10, this.totalResults);
    },
    paginatedInspecciones() {
      const start = (this.currentPage - 1) * 10;
      const end = this.currentPage * 10;
      return this.inspecciones.slice(start, end);
    }
  },
  async mounted() {
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Administrador Central';
    this.isAdmin = user?.roles && user.roles.includes('Administrador');
    this.isOnline = navigator.onLine;

    await this.loadAll();

    try {
      const status = await Network.getStatus();
      this.isOnline = status.connected;
      this.networkListener = await Network.addListener('networkStatusChange', (status) => {
        this.isOnline = status.connected;
      });
    } catch (e) {
      window.addEventListener('online', () => this.isOnline = true);
      window.addEventListener('offline', () => this.isOnline = false);
    }

    if (this.$route.query.downloadExcel === 'true') {
      this.$router.replace({ query: {} });
      this.downloadSábana();
    }
  },
  beforeUnmount() {
    if (this.networkListener) {
      this.networkListener.remove();
    }
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
    async loadAll() {
      this.loading = true;
      this.errorMsg = '';
      try {
        this.prediosCatalog = await db.getPredios();
        const [serverRes, local] = await Promise.all([
          api.getInspecciones().catch(() => ({ data: [] })),
          db.getInspeccionesPendientes(),
        ]);

        this.localDrafts = local
          .filter(item => item.estado === 'borrador')
          .map(item => ({
            ...item,
            predio_nombre: this.getLocalPredioName(item.predio_id),
          }));

        const serverItems = (serverRes.data || []).filter(item => item);
        const merged = [...serverItems];

        this.localDrafts.forEach(draft => {
          const exists = merged.some(item => item.folio === draft.folio);
          if (!exists) {
            merged.unshift({
              id: `local-${draft.folio}`,
              folio: draft.folio,
              fecha: draft.fecha,
              estado: 'borrador',
              predio: {
                id: draft.predio_id,
                nombre_rancho: draft.predio_nombre,
                localidad: draft.predio_localidad || '',
              },
              veterinario: {
                name: draft.veterinario_name || 'Local',
              },
              __localDraft: true,
              __draft: draft,
            });
          }
        });

        this.inspecciones = merged;
      } catch (e) {
        this.errorMsg = 'No se pudieron cargar las inspecciones.';
      } finally {
        this.loading = false;
      }
    },
    isBorrador(inspeccion) {
      return inspeccion.estado === 'borrador' || inspeccion.__localDraft;
    },
    getProductorName(productor) {
      if (!productor) return 'Sin Productor';
      return [
        productor.nombre,
        productor.apellido_paterno,
        productor.apellido_materno
      ].filter(Boolean).join(' ');
    },
    formatDate(dateStr) {
      if (!dateStr) return '';
      // Safe YYYY-MM-DD parsing
      const parts = dateStr.split('-');
      if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
      }
      try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
      } catch (e) {
        return dateStr;
      }
    },
    continueDraft(item) {
      this.$router.push(`/inspeccion/${item.predio_id}?visita_id=${item.visita_id || ''}`);
    },
    continueFromServer(inspeccion) {
      this.$router.push(`/inspeccion/${inspeccion.predio?.id}?inspeccion_id=${inspeccion.id}${inspeccion.visita_id ? `&visita_id=${inspeccion.visita_id}` : ''}`);
    },
    editInspeccion(inspeccion) {
      if (inspeccion.__localDraft) {
        this.continueDraft(inspeccion.__draft);
      } else {
        this.continueFromServer(inspeccion);
      }
    },
    viewInspeccion(inspeccion) {
      this.$router.push('/inspecciones/' + inspeccion.id);
    },
    async openPdf(inspeccion) {
      try {
        const blob = await api.getInspectionPdf(inspeccion.id);
        const url = URL.createObjectURL(blob);
        window.open(url, '_blank');
        setTimeout(() => URL.revokeObjectURL(url), 10000);
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo abrir el PDF.';
      }
    },
    async downloadSábana() {
      if (this.sidebarActive) {
        this.sidebarActive = false;
      }
      this.loading = true;
      this.errorMsg = '';
      this.successMsg = '';
      try {
        const blob = await api.getSábanaExcel();
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `dictamenes_pecuarios_${new Date().getDate()}-${new Date().getMonth() + 1}-${new Date().getFullYear()}.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(() => URL.revokeObjectURL(url), 15000);
        this.successMsg = 'Sábana Excel descargada con éxito.';
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo descargar la sábana Excel.';
      } finally {
        this.loading = false;
      }
    },
    getLocalPredioName(predioId) {
      const predio = this.prediosCatalog.find(item => String(item.id) === String(predioId));
      return predio?.nombre_rancho || predio?.nombre || `Rancho ${predioId}`;
    },
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
      }
    },
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
      }
    }
  }
};
</script>

<style scoped>
/* App Layout Structure */
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
  cursor: pointer;
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
  cursor: pointer;
}

.welcome-header {
  padding: 0.95rem 0.95rem 0.35rem;
}

/* Sidebar Styles */
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

.nav-link {
  padding: 0.85rem 1rem;
  border-radius: 0.8rem;
  color: #334155;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.7rem;
  text-decoration: none;
}

.nav-link.active {
  background: #2563eb;
  color: white;
}

.logout-btn {
  margin-top: auto;
}

/* Header Badges */
.connectivity-badge {
  font-size: 0.72rem;
  padding: 4px 10px;
  border: 1px solid;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.bg-success-subtle {
  background-color: #d1fae5;
  color: #065f46;
  border-color: #a7f3d0;
}

.bg-danger-subtle {
  background-color: #fee2e2;
  color: #991b1b;
  border-color: #fca5a5;
}

.pulse-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}

.bg-success {
  background-color: #10b981;
}

.bg-danger {
  background-color: #ef4444;
}

/* Web Badges */
.web-connectivity-pill {
  background-color: #d1fae5;
  color: #065f46;
  font-weight: 600;
  font-size: 0.78rem;
  padding: 6px 12px;
  border-radius: 50px;
  display: flex;
  align-items: center;
  gap: 6px;
  border: 1px solid #a7f3d0;
}

.web-connectivity-pill .dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.web-role-pill {
  background-color: white;
  color: #1e293b;
  font-weight: 600;
  font-size: 0.78rem;
  padding: 6px 14px;
  border-radius: 50px;
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}

/* Table styling matching desktop web index exactly */
.table th {
  font-size: 0.78rem !important;
  font-weight: 700 !important;
  color: #64748b !important;
  background: #f8fafc !important;
  padding: 14px 16px !important;
}

.table td {
  padding: 14px 16px !important;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}

.fs-7-5 {
  font-size: 0.78rem !important;
}

.badge {
  font-weight: 700;
  letter-spacing: 0.3px;
}

/* Mobile responsive card list */
.producer-mobile-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  border-left: 5px solid #2563eb;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.card-field {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.field-label {
  font-size: 0.72rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin-bottom: 4px;
}

.card-fields-box {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.field-value {
  font-size: 0.95rem;
  font-weight: 500;
  color: #1e293b;
}

.producer-name-bold {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
}

.field-subtitle {
  font-size: 0.85rem;
  color: #64748b;
  margin-top: 2px;
}

.producer-mobile-footer {
  margin-left: -20px;
  margin-right: -20px;
  margin-bottom: -20px;
  padding: 12px 20px;
  background-color: #f8fafc;
  border-top: 1px solid #e2e8f0;
  border-bottom-left-radius: 16px;
  border-bottom-right-radius: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.footer-actions-label {
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}

/* Custom chevrons pagination styling */
.pagination-custom-wrapper {
  margin: 0 auto;
}

.pagination-custom-btn {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  padding: 0;
}

.prev-btn {
  background-color: #f1f5f9;
  border: none;
  color: #64748b;
}

.prev-btn:hover:not(:disabled) {
  background-color: #e2e8f0;
}

.next-btn {
  background-color: #ffffff;
  border: 1.5px solid #cbd5e1;
  color: #2563eb;
}

.next-btn:hover:not(:disabled) {
  background-color: #eff6ff;
  border-color: #2563eb;
}

.pagination-custom-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Bottom Nav bar */
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

.bottom-nav-link.active {
  color: #2563eb;
}

.pagination-footer-custom {
  border-bottom-left-radius: 16px;
  border-bottom-right-radius: 16px;
  border-top: 1px solid #f1f5f9;
}

/* Responsive configurations */
@media (min-width: 769px) {
  .mobile-header,
  .bottom-nav {
    display: none;
  }

  .main-content {
    margin-left: 270px;
    padding: 2.5rem !important;
    min-height: 100vh;
  }

  .welcome-header {
    padding: 0;
    margin-bottom: 2rem;
  }

  .card-outer-mobile-flat {
    max-width: 1200px;
    margin: 0 auto;
  }
}

@media (max-width: 768px) {
  .sidebar {
    display: flex;
  }
  
  .main-content {
    padding: 1rem;
    padding-bottom: 90px;
  }

  .card-outer-mobile-flat {
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;
  }

  .card-outer-mobile-flat .card-header {
    background: white !important;
    border-radius: 16px !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
    border: 1px solid #e2e8f0 !important;
    margin-bottom: 16px !important;
  }

  .pagination-footer-custom {
    background: white !important;
    border-radius: 16px !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
    border: 1px solid #e2e8f0 !important;
    margin-top: 16px !important;
    padding: 16px !important;
  }
}
</style>
