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
          <a class="nav-link" @click.prevent="$router.push('/inspecciones')">
            <i class="bi bi-clipboard-check"></i> Inspecciones
          </a>
          <a class="nav-link active" @click.prevent="sidebarActive = false">
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
          <a class="nav-link" @click.prevent="$router.push('/predios')">
            <i class="bi bi-house-door"></i> Predios
          </a>
          <a class="nav-link active" @click.prevent="sidebarActive = false">
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
          <h2 class="h4 fw-bold mb-1 text-dark">Agenda de Campo</h2>
          <p class="text-secondary small mb-0">Programe y gestione las visitas a los predios</p>
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

      <!-- Card Container for Visitas Header -->
      <div class="card border-0 shadow-sm p-4 bg-white rounded-4 mb-4 card-outer-mobile-flat text-center text-md-start">
        <h5 class="fw-bold fs-4 text-dark mb-3 text-center text-md-start">Visitas Programadas</h5>
        
        <div class="d-flex flex-column gap-3 w-100 mb-4">
          <button class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 py-2-5 bg-primary text-white border-0 rounded-3" @click="openCreate">
            <i class="bi bi-calendar-plus"></i> Programar Visita
          </button>
        </div>

        <div class="w-100 text-start">
          <div class="small fw-semibold text-secondary mb-2 d-flex align-items-center gap-1">
            <i class="bi bi-funnel"></i> Filtrar por Fecha
          </div>
          <div class="d-flex flex-column flex-md-row gap-2">
            <input v-model="filters.fecha" type="date" class="form-control rounded-3 py-2 px-3 text-dark border-slate-200" style="outline: none;" @change="loadVisitas">
            <div class="d-flex gap-2">
              <button class="btn btn-primary px-4 py-2 rounded-3 d-flex align-items-center gap-1.5" @click="loadVisitas">
                <i class="bi bi-search"></i> Buscar
              </button>
              <button class="btn btn-outline-secondary px-3 py-2 rounded-3" @click="clearFilter">
                Limpiar
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Visitas List Container -->
      <div class="card border-0 shadow-sm p-0 overflow-hidden card-outer-mobile-flat bg-white rounded-4">
        <div class="card-body p-0 text-start">
          
          <!-- Empty State when filtered results are 0 -->
          <div v-if="!loading && visitas.length === 0" class="text-center p-5 text-muted">
            <i class="bi bi-calendar-x display-6 d-block mb-2 text-muted"></i>
            <p class="mb-0 small text-secondary">No hay visitas para mostrar</p>
          </div>

          <div v-else>
            <!-- 1. DESKTOP VIEW: Beautiful Table layout -->
            <div class="table-responsive d-none d-md-block">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr>
                    <th class="ps-4 text-secondary fw-bold text-uppercase fs-7 tracking-wider">Fecha Programada</th>
                    <th class="text-secondary fw-bold text-uppercase fs-7 tracking-wider">Productor / Predio</th>
                    <th class="text-secondary fw-bold text-uppercase fs-7 tracking-wider">Médico Veterinario</th>
                    <th class="text-secondary fw-bold text-uppercase fs-7 tracking-wider">Estado</th>
                    <th class="text-center text-secondary fw-bold text-uppercase fs-7 tracking-wider">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="visita in paginatedVisitas" :key="visita.id" class="border-bottom">
                    <td class="ps-4">
                      <div class="fw-bold text-dark fs-6">{{ formatDate(visita.fecha_programada) }}</div>
                      <small class="text-secondary small fst-italic">{{ getRelativeTime(visita.fecha_programada) }}</small>
                    </td>
                    <td>
                      <div class="fw-bold text-dark">{{ visita.predio?.productor?.nombre || 'Sin productor' }} {{ visita.predio?.productor?.apellido_paterno }}</div>
                      <small class="text-secondary fs-7">{{ visita.predio?.nombre_rancho }} ({{ visita.predio?.localidad }})</small>
                    </td>
                    <td class="text-secondary">{{ visita.veterinario?.name || 'Administrador Central' }}</td>
                    <td>
                      <span class="badge rounded-3 px-2-5 py-1-5 d-inline-flex align-items-center gap-1 fw-bold fs-7-5" :class="badgeClass(visita.estado)">
                        {{ (visita.estado || 'pendiente').toUpperCase() }}
                      </span>
                    </td>
                    <td>
                      <div class="d-flex justify-content-center gap-2">
                        <button 
                          v-if="!visita.inspeccion?.id"
                          @click="iniciarDictamen(visita)" 
                          class="btn btn-sm btn-primary d-flex align-items-center justify-content-center gap-1.5 px-3 py-1-5" 
                          style="background: #2563eb;"
                        >
                          <i class="bi bi-clipboard-plus"></i> Iniciar
                        </button>
                        <button 
                          v-else
                          @click="$router.push('/inspecciones/' + visita.inspeccion.id)" 
                          class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center gap-1.5 px-3 py-1-5"
                        >
                          <i class="bi bi-eye"></i> Dictamen
                        </button>
                        <button 
                          @click="openEdit(visita)" 
                          class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center bg-transparent border-primary text-primary" 
                          title="Editar / Reprogramar"
                          style="width: 32px; height: 32px;"
                        >
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button 
                          v-if="visita.estado !== 'cancelada'"
                          @click="cancelar(visita)" 
                          class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center bg-transparent border-danger text-danger" 
                          title="Cancelar"
                          style="width: 32px; height: 32px;"
                        >
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- 2. MOBILE VIEW: Floating cards with light gray box details matching screens exactly -->
            <div class="d-block d-md-none px-3 py-2">
              <div 
                v-for="visita in paginatedVisitas" 
                :key="visita.id" 
                class="producer-mobile-card shadow-sm mb-3 position-relative"
                style="border-left-color: #2563eb;"
              >
                <div class="card-fields-box">
                  <!-- Fecha Field -->
                  <div class="card-field">
                    <span class="field-label">FECHA</span>
                    <span class="field-value text-dark fw-bold fs-5">{{ formatDate(visita.fecha_programada) }}</span>
                    <span class="field-subtitle text-secondary fst-italic fs-7.5 mt-0.5">{{ getRelativeTime(visita.fecha_programada) }}</span>
                  </div>

                  <!-- Productor Field -->
                  <div class="card-field">
                    <span class="field-label">PRODUCTOR</span>
                    <span class="field-value fw-bold text-dark fs-5">
                      {{ visita.predio?.productor?.nombre || 'Sin productor' }} {{ visita.predio?.productor?.apellido_paterno }}
                    </span>
                    <span class="field-subtitle text-secondary fs-7 mt-0.5">
                      {{ visita.predio?.nombre_rancho }} ({{ visita.predio?.localidad || 'Nayarit' }})
                    </span>
                  </div>

                  <!-- Medico Field -->
                  <div class="card-field">
                    <span class="field-label">MÉDICO</span>
                    <span class="field-value text-dark fs-6">{{ visita.veterinario?.name || 'Administrador Central' }}</span>
                  </div>

                  <!-- Inyeccion Status Field -->
                  <div class="card-field">
                    <span class="field-label">INYECCIÓN</span>
                    <div class="badge rounded-3 py-2 px-3 fs-7.5 w-100 d-flex align-items-center justify-content-center gap-1.5" :class="visita.inspeccion?.id ? 'bg-success text-white' : 'bg-danger text-white'">
                      <i class="bi" :class="visita.inspeccion?.id ? 'bi-check-lg' : 'bi-x-lg'"></i>
                      {{ visita.inspeccion?.id ? 'Realizada' : 'Pendiente' }}
                    </div>
                    <span v-if="visita.inspeccion?.fecha_inyeccion" class="field-subtitle text-center text-secondary fs-7.5 mt-1.5">
                      {{ formatDateTime(visita.inspeccion.fecha_inyeccion, visita.inspeccion.hora_inyeccion) }}
                    </span>
                  </div>

                  <!-- Lectura Status Field -->
                  <div class="card-field">
                    <span class="field-label">LECTURA</span>
                    <div class="badge rounded-3 py-2 px-3 fs-7.5 w-100 d-flex align-items-center justify-content-center gap-1.5" :class="(visita.inspeccion?.id && visita.inspeccion?.fecha_lectura) ? 'bg-success text-white' : 'bg-danger text-white'">
                      <i class="bi" :class="(visita.inspeccion?.id && visita.inspeccion?.fecha_lectura) ? 'bi-check-lg' : 'bi-x-lg'"></i>
                      {{ (visita.inspeccion?.id && visita.inspeccion?.fecha_lectura) ? 'Realizada' : 'Pendiente' }}
                    </div>
                    <span v-if="visita.inspeccion?.fecha_lectura" class="field-subtitle text-center text-secondary fs-7.5 mt-1.5">
                      {{ formatDateTime(visita.inspeccion.fecha_lectura, visita.inspeccion.hora_lectura) }}
                    </span>
                  </div>
                </div>

                <!-- Actions Footer -->
                <div class="producer-mobile-footer d-flex align-items-center justify-content-between p-3" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
                  <div class="footer-actions-label fw-bold text-secondary mb-0">ACCIONES</div>
                  <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <button 
                      v-if="!visita.inspeccion?.id"
                      @click="iniciarDictamen(visita)" 
                      class="btn btn-sm btn-primary d-flex align-items-center justify-content-center gap-1.5 px-3 py-2 fw-semibold" 
                      style="border-radius: 10px; background: #2563eb; font-size: 0.82rem; height: 40px;"
                    >
                      <i class="bi bi-clipboard-plus"></i> Iniciar
                    </button>
                    <button 
                      v-else
                      @click="$router.push('/inspecciones/' + visita.inspeccion.id)" 
                      class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center gap-1.5 px-3 py-2 fw-semibold bg-transparent text-primary" 
                      style="border-radius: 10px; font-size: 0.82rem; height: 40px;"
                    >
                      <i class="bi bi-eye"></i> Dictamen
                    </button>
                    <button 
                      @click="openEdit(visita)" 
                      class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center bg-transparent border-secondary text-secondary" 
                      title="Editar / Reprogramar"
                      style="width: 40px; height: 40px; border-radius: 10px;"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button 
                      v-if="visita.estado !== 'cancelada'"
                      @click="cancelar(visita)" 
                      class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center bg-transparent border-danger text-danger" 
                      title="Cancelar Visita"
                      style="width: 40px; height: 40px; border-radius: 10px;"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- PAGINATION FOOTER: Custom chevron styles matching mockup -->
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
      <a class="bottom-nav-link" @click.prevent="$router.push('/inspecciones')">
        <i class="bi bi-clipboard-check"></i>
        <span>Dictámenes</span>
      </a>
      <a class="bottom-nav-link active" @click.prevent>
        <i class="bi bi-calendar-event-fill"></i>
        <span>Agenda</span>
      </a>
      <a class="bottom-nav-link" @click.prevent="sidebarActive = true">
        <i class="bi bi-people"></i>
        <span>Más</span>
      </a>
    </nav>

    <!-- Modal for programming visits -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card shadow-lg">
        <div class="modal-header">
          <span class="modal-title fw-bold text-dark fs-5">
            <i class="bi bi-calendar2-week text-primary me-2"></i>
            {{ formMode === 'create' ? 'Programar Visita' : 'Editar Visita' }}
          </span>
          <button class="btn-close-modal d-flex align-items-center justify-content-center" @click="closeModal">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body text-start">
          <div class="form-group-custom">
            <label class="form-label-custom">Predio / Rancho *</label>
            <select v-model="form.predio_id" class="form-control-custom" required>
              <option value="">Selecciona un predio</option>
              <option v-for="predio in predios" :key="predio.id" :value="predio.id">
                {{ predio.nombre_rancho || predio.nombre }} - {{ predio.productor?.nombre }} {{ predio.productor?.apellido_paterno }}
              </option>
            </select>
          </div>
          <div class="form-group-custom">
            <label class="form-label-custom">Fecha Programada *</label>
            <input v-model="form.fecha_programada" type="date" class="form-control-custom" required>
          </div>
          <div class="form-group-custom">
            <label class="form-label-custom">Observaciones</label>
            <textarea v-model="form.observaciones" class="form-control-custom" rows="3" placeholder="Ingresa notas o indicaciones para la visita..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-modal-cancel" @click="closeModal">Cancelar</button>
          <button class="btn-modal-save" @click="saveVisita" :disabled="saving">
            {{ saving ? 'Guardando...' : 'Guardar Datos' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Network } from '@capacitor/network';
import api from '../services/api.js';
import db from '../services/db.js';

export default {
  name: 'VisitasView',
  data() {
    return {
      userName: '',
      isAdmin: false,
      isOnline: true,
      sidebarActive: false,
      networkListener: null,
      loading: false,
      saving: false,
      visitas: [],
      predios: [],
      filters: {
        fecha: ''
      },
      showModal: false,
      formMode: 'create',
      currentId: null,
      form: {
        predio_id: '',
        fecha_programada: '',
        observaciones: ''
      },
      currentPage: 1,
      successMsg: '',
      errorMsg: ''
    };
  },
  computed: {
    totalPages() {
      return Math.ceil(this.totalResults / 10) || 1;
    },
    totalResults() {
      return this.visitas.length;
    },
    startResult() {
      return this.totalResults === 0 ? 0 : ((this.currentPage - 1) * 10) + 1;
    },
    endResult() {
      return Math.min(this.currentPage * 10, this.totalResults);
    },
    paginatedVisitas() {
      const start = (this.currentPage - 1) * 10;
      const end = this.currentPage * 10;
      return this.visitas.slice(start, end);
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
      this.errorMsg = '';
      await Promise.all([this.loadPredios(), this.loadVisitas()]);
    },
    async loadPredios() {
      try {
        const res = await api.getPredios();
        this.predios = res.data || [];
        await db.savePredios(this.predios);
      } catch (e) {
        this.predios = await db.getPredios();
      }
    },
    async loadVisitas() {
      this.loading = true;
      this.errorMsg = '';
      try {
        const res = await api.getVisitas(this.filters.fecha ? { fecha: this.filters.fecha } : {});
        this.visitas = res.data || [];
        await db.saveVisitas(this.visitas);
        this.currentPage = 1;
      } catch (e) {
        console.error('Error loading visitas:', e);
        const local = await db.getVisitas();
        this.visitas = this.filters.fecha ? local.filter(v => v.fecha_programada === this.filters.fecha) : local;
        this.errorMsg = `No se pudo leer el servidor: ${e.message || e}. Mostrando datos locales.`;
      } finally {
        this.loading = false;
      }
    },
    clearFilter() {
      this.filters.fecha = '';
      this.loadVisitas();
    },
    openCreate() {
      this.$router.push('/visitas/nuevo');
    },
    openEdit(visita) {
      this.$router.push(`/visitas/editar/${visita.id}`);
    },
    reprogramar(visita) {
      this.openEdit(visita);
    },
    async cancelar(visita) {
      if (!confirm('¿Cancelar esta visita?')) return;
      try {
        await api.updateVisitaEstado(visita.id, 'cancelada');
        this.successMsg = 'Visita cancelada correctamente.';
        await this.loadVisitas();
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo cancelar la visita.';
      }
    },
    iniciarDictamen(visita) {
      this.$router.push(`/inspeccion/${visita.predio_id}?visita_id=${visita.id}`);
    },
    async saveVisita() {
      this.saving = true;
      this.errorMsg = '';
      try {
        if (this.formMode === 'create') {
          await api.createVisita(this.form);
          this.successMsg = 'Visita programada correctamente.';
        } else {
          await api.updateVisita(this.currentId, this.form);
          this.successMsg = 'Visita actualizada correctamente.';
        }
        this.closeModal();
        await this.loadVisitas();
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo guardar la visita.';
      } finally {
        this.saving = false;
      }
    },
    closeModal() {
      this.showModal = false;
    },
    badgeClass(estado) {
      if (estado === 'completada') return 'bg-success text-white';
      if (estado === 'cancelada') return 'bg-danger text-white';
      return 'bg-warning text-dark';
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
    formatDateTime(dateStr, timeStr) {
      if (!dateStr) return '';
      const formattedDate = this.formatDate(dateStr);
      if (!timeStr) return formattedDate;
      const timeParts = timeStr.split(':');
      if (timeParts.length >= 2) {
        let hour = parseInt(timeParts[0]);
        const min = timeParts[1];
        const ampm = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12;
        hour = hour ? hour : 12; // 0 hour should format to 12
        return `${formattedDate} ${hour}:${min} ${ampm}`;
      }
      return `${formattedDate} ${timeStr}`;
    },
    getRelativeTime(dateStr) {
      if (!dateStr) return '';
      const parts = dateStr.split('-');
      let d;
      if (parts.length === 3) {
        d = new Date(parts[0], parts[1] - 1, parts[2]);
      } else {
        d = new Date(dateStr);
      }
      const today = new Date();
      today.setHours(0,0,0,0);
      d.setHours(0,0,0,0);
      const diffTime = today.getTime() - d.getTime();
      const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
      if (diffDays === 0) return 'hoy';
      if (diffDays === 1) return 'ayer';
      if (diffDays === -1) return 'mañana';
      if (diffDays > 0) return `hace ${diffDays} días`;
      return `dentro de ${Math.abs(diffDays)} días`;
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

/* Table styling matching desktop exactly */
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

.pagination-footer-custom {
  border-bottom-left-radius: 16px;
  border-bottom-right-radius: 16px;
  border-top: 1px solid #f1f5f9;
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

/* Modals */
.form-group-custom {
  margin-bottom: 16px;
}

.form-label-custom {
  display: block;
  font-size: 0.78rem;
  font-weight: 700;
  color: #334155;
  margin-bottom: 6px;
}

.form-control-custom {
  width: 100%;
  padding: 11px 14px;
  border: 1.5px solid #cbd5e1;
  border-radius: 10px;
  font-size: 0.95rem;
  color: #1e293b;
  background: white;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.modal-card {
  width: 100%;
  max-width: 520px;
  background: white;
  border-radius: 18px;
  overflow: hidden;
}

.modal-header, .modal-footer {
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
}

.modal-body {
  padding: 20px;
}

.modal-title {
  font-weight: 700;
  color: #0f172a;
}

.btn-close-modal {
  background: #e2e8f0;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
}

.btn-modal-save, .btn-modal-cancel {
  border: none;
  border-radius: 10px;
  padding: 10px 16px;
  font-weight: 600;
}

.btn-modal-save {
  background: #2563eb;
  color: #fff;
}

.btn-modal-cancel {
  background: #fff;
  color: #475569;
  border: 1px solid #cbd5e1;
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
