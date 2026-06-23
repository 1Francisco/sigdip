<template>
  <AppLayout>
    <PullToRefresh @refresh="onRefresh" :loading="refreshing">
      <!-- Top Action Bar (Premium Web Replica) -->
      <div class="welcome-header mb-4 text-start d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
        <div>
          <h2 class="h4 fw-bold mb-1 text-dark">Agenda de Campo</h2>
          <p class="text-secondary small mb-0">Programe y gestione las visitas a los predios</p>
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

      <!-- Card Container for Visitas Header -->
      <div class="card border-0 shadow-sm p-4 bg-white rounded-4 mb-4 card-outer-mobile-flat text-center text-lg-start">
        <h5 class="fw-bold fs-4 text-dark mb-3 text-center text-lg-start">Visitas Programadas</h5>
        
        <div class="d-flex flex-column gap-3 w-100 mb-4">
          <button class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 py-2-5 bg-primary text-white border-0 rounded-3" @click="openCreate">
            <i class="bi bi-calendar-plus"></i> Programar Visita
          </button>
        </div>

        <div class="w-100 text-start">
          <div class="small fw-semibold text-secondary mb-2 d-flex align-items-center gap-1">
            <i class="bi bi-funnel"></i> Filtrar por Fecha
          </div>
          <div class="d-flex flex-column flex-lg-row gap-2">
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
            <div class="table-responsive d-none d-lg-block">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr>
                    <th class="ps-4 text-secondary fw-bold text-uppercase fs-7 tracking-wider">Fecha Programada</th>
                    <th class="text-secondary fw-bold text-uppercase fs-7 tracking-wider">Productor / Predio</th>
                    <th class="text-secondary fw-bold text-uppercase fs-7 tracking-wider">Médico Veterinario</th>
                    <th class="text-center text-secondary fw-bold text-uppercase fs-7 tracking-wider">Inyección</th>
                    <th class="text-center text-secondary fw-bold text-uppercase fs-7 tracking-wider">Lectura</th>
                    <th class="text-secondary fw-bold text-uppercase fs-7 tracking-wider">Estado Visita</th>
                    <th class="text-center text-secondary fw-bold text-uppercase fs-7 tracking-wider">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="visita in paginatedVisitas" :key="visita.id" class="border-bottom">
                    <td class="ps-4">
                      <div class="fw-bold text-dark fs-6">{{ formatDate(visita.fecha_programada) }}</div>
                      <small class="text-secondary small fst-italic">{{ getRelativeTime(visita.fecha_programada) }}</small>
                      <div v-if="visita.codigo" class="d-flex align-items-center gap-1 flex-wrap" style="margin-top: 2px;">
                        <span class="text-primary fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.3px;">{{ visita.codigo }}</span>
                        <span v-if="visita._syncStatus === 'pendiente'"
                          class="badge bg-warning text-dark fw-bold"
                          style="font-size: 0.6rem; padding: 0 5px; border-radius: 4px;">
                          Pendiente
                        </span>
                      </div>
                    </td>
                    <td>
                      <div class="fw-bold text-dark">{{ visita.predio?.productor?.nombre || 'Sin productor' }} {{ visita.predio?.productor?.apellido_paterno }}</div>
                      <small class="text-secondary fs-7">{{ visita.predio?.nombre_rancho }} ({{ visita.predio?.localidad }})</small>
                    </td>
                    <td class="text-secondary">{{ visita.veterinario?.name || 'Administrador Central' }}</td>
                    <!-- Inyección -->
                    <td class="text-center">
                      <span class="badge rounded-pill px-2-5 py-1-5 fw-semibold" :class="visita.inyeccion ? 'bg-success text-white' : 'bg-danger text-white'">
                        <i class="bi" :class="visita.inyeccion ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
                        {{ visita.inyeccion ? 'Realizada' : 'Pendiente' }}
                      </span>
                      <div v-if="visita.inyeccion && visita.inspeccion?.fecha_inyeccion" class="small text-secondary mt-1" style="font-size: 0.75rem;">
                        {{ formatDateTime(visita.inspeccion.fecha_inyeccion, visita.inspeccion.hora_inyeccion) }}
                      </div>
                    </td>
                    <!-- Lectura -->
                    <td class="text-center">
                      <span class="badge rounded-pill px-2-5 py-1-5 fw-semibold" :class="(visita.inspeccion?.id && visita.inspeccion?.estado !== 'borrador') ? 'bg-success text-white' : 'bg-danger text-white'">
                        <i class="bi" :class="(visita.inspeccion?.id && visita.inspeccion?.estado !== 'borrador') ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
                        {{ (visita.inspeccion?.id && visita.inspeccion?.estado !== 'borrador') ? 'Realizada' : 'Pendiente' }}
                      </span>
                      <div v-if="visita.inspeccion?.id && visita.inspeccion?.estado !== 'borrador' && visita.inspeccion?.fecha_lectura" class="small text-secondary mt-1" style="font-size: 0.75rem;">
                        {{ formatDateTime(visita.inspeccion.fecha_lectura, visita.inspeccion.hora_lectura) }}
                      </div>
                      <div v-else-if="!(visita.inspeccion?.id && visita.inspeccion?.estado !== 'borrador')" class="small text-muted mt-1" style="font-size: 0.75rem; font-weight: 500;">
                        <template v-if="visita.inyeccion && visita.inspeccion?.fecha_inyeccion">
                          Estimada: {{ estimatedLecturaDate(visita.inspeccion.fecha_inyeccion) }}
                        </template>
                        <template v-else-if="visita.fecha_programada">
                          Estimada: {{ estimatedLecturaDate(visita.fecha_programada) }}
                        </template>
                      </div>
                    </td>
                    <!-- Estado Visita -->
                    <td>
                      <span class="badge rounded-pill px-2-5 py-1-5 fw-semibold text-capitalize" :class="badgeClass(visita.estado)">
                        {{ visita.estado || 'pendiente' }}
                      </span>
                    </td>
                    <td>
                      <div class="d-flex justify-content-center gap-2">
                        <template v-if="visita._syncStatus === 'pendiente'">
                          <button
                            @click="syncPendingVisita(visita)"
                            class="btn btn-sm btn-warning d-flex align-items-center justify-content-center gap-1.5 px-3 py-1-5 fw-semibold text-dark"
                            :disabled="syncingVisita === visita.codigo"
                          >
                            <i class="bi bi-cloud-arrow-up"></i>
                            {{ syncingVisita === visita.codigo ? '...' : 'Sync' }}
                          </button>
                          <button
                            @click="eliminarPendiente(visita)"
                            class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center bg-transparent border-danger text-danger"
                            title="Eliminar localmente"
                            style="width: 32px; height: 32px;"
                          >
                            <i class="bi bi-trash"></i>
                          </button>
                        </template>
                        <template v-else>
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
                            @click="$router.push('/visitas/' + visita.id)" 
                            class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center bg-transparent border-secondary text-secondary" 
                            title="Ver Detalle"
                            style="width: 32px; height: 32px;"
                          >
                            <i class="bi bi-eye"></i>
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
                        </template>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- 2. MOBILE VIEW: Floating cards with light gray box details matching screens exactly -->
            <div class="d-block d-lg-none px-3 py-2">
              <div class="mobile-cards-grid">
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
                    <span v-if="visita.codigo" class="text-primary fw-semibold fs-7 mt-0.5 d-flex align-items-center gap-1 flex-wrap">
                      {{ visita.codigo }}
                      <span v-if="visita._syncStatus === 'pendiente'"
                        class="badge bg-warning text-dark fw-bold"
                        style="font-size: 0.6rem; padding: 0 5px; border-radius: 4px;">
                        Pendiente
                      </span>
                    </span>
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

                  <!-- Estado Visita Field -->
                  <div class="card-field">
                    <span class="field-label">ESTADO VISITA</span>
                    <div class="badge rounded-3 py-2 px-3 fs-7.5 w-100 d-flex align-items-center justify-content-center gap-1.5 fw-bold text-capitalize" :class="badgeClass(visita.estado)">
                      {{ visita.estado || 'pendiente' }}
                    </div>
                  </div>

                  <!-- Inyeccion Status Field -->
                  <div class="card-field">
                    <span class="field-label">INYECCIÓN</span>
                    <div class="badge rounded-3 py-2 px-3 fs-7.5 w-100 d-flex align-items-center justify-content-center gap-1.5" :class="visita.inyeccion ? 'bg-success text-white' : 'bg-danger text-white'">
                      <i class="bi" :class="visita.inyeccion ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
                      {{ visita.inyeccion ? 'Realizada' : 'Pendiente' }}
                    </div>
                    <span v-if="visita.inyeccion && visita.inspeccion?.fecha_inyeccion" class="field-subtitle text-center text-secondary fs-7.5 mt-1.5">
                      {{ formatDateTime(visita.inspeccion.fecha_inyeccion, visita.inspeccion.hora_inyeccion) }}
                    </span>
                  </div>

                  <!-- Lectura Status Field -->
                  <div class="card-field">
                    <span class="field-label">LECTURA</span>
                    <div class="badge rounded-3 py-2 px-3 fs-7.5 w-100 d-flex align-items-center justify-content-center gap-1.5" :class="(visita.inspeccion?.id && visita.inspeccion?.estado !== 'borrador') ? 'bg-success text-white' : 'bg-danger text-white'">
                      <i class="bi" :class="(visita.inspeccion?.id && visita.inspeccion?.estado !== 'borrador') ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
                      {{ (visita.inspeccion?.id && visita.inspeccion?.estado !== 'borrador') ? 'Realizada' : 'Pendiente' }}
                    </div>
                    <span v-if="visita.inspeccion?.id && visita.inspeccion?.estado !== 'borrador' && visita.inspeccion?.fecha_lectura" class="field-subtitle text-center text-secondary fs-7.5 mt-1.5">
                      {{ formatDateTime(visita.inspeccion.fecha_lectura, visita.inspeccion.hora_lectura) }}
                    </span>
                    <!-- Estimated lectura date when pending -->
                    <span v-else-if="!(visita.inspeccion?.id && visita.inspeccion?.estado !== 'borrador')" class="field-subtitle text-center text-muted fs-7.5 mt-1" style="font-weight: 500;">
                      <template v-if="visita.inyeccion && visita.inspeccion?.fecha_inyeccion">
                        Estimada: {{ estimatedLecturaDate(visita.inspeccion.fecha_inyeccion) }}
                      </template>
                      <template v-else-if="visita.fecha_programada">
                        Estimada: {{ estimatedLecturaDate(visita.fecha_programada) }}
                      </template>
                    </span>
                  </div>
                </div>

                <!-- Actions Footer -->
                <div class="producer-mobile-footer d-flex align-items-center justify-content-between p-3" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
                  <div class="footer-actions-label fw-bold text-secondary mb-0">ACCIONES</div>
                  <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <template v-if="visita._syncStatus === 'pendiente'">
                      <button
                        @click="syncPendingVisita(visita)"
                        class="btn btn-sm btn-warning d-flex align-items-center justify-content-center gap-1.5 px-3 py-2 fw-semibold text-dark"
                        style="border-radius: 10px; font-size: 0.82rem; height: 40px;"
                        :disabled="syncingVisita === visita.codigo"
                      >
                        <i class="bi bi-cloud-arrow-up"></i>
                        {{ syncingVisita === visita.codigo ? 'Sincronizando...' : 'Sincronizar' }}
                      </button>
                      <button
                        @click="eliminarPendiente(visita)"
                        class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center bg-transparent border-danger text-danger"
                        title="Eliminar localmente"
                        style="width: 40px; height: 40px; border-radius: 10px;"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </template>
                    <template v-else>
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
                        @click="$router.push('/visitas/' + visita.id)" 
                        class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center bg-transparent border-secondary text-secondary" 
                        title="Ver Detalle"
                        style="width: 40px; height: 40px; border-radius: 10px;"
                      >
                        <i class="bi bi-eye"></i>
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
                    </template>
                  </div>
                </div>
              </div>
              </div>
            </div>

            <!-- PAGINATION FOOTER: Custom chevron styles matching mockup -->
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
          <label class="form-label-custom">Médico Veterinario *</label>
          <select v-model="form.veterinario_id" class="form-control-custom" required>
            <option value="">Selecciona un médico</option>
            <option v-for="medico in medicos" :key="medico.id" :value="medico.id">
              {{ medico.name }} {{ medico.email ? `(${medico.email})` : '' }}
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
</template>

