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
          <a class="nav-link active" @click.prevent="sidebarActive = false">
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
          <a class="nav-link active" @click.prevent="sidebarActive = false">
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
      
      <!-- Top Action Bar -->
      <div class="welcome-header mb-4 text-start d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
        <div>
          <h2 class="h4 fw-bold mb-1 text-dark">Descargas</h2>
          <p class="text-secondary small mb-0">Archivos locales descargados en el dispositivo (PDFs y Excel)</p>
        </div>
        
        <!-- Web Badges -->
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

      <!-- Notification Alerts -->
      <div v-if="errorMsg" class="alert alert-danger alert-dismissible fade show text-start shadow-sm border-0 d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i>
        <div>{{ errorMsg }}</div>
        <button type="button" class="btn-close" @click="errorMsg = ''" aria-label="Close"></button>
      </div>

      <div v-if="successMsg" class="alert alert-success alert-dismissible fade show text-start shadow-sm border-0 d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div>{{ successMsg }}</div>
        <button type="button" class="btn-close" @click="successMsg = ''" aria-label="Close"></button>
      </div>

      <!-- Search and filters -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="p-3">
          <div class="input-group search-input-group">
            <span class="input-group-text bg-white border-end-0 border-slate-200">
              <i class="bi bi-search text-secondary"></i>
            </span>
            <input 
              v-model="searchQuery" 
              type="text" 
              class="form-control border-start-0 border-slate-200 ps-1 py-2" 
              placeholder="Buscar por nombre de archivo..."
            >
            <button v-if="searchQuery" class="btn btn-link text-secondary border-end border-slate-200" @click="searchQuery = ''" style="position: absolute; right: 10px; top: 5px; z-index: 10;">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Files List -->
      <div class="position-relative">
        <!-- Loader -->
        <div v-if="loading" class="text-center p-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <p class="text-muted mt-2">Cargando archivos locales...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredFiles.length === 0" class="text-center p-5 card border-0 shadow-sm">
          <div class="my-4">
            <i class="bi bi-download display-3 text-secondary opacity-50"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">No se encontraron descargas</h5>
          <p class="text-secondary px-3 mb-4">
            {{ searchQuery ? 'No hay descargas que coincidan con la búsqueda.' : 'Aún no has descargado ningún dictamen PDF o sábana Excel en este dispositivo.' }}
          </p>
          <button 
            v-if="!searchQuery"
            class="btn btn-primary px-4 py-2 mx-auto rounded-pill d-flex align-items-center gap-2"
            @click="$router.push('/inspecciones')"
            style="background: #2563eb; width: fit-content;"
          >
            <i class="bi bi-clipboard-check"></i>
            Ir a Dictámenes para descargar
          </button>
        </div>

        <!-- Cards View -->
        <div v-else class="row g-3">
          <div 
            v-for="file in filteredFiles" 
            :key="file.name" 
            class="col-12"
          >
            <div class="card p-3 border-0 shadow-sm text-start hover-lift position-relative overflow-hidden mb-0">
              <!-- Accent border left -->
              <div 
                class="position-absolute start-0 top-0 bottom-0" 
                :style="{ width: '4px', backgroundColor: isExcel(file.name) ? '#10b981' : '#ef4444' }"
              ></div>
              
              <div class="d-flex align-items-center gap-3">
                <!-- File Icon -->
                <div 
                  class="rounded-3 p-3 d-flex align-items-center justify-content-center"
                  :style="{ backgroundColor: isExcel(file.name) ? '#eff6ff' : '#fee2e2', color: isExcel(file.name) ? '#10b981' : '#ef4444' }"
                  style="width: 50px; height: 50px; flex-shrink: 0;"
                >
                  <i :class="isExcel(file.name) ? 'bi bi-file-earmark-spreadsheet-fill fs-3' : 'bi bi-file-earmark-pdf-fill fs-3'"></i>
                </div>

                <!-- Info -->
                <div class="flex-grow-1 min-w-0">
                  <h6 class="fw-bold text-dark text-truncate mb-1" :title="file.name">
                    {{ file.name }}
                  </h6>
                  <div class="d-flex align-items-center gap-2 text-secondary small">
                    <span>{{ formatBytes(file.size) }}</span>
                    <span>•</span>
                    <span>{{ formatDate(file.mtime) }}</span>
                  </div>
                </div>
              </div>

              <!-- Action Buttons footer style -->
              <div class="producer-mobile-footer d-flex align-items-center justify-content-between mt-3 pt-3 border-top border-slate-100">
                <div class="footer-actions-label fw-bold text-secondary mb-0">ACCIONES</div>
                <div class="d-flex gap-2">
                  <button 
                    @click="shareFile(file)" 
                    class="btn btn-sm d-flex align-items-center justify-content-center bg-transparent border text-primary" 
                    :class="isExcel(file.name) ? 'border-success text-success' : 'border-primary text-primary'"
                    title="Compartir o Abrir"
                    style="width: 44px; height: 44px; border-radius: 12px;"
                  >
                    <i class="bi bi-share"></i>
                  </button>
                  <button 
                    @click="deleteFile(file)" 
                    class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center bg-transparent border-danger text-danger" 
                    title="Eliminar"
                    style="width: 44px; height: 44px; border-radius: 12px;"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
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
      <a class="bottom-nav-link" @click.prevent="$router.push('/productores')">
        <i class="bi bi-people"></i>
        <span>Productores</span>
      </a>
      <a class="bottom-nav-link" @click.prevent="$router.push('/predios')">
        <i class="bi bi-house-door"></i>
        <span>Predios</span>
      </a>
      <a class="bottom-nav-link" @click.prevent="$router.push('/inspecciones')">
        <i class="bi bi-clipboard-check"></i>
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
import { Filesystem, Directory } from '@capacitor/filesystem';
import { Share } from '@capacitor/share';

