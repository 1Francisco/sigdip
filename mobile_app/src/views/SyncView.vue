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
            <i class="bi bi-grid-1x2"></i> Dashboard
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
            <i class="bi bi-grid-1x2"></i> Dashboard
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
          <a class="nav-link" @click.prevent="$router.push('/inspecciones')">
            <i class="bi bi-clipboard-check"></i> Inspecciones
          </a>
          <a class="nav-link" @click.prevent="$router.push('/inspeccion')">
            <i class="bi bi-file-earmark-plus"></i> Nuevo Dictamen
          </a>
          <a class="nav-link" @click.prevent="$router.push('/descargas')">
            <i class="bi bi-download"></i> Descargas
          </a>
          <a class="nav-link active" @click.prevent="sidebarActive = false">
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
        class="badge rounded-pill px-2-5 py-1-5 d-flex align-items-center gap-1.5 fw-semibold me-2 border connectivity-badge shadow-sm"
        :class="isOnline ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle'"
      >
        <span class="pulse-dot" :class="isOnline ? 'bg-success' : 'bg-danger'"></span>
        <span class="badge-text">{{ isOnline ? 'Online' : 'Offline' }}</span>
      </div>

      <div class="avatar-circle rounded-circle" @click="sidebarActive = true">
        <i class="bi bi-person"></i>
      </div>
    </header>

    <main class="app-content main-content bg-light">
      <!-- Welcome Header -->
      <div class="welcome-header d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
          <h1 class="welcome-title text-primary fs-3 fw-bold mb-1">Sincronización</h1>
          <p class="welcome-subtitle text-secondary">Gestión de datos offline y catálogos locales</p>
        </div>
      </div>
      <!-- Status de conexión dinámico (Clon de la web) -->
      <div 
        class="card shadow-sm border-0 mb-4 p-4 rounded-4" 
        :style="{ borderLeft: isOnline ? '4px solid var(--color-success)' : '4px solid var(--color-danger)' }"
      >
        <div class="d-flex align-items-center gap-3">
          <div 
            class="rounded-circle d-flex align-items-center justify-content-center"
            style="width: 48px; height: 48px;"
            :class="isOnline ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'"
          >
            <i class="bi fs-3" :class="isOnline ? 'bi-cloud-check-fill' : 'bi-cloud-slash-fill'"></i>
          </div>
          <div>
            <div class="fw-bold fs-6 text-primary">{{ isOnline ? 'Dispositivo Conectado' : 'Modo Sin Conexión (Offline)' }}</div>
            <div class="text-secondary small">
              {{ isOnline ? 'La sincronización y descarga están habilitadas y listas.' : 'Trabajarás con tus catálogos locales y los datos se encolarán.' }}
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card shadow-sm">
          <div class="stat-icon-wrapper text-primary bg-primary-soft">
            <i class="bi bi-house-fill"></i>
          </div>
          <div class="stat-value">{{ prediosCount }}</div>
          <div class="stat-label">Ranchos</div>
        </div>

        <div class="stat-card shadow-sm">
          <div class="stat-icon-wrapper text-info bg-info-subtle">
            <i class="bi bi-calendar-event-fill"></i>
          </div>
          <div class="stat-value">{{ visitasCount }}</div>
          <div class="stat-label">Visitas</div>
        </div>

        <div class="stat-card shadow-sm">
          <div class="stat-icon-wrapper text-warning bg-warning-subtle">
            <i class="bi bi-cloud-arrow-up-fill"></i>
          </div>
          <div class="stat-value" :class="{ 'text-danger fw-bold': pendientes > 0 }">{{ pendientes }}</div>
          <div class="stat-label">Por subir</div>
        </div>

        <div class="stat-card shadow-sm">
          <div class="stat-icon-wrapper text-secondary bg-light">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-value small-value">{{ lastSyncText }}</div>
          <div class="stat-label">Último Sync</div>
        </div>
      </div>

      <!-- Botones de sincronización -->
      <div class="action-sync-buttons mb-4 d-flex flex-column gap-2">
        <button 
          class="btn btn-primary btn-lg w-100 shadow-sm d-flex align-items-center justify-content-center gap-2" 
          @click="downloadData" 
          :disabled="downloading || !isOnline"
        >
          <span v-if="downloading" class="loader"></span>
          <span v-else class="d-flex align-items-center gap-2">
            <i class="bi bi-cloud-arrow-down-fill"></i> Descargar Catálogos del Día
          </span>
        </button>

        <!-- Botón subir -->
        <button 
          class="btn btn-accent btn-lg w-100 shadow-sm d-flex align-items-center justify-content-center gap-2" 
          @click="uploadData" 
          :disabled="uploading || !isOnline || pendientes === 0"
        >
          <span v-if="uploading" class="loader"></span>
          <span v-else class="d-flex align-items-center gap-2">
            <i class="bi bi-cloud-arrow-up-fill"></i> Subir Dictámenes Pendientes ({{ pendientes }})
          </span>
        </button>
      </div>

      <!-- Resultados / Mensajes -->
      <div v-if="resultado" class="card shadow-sm border-0 border-start border-success border-4 p-4 rounded-4 mt-3 bg-white text-start">
        <div class="d-flex align-items-center gap-2 mb-1 text-success fw-bold">
          <i class="bi bi-check-circle-fill"></i> Sincronización Completada
        </div>
        <div class="text-secondary small">{{ resultado }}</div>
      </div>

      <div v-if="errorMsg" class="card shadow-sm border-0 border-start border-danger border-4 p-4 rounded-4 mt-3 bg-white text-start">
        <div class="d-flex align-items-center gap-2 mb-1 text-danger fw-bold">
          <i class="bi bi-exclamation-octagon-fill"></i> Error en Operación
        </div>
        <div class="text-secondary small">{{ errorMsg }}</div>
      </div>

      <!-- Borrar datos locales -->
      <div class="text-center mt-5 mb-4">
        <p class="text-muted small px-3">
          Solo utiliza este botón en caso de problemas técnicos extremos. Al limpiar datos locales se borrarán los ranchos cacheados y borradores locales.
        </p>
        <button class="btn btn-danger w-auto px-4 py-2 mt-1 shadow-sm" @click="clearLocalData">
          <i class="bi bi-trash-fill me-1"></i> Limpiar Caché Local
        </button>
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
  </div>