<script>
import AppLayout from '../components/AppLayout.vue';
import PullToRefresh from '../components/PullToRefresh.vue';
import api from '../services/api.js';
import db from '../services/db.js';

export default {
  name: 'VisitasView',
  components: { AppLayout, PullToRefresh },
  data() {
    return {
      userName: '',
      isAdmin: false,
      isOnline: true,
      loading: false,
      refreshing: false,
      saving: false,
      visitas: [],
      visitasPendientes: [],
      predios: [],
      medicos: [],
      filters: {
        fecha: ''
      },
      showModal: false,
      formMode: 'create',
      currentId: null,
      form: {
        predio_id: '',
        fecha_programada: '',
        observaciones: '',
        veterinario_id: ''
      },
      currentPage: 1,
      syncingVisita: null,
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

    this._onWindowOnline = () => this.isOnline = true;
    this._onWindowOffline = () => this.isOnline = false;
    window.addEventListener('online', this._onWindowOnline);
    window.addEventListener('offline', this._onWindowOffline);

    window.addEventListener('sigdip-sync-complete', this.refreshOnSync);
  },
  beforeUnmount() {
    window.removeEventListener('online', this._onWindowOnline);
    window.removeEventListener('offline', this._onWindowOffline);
    window.removeEventListener('sigdip-sync-complete', this.refreshOnSync);
  },
  methods: {
    async loadAll() {
      this.errorMsg = '';
      await this.loadPredios();
      await this.loadMedicos();
      await Promise.all([this.loadVisitas(), this.loadVisitasPendientes()]);
      this.mergePendingVisitas();
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

    mergePendingVisitas() {
      if (!this.visitasPendientes.length) return;
      const serverCodigos = new Set(
        (this.visitas || []).filter(v => v.codigo).map(v => v.codigo)
      );
      const newOnes = this.visitasPendientes.filter(p => !serverCodigos.has(p.codigo));
      if (newOnes.length) {
        this.visitas = [...newOnes, ...this.visitas];
      }
      this.currentPage = 1;
    },

    async loadVisitasPendientes() {
      try {
        const pendientes = await db.getVisitasPendientes();
        // Enrich with predio data from local catalog
        this.visitasPendientes = pendientes.map(p => {
          const pred = this.predios.find(pr => String(pr.id) === String(p.predio_id));
          return {
            ...p,
            id: p.codigo, // Use codigo as pseudo-id for rendering
            _syncStatus: 'pendiente',
            predio: pred || { nombre_rancho: `ID: ${p.predio_id}`, localidad: '—' },
            veterinario: { name: 'Pendiente de sincronización' },
            estado: 'pendiente',
            inyeccion: false,
            inspeccion: null
          };
        });
      } catch (e) {
        console.warn('Error loading pending visits:', e);
        this.visitasPendientes = [];
      }
    },
    async loadMedicos() {
      try {
        const res = await api.getMedicos();
        this.medicos = res.data || [];
      } catch (e) {
        console.warn('No se pudieron cargar los médicos:', e.message);
        this.medicos = [];
      }
    },
    async loadPredios() {
      // 1. Cargar datos locales inmediatamente
      this.predios = await db.getPredios();

      // 2. Intentar actualizar desde servidor en segundo plano
      try {
        const isReachable = await api.checkRealConnectivity();
        if (!isReachable) return;

        const res = await api.getPredios();
        if (res.data && res.data.length > 0) {
          this.predios = res.data;
          await db.savePredios(this.predios);
        }
      } catch (e) {
        console.warn('No se pudo actualizar predios desde el servidor:', e.message);
      }
    },
    async loadVisitas() {
      this.errorMsg = '';

      // 1. Cargar datos locales inmediatamente
      const local = await db.getVisitas();
      this.visitas = this.filters.fecha ? local.filter(v => v.fecha_programada === this.filters.fecha) : local;
      this.currentPage = 1;

      // 2. Intentar actualizar desde servidor en segundo plano
      this.loading = true;
      try {
        const isReachable = await api.checkRealConnectivity();
        if (!isReachable) {
          if (this.visitas.length === 0) {
            this.errorMsg = 'Sin conexión al servidor. Mostrando datos locales.';
          }
          return;
        }

        const res = await api.getVisitas();
        if (res.data) {
          await db.saveVisitas(res.data);
          const local = res.data;
          this.visitas = this.filters.fecha ? local.filter(v => v.fecha_programada === this.filters.fecha) : local;
          this.currentPage = 1;
          this.errorMsg = '';
        }
      } catch (e) {
        // Si ya tenemos datos locales, no mostrar error alarmante
        if (this.visitas.length > 0) {
          console.warn('No se pudo actualizar visitas desde el servidor, mostrando datos locales:', e.message);
        } else {
          this.errorMsg = `Sin conexión al servidor. Mostrando datos locales.`;
        }
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
    async syncPendingVisita(visita) {
      if (!this.isOnline) {
        this.errorMsg = 'No hay conexión a internet. No se puede sincronizar.';
        return;
      }
      this.syncingVisita = visita.codigo;
      this.errorMsg = '';
      try {
        const check = await api.checkVisitaCodigo(visita.codigo);
        if (check.exists) {
          const msg =
            `⚠️ El código ${visita.codigo} ya existe en el servidor.\n\n` +
            `Fecha: ${check.visita?.fecha_programada || '—'}\n` +
            `Predio: ${check.visita?.predio?.nombre_rancho || '—'}\n` +
            `Médico: ${check.visita?.veterinario?.name || '—'}\n\n` +
            `La visita local se eliminará y se conservará la del servidor.`;
          alert(msg);
          await db.removeVisitaPendiente(visita.codigo);
        } else {
          await api.createVisita({
            codigo: visita.codigo,
            predio_id: visita.predio_id,
            fecha_programada: visita.fecha_programada,
            veterinario_id: visita.veterinario_id,
            observaciones: visita.observaciones || ''
          });
          await db.removeVisitaPendiente(visita.codigo);
          this.successMsg = `Visita ${visita.codigo} sincronizada con éxito.`;
        }
        await this.loadAll();
      } catch (e) {
        this.errorMsg = e.message || 'Error al sincronizar la visita.';
      } finally {
        this.syncingVisita = null;
      }
    },
    async eliminarPendiente(visita) {
      if (!confirm(`¿Eliminar la visita ${visita.codigo} sin sincronizar?`)) return;
      await db.removeVisitaPendiente(visita.codigo);
      await this.loadAll();
    },
    refreshOnSync() {
      this.loadAll();
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
    estimatedLecturaDate(dateStr) {
      if (!dateStr) return '';
      const parts = dateStr.split('-');
      let d;
      if (parts.length === 3) {
        d = new Date(parts[0], parts[1] - 1, parts[2]);
      } else {
        d = new Date(dateStr);
      }
      d.setDate(d.getDate() + 3);
      const dd = String(d.getDate()).padStart(2, '0');
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const yyyy = d.getFullYear();
      return `${dd}/${mm}/${yyyy}`;
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
  background-color: #10b981 !important;
  color: #fff !important;
}

.bg-danger {
  background-color: #ef4444 !important;
  color: #fff !important;
}

.bg-warning {
  background-color: #f59e0b !important;
  color: #000 !important;
}

.text-white {
  color: #fff !important;
}

.text-dark {
  color: #1e293b !important;
}

.text-capitalize {
  text-transform: capitalize !important;
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