export default {
  name: 'DescargasView',
  data() {
    return {
      userName: '',
      isAdmin: false,
      isOnline: true,
      sidebarActive: false,
      networkListener: null,
      loading: false,
      searchQuery: '',
      files: [],
      errorMsg: '',
      successMsg: ''
    };
  },
  computed: {
    filteredFiles() {
      if (!this.searchQuery) return this.files;
      const q = this.searchQuery.toLowerCase();
      return this.files.filter(f => f.name.toLowerCase().includes(q));
    }
  },
  async mounted() {
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Administrador Central';
    this.isAdmin = user?.roles && user.roles.includes('Administrador');
    this.isOnline = navigator.onLine;

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

    await this.loadFiles();
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
        // Silenciar
      }
      localStorage.removeItem('sigdip_token');
      localStorage.removeItem('sigdip_user');
      sessionStorage.clear();
      this.$router.push('/login');
    },
    isExcel(filename) {
      return filename.toLowerCase().endsWith('.xlsx');
    },
    formatBytes(bytes, decimals = 2) {
      if (!bytes || bytes === 0) return '0 Bytes';
      const k = 1024;
      const dm = decimals < 0 ? 0 : decimals;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    },
    formatDate(dateVal) {
      if (!dateVal) return '-';
      const d = new Date(dateVal);
      if (isNaN(d.getTime())) return '-';
      return d.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },
    async loadFiles() {
      this.loading = true;
      this.errorMsg = '';
      try {
        if (window.Capacitor && window.Capacitor.isNativePlatform()) {
          const result = await Filesystem.readdir({
            path: '',
            directory: Directory.Documents
          });
          
          this.files = result.files.map(file => {
            const isObject = typeof file === 'object' && file !== null;
            const name = isObject ? file.name : file;
            
            const lowerName = name.toLowerCase();
            if (!lowerName.endsWith('.xlsx') && !lowerName.endsWith('.pdf')) {
              return null;
            }
            
            return {
              name: name,
              size: isObject ? file.size : 0,
              mtime: isObject ? file.mtime : Date.now(),
              uri: isObject ? file.uri : '',
              isNative: true
            };
          }).filter(f => f !== null);

          // Sort by mtime descending (newest first)
          this.files.sort((a, b) => b.mtime - a.mtime);
        } else {
          // Web browser fallback
          const mockData = localStorage.getItem('local_downloads_mock');
          this.files = mockData ? JSON.parse(mockData) : [];
          // Sort by date/mtime descending
          this.files.sort((a, b) => b.mtime - a.mtime);
        }
      } catch (e) {
        console.error('Error loading files:', e);
        this.errorMsg = 'No se pudieron leer los archivos locales: ' + e.message;
      } finally {
        this.loading = false;
      }
    },
    async shareFile(file) {
      this.errorMsg = '';
      this.successMsg = '';
      try {
        if (file.isNative) {
          await Share.share({
            title: file.name,
            url: file.uri,
            dialogTitle: 'Abrir / Compartir archivo'
          });
        } else {
          // Trigger browser download of mock data
          if (file.dataUrl) {
            const link = document.createElement('a');
            link.href = file.dataUrl;
            link.download = file.name;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            this.successMsg = `Archivo "${file.name}" descargado en el navegador.`;
          } else {
            alert('Datos no disponibles para este simulacro.');
          }
        }
      } catch (e) {
        console.error('Error sharing file:', e);
        this.errorMsg = 'No se pudo compartir/abrir el archivo: ' + e.message;
      }
    },
    async deleteFile(file) {
      this.errorMsg = '';
      this.successMsg = '';
      if (confirm(`¿Estás seguro de que deseas eliminar el archivo "${file.name}" de este dispositivo?`)) {
        try {
          if (file.isNative) {
            await Filesystem.deleteFile({
              path: file.name,
              directory: Directory.Documents
            });
          } else {
            let mockFiles = JSON.parse(localStorage.getItem('local_downloads_mock') || '[]');
            mockFiles = mockFiles.filter(f => f.name !== file.name);
            localStorage.setItem('local_downloads_mock', JSON.stringify(mockFiles));
          }
          this.successMsg = `Archivo "${file.name}" eliminado con éxito.`;
          await this.loadFiles();
        } catch (e) {
          console.error('Error deleting file:', e);
          this.errorMsg = 'No se pudo eliminar el archivo: ' + e.message;
        }
      }
    }
  }
};
</script>

<style scoped>
.app-container {
  min-height: 100vh;
}
.bg-light-page {
  background-color: var(--bg-primary);
}
.hover-lift {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md) !important;
}
.search-input-group .input-group-text,
.search-input-group .form-control {
  border-color: #e2e8f0;
}
.search-input-group .form-control:focus {
  box-shadow: none;
  border-color: var(--color-primary);
}
.producer-mobile-footer {
  border-top: 1px solid #e2e8f0;
}
.footer-actions-label {
  font-size: 0.72rem;
  letter-spacing: 0.8px;
}
</style>
