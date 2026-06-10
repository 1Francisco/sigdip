<template>
  <div class="app-container">
    <!-- Sidebar (Drawer) -->
    <div class="sidebar-overlay" :class="{ active: sidebarActive }" @click="sidebarActive = false"></div>
    
    <aside class="sidebar" :class="{ active: sidebarActive }">
      <div class="sidebar-brand">
        <img src="/icon_png.png" alt="SIGDIP" style="width: 22px; height: 22px; object-fit: contain;">
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
          <a class="nav-link active" @click.prevent="sidebarActive = false">
            <i class="bi bi-people"></i> Productores
          </a>
          <a class="nav-link" @click.prevent="$router.push('/predios')">
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
          <a class="nav-link" @click.prevent="$router.push('/descargas')">
            <i class="bi bi-download"></i> Descargas
          </a>
          <a class="nav-link" @click.prevent="$router.push('/inspecciones?downloadExcel=true')">
            <i class="bi bi-file-earmark-excel"></i> Sábana Excel
          </a>
        </template>
        <template v-else>
          <a class="nav-link" @click.prevent="$router.push('/dashboard')">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
          </a>
          <a class="nav-link active" @click.prevent="sidebarActive = false">
            <i class="bi bi-people"></i> Productores
          </a>
          <a class="nav-link" @click.prevent="$router.push('/predios')">
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
          <a class="nav-link" @click.prevent="$router.push('/descargas')">
            <i class="bi bi-download"></i> Descargas
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
        <img src="/icon_png.png" alt="SIGDIP" style="width: 20px; height: 20px; object-fit: contain; vertical-align: -3px; margin-right: 6px;">
        <span class="fw-bold">SIGDIP</span>
      </div>

      <!-- Connectivity Badge Mobile -->
      <div 
        class="badge rounded-pill px-2-5 py-1-5 d-flex align-items-center gap-1-5 fw-semibold me-2 border connectivity-badge shadow-sm"
        :class="isOnline ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle'"
      >
        <span class="pulse-dot" :class="isOnline ? 'bg-success' : 'bg-danger'"></span>
        <span class="badge-text">{{ isOnline ? 'Online' : 'Offline' }}</span>
      </div>

      <div class="avatar-circle rounded-circle">
        <i class="bi bi-person"></i>
      </div>
    </header>

    <!-- Main Content -->
    <main class="app-content main-content bg-light">
      
      <!-- Top Action Bar (Premium Web Replica) -->
      <div class="welcome-header mb-4 text-start d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
        <div>
          <h2 class="h4 fw-bold mb-1 text-dark">Productores</h2>
          <p class="text-secondary small mb-0">Administre la información de los dueños de ganado</p>
        </div>
        
        <!-- Web Badges (Conectado / Administrador Central) Cloned -->
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

      <!-- Card Container for Producers List -->
      <div class="card border-0 shadow-sm p-0 overflow-hidden card-outer-mobile-flat">
        <div class="card-header bg-white d-flex justify-content-between align-items-center p-3 border-bottom border-slate-100">
          <h5 class="mb-0 fw-bold fs-6 text-dark">Listado de Productores</h5>
          <button @click="$router.push('/productores/nuevo')" class="btn btn-primary btn-sm-custom d-flex align-items-center gap-1-5 px-3 py-2">
            <i class="bi bi-person-plus"></i> Nuevo Productor
          </button>
        </div>

        <!-- Inline Live Search Bar (Premium Added Value) -->
        <div class="p-3 bg-light-subtle border-bottom border-slate-100">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input 
              v-model="searchQuery" 
              type="text" 
              class="form-control border-start-0 px-2 py-2 fs-6-5" 
              placeholder="Buscar productor por nombre, CURP, UPP o teléfono..."
              style="outline: none; box-shadow: none; border-color: #dee2e6;"
              @input="currentPage = 1"
            >
          </div>
        </div>

        <div class="card-body p-0 text-start">
          
          <!-- Empty State when filtered results are 0 -->
          <div v-if="filteredProductores.length === 0" class="text-center p-5 text-muted">
            <i class="bi bi-person-x display-6 d-block mb-2 text-muted"></i>
            <p class="mb-0 small text-secondary">No se encontraron productores con el criterio buscado.</p>
          </div>

          <div v-else>
            <!-- 1. DESKTOP VIEW: Beautiful and precise Table replica -->
            <div class="table-responsive d-none d-lg-block">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr>
                    <th class="ps-4">Nombre</th>
                    <th>CURP</th>
                    <th>UPP</th>
                    <th>Teléfono</th>
                    <th>Predios</th>
                    <th class="text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in paginatedProductores" :key="p.id">
                    <td class="ps-4">
                      <div class="fw-bold text-dark">{{ p.nombre }}</div>
                      <small class="text-muted text-xs">Productor Registrado</small>
                    </td>
                    <td>
                      <code class="curp-code">{{ p.curp }}</code>
                    </td>
                    <td>{{ p.upp }}</td>
                    <td>{{ p.telefono || 'N/A' }}</td>
                    <td>
                      <span class="badge bg-primary-soft text-primary rounded-pill px-3 py-1 fw-bold">
                        {{ p.prediosCount }} ranchos
                      </span>
                    </td>
                    <td>
                      <div class="d-flex justify-content-center gap-2">
                        <button 
                          @click="openRanchoModal(p)" 
                          class="btn-action-outline-blue" 
                          title="Añadir Rancho"
                        >
                          <i class="bi bi-house-add"></i>
                        </button>
                        <button 
                          @click="$router.push('/productores/editar/' + p.id)" 
                          class="btn-action-outline-gray" 
                          title="Editar Productor"
                        >
                          <i class="bi bi-pencil"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- 2. MOBILE VIEW: Modern responsive high-fidelity cards exactly like screenshot -->
            <div class="d-block d-lg-none px-0 py-2">
              <div class="mobile-cards-grid">
                <div 
                  v-for="p in paginatedProductores" 
                  :key="p.id" 
                  class="producer-mobile-card shadow-sm mb-3 position-relative"
                >
                <!-- Productor Field -->
                <div class="card-field">
                  <span class="field-label">Productor</span>
                  <span class="field-value producer-name-bold">{{ p.nombre }}</span>
                  <span class="field-subtitle">Productor Registrado</span>
                </div>

                <!-- CURP Field -->
                <div class="card-field">
                  <span class="field-label">CURP</span>
                  <span class="field-value-curp">{{ p.curp }}</span>
                </div>

                <!-- UPP Field -->
                <div class="card-field">
                  <span class="field-label">UPP</span>
                  <span class="field-value">{{ p.upp }}</span>
                </div>

                <!-- Teléfono Field -->
                <div class="card-field">
                  <span class="field-label">Teléfono</span>
                  <span class="field-value">{{ p.telefono || 'N/A' }}</span>
                </div>

                <!-- Predios Field -->
                <div class="card-field">
                  <span class="field-label">Predios</span>
                  <span class="field-value text-primary-link" @click="openRanchoModal(p)">
                    {{ p.prediosCount }} ranchos
                  </span>
                </div>

                <!-- Actions Footer -->
                <div class="producer-mobile-footer">
                  <div class="footer-actions-label">Acciones</div>
                  <div class="d-flex gap-2">
                    <button 
                      @click="openRanchoModal(p)" 
                      class="btn-icon-square-blue" 
                      title="Añadir Rancho"
                    >
                      <i class="bi bi-house-add"></i>
                    </button>
                    <button 
                      @click="$router.push('/productores/editar/' + p.id)" 
                      class="btn-icon-square-gray" 
                      title="Editar Productor"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                  </div>
                </div>
              </div>
              </div>
            </div>

            <!-- 3. PAGINATION FOOTER: Precise design match to web screenshot -->
            <div class="pagination-container">
              <div class="pagination-info">
                Showing <strong class="text-dark">{{ startResult }}</strong> to <strong class="text-dark">{{ endResult }}</strong> of <strong class="text-dark">{{ totalResults }}</strong> results
              </div>
              <div class="pagination-buttons">
                <button 
                  class="pagination-btn" 
                  :disabled="currentPage === 1" 
                  @click="currentPage--"
                >
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
                <button 
                  class="pagination-btn" 
                  :disabled="currentPage === totalPages" 
                  @click="currentPage++"
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
      <a class="bottom-nav-link" :class="{ active: $route.path === '/dashboard' }" @click.prevent="$router.push('/dashboard')">
        <i class="bi" :class="$route.path === '/dashboard' ? 'bi-grid-1x2-fill' : 'bi-grid-1x2'"></i>
        <span>Inicio</span>
      </a>
      <a class="bottom-nav-link" :class="{ active: $route.path.startsWith('/productores') }" @click.prevent="$router.push('/productores')">
        <i class="bi" :class="$route.path.startsWith('/productores') ? 'bi-people-fill' : 'bi-people'"></i>
        <span>Productores</span>
      </a>
      <a class="bottom-nav-link" :class="{ active: $route.path.startsWith('/predios') }" @click.prevent="$router.push('/predios')">
        <i class="bi" :class="$route.path.startsWith('/predios') ? 'bi-house-door-fill' : 'bi-house-door'"></i>
        <span>Predios</span>
      </a>
      <a class="bottom-nav-link" :class="{ active: $route.path.startsWith('/inspeccione') || $route.path.startsWith('/inspeccion') }" @click.prevent="$router.push('/inspecciones')">
        <i class="bi" :class="($route.path.startsWith('/inspeccione') || $route.path.startsWith('/inspeccion')) ? 'bi-clipboard-check-fill' : 'bi-clipboard-check'"></i>
        <span>Dictámenes</span>
      </a>
      <a class="bottom-nav-link" :class="{ active: $route.path === '/sync' || $route.path === '/scan' }" @click.prevent="$router.push('/sync')">
        <i class="bi bi-arrow-repeat"></i>
        <span>Sincronizar</span>
      </a>
    </nav>

    <!-- MODAL 1: NUEVO/EDITAR PRODUCTOR -->
    <div v-if="showProductorModal" class="modal-overlay" @click.self="showProductorModal = false">
      <div class="modal-card">
        <div class="modal-header">
          <span class="modal-title">
            <i class="bi" :class="productorModalMode === 'create' ? 'bi-person-plus-fill text-primary' : 'bi-pencil-square text-warning'"></i>
            {{ productorModalMode === 'create' ? 'Nuevo Productor' : 'Editar Productor' }}
          </span>
          <button class="btn-close-modal" @click="showProductorModal = false">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveProductor">
            <div class="form-group-custom">
              <label class="form-label-custom">Nombre(s) *</label>
              <input v-model="productorForm.nombre" type="text" class="form-control-custom" placeholder="Ej: Pepito" required>
            </div>
            
            <div class="row">
              <div class="col-6 p-0 pe-2">
                <div class="form-group-custom">
                  <label class="form-label-custom">Apellido Paterno *</label>
                  <input v-model="productorForm.apellido_paterno" type="text" class="form-control-custom" placeholder="Ej: Tejeda" required>
                </div>
              </div>
              <div class="col-6 p-0 ps-2">
                <div class="form-group-custom">
                  <label class="form-label-custom">Apellido Materno</label>
                  <input v-model="productorForm.apellido_materno" type="text" class="form-control-custom" placeholder="Ej: Figueroa">
                </div>
              </div>
            </div>

            <div class="form-group-custom">
              <label class="form-label-custom">CURP *</label>
              <input 
                v-model="productorForm.curp" 
                type="text" 
                class="form-control-custom font-mono" 
                placeholder="18 caracteres" 
                maxlength="18"
                style="text-transform: uppercase;"
                required
              >
            </div>

            <div class="form-group-custom">
              <label class="form-label-custom">Clave UPP Principal *</label>
              <input v-model="productorForm.upp" type="text" class="form-control-custom" placeholder="Ej: 57625285" required>
            </div>

            <div class="form-group-custom">
              <label class="form-label-custom">Teléfono</label>
              <input v-model="productorForm.telefono" type="tel" class="form-control-custom" placeholder="Ej: 3111129405">
            </div>

            <!-- Registro Opcional de Rancho/Predio en 2 Pasos (Copia Exacta de la Web) -->
            <div v-if="productorModalMode === 'create'" class="form-group-custom d-flex align-items-center gap-2 mb-3 mt-3">
              <input v-model="registrarPredio" type="checkbox" id="registrarPredioCheck" style="width: 18px; height: 18px; cursor: pointer;">
              <label for="registrarPredioCheck" style="font-size: 0.85rem; font-weight: 600; color: #1e293b; cursor: pointer; margin-bottom: 0;">
                ¿Desea registrar también una Unidad de Producción (Rancho) para este productor?
              </label>
            </div>

            <div v-if="productorModalMode === 'create' && registrarPredio" class="p-3 bg-light rounded-3 border mb-3 text-start">
              <div class="fw-bold fs-7 text-primary mb-2 text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                <i class="bi bi-house-door-fill"></i> Datos de la Unidad de Producción (Rancho)
              </div>
              
              <div class="form-group-custom">
                <label class="form-label-custom">Nombre del Rancho *</label>
                <input v-model="productorForm.nombre_rancho" type="text" class="form-control-custom" placeholder="Ej: El Refugio" :required="registrarPredio">
              </div>

              <div class="form-group-custom">
                <label class="form-label-custom">Clave UPP / PSG del Rancho *</label>
                <input v-model="productorForm.clave_unidad_produccion" type="text" class="form-control-custom" placeholder="Ej: 57625285" :required="registrarPredio">
              </div>

              <div class="row">
                <div class="col-6 p-0 pe-2">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Localidad</label>
                    <input v-model="productorForm.predio_localidad" type="text" class="form-control-custom" placeholder="Ej: Tepic">
                  </div>
                </div>
                <div class="col-6 p-0 ps-2">
                  <div class="form-group-custom">
                    <label class="form-label-custom">Municipio</label>
                    <input v-model="productorForm.predio_municipio" type="text" class="form-control-custom" placeholder="Ej: Tepic">
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button class="btn-modal-cancel" @click="showProductorModal = false">Cancelar</button>
          <button class="btn-modal-save" @click="saveProductor">Guardar Datos</button>
        </div>
      </div>
    </div>

    <!-- MODAL 2: AÑADIR NUEVO RANCHO (PREDIO) -->
    <div v-if="showRanchoModal" class="modal-overlay" @click.self="showRanchoModal = false">
      <div class="modal-card">
        <div class="modal-header">
          <span class="modal-title">
            <i class="bi bi-house-add-fill text-primary"></i>
            Añadir Rancho
          </span>
          <button class="btn-close-modal" @click="showRanchoModal = false">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="alert bg-primary-soft text-primary p-3 rounded-3 mb-4 small text-start">
            <i class="bi bi-info-circle-fill me-2"></i>
            Añadiendo nuevo rancho para: <strong>{{ activeProductor?.nombre }}</strong>
          </div>
          <form @submit.prevent="saveRancho">
            <div class="form-group-custom">
              <label class="form-label-custom">Nombre del Rancho *</label>
              <input v-model="ranchoForm.nombre_rancho" type="text" class="form-control-custom" placeholder="Ej: El Refugio" required>
            </div>

            <div class="form-group-custom">
              <label class="form-label-custom">Clave UPP / PSG del Rancho *</label>
              <input v-model="ranchoForm.upp" type="text" class="form-control-custom" placeholder="Ej: 57625285" required>
            </div>

            <div class="row">
              <div class="col-6 p-0 pe-2">
                <div class="form-group-custom">
                  <label class="form-label-custom">Localidad</label>
                  <input v-model="ranchoForm.localidad" type="text" class="form-control-custom" placeholder="Ej: Tepic">
                </div>
              </div>
              <div class="col-6 p-0 ps-2">
                <div class="form-group-custom">
                  <label class="form-label-custom">Municipio</label>
                  <input v-model="ranchoForm.municipio" type="text" class="form-control-custom" placeholder="Ej: Tepic">
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button class="btn-modal-cancel" @click="showRanchoModal = false">Cancelar</button>
          <button class="btn-modal-save" @click="saveRancho">Añadir Rancho</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { Network } from '@capacitor/network';
