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
      <div class="welcome-header mb-3 text-start d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
        <div>
          <h2 class="h4 fw-bold mb-1 text-dark"><i class="bi bi-folder2-open me-2 text-primary"></i>Mis Descargas</h2>
          <p class="text-secondary small mb-0">Archivos PDF y Excel guardados en tu dispositivo</p>
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

      <!-- Stats Cards Row -->
      <div class="row g-3 mb-4">
        <div class="col-4">
          <div class="dl-stat-card">
            <div class="dl-stat-icon" style="background: linear-gradient(135deg, #eff6ff, #dbeafe);">
              <i class="bi bi-files text-primary fs-4"></i>
            </div>
            <div class="dl-stat-value">{{ files.length }}</div>
            <div class="dl-stat-label">Total</div>
          </div>
        </div>
        <div class="col-4">
          <div class="dl-stat-card">
            <div class="dl-stat-icon" style="background: linear-gradient(135deg, #fef2f2, #fecaca);">
              <i class="bi bi-file-earmark-pdf-fill text-danger fs-4"></i>
            </div>
            <div class="dl-stat-value">{{ pdfCount }}</div>
            <div class="dl-stat-label">PDFs</div>
          </div>
        </div>
        <div class="col-4">
          <div class="dl-stat-card">
            <div class="dl-stat-icon" style="background: linear-gradient(135deg, #ecfdf5, #a7f3d0);">
              <i class="bi bi-file-earmark-spreadsheet-fill text-success fs-4"></i>
            </div>
            <div class="dl-stat-value">{{ excelCount }}</div>
            <div class="dl-stat-label">Excel</div>
          </div>
        </div>
      </div>

      <!-- Notification Alerts -->
      <div v-if="errorMsg" class="alert alert-danger alert-dismissible fade show text-start shadow-sm border-0 d-flex align-items-center gap-2 rounded-3" role="alert">
        <i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i>
        <div class="flex-grow-1">{{ errorMsg }}</div>
        <button type="button" class="btn-close" @click="errorMsg = ''" aria-label="Close"></button>
      </div>

      <div v-if="successMsg" class="alert alert-success alert-dismissible fade show text-start shadow-sm border-0 d-flex align-items-center gap-2 rounded-3" role="alert">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div class="flex-grow-1">{{ successMsg }}</div>
        <button type="button" class="btn-close" @click="successMsg = ''" aria-label="Close"></button>
      </div>

      <!-- Search Bar -->
      <div class="dl-search-wrapper mb-4" v-if="files.length > 0">
        <div class="position-relative">
          <i class="bi bi-search dl-search-icon"></i>
          <input 
            v-model="searchQuery" 
            type="text" 
            class="dl-search-input" 
            placeholder="Buscar archivo..."
          >
          <button v-if="searchQuery" class="dl-search-clear" @click="searchQuery = ''">
            <i class="bi bi-x-circle-fill"></i>
          </button>
        </div>
      </div>

      <!-- Files List -->
      <div class="position-relative">
        <!-- Loader -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <p class="text-muted mt-3 fw-semibold">Escaneando archivos locales...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredFiles.length === 0" class="dl-empty-state">
          <div class="dl-empty-icon-wrapper">
            <i class="bi bi-folder2-open"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">{{ searchQuery ? 'Sin resultados' : 'No hay descargas' }}</h5>
          <p class="text-secondary mb-4 px-2">
            {{ searchQuery ? 'No se encontraron archivos que coincidan con tu búsqueda.' : 'Descarga dictámenes en PDF o la sábana Excel desde la sección de Inspecciones para verlos aquí.' }}
          </p>
          <button 
            v-if="!searchQuery"
            class="btn dl-btn-go-inspections"
            @click="$router.push('/inspecciones')"
          >
            <i class="bi bi-clipboard-check me-2"></i>
            Ir a Dictámenes
          </button>
        </div>

        <!-- File Cards -->
        <div v-else>
          <div 
            v-for="file in filteredFiles" 
            :key="file.name" 
            class="dl-file-card"
            @click="openFile(file)"
          >
            <!-- File type color bar -->
            <div class="dl-file-color-bar" :class="isExcel(file.name) ? 'bg-success' : 'bg-danger'"></div>
            
            <div class="dl-file-body">
              <!-- Top row: icon + info -->
              <div class="d-flex align-items-center gap-3">
                <div class="dl-file-icon" :class="isExcel(file.name) ? 'dl-file-icon-excel' : 'dl-file-icon-pdf'">
                  <i :class="isExcel(file.name) ? 'bi bi-file-earmark-spreadsheet-fill' : 'bi bi-file-earmark-pdf-fill'"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                  <h6 class="dl-file-name" :title="file.name">{{ file.name }}</h6>
                  <div class="dl-file-meta">
                    <span><i class="bi bi-hdd me-1"></i>{{ formatBytes(file.size) }}</span>
                    <span class="dl-file-meta-dot">•</span>
                    <span><i class="bi bi-clock me-1"></i>{{ formatDate(file.mtime) }}</span>
                  </div>
                </div>
              </div>

              <!-- Actions row -->
              <div class="dl-file-actions">
                <span class="dl-actions-label">ACCIONES</span>
                <div class="d-flex gap-2">
                  <button 
                    @click.stop="openFile(file)" 
                    class="dl-action-btn dl-action-open"
                    :title="isPdf(file.name) ? 'Ver PDF' : 'Descargar Excel'"
                  >
                    <i :class="isPdf(file.name) ? 'bi bi-eye-fill' : 'bi bi-box-arrow-up-right'"></i>
                    <span>{{ isPdf(file.name) ? 'Ver' : 'Abrir' }}</span>
                  </button>
                  <button 
                    @click.stop="shareFile(file)" 
                    class="dl-action-btn dl-action-share"
                    title="Compartir"
                  >
                    <i class="bi bi-share-fill"></i>
                    <span>Enviar</span>
                  </button>
                  <button 
                    @click.stop="deleteFile(file)" 
                    class="dl-action-btn dl-action-delete"
                    title="Eliminar"
                  >
                    <i class="bi bi-trash3-fill"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- PDF Preview Modal -->
      <div v-if="previewVisible" class="dl-preview-overlay" @click.self="closePreview">
        <div class="dl-preview-modal">
          <div class="dl-preview-header">
            <div class="d-flex align-items-center gap-2 min-w-0">
              <i class="bi bi-file-earmark-pdf-fill text-danger fs-5"></i>
              <span class="fw-bold text-truncate">{{ previewFileName }}</span>
            </div>
            <div class="d-flex gap-2">
              <a v-if="previewBlobUrl" :href="previewBlobUrl" target="_blank" class="dl-preview-btn-external" title="Abrir en pestaña nueva">
                <i class="bi bi-box-arrow-up-right"></i>
              </a>
              <button class="dl-preview-btn-close" @click="closePreview">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
          </div>
          <div class="dl-preview-body">
            <div v-if="previewLoading" class="d-flex flex-column align-items-center justify-content-center h-100">
              <div class="spinner-border text-primary mb-3" role="status"></div>
              <p class="text-muted">Cargando vista previa...</p>
            </div>
            <iframe 
              v-else-if="previewBlobUrl"
              :src="previewBlobUrl" 
              class="dl-preview-iframe"
            ></iframe>
            <div v-else class="d-flex flex-column align-items-center justify-content-center h-100 text-center p-4">
              <i class="bi bi-exclamation-triangle display-4 text-warning mb-3"></i>
              <p class="text-secondary">No se pudo cargar la vista previa del archivo.</p>
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
import { Filesystem, Directory, Encoding } from '@capacitor/filesystem';
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
      successMsg: '',
      // Preview modal state
      previewVisible: false,
      previewLoading: false,
      previewBlobUrl: null,
      previewFileName: ''
    };
  },
  computed: {
    filteredFiles() {
      if (!this.searchQuery) return this.files;
      const q = this.searchQuery.toLowerCase();
      return this.files.filter(f => f.name.toLowerCase().includes(q));
    },
    pdfCount() {
      return this.files.filter(f => this.isPdf(f.name)).length;
    },
    excelCount() {
      return this.files.filter(f => this.isExcel(f.name)).length;
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
    this.cleanPreviewUrl();
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
    isPdf(filename) {
      return filename.toLowerCase().endsWith('.pdf');
    },
    formatBytes(bytes, decimals = 1) {
      if (!bytes || bytes === 0) return '—';
      const k = 1024;
      const dm = decimals < 0 ? 0 : decimals;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    },
    formatDate(dateVal) {
      if (!dateVal) return '—';
      const d = new Date(dateVal);
      if (isNaN(d.getTime())) return '—';
      return d.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'short',
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

          this.files.sort((a, b) => b.mtime - a.mtime);
        } else {
          // Web browser fallback — show demo data so the view isn't empty during dev
          const mockData = localStorage.getItem('local_downloads_mock');
          if (mockData) {
            this.files = JSON.parse(mockData);
          } else {
            // Seed some demo files for development preview
            this.files = [
              { name: 'dictamenes_pecuarios_5-6-2026.xlsx', size: 245760, mtime: Date.now() - 3600000, isNative: false },
              { name: 'dictamen_DP-2026-001_5-6-2026.pdf', size: 189432, mtime: Date.now() - 7200000, isNative: false },
              { name: 'dictamen_DP-2026-002_4-6-2026.pdf', size: 203145, mtime: Date.now() - 86400000, isNative: false },
            ];
            localStorage.setItem('local_downloads_mock', JSON.stringify(this.files));
          }
          this.files.sort((a, b) => b.mtime - a.mtime);
        }
      } catch (e) {
        console.error('Error loading files:', e);
        this.errorMsg = 'No se pudieron leer los archivos locales: ' + e.message;
      } finally {
        this.loading = false;
      }
    },

    // ---- OPEN / PREVIEW ----
    async openFile(file) {
      this.errorMsg = '';
      this.successMsg = '';

      if (file.isNative) {
        // Native: read file content and create blob URL for preview
        if (this.isPdf(file.name)) {
          this.previewFileName = file.name;
          this.previewVisible = true;
          this.previewLoading = true;
          try {
            const fileData = await Filesystem.readFile({
              path: file.name,
              directory: Directory.Documents
            });
            const byteCharacters = atob(fileData.data);
            const byteNumbers = new Array(byteCharacters.length);
            for (let i = 0; i < byteCharacters.length; i++) {
              byteNumbers[i] = byteCharacters.charCodeAt(i);
            }
            const byteArray = new Uint8Array(byteNumbers);
            const blob = new Blob([byteArray], { type: 'application/pdf' });
            this.cleanPreviewUrl();
            this.previewBlobUrl = URL.createObjectURL(blob);
          } catch (err) {
            console.error('Error reading PDF:', err);
            this.previewBlobUrl = null;
          } finally {
            this.previewLoading = false;
          }
        } else {
          // Excel: open via Share (no in-app preview possible)
          try {
            await Share.share({
              title: file.name,
              url: file.uri,
              dialogTitle: 'Abrir archivo Excel'
            });
          } catch (e) {
            // User cancelled
          }
        }
      } else {
        // Browser fallback — open demo/mock
        if (this.isPdf(file.name)) {
          this.previewFileName = file.name;
          this.previewVisible = true;
          this.previewLoading = false;
          // In browser dev mode, show a placeholder message since there's no real file
          this.previewBlobUrl = null;
        } else {
          alert(`En el dispositivo nativo, se abrirá "${file.name}" con la aplicación de hojas de cálculo instalada.`);
        }
      }
    },

    cleanPreviewUrl() {
      if (this.previewBlobUrl) {
        URL.revokeObjectURL(this.previewBlobUrl);
        this.previewBlobUrl = null;
      }
    },

    closePreview() {
      this.previewVisible = false;
      this.previewFileName = '';
      this.cleanPreviewUrl();
    },

    // ---- SHARE ----
    async shareFile(file) {
      this.errorMsg = '';
      this.successMsg = '';
      try {
        if (file.isNative) {
          await Share.share({
            title: file.name,
            url: file.uri,
            dialogTitle: 'Compartir archivo'
          });
        } else {
          alert(`En el dispositivo nativo, podrás enviar "${file.name}" por WhatsApp, Correo, etc.`);
        }
      } catch (e) {
        if (e.message && !e.message.includes('cancel')) {
          console.error('Error sharing file:', e);
          this.errorMsg = 'No se pudo compartir el archivo: ' + e.message;
        }
      }
    },

    // ---- DELETE ----
    async deleteFile(file) {
      this.errorMsg = '';
      this.successMsg = '';
      if (confirm(`¿Eliminar "${file.name}" del dispositivo?\n\nEsta acción no se puede deshacer.`)) {
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
          this.successMsg = `"${file.name}" eliminado correctamente.`;
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

/* ===== Stats Cards ===== */
.dl-stat-card {
  background: #fff;
  border-radius: 16px;
  padding: 16px 12px;
  text-align: center;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  border: 1px solid #f1f5f9;
  transition: transform 0.2s ease;
}
.dl-stat-card:hover {
  transform: translateY(-2px);
}
.dl-stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 8px;
}
.dl-stat-value {
  font-size: 1.5rem;
  font-weight: 800;
  color: #1e293b;
  line-height: 1;
}
.dl-stat-label {
  font-size: 0.7rem;
  color: #94a3b8;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 4px;
}

/* ===== Search ===== */
.dl-search-wrapper {
  position: relative;
}
.dl-search-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 0.9rem;
  z-index: 2;
}
.dl-search-input {
  width: 100%;
  padding: 12px 44px 12px 44px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  font-size: 0.9rem;
  background: #fff;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  transition: border-color 0.2s, box-shadow 0.2s;
  font-family: inherit;
  outline: none;
}
.dl-search-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}
.dl-search-input::placeholder {
  color: #cbd5e1;
}
.dl-search-clear {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
  font-size: 1rem;
  z-index: 2;
}
.dl-search-clear:hover { color: #64748b; }

/* ===== Empty State ===== */
.dl-empty-state {
  text-align: center;
  padding: 48px 20px;
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  border: 1px solid #f1f5f9;
}
.dl-empty-icon-wrapper {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  font-size: 2rem;
  color: #2563eb;
}
.dl-btn-go-inspections {
  display: inline-flex;
  align-items: center;
  padding: 12px 28px;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
  border: none;
  border-radius: 14px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(37,99,235,0.3);
  transition: transform 0.2s, box-shadow 0.2s;
  font-family: inherit;
}
.dl-btn-go-inspections:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(37,99,235,0.4);
}

