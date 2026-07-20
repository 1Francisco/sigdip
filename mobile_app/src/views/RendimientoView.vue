<template>
  <AppLayout>
    <div class="welcome-header mb-4 text-start d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <h2 class="h4 fw-bold mb-1">Rendimiento de Médicos</h2>
        <p class="text-secondary small mb-0">Reportes de actividad y productividad en campo</p>
      </div>

    </div>

    <!-- Alerta Offline -->
    <div v-if="!isOnline" class="card border-0 shadow-sm p-4 rounded-4 mb-4 text-center bg-white border-start border-danger border-4">
      <div class="d-flex flex-column align-items-center gap-3">
        <div class="bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
          <i class="bi bi-cloud-slash-fill fs-2"></i>
        </div>
        <div>
          <h5 class="fw-bold mb-1 text-danger">Módulo Offline</h5>
          <p class="text-secondary small mb-0">
            La sección de rendimiento requiere conexión a Internet para consolidar las estadísticas en tiempo real desde el servidor central.
          </p>
        </div>
        <button class="btn btn-outline-danger btn-sm rounded-pill px-4 mt-1" @click="checkConnection">
          <i class="bi bi-arrow-repeat me-1"></i> Reintentar Conexión
        </button>
      </div>
    </div>

    <!-- Contenido Principal (Online) -->
    <div v-else>
      <div v-if="loading" class="text-center py-5 text-muted" data-cy="loading">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <p class="small mb-0">Generando reportes de rendimiento...</p>
      </div>

      <div v-else>

        <!-- Botón Filtros / Limpiar -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <button class="btn btn-sm rounded-pill px-3 shadow-sm btn-filter-toggle" :class="showFilters ? 'btn-primary' : 'btn-outline-primary'" @click="showFilters = !showFilters">
            <i class="bi bi-funnel-fill me-1"></i> {{ showFilters ? 'Ocultar Filtros' : 'Mostrar Filtros' }}
          </button>
          <button v-if="hasActiveFilters" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm btn-clear-filters" @click="clearFilters">
            <i class="bi bi-x-lg me-1"></i> Limpiar Filtros
          </button>
        </div>

        <!-- Filtros Panel -->
        <div v-if="showFilters" class="card border-0 shadow-sm mb-4 bg-light-page p-3 border-slate-100 rounded-4 text-start panel-filters">
          <div class="row g-2">
            <div class="col-6 col-md-2">
              <label class="form-label small fw-semibold text-secondary mb-1">Año</label>
              <select v-model="filterParams.year" class="form-select form-select-sm select-year">
                <option value="">Todos</option>
                <option v-for="y in filterOptions.years" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label small fw-semibold text-secondary mb-1">Estado</label>
              <select v-model="filterParams.estado" class="form-select form-select-sm select-estado">
                <option value="">Todos</option>
                <option value="borrador">Borrador</option>
                <option value="sincronizado">Sincronizado</option>
              </select>
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label small fw-semibold text-secondary mb-1">Zona</label>
              <select v-model="filterParams.zona" class="form-select form-select-sm select-zona">
                <option value="">Todas</option>
                <option value="A">Zona A</option>
                <option value="B">Zona B</option>
              </select>
            </div>
            <div v-if="currentTab === 'medicos'" class="col-6 col-md-3">
              <label class="form-label small fw-semibold text-secondary mb-1">Localidad</label>
              <select v-model="filterParams.localidad" class="form-select form-select-sm select-localidad">
                <option value="">Todas</option>
                <option v-for="l in filterOptions.localidades" :key="l" :value="l">{{ l }}</option>
              </select>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label small fw-semibold text-secondary mb-1">Médico</label>
              <select v-model="filterParams.medico_id" class="form-select form-select-sm select-medico">
                <option value="">Todos</option>
                <option v-for="m in filterOptions.medicos" :key="m.id" :value="m.id">{{ m.name }}</option>
              </select>
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end justify-content-end gap-2 mt-2 mt-md-0">
              <button class="btn btn-sm btn-primary rounded-pill px-4 flex-grow-1 flex-md-grow-0 btn-apply-filters" @click="fetchData">
                <i class="bi bi-funnel"></i> Aplicar Filtros
              </button>
            </div>
          </div>
        </div>

        <!-- Pestañas (Tabs) en modo scroll horizontal -->
        <div class="tab-scroller mb-3">
          <ul class="nav nav-pills nav-pills-premium-mobile flex-nowrap" style="overflow-x: auto; white-space: nowrap; padding-bottom: 5px;">
            <li class="nav-item">
              <a class="nav-link tab-medicos" :class="{ active: currentTab === 'medicos' }" @click="currentTab = 'medicos'">
                <i class="bi bi-person-badge"></i> Médicos
              </a>
            </li>


            <li class="nav-item">
              <a class="nav-link tab-mensual" :class="{ active: currentTab === 'mensual' }" @click="currentTab = 'mensual'">
                <i class="bi bi-table"></i> Detalle Mensual
              </a>
            </li>
          </ul>
        </div>

        <!-- Contenido de las pestañas -->
        <div class="tab-content">
          <!-- 1. TABS: MÉDICOS -->
          <div v-if="currentTab === 'medicos'" class="pane-medicos">


            <!-- Tabla responsiva de Médicos -->
             <div class="card border-0 shadow-sm rounded-4 mb-4">
               <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                 <span><i class="bi bi-table text-primary me-2"></i> Detalle de Rendimiento</span>
                 <span class="badge bg-primary-soft text-primary rounded-pill font-mono">{{ medicosRendimiento.length }} médicos</span>
               </div>
               <div class="card-body p-0 text-start">
                 <div v-if="medicosRendimiento.length === 0" class="text-center p-5 text-muted">
                   No hay datos registrados de médicos
                 </div>
                 <div v-else class="table-responsive">
                   <table class="table table-hover align-middle mb-0 table-medicos-rendimiento">
                     <thead class="table-light">
                       <tr>
                         <th class="ps-4">Médico</th>
                         <th class="text-center">Inspecciones</th>
                         <th class="text-center">Visitas</th>
                         <th class="text-center">Completadas</th>
                         <th class="text-center">Predios</th>
                         <th class="text-center">Animales</th>
                         <th class="text-center">Anim/Insp</th>
                         <th class="text-center">% Reactores</th>
                         <th class="text-center">% Finalización</th>
                         <th class="text-center">Eficiencia</th>
                         <th>Última actividad</th>
                         <th class="text-center">Detalle</th>
                       </tr>
                     </thead>
                     <tbody>
                       <tr v-for="m in medicosRendimiento" :key="m.id" class="item-medico-row">
                         <td class="ps-4 fw-semibold row-medico-name">{{ m.name }}</td>
                         <td class="text-center">
                           <span class="badge bg-primary rounded-pill row-medico-inspecciones">{{ m.total_inspecciones }}</span>
                         </td>
                         <td class="text-center">{{ m.total_visitas }}</td>
                         <td class="text-center">
                           <span v-if="m.total_visitas > 0" class="badge bg-success rounded-pill">{{ m.visitas_completadas }}</span>
                           <span v-else class="text-muted">—</span>
                         </td>
                         <td class="text-center">{{ m.predios_atendidos }}</td>
                         <td class="text-center">{{ m.total_animales }}</td>
                         <td class="text-center">{{ m.promedio_animales }}</td>
                         <td class="text-center">
                           <span v-if="m.tasa_reactores > 0" class="badge bg-danger rounded-pill">{{ m.tasa_reactores }}%</span>
                           <span v-else class="text-muted">0%</span>
                         </td>
                         <td class="text-center">
                           <div v-if="m.total_visitas > 0" class="d-flex align-items-center justify-content-center gap-1">
                             <div class="progress" style="height: 6px; width: 60px; background-color: #e2e8f0; border-radius: 3px; overflow: hidden;">
                               <div class="progress-bar bg-success" :style="{ width: m.tasa_finalizacion + '%' }"></div>
                             </div>
                             <small class="font-mono">{{ m.tasa_finalizacion }}%</small>
                           </div>
                           <span v-else class="text-muted">—</span>
                         </td>
                         <td class="text-center">
                           <div class="d-flex align-items-center justify-content-center gap-1">
                             <div class="progress" style="height: 8px; width: 60px; background-color: #e2e8f0; border-radius: 4px; overflow: hidden;">
                               <div class="progress-bar" :class="getScoreColorClass(m.eficiencia_score)" :style="{ width: Math.min(m.eficiencia_score, 100) + '%' }"></div>
                             </div>
                             <small class="fw-bold font-mono" :class="getScoreTextColorClass(m.eficiencia_score)">{{ m.eficiencia_score }}</small>
                           </div>
                         </td>
                         <td>
                           <small v-if="m.ultima_inspeccion" class="text-muted font-mono">{{ formatDate(m.ultima_inspeccion) }}</small>
                           <small v-else class="text-muted fst-italic">Sin actividad</small>
                         </td>
                         <td class="text-center">
                           <button class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-select-medico" :class="{ active: filterParams.medico_id == m.id }" @click="selectMedicoRow(m.id)">
                             <i class="bi bi-eye"></i>
                           </button>
                         </td>
                       </tr>
                     </tbody>
                   </table>
                 </div>
               </div>
             </div>

             <!-- Detalle por médico seleccionado -->
             <div v-if="filterParams.medico_id && detalleMedico" class="card border-0 shadow-sm rounded-4 mb-4 select-medico-details">
               <div class="card-header bg-white fw-bold py-3 text-start border-bottom">
                 <i class="bi bi-list-ul me-2 text-primary"></i> Últimas inspecciones
                 <span class="text-secondary fw-normal"> — {{ getSelectedMedicoName() }}</span>
               </div>
               <div class="card-body p-0 text-start">
                 <div v-if="detalleMedico.length === 0" class="text-center p-5 text-muted">
                   No hay inspecciones registradas para este médico.
                 </div>
                 <div v-else class="table-responsive">
                   <table class="table table-hover align-middle mb-0 table-medico-inspecciones">
                     <thead class="table-light">
                       <tr>
                         <th>Fecha</th>
                         <th>Folio</th>
                         <th>Productor</th>
                         <th>Predio</th>
                         <th>Tipo Prueba</th>
                         <th>Estado</th>
                       </tr>
                     </thead>
                     <tbody>
                       <tr v-for="ins in detalleMedico" :key="ins.id">
                         <td>{{ formatDate(ins.fecha) }}</td>
                         <td>
                           <span v-if="!ins.folio || ins.folio === ins.clave_interna" class="text-muted fst-italic">{{ ins.clave_interna || '—' }}</span>
                           <span v-else>{{ ins.folio }}</span>
                         </td>
                         <td>{{ ins.productor_nombre }}</td>
                         <td>{{ ins.predio_nombre }}</td>
                         <td>{{ ins.tipo_prueba }}</td>
                         <td>
                           <span class="badge" :class="ins.estado === 'sincronizado' ? 'bg-success text-white' : 'bg-warning text-dark'">
                             {{ capitalizeFirst(ins.estado) }}
                           </span>
                         </td>
                       </tr>
                     </tbody>
                   </table>
                 </div>
               </div>
             </div>
          </div>





          <!-- 6. TABS: DETALLE MENSUAL -->
          <div v-if="currentTab === 'mensual'" class="pane-mensual">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
              <div class="card-header bg-white fw-bold py-3">
                <span><i class="bi bi-calendar-month text-primary me-2"></i> Detalle Mensual ({{ selectedYear }})</span>
              </div>
              <div class="card-body p-0 text-start">
                <div v-if="mensualRows.length === 0" class="text-center p-5 text-muted">
                  No hay registros de rendimiento mensual para el año {{ selectedYear }}
                </div>
                <div v-else class="table-responsive">
                  <table class="table table-hover align-middle mb-0 table-mensual">
                    <thead class="table-light">
                      <tr>
                        <th class="ps-4 col-medico">Médico</th>
                        <th class="col-mes">Mes</th>
                        <th class="text-center col-num">Inspecciones</th>
                        <th class="text-center col-num">PPC</th>
                        <th class="text-center col-num">PCC</th>
                        <th class="text-center col-num">Predios</th>
                        <th class="text-center col-num">Visitas</th>
                        <th class="text-center col-num">Animales</th>
                        <th class="text-center col-num">Reactores</th>
                        <th class="text-center col-num">React. PPC</th>
                        <th class="text-center col-num">React. PCC</th>
                        <th class="text-center pe-4 col-download">Descargar</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in mensualRows" :key="row.veterinario_id + '|' + row.mes" class="item-mensual-row">
                        <td class="ps-4 fw-semibold row-medico-nombre">{{ row.medico_nombre }}</td>
                        <td class="row-mes-nombre">{{ capitalizeFirst(formatMonthNameOnly(row.mes)) }}</td>
                        <td class="text-center">
                          <span class="badge bg-primary rounded-pill row-total-inspecciones">{{ row.total_inspecciones }}</span>
                        </td>
                        <td class="text-center">{{ row.ppc }}</td>
                        <td class="text-center">{{ row.pcc }}</td>
                        <td class="text-center">{{ row.predios }}</td>
                        <td class="text-center">{{ row.total_visitas }}</td>
                        <td class="text-center">{{ row.total_animales }}</td>
                        <td class="text-center">
                          <span v-if="row.total_reactores > 0" class="badge bg-danger rounded-pill">{{ row.total_reactores }}</span>
                          <span v-else class="text-muted">0</span>
                        </td>
                        <td class="text-center">
                          <span v-if="row.reactores_ppc > 0" class="badge bg-danger rounded-pill">{{ row.reactores_ppc }}</span>
                          <span v-else class="text-muted">0</span>
                        </td>
                        <td class="text-center">
                          <span v-if="row.reactores_pcc > 0" class="badge bg-danger rounded-pill">{{ row.reactores_pcc }}</span>
                          <span v-else class="text-muted">0</span>
                        </td>
                        <td class="text-center pe-4 col-download">
                          <button class="btn btn-sm px-1 py-0 text-danger" title="Descargar PDF este mes" @click="downloadRowFile(row, 'pdf')">
                            <i class="bi bi-file-earmark-pdf"></i>
                          </button>
                        </td>
                      </tr>
                      <!-- Fila de Totales Generales -->
                      <tr class="table-primary fw-bold row-total-general">
                        <td class="ps-4">Total General</td>
                        <td class="text-muted fst-italic">——</td>
                        <td class="text-center">{{ totalInspeccionesMensual }}</td>
                        <td class="text-center">{{ totalPPCMensual }}</td>
                        <td class="text-center">{{ totalPCCMensual }}</td>
                        <td class="text-center">{{ totalPrediosMensual }}</td>
                        <td class="text-center">{{ totalVisitasMensual }}</td>
                        <td class="text-center">{{ totalAnimalesMensual }}</td>
                        <td class="text-center">
                          <span v-if="totalReactoresMensual > 0" class="badge bg-danger rounded-pill">{{ totalReactoresMensual }}</span>
                          <span v-else>0</span>
                        </td>
                        <td class="text-center">
                          <span v-if="totalReactoresPPCMensual > 0" class="badge bg-danger rounded-pill">{{ totalReactoresPPCMensual }}</span>
                          <span v-else>0</span>
                        </td>
                        <td class="text-center">
                          <span v-if="totalReactoresPCCMensual > 0" class="badge bg-danger rounded-pill">{{ totalReactoresPCCMensual }}</span>
                          <span v-else>0</span>
                        </td>
                        <td class="text-center pe-4"></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '../components/AppLayout.vue';
