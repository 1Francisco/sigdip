<template>
  <AppLayout>
    <PullToRefresh @refresh="onRefresh" :loading="refreshing">
      <!-- Top Action Bar (Premium Web Replica) -->
      <div class="welcome-header mb-4 text-start d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
          <h2 class="h4 fw-bold mb-1 text-dark">Médicos Verificadores</h2>
          <p class="text-secondary small mb-0">Administra el personal autorizado para realizar inspecciones</p>
        </div>
        
        <!-- Web Badges -->
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

      <!-- Card Container for Medicos List -->
      <div class="card border-0 shadow-sm p-0 overflow-hidden card-outer-mobile-flat bg-white rounded-4">
        <div class="card-header bg-white p-3 p-md-4 border-bottom border-slate-100">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 w-100">
            <h5 class="mb-0 fw-bold fs-5 text-dark text-center text-md-start w-100 w-md-auto">
              <i class="bi bi-people text-primary me-2"></i>Personal en Campo
            </h5>
            <div class="w-100 w-md-auto">
              <button @click="$router.push('/medicos/nuevo')" class="btn btn-primary btn-sm-custom d-flex align-items-center justify-content-center gap-1.5 px-3 py-2 bg-primary text-white border-0 rounded-3 w-100 w-md-auto">
                <i class="bi bi-plus-lg"></i> Nuevo Médico
              </button>
            </div>
          </div>
        </div>

        <div class="card-body p-0 text-start">
          
          <!-- Empty State when filtered results are 0 -->
          <div v-if="!loading && medicos.length === 0" class="text-center p-5 text-muted">
            <i class="bi bi-people display-6 d-block mb-2 text-muted"></i>
            <p class="mb-0 small text-secondary">No hay médicos registrados en el sistema</p>
          </div>

          <div v-else>
            <!-- 1. TABLE VIEW: Horizontally scrollable on mobile to match screenshot (Hidden on mobile) -->
            <div class="table-responsive d-none d-lg-block">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="ps-4 text-secondary fw-bold text-uppercase fs-7 tracking-wider">Nombre Completo</th>
                    <th class="text-secondary fw-bold text-uppercase fs-7 tracking-wider">Correo de Acceso</th>
                    <th class="text-secondary fw-bold text-uppercase fs-7 tracking-wider">Fecha Regist.</th>
                    <th class="text-center text-secondary fw-bold text-uppercase fs-7 tracking-wider">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="medico in paginatedMedicos" :key="medico.id" class="border-bottom">
                    <td class="ps-4">
                      <div class="d-flex align-items-center">
                        <!-- Icon matching mockup -->
                        <div class="bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 40px; height: 40px; background-color: rgba(37, 99, 235, 0.1);">
                          <i class="bi bi-person-badge fs-5"></i>
                        </div>
                        <div>
                          <div class="fw-bold text-dark fs-6">{{ medico.name }}</div>
                          <small class="text-secondary">Rol: Médico de Campo</small>
                        </div>
                      </div>
                    </td>
                    <td class="text-dark">{{ medico.email }}</td>
                    <td class="text-dark">{{ medico.created_at }}</td>
                    <td>
                      <div class="d-flex justify-content-center gap-2">
                        <button 
                          @click="$router.push('/medicos/editar/' + medico.id)" 
                          class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center gap-1.5 px-3 py-1-5 bg-transparent border-primary text-primary rounded-3" 
                          title="Editar médico"
                        >
                          <i class="bi bi-pencil"></i> Editar
                        </button>
                        <button 
                          @click="deleteMedico(medico)" 
                          class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center gap-1.5 px-3 py-1-5 bg-transparent border-danger text-danger rounded-3" 
                          title="Eliminar médico"
                        >
                          <i class="bi bi-trash"></i> Eliminar
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- 2. MOBILE CARDS VIEW (Mobile only) -->
            <div class="mobile-cards d-lg-none">
              <div class="mobile-cards-grid">
                <article v-for="medico in paginatedMedicos" :key="medico.id" class="medico-mobile-card shadow-sm">
                  <div class="medico-content">
                    <div class="field-block">
                      <span class="field-label">MÉDICO VERIFICADOR</span>
                      <span class="field-value fw-bold text-dark fs-6">{{ medico.name }}</span>
                      <small class="text-secondary d-block mt-0.5">Rol: Médico de Campo</small>
                    </div>

                    <div class="field-block">
                      <span class="field-label">CORREO DE ACCESO</span>
                      <span class="field-value text-dark" style="word-break: break-all;">{{ medico.email }}</span>
                    </div>

                    <div class="field-block">
                      <span class="field-label">FECHA REGISTRO</span>
                      <span class="field-value text-dark">{{ medico.created_at }}</span>
                    </div>
                  </div>

                  <div class="medico-mobile-footer">
                    <span class="footer-actions-label">Acciones</span>
                    <div class="d-flex gap-2">
                      <button 
                        @click="$router.push('/medicos/editar/' + medico.id)" 
                        class="btn-icon-square-blue" 
                        title="Editar médico"
                      >
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button 
                        @click="deleteMedico(medico)" 
                        class="btn-icon-square-red" 
                        title="Eliminar médico"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </div>
                </article>
              </div>
            </div>

            <!-- PAGINATION FOOTER: Custom chevron styles matching mockup -->
            <div class="pagination-footer-custom d-flex justify-content-between align-items-center flex-wrap gap-3 p-4 bg-white">
              <!-- Left: Pagination Info (Desktop only) -->
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
    </PullToRefresh>
  </AppLayout>