import db from '../services/db.js';
import api from '../services/api.js';

export default {
  name: 'ProductoresView',
  data() {
    return {
      userName: '',
      isAdmin: false,
      sidebarActive: false,
      isOnline: true,
      networkListener: null,
      searchQuery: '',
      productores: [],
      
      // Pagination State
      currentPage: 1,

      // Modal states
      showProductorModal: false,
      showRanchoModal: false,
      productorModalMode: 'create', // 'create' | 'edit'
      activeProductor: null,
      registrarPredio: false, // Control para registro en 2 pasos opcional

      // Modal forms
      productorForm: {
        id: '',
        nombre: '',
        apellido_paterno: '',
        apellido_materno: '',
        curp: '',
        upp: '',
        telefono: '',
        
        // Campos para rancho opcional (2 pasos copia de la web)
        nombre_rancho: '',
        clave_unidad_produccion: '',
        predio_localidad: 'General',
        predio_municipio: 'General'
      },
      ranchoForm: {
        nombre_rancho: '',
        upp: '',
        localidad: 'General',
        municipio: 'General'
      }
    };
  },
  computed: {
    filteredProductores() {
      if (!this.searchQuery.trim()) {
        return this.productores;
      }
      const q = this.searchQuery.toLowerCase().trim();
      return this.productores.filter(p => {
        return p.nombre.toLowerCase().includes(q) ||
               p.curp.toLowerCase().includes(q) ||
               p.upp.toLowerCase().includes(q) ||
               (p.telefono && p.telefono.toLowerCase().includes(q));
      });
    },
    
    // Pagination computed details
    totalResults() {
      return this.filteredProductores.length;
    },
    totalPages() {
      return Math.ceil(this.totalResults / 10) || 1;
    },
    startResult() {
      return this.totalResults === 0 ? 0 : (this.currentPage - 1) * 10 + 1;
    },
    endResult() {
      return Math.min(this.currentPage * 10, this.totalResults);
    },
    paginatedProductores() {
      return this.filteredProductores.slice((this.currentPage - 1) * 10, this.currentPage * 10);
    }
  },
  async mounted() {
    // 1. Cargar datos del usuario autenticado
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Administrador Central';
    this.isAdmin = user?.roles && user.roles.includes('Administrador');

    // 2. Extraer y agrupar productores locales offline-first
    await this.loadProductores();

    // 3. Inicializar estado de conectividad nativa
    try {
      const status = await Network.getStatus();
      this.isOnline = status.connected;

      this.networkListener = await Network.addListener('networkStatusChange', (status) => {
        this.isOnline = status.connected;
      });
    } catch (e) {
      console.warn('Network API no disponible, usando fallback local.', e);
      this.isOnline = navigator.onLine;
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

    // AÑADIR PRODUCTOR (NUEVO)
    openCreateProductorModal() {
      this.productorModalMode = 'create';
      this.registrarPredio = false;
      this.productorForm = {
        id: '',
        nombre: '',
        apellido_paterno: '',
        apellido_materno: '',
        curp: '',
        upp: '',
        telefono: '',
        nombre_rancho: '',
        clave_unidad_produccion: '',
        predio_localidad: 'General',
        predio_municipio: 'General'
      };
      this.showProductorModal = true;
    },

    // EDITAR PRODUCTOR
    openEditProductorModal(p) {
      this.productorModalMode = 'edit';
      this.productorForm = {
        id: p.id,
        nombre: p.nombreRaw || '',
        apellido_paterno: p.apellido_paterno || '',
        apellido_materno: p.apellido_materno || '',
        curp: p.curp !== 'N/A' ? p.curp : '',
        upp: p.upp !== 'N/A' ? p.upp : '',
        telefono: p.telefono !== 'N/A' ? p.telefono : ''
      };
      this.showProductorModal = true;
    },

    // AÑADIR RANCHO
    openRanchoModal(p) {
      this.$router.push({
        path: '/predios/nuevo',
        query: { productor_id: p.id }
      });
    },

    // GUARDAR PRODUCTOR (API ROTA AL SERVIDOR SI ONLINE, FALLBACK INDEXEDDB)
    async saveProductor() {
      if (!this.productorForm.nombre.trim() || !this.productorForm.apellido_paterno.trim() || !this.productorForm.curp.trim() || !this.productorForm.upp.trim()) {
        alert('⚠️ Por favor completa todos los campos requeridos (*).');
        return;
      }

      // Validar datos del rancho si se seleccionó registrarlo
      if (this.productorModalMode === 'create' && this.registrarPredio) {
        if (!this.productorForm.nombre_rancho.trim() || !this.productorForm.clave_unidad_produccion.trim()) {
          alert('⚠️ Por favor completa los campos requeridos del rancho (*).');
          return;
        }
      }

      const body = {
        nombre: this.productorForm.nombre.trim(),
        apellido_paterno: this.productorForm.apellido_paterno.trim(),
        apellido_materno: this.productorForm.apellido_materno.trim(),
        curp: this.productorForm.curp.toUpperCase().trim(),
        upp: this.productorForm.upp.trim(),
        telefono: this.productorForm.telefono.trim(),

        // Registro opcional en 2 pasos (Copia exacta de la web)
        registrar_predio: this.productorModalMode === 'create' && this.registrarPredio ? 1 : 0,
        nombre_rancho: this.productorModalMode === 'create' && this.registrarPredio ? this.productorForm.nombre_rancho.trim() : null,
        clave_unidad_produccion: this.productorModalMode === 'create' && this.registrarPredio ? this.productorForm.clave_unidad_produccion.trim() : null,
        predio_localidad: this.productorModalMode === 'create' && this.registrarPredio ? (this.productorForm.predio_localidad.trim() || 'General') : 'General',
        predio_municipio: this.productorModalMode === 'create' && this.registrarPredio ? (this.productorForm.predio_municipio.trim() || 'General') : 'General'
      };

      try {
        let serverProductor = null;
        let serverRancho = null;
        let createdOnServer = false;

        // 1. Si está conectado, guardar en el servidor Laravel central
        if (this.isOnline) {
          try {
            if (this.productorModalMode === 'create') {
              const res = await api.storeProductor(body);
              if (res && res.success) {
                serverProductor = res.productor;
                serverRancho = res.predio; // Rancho creado opcionalmente
                createdOnServer = true;
              }
            } else {
              const res = await api.updateProductor(this.productorForm.id, body);
              if (res && res.success) {
                serverProductor = res.productor;
                createdOnServer = true;
              }
            }
          } catch (apiErr) {
            console.warn('Error al conectar con la API de Laravel, usando guardado local offline:', apiErr);
            alert('⚠️ Error de validación o conexión con el servidor: ' + (apiErr.message || 'Inténtalo de nuevo.'));
            return; // Detener flujo para no crear inconsistencias si la validación del servidor falló (ej: CURP repetido)
          }
        }

        // 2. Guardar en la base de datos IndexedDB local (offline-first)
        const predios = await db.getPredios();
        
        if (this.productorModalMode === 'create') {
          // Generar ID: Usar el ID que devolvió el servidor central si se creó con éxito, o ID temporal si offline
          const finalProductorId = createdOnServer && serverProductor ? serverProductor.id : ('OFFLINE_PROD_' + Date.now());
          
          let newPredio = null;

          if (this.registrarPredio) {
            // El usuario registró productor + rancho
            newPredio = {
              id: createdOnServer && serverRancho ? serverRancho.id : ('OFFLINE_PREDIO_' + Date.now()),
              nombre: body.nombre_rancho,
              upp: body.clave_unidad_produccion,
              localidad: body.predio_localidad,
              municipio: body.predio_municipio,
              productor_id: finalProductorId,
              productor: {
                id: finalProductorId,
                nombre: body.nombre,
                apellido_paterno: body.apellido_paterno,
                apellido_materno: body.apellido_materno,
                curp: body.curp,
                upp: body.upp,
                telefono: body.telefono
              }
            };
          } else {
            // El usuario registró el productor SIN rancho/predio
            newPredio = {
              id: 'OFFLINE_PREDIO_EMPTY_' + Date.now(),
              nombre: 'Sin Rancho',
              upp: 'N/A',
              localidad: 'General',
              municipio: 'General',
              productor_id: finalProductorId,
              productor: {
                id: finalProductorId,
                nombre: body.nombre,
                apellido_paterno: body.apellido_paterno,
                apellido_materno: body.apellido_materno,
                curp: body.curp,
                upp: body.upp,
                telefono: body.telefono
              }
            };
          }

          predios.push(newPredio);
          await db.savePredios(predios);
          
          if (createdOnServer) {
            alert(newPredio.nombre !== 'Sin Rancho' 
              ? '✅ ¡Productor y su Rancho registrados con éxito en el servidor!' 
              : '✅ ¡Productor registrado con éxito (Sin Rancho) en el servidor!'
            );
          } else {
            alert('✅ ¡Productor guardado localmente (Modo Offline)! Se sincronizará con el servidor al conectar.');
          }
        } else {
          // Edición
          const targetId = this.productorForm.id;

          predios.forEach(p => {
            if (p.productor && p.productor.id === targetId) {
              p.productor.nombre = body.nombre;
              p.productor.apellido_paterno = body.apellido_paterno;
              p.productor.apellido_materno = body.apellido_materno;
              p.productor.curp = body.curp;
              p.productor.upp = body.upp;
              p.productor.telefono = body.telefono;
            }
          });

          await db.savePredios(predios);
          
          if (createdOnServer) {
            alert('✅ ¡Datos del productor actualizados con éxito en el servidor y local!');
          } else {
            alert('✅ ¡Cambios guardados localmente en offline!');
          }
        }

        this.showProductorModal = false;
        await this.loadProductores();
      } catch (err) {
        console.error('Error al guardar productor:', err);
        alert('❌ Ocurrió un error inesperado al procesar los datos.');
      }
    },

    // GUARDAR RANCHO (API AL SERVIDOR SI ONLINE, FALLBACK INDEXEDDB)
    async saveRancho() {
      if (!this.ranchoForm.nombre_rancho.trim() || !this.ranchoForm.upp.trim()) {
        alert('⚠️ Por favor ingresa el nombre del rancho y su clave UPP.');
        return;
      }

      const body = {
        nombre_rancho: this.ranchoForm.nombre_rancho.trim(),
        clave_unidad_produccion: this.ranchoForm.upp.trim(),
        localidad: this.ranchoForm.localidad.trim() || 'General',
        municipio: this.ranchoForm.municipio.trim() || 'General',
        productor_id: this.activeProductor.id
      };

      try {
        let serverRancho = null;
        let createdOnServer = false;

        // 1. Si está conectado, guardar en el servidor Laravel central
        if (this.isOnline) {
          try {
            const res = await api.storeRancho(body);
            if (res && res.success) {
              serverRancho = res.predio;
              createdOnServer = true;
            }
          } catch (apiErr) {
            console.warn('Error al crear rancho en el servidor:', apiErr);
            alert('⚠️ Error de validación o conexión con el servidor: ' + (apiErr.message || 'Inténtalo de nuevo.'));
            return;
          }
        }

        // 2. Guardar en IndexedDB local
        const predios = await db.getPredios();

        const finalRanchoId = createdOnServer && serverRancho ? serverRancho.id : ('OFFLINE_PREDIO_' + Date.now());

        const newPredio = {
          id: finalRanchoId,
          nombre: body.nombre_rancho,
          upp: body.clave_unidad_produccion,
          localidad: body.localidad,
          municipio: body.municipio,
          productor_id: this.activeProductor.id,
          productor: {
            id: this.activeProductor.id,
            nombre: this.activeProductor.nombreRaw || this.activeProductor.nombre,
            apellido_paterno: this.activeProductor.apellido_paterno || '',
            apellido_materno: this.activeProductor.apellido_materno || '',
            curp: this.activeProductor.curp,
            upp: this.activeProductor.upp,
            telefono: this.activeProductor.telefono
          }
        };

        predios.push(newPredio);
        await db.savePredios(predios);

        if (createdOnServer) {
          alert(`✅ ¡Rancho "${body.nombre_rancho}" registrado con éxito en el servidor y local!`);
        } else {
          alert(`✅ ¡Rancho "${body.nombre_rancho}" guardado localmente (Offline)! Se sincronizará al conectar.`);
        }

        this.showRanchoModal = false;
        await this.loadProductores();
      } catch (err) {
        console.error('Error al guardar rancho:', err);
        alert('❌ Ocurrió un error inesperado al guardar el rancho.');
      }
    },

    async loadProductores() {
      try {
        let predios = await db.getPredios();

        try {
          const live = await api.getPredios();
          if (live?.data && Array.isArray(live.data) && live.data.length > 0) {
            predios = live.data;
            await db.savePredios(predios);
          }
        } catch (e) {
          // Si el backend no responde, seguimos con el cache local.
        }
        
        if (predios.length === 0) {
          console.warn('Catálogo local de predios vacío. Usar Sincronizar > Descargar Catálogos.');
        }

        const productoresMap = {};

        predios.forEach(predio => {
          if (predio.productor) {
            const prod = predio.productor;
            const id = prod.id;

            if (!productoresMap[id]) {
              const fullNombre = [prod.nombre, prod.apellido_paterno, prod.apellido_materno]
                .filter(Boolean)
                .join(' ')
                .trim();

              productoresMap[id] = {
                id: prod.id,
                nombre: fullNombre || 'Productor Registrado',
                nombreRaw: prod.nombre || '',
                apellido_paterno: prod.apellido_paterno || '',
                apellido_materno: prod.apellido_materno || '',
                curp: prod.curp || 'N/A',
                upp: prod.upp || 'N/A',
                telefono: prod.telefono || 'N/A',
                prediosCount: 0,
                ranchos: []
              };
            }

            const predioName = predio.nombre || predio.nombre_rancho || '';
            if (predioName && predioName !== 'Sin Rancho') {
              productoresMap[id].prediosCount++;
              productoresMap[id].ranchos.push(predio);
            }

            // Si el UPP del productor no está definido, usar el del predio
            if ((productoresMap[id].upp === 'N/A' || !productoresMap[id].upp) && predio.upp) {
              productoresMap[id].upp = predio.upp;
            }
          }
        });

        this.productores = Object.values(productoresMap);
      } catch (err) {
        console.error('Error al agrupar productores locales:', err);
      }
    }
  }
};
</script>

<style scoped>
.app-container {
  display: flex;
  flex-direction: column;
  min-height: 100dvh;
}

/* Sidebar Drawer Style Cloned from Dashboard */
.sidebar {
  width: 270px;
  height: 100dvh;
  position: fixed;
  top: 0;
  left: 0;
  background: white;
  border-right: 1px solid #e2e8f0;
  z-index: 1100;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  transform: translateX(-100%);
  display: flex;
  flex-direction: column;
}

.sidebar.active {
  transform: translateX(0);
}

.sidebar-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(15, 23, 42, 0.45);
  z-index: 1080;
  backdrop-filter: blur(4px);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.3s ease;
}