import api from '../services/api.js';
import { CONFIG } from '../config.js';
import { Filesystem, Directory } from '@capacitor/filesystem';
import { Share } from '@capacitor/share';
import { LocalNotifications } from '@capacitor/local-notifications';
import { Dialog } from '@capacitor/dialog';
import { FileOpener } from '@capacitor-community/file-opener';

export default {
  name: 'RendimientoView',
  components: {
    AppLayout
  },
  data() {
    return {
      loading: true,
      isOnline: true,
      showFilters: false,
      currentTab: 'medicos',
      kpis: {
        total_inspecciones: 0,
        total_visitas: 0,
        medicos_activos: 0,
        total_animales: 0,
        total_reactores: 0
      },
      medicosRendimiento: [],
      actividades: [],
      nombresPruebas: {},
      zonas: [],
      cuarentenasD: [],
      cuarentenasP: [],
      totalSinCuarentena: 0,
      meses: [],
      mensualRows: [],
      selectedYear: null,
      detalleMedico: null,
      filterOptions: {
        years: [],
        localidades: [],
        medicos: []
      },
      filterParams: {
        year: '',
        estado: '',
        zona: '',
        localidad: '',
        medico_id: ''
      }
    };
  },
  computed: {
    hasActiveFilters() {
      return Object.values(this.filterParams).some(val => val !== '');
    },
    totalInspeccionesMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.total_inspecciones || 0), 0);
    },
    totalPPCMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.ppc || 0), 0);
    },
    totalPCCMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.pcc || 0), 0);
    },
    totalPrediosMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.predios || 0), 0);
    },
    totalVisitasMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.total_visitas || 0), 0);
    },
    totalAnimalesMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.total_animales || 0), 0);
    },
    totalReactoresMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.total_reactores || 0), 0);
    },
    totalReactoresPPCMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.reactores_ppc || 0), 0);
    },
    totalReactoresPCCMensual() {
      return this.mensualRows.reduce((sum, r) => sum + (r.reactores_pcc || 0), 0);
    }
  },
  created() {
    this.isOnline = navigator.onLine;
  },
  mounted() {
    if (this.isOnline) {
      this.fetchData();
    }
  },
  methods: {
    checkConnection() {
      this.isOnline = navigator.onLine;
      if (this.isOnline) {
        this.fetchData();
      }
    },
    async fetchData() {
      this.loading = true;
      try {
        const response = await api.getRendimiento(this.filterParams);
        if (response && response.success) {
          this.kpis = response.kpis;
          this.medicosRendimiento = response.medicosRendimiento;
          this.actividades = response.actividades;
          this.nombresPruebas = response.nombresPruebas;
          this.zonas = response.zonas;
          this.cuarentenasD = response.cuarentenasD;
          this.cuarentenasP = response.cuarentenasP;
          this.totalSinCuarentena = response.totalSinCuarentena;
          this.meses = response.meses;
          this.mensualRows = response.mensualRows;
          this.selectedYear = response.selectedYear;
          this.detalleMedico = response.detalleMedico;

          // Cargar filtros solo una vez para evitar sobrescribir selecciones
          if (this.filterOptions.years.length === 0) {
            this.filterOptions.years = response.filters.years;
            this.filterOptions.localidades = response.filters.localidades;
            this.filterOptions.medicos = response.filters.medicos;
          }
        }
      } catch (e) {
        console.error('Error fetching rendimiento data:', e);
      } finally {
        this.loading = false;
      }
    },
    clearFilters() {
      this.filterParams = {
        year: '',
        estado: '',
        zona: '',
        localidad: '',
        medico_id: ''
      };
      this.detalleMedico = null;
      this.fetchData();
    },
    formatMonthName(mesStr) {
      if (!mesStr) return '';
      const parts = mesStr.split('-');
      if (parts.length < 2) return mesStr;
      const [year, month] = parts;
      const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
      const mIndex = parseInt(month, 10) - 1;
      return `${months[mIndex]} ${year}`;
    },
    formatDate(dateStr) {
      if (!dateStr) return '';
      const date = new Date(dateStr);
      if (isNaN(date.getTime())) return dateStr;
      return date.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    },
    getScoreColorClass(score) {
      if (score >= 70) return 'bg-success';
      if (score >= 40) return 'bg-warning';
      return 'bg-danger';
    },
    getScoreTextColorClass(score) {
      if (score >= 70) return 'text-success';
      if (score >= 40) return 'text-warning';
      return 'text-danger';
    },
    formatMonthNameOnly(mesStr) {
      if (!mesStr) return '';
      const parts = mesStr.split('-');
      if (parts.length < 2) return mesStr;
      const month = parseInt(parts[1], 10);
      const months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
      return months[month - 1];
    },
    capitalizeFirst(str) {
      if (!str) return '';
      return str.charAt(0).toUpperCase() + str.slice(1);
    },
    async downloadBlob(url, filename) {
      try {
        const token = localStorage.getItem('sigdip_token');
        const response = await fetch(url, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        if (!response.ok) {
          throw new Error('Fallo en la descarga');
        }
        const blob = await response.blob();

        if (window.Capacitor && window.Capacitor.isNativePlatform()) {
          const reader = new FileReader();
          reader.readAsDataURL(blob);
          reader.onloadend = async () => {
            try {
              const base64data = reader.result.split(',')[1];
              // Guardar en Documentos del dispositivo
              const docResult = await Filesystem.writeFile({
                path: filename,
                data: base64data,
                directory: Directory.Documents,
                recursive: true
              });

              // Intentar guardar en carpeta de Descargas (Android)
              let downloadSaved = false;
              try {
                await Filesystem.writeFile({
                  path: filename,
                  data: base64data,
                  directory: Directory.Downloads,
                  recursive: true
                });
                downloadSaved = true;
              } catch (dlErr) {
                console.warn('Could not save to Downloads folder directly:', dlErr);
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
                      body: `El archivo "${filename}" se descargó exitosamente. Toca para abrirlo.`,
                      id: Math.floor(Math.random() * 1000000),
                      sound: true,
                      extra: {
                        uri: docResult.uri,
                        filename: filename
                      }
                    }
                  ]
                });
              } catch (notiErr) {
                console.warn('Error scheduling local notification:', notiErr);
              }

              // Mostrar alerta amigable indicando ubicación
              if (downloadSaved) {
                await Dialog.alert({ title: 'Descarga completada', message: `El archivo "${filename}" se guardó en la carpeta de Descargas de tu teléfono.` });
              } else {
                await Dialog.alert({ title: 'Descarga completada', message: `El archivo "${filename}" se guardó en los Documentos de tu teléfono.` });
              }

              // Preguntar al usuario qué desea hacer
              const result = await Dialog.confirm({
                title: 'Descarga completada',
                message: `"${filename}" guardado.\n\n¿Abrir archivo para leerlo?`,
                okButtonTitle: 'Ver',
                cancelButtonTitle: 'Compartir'
              });
              if (result.value) {
                try {
                  const cacheName = `view_${Date.now()}_${filename}`;
                  await Filesystem.writeFile({ path: cacheName, data: base64data, directory: Directory.Cache });
                  const { uri } = await Filesystem.getUri({ path: cacheName, directory: Directory.Cache });
                  await FileOpener.open({ filePath: uri, contentType: 'application/pdf' });
                } catch (openErr) {
                  console.warn('Error al abrir el archivo:', openErr);
                }
              } else {
                try {
                  await Share.share({
                    title: filename,
                    url: docResult.uri,
                    dialogTitle: `Compartir ${filename}`
                  });
                } catch (shareErr) {
                  console.warn('Error al compartir archivo:', shareErr);
                }
              }
            } catch (writeErr) {
              console.error('Error writing file locally:', writeErr);
              await Dialog.alert({ title: 'Error', message: 'Error al guardar el archivo en el teléfono: ' + writeErr.message });
            }
          };
        } else {
          // Fallback para web tradicional (escritorio y navegador móvil)
          const blobUrl = window.URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = blobUrl;
          link.setAttribute('download', filename);
          document.body.appendChild(link);
          link.click();
          link.remove();
          window.URL.revokeObjectURL(blobUrl);
        }
      } catch (e) {
        console.error('Error downloading report:', e);
        await Dialog.alert({ title: 'Error', message: 'Error al descargar el reporte. Verifique la conexión.' });
      }
    },
    downloadRowFile(row, type) {
      const parts = row.mes.split('-');
      const firstDay = `${parts[0]}-${parts[1]}-01`;
      const year = parseInt(parts[0], 10);
      const month = parseInt(parts[1], 10);
      const lastDayDate = new Date(year, month, 0);
      const lastDay = `${parts[0]}-${parts[1]}-${String(lastDayDate.getDate()).padStart(2, '0')}`;
      
      const filename = `rendimiento_${row.medico_nombre}_${row.mes}.${type === 'pdf' ? 'pdf' : 'xlsx'}`;
      const url = `${CONFIG.API_BASE_URL}/reportes/rendimiento/${type}?medico_id=${row.veterinario_id}&fecha_desde=${firstDay}&fecha_hasta=${lastDay}`;
      
      this.downloadBlob(url, filename);
    },


    getZonaLetra(tipo) {
      if (!tipo) return '';
      return tipo.substring(0, 1);
    },
    getZonaClass(letra) {
      return letra === 'A' ? 'bg-info text-white' : 'bg-warning text-dark';
    },
    getProgressWidth(totalDetalle, totalGrupo) {
      if (!totalGrupo) return 0;
      return Math.round((totalDetalle / totalGrupo) * 100);
    },
    selectMedicoRow(id) {
      if (this.filterParams.medico_id == id) {
        this.filterParams.medico_id = '';
      } else {
        this.filterParams.medico_id = id;
      }
      this.fetchData();
    },
    getSelectedMedicoName() {
      const match = this.filterOptions.medicos.find(m => m.id == this.filterParams.medico_id);
      return match ? match.name : '';
    }
  }
};
</script>