</template>

<script>
import AppLayout from '../components/AppLayout.vue';
import PullToRefresh from '../components/PullToRefresh.vue';
import api from '../services/api.js';
import db from '../services/db.js';

export default {
  name: 'MedicosView',
  components: { AppLayout, PullToRefresh },
  data() {
    return {
      userName: '',
      isAdmin: false,
      isOnline: true,
      loading: false,
      refreshing: false,
      medicos: [],
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
      return this.medicos.length;
    },
    startResult() {
      return this.totalResults === 0 ? 0 : ((this.currentPage - 1) * 10) + 1;
    },
    endResult() {
      return Math.min(this.currentPage * 10, this.totalResults);
    },
    paginatedMedicos() {
      const start = (this.currentPage - 1) * 10;
      const end = this.currentPage * 10;
      return this.medicos.slice(start, end);
    }
  },
  async mounted() {
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Administrador Central';
    this.isAdmin = user?.roles && user.roles.includes('Administrador');
    this.isOnline = navigator.onLine;

    // Solo permitir acceso a Administradores
    if (!this.isAdmin) {
      alert('Acceso restringido. Solo administradores pueden gestionar el personal médico.');
      this.$router.push('/dashboard');
      return;
    }

    await this.loadMedicos();

    this._onWindowOnline = () => this.isOnline = true;
    this._onWindowOffline = () => this.isOnline = false;
    window.addEventListener('online', this._onWindowOnline);
    window.addEventListener('offline', this._onWindowOffline);
  },
  beforeUnmount() {
    window.removeEventListener('online', this._onWindowOnline);
    window.removeEventListener('offline', this._onWindowOffline);
  },
  methods: {
    async loadMedicos() {
      this.loading = true;
      this.errorMsg = '';

      // 1. Mostrar datos locales inmediatamente
      try {
        this.medicos = await db.getMedicos();
      } catch (e) {
        // Si no hay datos locales, seguimos con array vacío
      }

      // 2. Intentar actualizar desde servidor en segundo plano
      try {
        const isReachable = await api.checkRealConnectivity();
        if (!isReachable) {
          if (this.medicos.length === 0) {
            this.errorMsg = 'Sin conexión al servidor. No hay datos locales de médicos disponibles.';
          }
          return;
        }

        const res = await api.getMedicos();
        if (res.data) {
          this.medicos = res.data;
          this.currentPage = 1;
          // Guardar en local para próxima vez
          await db.saveMedicos(this.medicos);
          this.errorMsg = '';
        }
      } catch (e) {
        if (this.medicos.length > 0) {
          console.warn('No se pudieron actualizar médicos desde el servidor, mostrando datos locales:', e.message);
        } else {
          this.errorMsg = e.message || 'No se pudieron cargar los médicos verificadores desde el servidor.';
        }
      } finally {
        this.loading = false;
      }
    },
    async onRefresh() {
      this.refreshing = true;
      try {
        await this.loadMedicos();
      } catch (e) {
        console.warn('Refresh error:', e);
      } finally {
        this.refreshing = false;
      }
    },
    async deleteMedico(medico) {
      if (!confirm(`¿Estás seguro de eliminar al médico "${medico.name}"? No podrá volver a iniciar sesión.`)) return;
      this.errorMsg = '';
      this.successMsg = '';
      try {
        await api.deleteMedico(medico.id);
        this.successMsg = `Médico "${medico.name}" eliminado del sistema.`;
        await this.loadMedicos();
      } catch (e) {
        this.errorMsg = e.message || 'No se pudo eliminar al médico.';
      }
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

/* Responsive configurations */
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

  .mobile-cards {
    padding: 0 0.95rem 1rem;
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

  .medico-mobile-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    border-left: 5px solid #2563eb;
    padding: 20px 20px 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
    margin-bottom: 0.95rem;
  }

  .medico-content {
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

  .medico-mobile-footer {
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

  .btn-icon-square-red {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    border: 1.5px solid #ef4444;
    background-color: #ffffff;
    color: #ef4444;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
  }

  .btn-icon-square-red:active {
    background-color: #fee2e2;
    transform: scale(0.95);
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
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
  }

  .btn-icon-square-blue:active {
    background-color: #eff6ff;
    transform: scale(0.95);
  }
}
</style>