.sidebar-overlay.active {
  opacity: 1;
  pointer-events: auto;
}

.sidebar-brand {
  padding: 24px 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
  font-size: 1.25rem;
  color: var(--color-primary);
  border-bottom: 1px solid #f1f5f9;
}

.btn-close-sidebar {
  margin-left: auto;
  background: #f1f5f9;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.nav {
  padding: 15px 0;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.nav-link {
  padding: 12px 20px;
  color: var(--text-secondary);
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  margin: 2px 12px;
  border-radius: 12px;
  transition: all 0.2s;
  cursor: pointer;
}

.nav-link:hover {
  background-color: var(--color-primary-light);
  color: var(--color-primary);
}

.nav-link.active {
  background-color: var(--color-primary);
  color: white;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
}

.logout-btn {
  margin-top: auto;
}

/* Mobile Header Style Cloned from Dashboard */
.mobile-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: white;
  padding: 12px 16px;
  position: sticky;
  top: 0;
  z-index: 1000;
  border-bottom: 1px solid #e2e8f0;
}

.header-hamburger-btn {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  background: #f1f5f9;
  border: none;
  color: #64748b;
  cursor: pointer;
}

.header-hamburger-btn:active {
  background: #e2e8f0;
}

.brand-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-circle {
  width: 40px;
  height: 40px;
  background-color: var(--color-primary-light);
  color: var(--color-primary);
  font-size: 1.2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* Connectivity Badge Mobile */
.connectivity-badge {
  font-size: 0.72rem;
  padding: 4px 10px;
  border: 1px solid;
  display: flex;
  align-items: center;
  justify-content: center;
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
  background-color: var(--color-success);
  animation: pulse-green 2s infinite;
}

.bg-danger {
  background-color: var(--color-danger);
}

@keyframes pulse-green {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(16, 185, 129, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

/* Web-replica Header Badges */
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

/* Premium Layout for List card */
.btn-primary {
  background: var(--color-primary) !important;
  background-image: none !important;
  color: white;
  border-radius: var(--radius-sm);
  font-weight: 600;
  border: none;
  box-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
  transition: all 0.2s ease;
}

.btn-primary:hover {
  background: var(--color-primary-dark) !important;
  background-image: none !important;
}

.btn-primary:active {
  background: var(--color-primary-dark) !important;
  background-image: none !important;
  transform: scale(0.97);
}

.btn-sm-custom {
  font-size: 0.8rem;
  padding: 6px 12px;
}

/* Search input style */
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

/* Table Style Cloned */
.table {
  width: 100%;
  margin-bottom: 0;
  color: var(--text-primary);
  border-collapse: collapse;
}

.table th {
  font-weight: 700;
  color: #475569;
  background-color: #f8fafc;
  padding: 14px 16px;
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid #e2e8f0;
}

.table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  font-size: 0.88rem;
}

.table tbody tr:hover {
  background-color: #f8fafc;
}

/* CURP Pink Code styling */
.curp-code {
  color: #d63384;
  font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important;
  background-color: #fdf2f8;
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 0.82rem;
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* Badges bg-primary-soft */
.bg-primary-soft {
  background-color: #eff6ff !important;
  color: #2563eb !important;
}

/* Outline Buttons for Actions Table/Mobile */
.btn-action-outline-blue {
  background-color: white;
  border: 1px solid #bfdbfe;
  color: #2563eb;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-action-outline-blue:hover {
  background-color: #eff6ff;
  border-color: #2563eb;
}

.btn-action-outline-gray {
  background-color: white;
  border: 1px solid #e2e8f0;
  color: #64748b;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-action-outline-gray:hover {
  background-color: #f8fafc;
  border-color: #94a3b8;
}

/* Mobile Cards Grid Layout for horizontal view */
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

@media (max-width: 991.98px) {
  .card-outer-mobile-flat {
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;
  }
  .card-outer-mobile-flat .card-header {
    border-radius: 12px !important;
    margin-bottom: 12px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: var(--shadow-sm) !important;
  }
  .card-outer-mobile-flat .bg-light-subtle {
    border-radius: 12px !important;
    margin-bottom: 16px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: var(--shadow-sm) !important;
    background-color: #ffffff !important;
  }
}

/* Mobile Cards list styling (SENASICA Cloned Style) */
.producer-mobile-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  border-left: 5px solid #2563eb;
  padding: 20px 20px 16px 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
}

.card-field {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.field-label {
  font-size: 0.72rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin-bottom: 4px;
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

.field-value-curp {
  color: #d63384;
  font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
  font-size: 0.92rem;
  font-weight: 600;
  letter-spacing: 0.3px;
}

.text-primary-link {
  color: #2563eb;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  display: inline-block;
  align-self: flex-start;
}

/* Bleed card footer style from mobile screenshot */
.producer-mobile-footer {
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

.footer-actions-label {
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}

.btn-icon-square-blue {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  border: 1.5px solid #2563eb;
  background-color: #ffffff;
  color: #2563eb;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  cursor: pointer;
  transition: all 0.2s ease;
  padding: 0;
}

.btn-icon-square-blue:active {
  background-color: #eff6ff;
  transform: scale(0.95);
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

.small-desc {
  font-size: 0.72rem;
  color: #94a3b8;
}

.fs-6-5 {
  font-size: 0.85rem !important;
}


.mx-3 {
  margin-left: 1rem !important;
  margin-right: 1rem !important;
}
.text-slate-200 {
  color: #e2e8f0 !important;
}

/* Pagination Footer Cloned Style */
.pagination-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
  padding: 16px 24px;
  background: white;
  border-top: 1px solid #f1f5f9;
}

@media(min-width: 992px) {
  .pagination-container {
    flex-direction: row;
    gap: 0;
  }
}

.pagination-info {
  font-size: 0.85rem;
  color: #64748b;
}

.pagination-buttons {
  display: flex;
  align-items: center;
  gap: 4px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
  padding: 2px;
  background: #f8fafc;
}

.pagination-btn {
  min-width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  color: #2563eb;
  font-weight: 600;
  font-size: 0.85rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pagination-btn:hover:not(:disabled) {
  background-color: #eff6ff;
}

.pagination-btn.active {
  background-color: #2563eb;
  color: white;
  box-shadow: 0 2px 4px rgba(37, 99, 235, 0.15);
}

.pagination-btn:disabled {
  color: #94a3b8;
  cursor: not-allowed;
  opacity: 0.5;
}

/* Premium Modals styling */
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 16px;
}

.modal-card {
  background: white;
  border-radius: 20px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  animation: slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slide-up {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
}

.modal-title {
  font-weight: 700;
  font-size: 1.1rem;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-close-modal {
  background: #f1f5f9;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-close-modal:hover {
  background-color: #e2e8f0;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  flex-grow: 1;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  background: #f8fafc;
}

/* Modal Form Fields */
.form-group-custom {
  margin-bottom: 16px;
}

.form-label-custom {
  display: block;
  font-size: 0.72rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
}

.form-control-custom {
  width: 100%;
  padding: 10px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.92rem;
  color: #1e293b;
  background-color: white;
  transition: all 0.2s ease;
}

.form-control-custom:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-control-custom::placeholder {
  color: #94a3b8;
}

.btn-modal-save {
  background: #2563eb;
  color: white;
  font-weight: 600;
  padding: 10px 20px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.btn-modal-save:hover {
  background: #1d4ed8;
}

.btn-modal-cancel {
  background: white;
  color: #64748b;
  font-weight: 600;
  padding: 10px 20px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.btn-modal-cancel:hover {
  background: #f8fafc;
  color: #334155;
}

</style>