<style scoped>
.tab-scroller {
  margin-left: -1rem;
  margin-right: -1rem;
  padding-left: 1rem;
  padding-right: 1rem;
}
.nav-pills-premium-mobile {
  background: #f1f5f9;
  padding: 4px;
  border-radius: 12px;
  display: flex;
  width: max-content;
}
.nav-pills-premium-mobile .nav-link {
  color: #64748b;
  font-weight: 600;
  padding: 0.5rem 1rem;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.8rem;
  transition: all 0.2s ease;
}
.nav-pills-premium-mobile .nav-link.active {
  color: #2563eb;
  background: white;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
}
.alert {
  border-radius: 16px;
}
.list-group-item {
  border-color: #f1f5f9;
}
.progress {
  box-shadow: none;
}
.table-responsive {
  display: block;
  width: 100%;
  overflow-x: auto !important;
  -webkit-overflow-scrolling: touch;
}
.table {
  width: 100%;
  margin-bottom: 1rem;
  color: #212529;
  vertical-align: top;
  border-collapse: collapse;
}
.table th,
.table td {
  padding: 0.75rem;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}
.table-light {
  background-color: #f8fafc;
  color: #1e293b;
}
.table-primary {
  background-color: #eff6ff !important;
  color: #1e3a8a !important;
}
.table-hover tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.02);
}
.table-mensual {
  table-layout: fixed;
  min-width: 1200px; /* Asegura scroll horizontal en pantallas móviles */
}
.table-mensual th,
.table-mensual td { overflow: hidden; text-overflow: ellipsis; font-size: 0.85rem; }
.table-mensual .col-medico { width: 22%; min-width: 170px; }
.table-mensual .col-mes { width: 14%; min-width: 110px; }
.table-mensual .col-num { width: 10%; }
.table-mensual .col-download { width: 12%; min-width: 100px; white-space: nowrap; }
.table-medicos-rendimiento {
  min-width: 1250px;
}
.table-medico-inspecciones {
  min-width: 800px;
}
.table-tendencia-mensual {
  min-width: 800px;
}
</style>
