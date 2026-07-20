<template>
  <AppLayout>
    <PullToRefresh @refresh="onRefresh" :loading="refreshing">
      <!-- Top Action Bar (Premium Web Replica) -->
      <div class="welcome-header mb-4 text-start d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
        <div>
          <h2 class="h4 fw-bold mb-1 text-dark">Inspecciones Pecuarias</h2>
          <p class="text-secondary small mb-0">Historial de registros and seguimiento</p>
        </div>
        
        <!-- Web Badges (Conectado / Administrador Central) -->
        <div class="d-none d-lg-flex align-items-center gap-2">
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
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3 w-100">
            <h5 class="mb-0 fw-bold fs-5 text-dark text-center text-lg-start w-100 w-lg-auto">Dictámenes Registrados</h5>
            <div class="d-flex flex-column flex-lg-row gap-2 w-100 w-lg-auto">
              <button v-if="isAdmin || isMedico" @click="downloadSábana()" class="btn btn-outline-success btn-sm-custom d-flex align-items-center justify-content-center gap-1.5 px-3 py-2 fw-semibold border-success text-success bg-transparent rounded-3 w-100 w-lg-auto">
                <i class="bi bi-file-earmark-excel"></i> Descargar Sábana
              </button>
              <button @click="$router.push('/inspeccion')" class="btn btn-primary btn-sm-custom d-flex align-items-center justify-content-center gap-1-5 px-3 py-2 bg-primary text-white border-0 rounded-3 w-100 w-lg-auto">
                <i class="bi bi-plus-lg"></i> Nuevo Dictamen
              </button>
            </div>
          </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
          <div class="filter-row">
            <div class="filter-group filter-search">
              <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" v-model="filtro.texto" class="filter-input" placeholder="Buscar por folio, predio, veterinario...">
              </div>
            </div>
            <div class="filter-group filter-date">
              <input type="date" v-model="filtro.fecha_desde" class="filter-input" placeholder="Desde" title="Fecha desde">
            </div>
            <div class="filter-group filter-date">
              <input type="date" v-model="filtro.fecha_hasta" class="filter-input" placeholder="Hasta" title="Fecha hasta">
            </div>
            <div class="filter-group filter-estado">
              <select v-model="filtro.estado" class="filter-select">
                <option value="">Todos los estados</option>
                <option value="borrador">Borrador</option>
                <option value="finalizado">Finalizado</option>
              </select>
            </div>
            <div class="filter-group filter-actions">
              <button v-if="hayFiltrosActivos" @click="limpiarFiltros" class="btn-clear">
                <i class="bi bi-x-lg"></i> Limpiar
              </button>
            </div>
          </div>
        </div>

        <div class="card-body p-0 text-start">
          <!-- Empty State -->
          <div v-if="!loading && inspecciones.length === 0" class="text-center p-5 text-muted">
            <i class="bi bi-clipboard-x display-6 d-block mb-2 text-muted"></i>
            <p class="mb-0 small text-secondary">No hay inspecciones para mostrar</p>
          </div>
          <div v-else-if="!loading && inspeccionesFiltradas.length === 0" class="text-center p-5 text-muted">
            <i class="bi bi-search display-6 d-block mb-2 text-muted"></i>
            <p class="mb-0 small text-secondary">No hay inspecciones que coincidan con los filtros</p>
            <button @click="limpiarFiltros" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mt-3">
              <i class="bi bi-x-lg"></i> Limpiar filtros
            </button>
          </div>

          <div v-else>
            <!-- 1. DESKTOP VIEW: Beautiful and precise Table replica matching screenshot exactly -->
            <div class="table-responsive d-none d-lg-block">
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
                        {{ inspeccion.clave_interna || inspeccion.folio || 'Sin Folio (Borrador)' }}
                      </span>
                      <a v-else @click.prevent="viewInspeccion(inspeccion)" class="text-decoration-none text-primary fw-bold fs-6" style="cursor: pointer;">
                        {{ inspeccion.clave_interna || inspeccion.folio }}
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
                      <span v-if="inspeccion.modified_at && !isBorrador(inspeccion)" class="badge bg-info text-dark rounded-3 px-2-5 py-1-5 d-inline-flex align-items-center gap-1 fw-bold fs-7-5 ms-1 mt-1">
                        <i class="bi bi-arrow-repeat"></i> Modificado {{ inspeccion.modified_at_formatted || formatDateTime(inspeccion.modified_at) }}
                      </span>
                      <small v-if="inspeccion.modified_at && isBorrador(inspeccion)" class="text-muted d-block mt-1" style="font-size:0.65rem;">
                        <i class="bi bi-clock"></i> {{ inspeccion.modified_at_formatted || formatDateTime(inspeccion.modified_at) }}
                      </small>
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
            <div class="d-block d-lg-none px-3 py-2">
              <div class="mobile-cards-grid">
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
                    <span class="field-value producer-name-bold text-secondary fst-italic" v-if="isBorrador(inspeccion)">{{ inspeccion.clave_interna || inspeccion.folio || 'Sin Folio (Borrador)' }}</span>
                    <span class="field-value producer-name-bold text-primary" v-else @click="viewInspeccion(inspeccion)">{{ inspeccion.clave_interna || inspeccion.folio }}</span>
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
                    <span v-if="inspeccion.modified_at && !isBorrador(inspeccion)" class="badge bg-info text-dark rounded-3 px-2 py-1 d-flex align-items-center justify-content-center gap-1 fw-bold fs-7-5 mt-1 w-100">
                      <i class="bi bi-arrow-repeat"></i> Modificado {{ inspeccion.modified_at_formatted || formatDateTime(inspeccion.modified_at) }}
                    </span>
                    <small v-if="inspeccion.modified_at && isBorrador(inspeccion)" class="text-muted d-block mt-1 text-center" style="font-size:0.65rem;">
                      <i class="bi bi-clock"></i> {{ inspeccion.modified_at_formatted || formatDateTime(inspeccion.modified_at) }}
                    </small>
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
            </div>

            <!-- PAGINATION FOOTER: Precise design match to screenshot -->
            <div class="pagination-footer-custom d-flex justify-content-between align-items-center flex-wrap gap-3 p-4 bg-white">
              <!-- Left: Pagination Info (Desktop only or responsive) -->
              <div class="text-secondary small d-none d-lg-block">
                Mostrando <strong class="text-dark">{{ startResult }}</strong> a <strong class="text-dark">{{ endResult }}</strong> de <strong class="text-dark">{{ totalResults }}</strong> registros
              </div>
              
              <!-- Center/Right: Beautiful Custom Chevrons Pagination -->
              <div class="pagination-custom-wrapper d-flex align-items-center justify-content-center w-100 w-lg-auto gap-4 py-2">
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
    </PullToRefresh>
  </AppLayout>
