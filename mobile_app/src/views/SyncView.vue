<template>
  <div class="app-container">
    <header class="app-header">
      <div>
        <h1>🔄 Sincronización</h1>
        <div class="subtitle">Gestión de datos offline</div>
      </div>
      <button @click="$router.push('/dashboard')" style="background:none;border:none;color:#fff;font-size:1.4rem;cursor:pointer;">✕</button>
    </header>

    <main class="app-content">
      <!-- Status de conexión -->
      <div class="card" :style="{ borderLeft: '4px solid ' + (isOnline ? 'var(--color-success)' : 'var(--color-danger)') }">
        <div style="display: flex; align-items: center; gap: 12px;">
          <span style="font-size: 2rem;">{{ isOnline ? '🟢' : '🔴' }}</span>
          <div>
            <div style="font-weight: 700; font-size: 0.9rem;">{{ isOnline ? 'Conectado' : 'Sin Conexión' }}</div>
            <div style="font-size: 0.75rem; color: var(--text-secondary);">{{ isOnline ? 'Listo para sincronizar' : 'Los datos se guardarán localmente' }}</div>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-value">{{ prediosCount }}</div>
          <div class="stat-label">Ranchos</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ visitasCount }}</div>
          <div class="stat-label">Visitas</div>
        </div>
        <div class="stat-card">
          <div class="stat-value" :style="{ color: pendientes > 0 ? 'var(--color-accent)' : 'var(--color-success)' }">{{ pendientes }}</div>
          <div class="stat-label">Pendientes de subir</div>
        </div>
        <div class="stat-card">
          <div class="stat-value" style="font-size: 0.85rem;">{{ lastSyncText }}</div>
          <div class="stat-label">Última sync</div>
        </div>
      </div>

      <!-- Botón descargar -->
      <button class="btn btn-primary btn-lg" @click="downloadData" :disabled="downloading || !isOnline" style="margin-bottom: 12px;">
        <span v-if="downloading" class="loader"></span>
        <span v-else>
          <span class="btn-icon">⬇️</span> Descargar Catálogos
        </span>
      </button>

      <!-- Botón subir -->
      <button class="btn btn-accent btn-lg" @click="uploadData" :disabled="uploading || !isOnline || pendientes === 0" style="margin-bottom: 12px;">
        <span v-if="uploading" class="loader"></span>
        <span v-else>
          <span class="btn-icon">⬆️</span> Subir Dictámenes ({{ pendientes }})
        </span>
      </button>

      <!-- Resultado -->
      <div v-if="resultado" class="card" style="margin-top: 12px; border-left: 4px solid var(--color-success);">
        <div style="font-weight: 700; font-size: 0.85rem; margin-bottom: 4px;">✅ Resultado</div>
        <div style="font-size: 0.8rem; color: var(--text-secondary);">{{ resultado }}</div>
      </div>

      <div v-if="errorMsg" class="card" style="margin-top: 12px; border-left: 4px solid var(--color-danger);">
        <div style="font-weight: 700; font-size: 0.85rem; margin-bottom: 4px;">❌ Error</div>
        <div style="font-size: 0.8rem; color: var(--text-secondary);">{{ errorMsg }}</div>
      </div>

      <!-- Borrar datos locales -->
      <div style="margin-top: 32px;">
        <button class="btn btn-danger" @click="clearLocalData" style="opacity: 0.7;">
          🗑️ Borrar Datos Locales
        </button>
      </div>
    </main>

    <nav class="bottom-nav">
      <a @click.prevent="$router.push('/dashboard')">
        <span class="nav-icon">🏠</span>
        Inicio
      </a>
      <a @click.prevent="$router.push('/scan')">
        <span class="nav-icon">📷</span>
        Escanear
      </a>
      <a @click.prevent="$router.push('/inspeccion')">
        <span class="nav-icon">📝</span>
        Dictamen
      </a>
      <a class="active" @click.prevent>
        <span class="nav-icon">🔄</span>
        Sync
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
        this.resultado = `Descargados ${res.data.predios.length} ranchos y ${res.data.visitas.length} visitas.`;
        await this.refreshStats();
      } catch (err) {
        this.errorMsg = err.message;
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
          this.resultado += ` (${res.errores.length} con errores)`;
        }
        await this.refreshStats();
      } catch (err) {
        this.errorMsg = err.message;
      } finally {
        this.uploading = false;
      }
    },

    async clearLocalData() {
      if (confirm('¿Seguro que deseas borrar todos los datos locales? Los dictámenes no sincronizados se perderán.')) {
        await db.clearAll();
        await this.refreshStats();
        this.resultado = 'Datos locales eliminados.';
      }
    }
  }
};
</script>
