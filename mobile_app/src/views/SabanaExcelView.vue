<template>
  <AppLayout>
    <PullToRefresh @refresh="onRefresh" :loading="refreshing">
      <div class="welcome-header mb-4 text-start d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h2 class="h4 fw-bold mb-1">Sábana General</h2>
          <p class="text-secondary small mb-0">Consulta y exporta dictámenes pecuarios</p>
        </div>
      </div>

      <div v-if="!isOnline" class="card border-0 shadow-sm p-4 rounded-4 mb-4 text-center bg-white border-start border-danger border-4">
        <div class="d-flex flex-column align-items-center gap-3">
          <div class="bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
            <i class="bi bi-cloud-slash-fill fs-2"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-1 text-danger">Sin Conexión</h5>
            <p class="text-secondary small mb-0">La sábana requiere conexión a Internet para consultar los datos del servidor.</p>
          </div>
          <button class="btn btn-outline-danger btn-sm rounded-pill px-4 mt-1" @click="checkConnection">
            <i class="bi bi-arrow-repeat me-1"></i> Reintentar
          </button>
        </div>
      </div>

      <div v-else>
        <div v-if="loading" class="text-center py-5 text-muted">
          <div class="spinner-border text-primary mb-3" role="status"></div>
          <p class="small mb-0">Cargando sábana de dictámenes...</p>
        </div>

        <div v-else>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-sm rounded-pill px-3 shadow-sm" :class="showFilters ? 'btn-primary' : 'btn-outline-primary'" @click="showFilters = !showFilters">
              <i class="bi bi-funnel-fill me-1"></i> {{ showFilters ? 'Ocultar Filtros' : 'Mostrar Filtros' }}
            </button>
            <button v-if="hasActiveFilters" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm" @click="clearFilters">
              <i class="bi bi-x-lg me-1"></i> Limpiar Filtros
            </button>
          </div>

          <div v-if="showFilters" class="card border-0 shadow-sm mb-4 bg-light-page p-3 border-slate-100 rounded-4 text-start">
            <div class="row g-2">
              <div class="col-6 col-md-4">
                <label class="form-label small fw-semibold text-secondary mb-1">Zona</label>
                <select v-model="filterParams.zona" class="form-select form-select-sm">
                  <option value="">Todas las Zonas</option>
                  <option v-for="z in filterOptions.zonas" :key="z" :value="z">Zona {{ z }}</option>
                </select>
              </div>
              <div class="col-6 col-md-4">
                <label class="form-label small fw-semibold text-secondary mb-1">Tipo Actividad</label>
                <select v-model="filterParams.tipo_actividad" class="form-select form-select-sm">
                  <option value="">Todas las Actividades</option>
                  <option v-for="t in filterOptions.tipos_actividad" :key="t" :value="t">{{ t }}</option>
                </select>
              </div>
              <div v-if="isAdmin" class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary mb-1">Médico</label>
                <select v-model="filterParams.medico_id" class="form-select form-select-sm">
                  <option value="">Todos los Médicos</option>
                  <option v-for="m in filterOptions.medicos" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
              </div>
              <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                <button class="btn btn-sm btn-primary rounded-pill px-4" @click="fetchData(true)">
                  <i class="bi bi-search me-1"></i> Filtrar
                </button>
              </div>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
              <div class="card border-0 shadow-sm bg-white rounded-3 stat-card-custom">
                <div class="card-body p-2 d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center animate-icon" style="width: 32px; height: 32px; flex-shrink: 0;">
                    <i class="bi bi-journal-text" style="font-size: 1.1rem;"></i>
                  </div>
                  <div class="min-w-0">
                    <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.03em; line-height: 1.1;">Dictámenes</div>
                    <div class="fw-bold mb-0 text-dark" style="font-size: 1.1rem; line-height: 1.2;">{{ kpis.total_inspecciones }}</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card border-0 shadow-sm bg-white rounded-3 stat-card-custom">
                <div class="card-body p-2 d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center animate-icon" style="width: 32px; height: 32px; flex-shrink: 0;">
                    <i class="bi bi-border-all" style="font-size: 1.1rem;"></i>
                  </div>
                  <div class="min-w-0">
                    <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.03em; line-height: 1.1;">Probados</div>
                    <div class="fw-bold mb-0 text-dark" style="font-size: 1.1rem; line-height: 1.2;">{{ kpis.total_probados }}</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card border-0 shadow-sm bg-white rounded-3 stat-card-custom">
                <div class="card-body p-2 d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center animate-icon" style="width: 32px; height: 32px; flex-shrink: 0;">
                    <i class="bi bi-check-circle-fill" style="font-size: 1.1rem;"></i>
                  </div>
                  <div class="min-w-0">
                    <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.03em; line-height: 1.1;">Negativos</div>
                    <div class="fw-bold mb-0 text-success" style="font-size: 1.1rem; line-height: 1.2;">{{ kpis.total_negativos }}</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card border-0 shadow-sm bg-white rounded-3 stat-card-custom">
                <div class="card-body p-2 d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center animate-icon" style="width: 32px; height: 32px; flex-shrink: 0;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.1rem;"></i>
                  </div>
                  <div class="min-w-0">
                    <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.03em; line-height: 1.1;">Reactores</div>
                    <div class="fw-bold mb-0 text-danger" style="font-size: 1.1rem; line-height: 1.2;">{{ kpis.total_reactores }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card border-0 shadow-sm rounded-4 mb-4" style="overflow: visible;">
            <div class="card-header bg-white fw-bold py-3 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
              <span><i class="bi bi-table text-primary me-2"></i> Vista Previa de la Sábana</span>
              <button class="btn rounded-3 px-4 shadow fw-bold" style="background-color: #198754 !important; color: #fff !important; border: none !important;" @click="downloadExcel" :disabled="downloading">
                <i class="bi bi-download me-1"></i> {{ downloading ? 'Descargando...' : 'Excel' }}
              </button>
            </div>
            <div class="card-body p-0 text-start">
              <div v-if="inspecciones.length === 0" class="text-center p-5 text-muted">
                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                <h5 class="fw-semibold text-secondary">No se encontraron dictámenes</h5>
                <p class="text-muted small">Intenta ajustando los filtros.</p>
              </div>
              <div v-else class="table-responsive" style="overflow-x: auto; width: 100%;">
                <table class="table table-hover align-middle mb-0" style="min-width: 1100px;">
                  <thead class="table-light small text-uppercase text-secondary">
                    <tr>
                      <th class="ps-3">Clave</th>
                      <th>Predio</th>
                      <th>UPP</th>
                      <th>Beneficiario</th>
                      <th>Localidad / Mpio.</th>
                      <th>Prueba</th>
                      <th>Fecha</th>
                      <th class="text-center">Probados</th>
                      <th class="text-center">Neg.</th>
                      <th class="text-center">React.</th>
                      <th class="pe-3">Médico</th>
                    </tr>
                  </thead>
                  <tbody class="small">
                    <tr v-for="ins in inspecciones" :key="ins.id" style="cursor: pointer;" @click="$router.push('/inspecciones/' + ins.id)">
                      <td class="ps-3 fw-bold text-primary">{{ ins.clave || 'Sin Folio' }}</td>
                      <td class="fw-semibold">{{ ins.predio || 'N/A' }}</td>
                      <td><span class="badge bg-light text-dark border">{{ ins.upp || 'N/A' }}</span></td>
                      <td>{{ ins.productor || 'N/A' }}</td>
                      <td>
                        <div>{{ ins.localidad || '-' }}</div>
                        <small class="text-muted">{{ ins.municipio || '' }}</small>
                      </td>
                      <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">{{ ins.prueba || 'N/A' }}</span></td>
                      <td>{{ ins.fecha || 'N/A' }}</td>
                      <td class="text-center fw-bold">{{ ins.probados }}</td>
                      <td class="text-center text-success fw-bold">{{ ins.negativos }}</td>
                      <td class="text-center">
                        <span v-if="ins.reactores > 0" class="badge bg-danger rounded-pill fw-bold">{{ ins.reactores }}</span>
                        <span v-else class="text-muted">0</span>
                      </td>
                      <td class="pe-3"><small class="text-secondary fw-semibold">{{ ins.veterinario || 'N/A' }}</small></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-if="pagination.last_page > 1 || pagination.total > pagination.per_page" class="pagination-footer-custom d-flex justify-content-between align-items-center flex-wrap gap-3 p-4 bg-white">
              <div class="text-secondary small d-none d-lg-block">
                Mostrando <strong class="text-dark">{{ pagination.from }}</strong> a <strong class="text-dark">{{ pagination.to }}</strong> de <strong class="text-dark">{{ pagination.total }}</strong> registros
              </div>
              <div class="pagination-custom-wrapper d-flex align-items-center justify-content-center w-100 w-lg-auto gap-4 py-2">
                <button class="pagination-custom-btn prev-btn" :disabled="pagination.current_page <= 1" @click="changePage(pagination.current_page - 1)">
                  <i class="bi bi-chevron-left"></i>
                </button>
                <div class="pagination-custom-text text-center">
                  <div class="fw-bold text-dark fs-6" style="line-height: 1.2;">Pág. {{ pagination.current_page }} de {{ pagination.last_page }}</div>
                  <small class="text-secondary" style="font-size: 0.78rem;">({{ pagination.total }} registros)</small>
                </div>
                <button class="pagination-custom-btn next-btn" :disabled="pagination.current_page >= pagination.last_page" @click="changePage(pagination.current_page + 1)">
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
import api from '../services/api.js';
import AppLayout from '../components/AppLayout.vue';
import PullToRefresh from '../components/PullToRefresh.vue';

export default {
  name: 'SabanaExcelView',
  components: { AppLayout, PullToRefresh },
  data() {
    return {
      loading: true,
      refreshing: false,
      isOnline: true,
      showFilters: false,
      downloading: false,
      kpis: {
        total_inspecciones: 0,
        total_probados: 0,
        total_negativos: 0,
        total_reactores: 0,
      },
      inspecciones: [],
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0,
        from: 0,
        to: 0,
      },
      filterOptions: {
        zonas: [],
        tipos_actividad: [],
        medicos: [],
      },
      filterParams: {
        zona: '',
        tipo_actividad: '',
        medico_id: '',
      },
      isAdmin: false,
    };
  },
  computed: {
    hasActiveFilters() {
      return this.filterParams.zona !== '' || this.filterParams.tipo_actividad !== '' || this.filterParams.medico_id !== '';
    },
  },
  async mounted() {
    const user = api.getCurrentUser();
    this.isAdmin = user?.roles && user.roles.includes('Administrador');
    this.isOnline = navigator.onLine;
    if (this.isOnline) {
      await this.fetchData(true);
    } else {
      this.loading = false;
    }
  },
  methods: {
    async fetchData(resetPage = false) {
      if (resetPage) {
        this.pagination.current_page = 1;
      }
      this.loading = true;
      this.errorMsg = '';
      try {
        const params = { ...this.filterParams, page: this.pagination.current_page };
        Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
        const data = await api.getSabanaData(params);
        this.kpis = data.kpis;
        this.inspecciones = data.inspecciones;
        this.pagination = data.pagination;
        this.filterOptions = data.filter_options;
        this.isAdmin = data.is_admin;
      } catch (e) {
        this.errorMsg = e.message || 'Error al cargar datos';
      } finally {
        this.loading = false;
        this.refreshing = false;
      }
    },
    async onRefresh() {
      this.refreshing = true;
      try {
        await this.fetchData(true);
      } catch (e) {
        console.warn('Refresh error:', e);
      } finally {
        this.refreshing = false;
      }
    },
    async changePage(page) {
      this.pagination.current_page = page;
      await this.fetchData(false);
    },
    async downloadExcel() {
      this.downloading = true;
      try {
        const params = {};
        if (this.filterParams.zona) params.zona = this.filterParams.zona;
        if (this.filterParams.tipo_actividad) params.tipo_actividad = this.filterParams.tipo_actividad;
        if (this.filterParams.medico_id) params.medico_id = this.filterParams.medico_id;
        const blob = await api.getSábanaExcel(params);
        const fileName = `sabana_dictamenes_${new Date().toISOString().slice(0, 10)}.xlsx`;

        if (window.Capacitor && window.Capacitor.isNativePlatform()) {
          const { Filesystem, Directory } = await import('@capacitor/filesystem');
          const { LocalNotifications } = await import('@capacitor/local-notifications');
          const reader = new FileReader();
          reader.readAsDataURL(blob);
          reader.onloadend = async () => {
            const base64data = reader.result.split(',')[1];
            try {
              await Filesystem.writeFile({ path: fileName, data: base64data, directory: Directory.Downloads, recursive: true });
            } catch (_) {
              await Filesystem.writeFile({ path: fileName, data: base64data, directory: Directory.Documents, recursive: true });
            }
            try {
              await LocalNotifications.schedule({
                notifications: [{
                  title: 'Descarga Completa',
                  body: `El archivo "${fileName}" se descargó exitosamente.`,
                  id: Math.floor(Math.random() * 1000000),
                  sound: true,
                }]
              });
            } catch (_) { /* ignore */ }
          };
        } else {
          const url = URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = fileName;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          setTimeout(() => URL.revokeObjectURL(url), 15000);
        }
      } catch (e) {
        alert('Error al descargar: ' + (e.message || 'desconocido'));
      } finally {
        this.downloading = false;
      }
    },
    clearFilters() {
      this.filterParams = { zona: '', tipo_actividad: '', medico_id: '' };
      this.fetchData(true);
    },
    checkConnection() {
      this.isOnline = navigator.onLine;
      if (this.isOnline) this.fetchData(true);
    },
  },
};
</script>

<style scoped>
.stat-card-custom {
  padding: 0 !important;
  margin-bottom: 0 !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card-custom:active {
  transform: scale(0.97) !important;
}
.animate-icon {
  transition: transform 0.3s ease;
}
.stat-card-custom:hover .animate-icon {
  transform: scale(1.1);
}
.stat-card-custom:active,
.card:active {
  transform: none !important;
  box-shadow: none !important;
}

.table th, .table td {
  padding: 0.6rem 0.5rem;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.85rem;
}
.table-light {
  background-color: #f8fafc;
  color: #1e293b;
}
.table-hover tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.02);
}
.bg-primary-subtle { background-color: #eff6ff !important; }
.bg-info-subtle { background-color: #ecfeff !important; }
.bg-success-subtle { background-color: #f0fdf4 !important; }
.bg-danger-subtle { background-color: #fef2f2 !important; }

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
</style>