</template>

<script>
import AppLayout from '../components/AppLayout.vue';
import PullToRefresh from '../components/PullToRefresh.vue';
import api from '../services/api.js';
import db from '../services/db.js';
import { Filesystem, Directory } from '@capacitor/filesystem';
import { App } from '@capacitor/app';
import { Share } from '@capacitor/share';
import { LocalNotifications } from '@capacitor/local-notifications';
import { Dialog } from '@capacitor/dialog';
import { FileOpener } from '@capacitor-community/file-opener';

export default {
  name: 'InspeccionesView',
  components: { AppLayout, PullToRefresh },
  data() {
    return {
      userName: '',
      isAdmin: false,
      isMedico: false,
      isOnline: true,
      loading: false,
      refreshing: false,
      inspecciones: [],
      localDrafts: [],
      prediosCatalog: [],
      errorMsg: '',
      successMsg: '',
      currentPage: 1,
      filtro: {
        texto: '',
        fecha_desde: '',
        fecha_hasta: '',
        estado: ''
      }
    };
  },
  computed: {
    inspeccionesFiltradas() {
      let items = this.inspecciones;
      const q = this.filtro.texto.trim().toLowerCase();
      if (q) {
        items = items.filter(i => {
          const folio = (i.folio || '').toLowerCase();
          const clave = (i.clave_interna || '').toLowerCase();
          const predio = (i.predio?.nombre_rancho || '').toLowerCase();
          const vet = (i.veterinario?.name || '').toLowerCase();
          const prod = this.getProductorName(i.predio?.productor).toLowerCase();
          return folio.includes(q) || clave.includes(q) || predio.includes(q) || vet.includes(q) || prod.includes(q);
        });
      }
      if (this.filtro.fecha_desde) {
        items = items.filter(i => i.fecha >= this.filtro.fecha_desde);
      }
      if (this.filtro.fecha_hasta) {
        items = items.filter(i => i.fecha <= this.filtro.fecha_hasta);
      }
      if (this.filtro.estado) {
        items = items.filter(i => i.estado === this.filtro.estado);
      }
      return items.sort((a, b) => {
        const aMod = a.modified_at ? new Date(a.modified_at).getTime() : 0;
        const bMod = b.modified_at ? new Date(b.modified_at).getTime() : 0;
        if (aMod !== bMod) return bMod - aMod;
        return new Date(b.fecha) - new Date(a.fecha);
      });
    },
    hayFiltrosActivos() {
      return this.filtro.texto || this.filtro.fecha_desde || this.filtro.fecha_hasta || this.filtro.estado;
    },
    totalPages() {
      return Math.ceil(this.totalResults / 20) || 1;
    },
    totalResults() {
      return this.inspeccionesFiltradas.length;
    },
    startResult() {
      return this.totalResults === 0 ? 0 : ((this.currentPage - 1) * 20) + 1;
    },
    endResult() {
      return Math.min(this.currentPage * 20, this.totalResults);
    },
    paginatedInspecciones() {
      const start = (this.currentPage - 1) * 20;
      const end = this.currentPage * 20;
      return this.inspeccionesFiltradas.slice(start, end);
    }
  },
  watch: {
    filtro: {
      handler() {
        this.currentPage = 1;
      },
      deep: true
    }
  },
  async mounted() {
    const user = api.getCurrentUser();
      this.userName = user?.name || 'Administrador Central';
      this.isAdmin = user?.roles && user.roles.includes('Administrador');
      this.isMedico = user?.roles && user.roles.includes('Medico_Campo');
      this.isOnline = navigator.onLine;

    await this.loadAll();

    this._onWindowOnline = () => this.isOnline = true;
    this._onWindowOffline = () => this.isOnline = false;
    window.addEventListener('online', this._onWindowOnline);
    window.addEventListener('offline', this._onWindowOffline);

    if (this.$route.query.downloadExcel === 'true') {
      this.$router.replace({ query: {} });
      this.downloadSábana();
    }
  },
  beforeUnmount() {
    window.removeEventListener('online', this._onWindowOnline);
    window.removeEventListener('offline', this._onWindowOffline);
  },
  methods: {
    async loadAll() {
      this.loading = true;
      this.errorMsg = '';
      try {
        this.prediosCatalog = await db.getPredios();

        // 1. Cargar borradores locales inmediatamente (siempre disponible)
        const local = await db.getInspeccionesPendientes();
        this.localDrafts = local
          .filter(item => item.estado === 'borrador')
          .map(item => ({
            ...item,
            predio_nombre: this.getLocalPredioName(item.predio_id),
          }));

        // Mostrar borradores locales de inmediato
        const localMerged = [];
        this.localDrafts.forEach(draft => {
          localMerged.unshift({
            id: `local-${draft.folio}`,
            folio: draft.folio,
            clave_interna: draft.clave_interna,
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
        });
        this.inspecciones = localMerged;

        // 2. Intentar obtener datos del servidor en segundo plano
        const isReachable = await api.checkRealConnectivity();
        if (isReachable) {
          try {
            const serverRes = await api.getInspecciones();
            const serverItems = (serverRes.data || []).filter(item => item);
            const merged = [...serverItems];

            // Agregar borradores locales que no existan en el servidor
            this.localDrafts.forEach(draft => {
              const exists = merged.some(item => item.folio === draft.folio);
              if (!exists) {
                merged.unshift({
                  id: `local-${draft.folio}`,
                  folio: draft.folio,
                  clave_interna: draft.clave_interna,
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
          } catch (serverErr) {
            console.warn('No se pudieron cargar inspecciones del servidor, mostrando datos locales:', serverErr.message);
          }
        }
      } catch (e) {
        if (this.inspecciones.length === 0) {
          this.errorMsg = 'No se pudieron cargar las inspecciones.';
        }
      } finally {
        this.loading = false;
      }
    },
    limpiarFiltros() {
      this.filtro.texto = '';
      this.filtro.fecha_desde = '';
      this.filtro.fecha_hasta = '';
      this.filtro.estado = '';
      this.currentPage = 1;
    },
    async onRefresh() {
      this.refreshing = true;
      try {
        await this.loadAll();
      } catch (e) {
        console.warn('Refresh error:', e);
      } finally {
        this.refreshing = false;
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
    formatDateTime(isoStr) {
      if (!isoStr) return '';
      try {
        const d = new Date(isoStr);
        return d.toLocaleDateString('es-MX', {
          day: '2-digit', month: '2-digit', year: 'numeric',
          hour: '2-digit', minute: '2-digit'
        });
      } catch (e) {
        return isoStr;
      }
    },
    continueDraft(item) {
      this.$router.push(`/inspeccion/editar/${item.id || item.folio}${item.visita_id ? `?visita_id=${item.visita_id}` : ''}`);
    },
    continueFromServer(inspeccion) {
      this.$router.push(`/inspeccion/editar/${inspeccion.id}${inspeccion.visita_id ? `?visita_id=${inspeccion.visita_id}` : ''}`);
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
      this.errorMsg = '';
      this.successMsg = '';
      try {
        const blob = await api.getInspectionPdf(inspeccion.id);
        const fileName = `dictamen_${inspeccion.clave_interna || inspeccion.id}_${new Date().getDate()}-${new Date().getMonth() + 1}-${new Date().getFullYear()}.pdf`;

        if (window.Capacitor && window.Capacitor.isNativePlatform()) {
          const reader = new FileReader();
          reader.readAsDataURL(blob);
          reader.onloadend = async () => {
            try {
              const base64data = reader.result.split(',')[1];
              const result = await Filesystem.writeFile({
                path: fileName,
                data: base64data,
                directory: Directory.Documents,
                recursive: true
              });
              // Also save to public Downloads folder (Android)
              let downloadSaved = false;
              try {
                await Filesystem.writeFile({
                  path: fileName,
                  data: base64data,
                  directory: Directory.Downloads,
                  recursive: true
                });
                downloadSaved = true;
                this.successMsg = `PDF guardado en Descargas del dispositivo.`;
              } catch (_) {
                this.successMsg = `PDF guardado en Documentos: ${fileName}`;
              }

              // Programar notificación de descarga completa
              try {
                const permission = await LocalNotifications.checkPermissions();
                if (permission.display !== 'granted') {
                  await LocalNotifications.requestPermissions();
                }
                await LocalNotifications.schedule({
                  notifications: [
                    {
                      title: "Descarga Completa",
                      body: `El archivo "${fileName}" se descargó exitosamente. Toca para abrirlo.`,
                      id: Math.floor(Math.random() * 1000000),
                      sound: true,
                      extra: {
                        uri: result.uri,
                        filename: fileName
                      }
                    }
                  ]
                });
              } catch (notiErr) {
                console.warn('Error scheduling local notification:', notiErr);
              }

               // Preguntar al usuario qué desea hacer
              const resultDialog = await Dialog.confirm({
                title: 'Descarga completada',
                message: `"${fileName}" guardado.\n\n¿Abrir archivo para leerlo?`,
                okButtonTitle: 'Ver',
                cancelButtonTitle: 'Compartir'
              });
              if (resultDialog.value) {
                try {
                  const cacheName = `view_${Date.now()}_${fileName}`;
                  await Filesystem.writeFile({ path: cacheName, data: base64data, directory: Directory.Cache });
                  const { uri } = await Filesystem.getUri({ path: cacheName, directory: Directory.Cache });
                  await FileOpener.open({ filePath: uri, contentType: 'application/pdf' });
                } catch (openErr) {
                  console.warn('Error al abrir el archivo:', openErr);
                }
              } else {
                try {
                  await Share.share({
                    title: fileName,
                    url: result.uri,
                    dialogTitle: `Compartir ${fileName}`
                  });
                } catch (shareErr) {
                  console.warn('Error al compartir archivo:', shareErr);
                }
              }
            } catch (err) {
              console.error('Error saving PDF native:', err);
              this.errorMsg = 'No se pudo guardar el PDF en el dispositivo: ' + err.message;
            }
          };
        } else {
          const url = URL.createObjectURL(blob);
          window.open(url, '_blank');
          setTimeout(() => URL.revokeObjectURL(url), 10000);
        }
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo abrir el PDF.';
      }
    },
    async downloadSábana() {
      this.loading = true;
      this.errorMsg = '';
      this.successMsg = '';
      try {
        const blob = await api.getSábanaExcel();
        const fileName = `dictamenes_pecuarios_${new Date().getDate()}-${new Date().getMonth() + 1}-${new Date().getFullYear()}.xlsx`;

        // Check if we are running in a Capacitor Native context (Android/iOS)
        if (window.Capacitor && window.Capacitor.isNativePlatform()) {
          const reader = new FileReader();
          reader.readAsDataURL(blob);
          reader.onloadend = async () => {
            try {
              const base64data = reader.result.split(',')[1];
              const result = await Filesystem.writeFile({
                path: fileName,
                data: base64data,
                directory: Directory.Documents,
                recursive: true
              });
              // Also save to public Downloads folder (Android)
              let downloadSaved = false;
              try {
                await Filesystem.writeFile({
                  path: fileName,
                  data: base64data,
                  directory: Directory.Downloads,
                  recursive: true
                });
                downloadSaved = true;
                this.successMsg = `Sábana Excel guardada con éxito en Descargas del dispositivo.`;
              } catch (_) {
                this.successMsg = `Sábana Excel guardada con éxito en Documentos: ${fileName}`;
              }

              // Programar notificación de descarga completa
              try {
                const permission = await LocalNotifications.checkPermissions();
                if (permission.display !== 'granted') {
                  await LocalNotifications.requestPermissions();
                }
                await LocalNotifications.schedule({
                  notifications: [
                    {
                      title: "Descarga Completa",
                      body: `El archivo "${fileName}" se descargó exitosamente. Toca para abrirlo.`,
                      id: Math.floor(Math.random() * 1000000),
                      sound: true,
                      extra: {
                        uri: result.uri,
                        filename: fileName
                      }
                    }
                  ]
                });
              } catch (notiErr) {
                console.warn('Error scheduling local notification:', notiErr);
              }

              // Alerta
              if (downloadSaved) {
                await Dialog.alert({ title: 'Descarga completada', message: `El archivo "${fileName}" se guardó en la carpeta de Descargas de tu teléfono.` });
              } else {
                await Dialog.alert({ title: 'Descarga completada', message: `El archivo "${fileName}" se guardó en los Documentos de tu teléfono.` });
              }

              // Preguntar al usuario qué desea hacer
              const resultDialog = await Dialog.confirm({
                title: 'Descarga completada',
                message: `"${fileName}" guardado.\n\n¿Abrir archivo para leerlo?`,
                okButtonTitle: 'Ver',
                cancelButtonTitle: 'Compartir'
              });
              if (resultDialog.value) {
                try {
                  const cacheName = `view_${Date.now()}_${fileName}`;
                  await Filesystem.writeFile({ path: cacheName, data: base64data, directory: Directory.Cache });
                  const { uri } = await Filesystem.getUri({ path: cacheName, directory: Directory.Cache });
                  const contentType = fileName.endsWith('.xlsx') ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'application/pdf';
                  await FileOpener.open({ filePath: uri, contentType });
                } catch (openErr) {
                  console.warn('Error al abrir el archivo:', openErr);
                }
              } else {
                try {
                  await Share.share({
                    title: fileName,
                    url: result.uri,
                    dialogTitle: `Compartir ${fileName}`
                  });
                } catch (shareErr) {
                  console.warn('Error al compartir archivo:', shareErr);
                }
              }
            } catch (err) {
              console.error('Error saving file native:', err);
              this.errorMsg = 'No se pudo guardar el archivo en el dispositivo móvil: ' + err.message;
            } finally {
              this.loading = false;
            }
          };
        } else {
          // Standard browser download
          const url = URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = fileName;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          setTimeout(() => URL.revokeObjectURL(url), 15000);
          this.successMsg = 'Sábana Excel descargada con éxito.';
          this.loading = false;
        }
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo descargar la sábana Excel.';
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



.pagination-footer-custom {
  border-bottom-left-radius: 16px;
  border-bottom-right-radius: 16px;
  border-top: 1px solid #f1f5f9;
}

/* Responsive configurations */
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
  .bottom-nav {
    display: none;
  }

  .main-content {
    margin-left: 270px;
    padding: 2.5rem !important;
    min-height: 100dvh;
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

@media (max-width: 991.98px) {
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

/* ===== Filter Bar ===== */
.filter-bar {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 8px 16px;
}

.filter-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.filter-group {
  flex: 1 1 auto;
  min-width: 0;
}

.filter-search {
  flex: 2 1 200px;
}

.filter-date {
  flex: 1 1 140px;
  min-width: 120px;
}

.filter-estado {
  flex: 1 1 140px;
  min-width: 120px;
}

.filter-actions {
  flex: 0 0 auto;
}

.search-wrapper {
  display: flex;
  align-items: center;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 50px;
  padding: 0 12px;
  transition: border-color 0.2s ease;
}

.search-wrapper:focus-within {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-icon {
  color: #94a3b8;
  font-size: 0.85rem;
  margin-right: 8px;
  flex-shrink: 0;
}

.filter-input {
  width: 100%;
  padding: 8px 0;
  border: none;
  background: transparent;
  font-size: 0.88rem;
  font-family: inherit;
  color: #1e293b;
  outline: none;
}

.filter-input[type="date"] {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 50px;
  background: white;
  cursor: pointer;
}

.filter-input[type="date"]:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.filter-select {
  width: 100%;
  padding: 8px 32px 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 50px;
  background: white;
  font-size: 0.88rem;
  font-family: inherit;
  color: #1e293b;
  outline: none;
  appearance: none;
  -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  cursor: pointer;
}

.filter-select:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.btn-clear {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 50px;
  background: white;
  color: #64748b;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.btn-clear:active {
  background: #f1f5f9;
}

@media (max-width: 768px) {
  .filter-search {
    flex: 1 1 100%;
  }
  .filter-date {
    flex: 1 1 calc(50% - 4px);
    min-width: 0;
  }
  .filter-estado {
    flex: 1 1 calc(50% - 4px);
    min-width: 0;
  }
  .filter-actions {
    flex: 1 1 100%;
    display: flex;
    justify-content: center;
  }
}
</style>
