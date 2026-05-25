<template>
  <div class="app-container">
    <header class="app-header">
      <div>
        <h1>SIGDIP Móvil</h1>
        <div class="subtitle">{{ userName }}</div>
      </div>
      <button @click="doLogout" style="background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;">🚪</button>
    </header>

    <main class="app-content">
      <!-- Botón principal: Escanear -->
      <div class="scan-hero">
        <button class="scan-btn-circle" @click="$router.push('/scan')">
          <span class="icon">📷</span>
          <span class="label">Escanear</span>
        </button>
        <p style="color: var(--text-secondary); font-size: 0.8rem; text-align: center;">
          Escanea el código de barras del arete SINIIGA
        </p>
      </div>

      <!-- Estadísticas rápidas -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-value">{{ pendientes }}</div>
          <div class="stat-label">Pendientes de Sync</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ visitasCount }}</div>
          <div class="stat-label">Visitas del Día</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ prediosCount }}</div>
          <div class="stat-label">Ranchos Cargados</div>
        </div>
        <div class="stat-card">
          <div class="stat-value" :style="{ color: lastSync ? 'var(--color-success)' : 'var(--color-danger)' }">
            {{ lastSync ? '✔' : '✘' }}
          </div>
          <div class="stat-label">{{ lastSync || 'Sin sincronizar' }}</div>
        </div>
      </div>

      <!-- Acciones rápidas -->
      <div class="section-title">Acciones Rápidas</div>
      <div style="display: flex; flex-direction: column; gap: 12px;">
        <button class="btn btn-primary" @click="$router.push('/inspeccion')">
          <span class="btn-icon">📝</span> Nuevo Dictamen
        </button>
        <button class="btn btn-accent" @click="$router.push('/sync')">
          <span class="btn-icon">🔄</span> Sincronizar Datos
        </button>
      </div>

      <!-- Visitas pendientes -->
      <div v-if="visitas.length" style="margin-top: 24px;">
        <div class="section-title">Mis Visitas Pendientes</div>
        <div
          v-for="visita in visitas"
          :key="visita.id"
          class="list-item"
          @click="startInspeccion(visita)"
        >
          <div class="list-item-icon" style="background: #E8F5E9; color: var(--color-primary);">🏠</div>
          <div class="list-item-content">
            <div class="list-item-title">{{ visita.predio?.nombre || 'Rancho ' + visita.predio_id }}</div>
            <div class="list-item-subtitle">📅 {{ visita.fecha_programada }}</div>
          </div>
          <span class="badge badge-warning">Pendiente</span>
        </div>
      </div>
    </main>

    <!-- Bottom Nav -->
    <nav class="bottom-nav">
      <a class="active" @click.prevent>
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
      <a @click.prevent="$router.push('/sync')">
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
  name: 'DashboardView',
  data() {
    return {
      userName: '',
      pendientes: 0,
      visitasCount: 0,
      prediosCount: 0,
      lastSync: '',
      visitas: []
    };
  },
  async mounted() {
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Médico';

    this.pendientes = await db.countPendientes();
    this.visitas = await db.getVisitas();
    this.visitasCount = this.visitas.length;
    const predios = await db.getPredios();
    this.prediosCount = predios.length;

    const sync = await db.getLastSync();
    if (sync) {
      const d = new Date(sync);
      this.lastSync = d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
    }
  },
  methods: {
    startInspeccion(visita) {
      this.$router.push(`/inspeccion/${visita.predio_id}?visita_id=${visita.id}`);
    },
    async doLogout() {
      try { await api.logout(); } catch (e) { /* offline */ }
      localStorage.clear();
      this.$router.push('/login');
    }
  }
};
</script>