/* ===== File Card ===== */
.dl-file-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  border: 1px solid #f1f5f9;
  overflow: hidden;
  margin-bottom: 12px;
  display: flex;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.dl-file-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0,0,0,0.08);
}
.dl-file-card:active {
  transform: scale(0.99);
}
.dl-file-color-bar {
  width: 5px;
  flex-shrink: 0;
}
.dl-file-body {
  flex: 1;
  padding: 16px;
  min-width: 0;
}
.dl-file-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  flex-shrink: 0;
}
.dl-file-icon-pdf {
  background: linear-gradient(135deg, #fef2f2, #fecaca);
  color: #dc2626;
}
.dl-file-icon-excel {
  background: linear-gradient(135deg, #ecfdf5, #a7f3d0);
  color: #059669;
}
.dl-file-name {
  font-weight: 700;
  font-size: 0.88rem;
  color: #1e293b;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dl-file-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.72rem;
  color: #94a3b8;
  font-weight: 500;
}
.dl-file-meta-dot {
  color: #cbd5e1;
}

/* ===== File Actions ===== */
.dl-file-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid #f1f5f9;
}
.dl-actions-label {
  font-size: 0.65rem;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: 1px;
}
.dl-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 8px 14px;
  border-radius: 10px;
  font-size: 0.75rem;
  font-weight: 600;
  border: 1.5px solid;
  cursor: pointer;
  transition: all 0.2s ease;
  background: transparent;
  font-family: inherit;
}
.dl-action-open {
  border-color: #2563eb;
  color: #2563eb;
  background: #eff6ff;
}
.dl-action-open:hover {
  background: #2563eb;
  color: #fff;
}
.dl-action-share {
  border-color: #059669;
  color: #059669;
  background: #ecfdf5;
}
.dl-action-share:hover {
  background: #059669;
  color: #fff;
}
.dl-action-delete {
  border-color: #ef4444;
  color: #ef4444;
  background: #fef2f2;
  padding: 8px 10px;
}
.dl-action-delete:hover {
  background: #ef4444;
  color: #fff;
}

/* ===== PDF Preview Modal ===== */
.dl-preview-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.65);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  animation: dlFadeIn 0.2s ease;
}
@keyframes dlFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
.dl-preview-modal {
  width: 100%;
  max-width: 900px;
  height: 85vh;
  background: #fff;
  border-radius: 20px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  animation: dlSlideUp 0.25s ease;
}
@keyframes dlSlideUp {
  from { transform: translateY(30px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
.dl-preview-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  gap: 12px;
}
.dl-preview-btn-external {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eff6ff;
  color: #2563eb;
  border: none;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.15s;
  text-decoration: none;
}
.dl-preview-btn-external:hover { background: #dbeafe; }
.dl-preview-btn-close {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fef2f2;
  color: #ef4444;
  border: none;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.15s;
}
.dl-preview-btn-close:hover { background: #fecaca; }
.dl-preview-body {
  flex: 1;
  overflow: hidden;
  background: #f1f5f9;
}
.dl-preview-iframe {
  width: 100%;
  height: 100%;
  border: none;
}
</style>