</template>

<script>
import api from '../services/api.js';
import db from '../services/db.js';

export default {
  name: 'SyncView',
  data() {
    return {
      sidebarActive: false,
      isAdmin: false,
      isOnline: navigator.onLine,
      prediosCount: 0,
      visitasCount: 0,
      pendientes: 0,
      lastSyncText: 'Nunca',
      downloading: false,
      uploading: false,
      resultado: '',
      errorMsg: ''
    };
  },
  async mounted() {
    window.addEventListener('online', () => this.isOnline = true);
    window.addEventListener('offline', () => this.isOnline = false);

    // Escuchar el evento de sincronización de fondo para actualizar las estadísticas en tiempo real
    this._syncListener = async (e) => {
      await this.refreshStats();
      this.resultado = `Sincronizados ${e.detail.procesados} dictámenes automáticamente en segundo plano.`;
    };
    window.addEventListener('sigdip-sync-complete', this._syncListener);

    const user = api.getCurrentUser();
    this.isAdmin = user?.roles && user.roles.includes('Administrador');

    await this.refreshStats();
  },
  unmounted() {
    if (this._syncListener) {
      window.removeEventListener('sigdip-sync-complete', this._syncListener);
    }
  },
  methods: {
    async refreshStats() {
      const predios = await db.getPredios();
      this.prediosCount = predios.length;
      const visitas = await db.getVisitas();
      this.visitasCount = visitas.length;
      this.pendientes = await db.countPendientes();
      const sync = await db.getLastSync();
      if (sync) {
        this.lastSyncText = new Date(sync).toLocaleDateString('es-MX', {
          day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
        });
      }
    },

    async downloadData() {
      this.downloading = true;
      this.resultado = '';
      this.errorMsg = '';
      try {
        const res = await api.downloadCatalogos();
        await db.savePredios(res.data.predios);
        await db.saveVisitas(res.data.visitas);
        await db.setLastSync();
        this.resultado = `Descargados ${res.data.predios.length} ranchos y ${res.data.visitas.length} visitas correctamente en local.`;
        await this.refreshStats();
      } catch (err) {
        this.errorMsg = err.message || 'No se pudo establecer conexión con el servidor de CEFPPENAY.';
      } finally {
        this.downloading = false;
      }
    },

    async uploadData() {
      this.uploading = true;
      this.resultado = '';
      this.errorMsg = '';
      try {
        const inspecciones = await db.getInspeccionesPendientes();
        const res = await api.uploadInspecciones(inspecciones);
        if (res.procesados && res.procesados.length) {
          await db.clearInspeccionesSincronizadas(res.procesados);
        }
        this.resultado = `Sincronizados ${res.procesados.length} dictámenes exitosamente.`;
        if (res.errores && res.errores.length) {
          this.resultado += ` (${res.errores.length} con errores de validación)`;
        }
        await this.refreshStats();
      } catch (err) {
        this.errorMsg = err.message || 'Error al conectar con la base de datos central.';
      } finally {
        this.uploading = false;
      }
    },

    async clearLocalData() {
      if (confirm('🚨 ATENCIÓN: ¿Seguro que deseas borrar todos los datos locales?\nLos dictámenes creados en offline que NO estén sincronizados se perderán permanentemente.')) {
        await db.clearAll();
        await this.refreshStats();
        this.resultado = 'Caché y catálogos locales eliminados con éxito.';
      }
    },
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
    }
  }
};
</script>

<style scoped>
.app-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

@media (min-width: 992px) {
  .mobile-header {
    display: none !important;
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
}

/* Status Conexión colors */
.bg-success-subtle {
  background-color: #d1fae5 !important;
}

.bg-danger-subtle {
  background-color: #fee2e2 !important;
}

.bg-info-subtle {
  background-color: #e0f2fe !important;
}

.text-info {
  color: var(--color-info) !important;
}

/* Stat Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  border: 1px solid #f1f5f9;
}

.stat-icon-wrapper {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  margin-bottom: 12px;
}

.stat-value {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
}

.stat-value.small-value {
  font-size: 0.8rem;
  font-weight: 700;
  word-break: break-all;
  height: 34px;
  display: flex;
  align-items: center;
  text-align: left;
}

.stat-label {
  font-size: 0.68rem;
  color: var(--text-secondary);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.2px;
  margin-top: 4px;
}

.action-sync-buttons .btn {
  padding: 15px;
  font-size: 0.95rem;
  border-radius: 12px;
  font-weight: 600;
}


.loader {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
