<template>
  <div class="app-container bg-light-page">
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
          <a class="nav-link" :class="{ active: isActive('/dashboard') }" @click.prevent="navigate('/dashboard')">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
          </a>
          <a class="nav-link" :class="{ active: isActive('/productores') }" @click.prevent="navigate('/productores')">
            <i class="bi bi-people"></i> Productores
          </a>
          <a class="nav-link" :class="{ active: isActive('/predios') }" @click.prevent="navigate('/predios')">
            <i class="bi bi-house-door"></i> Predios
          </a>
          <a class="nav-link" :class="{ active: isActive('/animales') }" @click.prevent="navigate('/animales')">
            <i class="bi bi-gender-male"></i> Animales
          </a>
          <a class="nav-link" :class="{ active: isActive('/aretes-censo') }" @click.prevent="navigate('/aretes-censo')">
            <i class="bi bi-upc-scan"></i> Aretes del Censo
          </a>
          <a class="nav-link" :class="{ active: isActive('/inspecciones') || isActive('/inspeccion') }" @click.prevent="navigate('/inspecciones')">
            <i class="bi bi-clipboard-check"></i> Inspecciones
          </a>
          <a class="nav-link" :class="{ active: isActive('/visitas') }" @click.prevent="navigate('/visitas')">
            <i class="bi bi-calendar-event"></i> Agenda / Visitas
          </a>
          <a class="nav-link" :class="{ active: isActive('/medicos') }" @click.prevent="navigate('/medicos')">
            <i class="bi bi-person-badge"></i> Médicos
          </a>
          <a class="nav-link" @click.prevent="alertWebOnly('Importar Excel')">
            <i class="bi bi-file-earmark-arrow-up"></i> Importar Excel
          </a>
          <hr class="mx-3 text-slate-200">
          <a class="nav-link" :class="{ active: isActive('/descargas') }" @click.prevent="navigate('/descargas')">
            <i class="bi bi-download"></i> Descargas
          </a>
          <a class="nav-link" @click.prevent="navigate('/inspecciones?downloadExcel=true')">
            <i class="bi bi-file-earmark-excel"></i> Sábana Excel
          </a>
        </template>
        <template v-else>
          <a class="nav-link" :class="{ active: isActive('/dashboard') }" @click.prevent="navigate('/dashboard')">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
          </a>
          <a class="nav-link" :class="{ active: isActive('/productores') }" @click.prevent="navigate('/productores')">
            <i class="bi bi-people"></i> Productores
          </a>
          <a class="nav-link" :class="{ active: isActive('/predios') }" @click.prevent="navigate('/predios')">
            <i class="bi bi-house-door"></i> Predios
          </a>
          <a class="nav-link" :class="{ active: isActive('/visitas') }" @click.prevent="navigate('/visitas')">
            <i class="bi bi-calendar-event"></i> Agenda / Visitas
          </a>
          <a class="nav-link" :class="{ active: isActive('/inspecciones') || isActive('/inspeccion') }" @click.prevent="navigate('/inspecciones')">
            <i class="bi bi-clipboard-check"></i> Inspecciones
          </a>
          <a class="nav-link" :class="{ active: isActive('/inspeccion') && !$route.path.includes('/editar') }" @click.prevent="navigate('/inspeccion')">
            <i class="bi bi-file-earmark-plus"></i> Nuevo Dictamen
          </a>
          <a class="nav-link" :class="{ active: isActive('/descargas') }" @click.prevent="navigate('/descargas')">
            <i class="bi bi-download"></i> Descargas
          </a>
          <a class="nav-link" :class="{ active: isActive('/sync') }" @click.prevent="navigate('/sync')">
            <i class="bi bi-arrow-repeat"></i> Sincronizar
          </a>
        </template>

        <hr class="mx-3 text-slate-200">
        <a class="nav-link text-danger logout-btn" @click.prevent="doLogout">
          <i class="bi bi-box-arrow-left"></i> Salir
        </a>
      </nav>
    </aside>

    <header class="mobile-header shadow-sm">
      <button class="header-hamburger-btn rounded-circle" @click="sidebarActive = true">
        <i class="bi bi-list fs-4"></i>
      </button>

      <div class="brand-title flex-grow-1 text-center">
        <img src="/icon_png.png" alt="SIGDIP" style="width: 20px; height: 20px; object-fit: contain; vertical-align: -3px; margin-right: 6px;">
        <span class="fw-bold">SIGDIP</span>
      </div>

      <div
        class="badge rounded-pill px-2-5 py-1-5 d-flex align-items-center gap-1.5 fw-semibold me-2 border connectivity-badge shadow-sm"
        :class="isOnline ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle'"
      >
        <span class="pulse-dot" :class="isOnline ? 'bg-success' : 'bg-danger'"></span>
        <span class="badge-text">{{ isOnline ? 'Online' : 'Offline' }}</span>
      </div>

      <div class="avatar-circle rounded-circle">
        <i class="bi bi-person"></i>
      </div>
    </header>

    <main class="app-content main-content bg-light">
      <slot />
    </main>

    <nav class="bottom-nav">
      <a class="bottom-nav-link" :class="{ active: isActive('/dashboard') }" @click.prevent="navigate('/dashboard')">
        <i class="bi" :class="isActive('/dashboard') ? 'bi-grid-1x2-fill' : 'bi-grid-1x2'"></i>
        <span>Inicio</span>
      </a>
      <a class="bottom-nav-link" :class="{ active: isActive('/productores') }" @click.prevent="navigate('/productores')">
        <i class="bi" :class="isActive('/productores') ? 'bi-people-fill' : 'bi-people'"></i>
        <span>Productores</span>
      </a>
      <a class="bottom-nav-link" :class="{ active: isActive('/predios') }" @click.prevent="navigate('/predios')">
        <i class="bi" :class="isActive('/predios') ? 'bi-house-door-fill' : 'bi-house-door'"></i>
        <span>Predios</span>
      </a>
      <a class="bottom-nav-link" :class="{ active: isActive('/inspecciones') || isActive('/inspeccion') }" @click.prevent="navigate('/inspecciones')">
        <i class="bi" :class="(isActive('/inspecciones') || isActive('/inspeccion')) ? 'bi-clipboard-check-fill' : 'bi-clipboard-check'"></i>
        <span>Dictámenes</span>
      </a>
      <a class="bottom-nav-link" :class="{ active: isActive('/sync') || isActive('/scan') }" @click.prevent="navigate('/sync')">
        <i class="bi bi-arrow-repeat"></i>
        <span>Sincronizar</span>
      </a>
    </nav>
  </div>
</template>

<script>
import { Network } from '@capacitor/network';
import api from '../services/api.js';

export default {
  name: 'AppLayout',
  data() {
    return {
      userName: '',
      isAdmin: false,
      isOnline: true,
      sidebarActive: false,
      networkListener: null,
    };
  },
  mounted() {
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Administrador Central';
    this.isAdmin = user?.roles && user.roles.includes('Administrador');
    this.isOnline = navigator.onLine;
    this.initNetworkListener();
  },
  beforeUnmount() {
    if (this.networkListener) {
      this.networkListener.remove();
    }
  },
  methods: {
    initNetworkListener() {
      try {
        Network.getStatus().then((status) => {
          this.isOnline = status.connected;
          this.networkListener = Network.addListener('networkStatusChange', (status) => {
            this.isOnline = status.connected;
          });
        });
      } catch (e) {
        this._onWindowOnline = () => this.isOnline = true;
        this._onWindowOffline = () => this.isOnline = false;
        window.addEventListener('online', this._onWindowOnline);
        window.addEventListener('offline', this._onWindowOffline);
      }
    },
    isActive(path) {
      if (path === '/dashboard') return this.$route.path === '/dashboard';
      if (path === '/inspecciones') return this.$route.path.startsWith('/inspeccione');
      if (path === '/inspeccion') return this.$route.path.startsWith('/inspeccion') && !this.$route.path.startsWith('/inspeccione');
      return this.$route.path.startsWith(path);
    },
    navigate(path) {
      this.sidebarActive = false;
      this.$router.push(path);
    },
    alertWebOnly(section) {
      alert(`La sección de ${section} es una función administrativa disponible en la web de escritorio.`);
      this.sidebarActive = false;
    },
    async doLogout() {
      try { await api.logout(); } catch (e) { /* ignore */ }
      localStorage.removeItem('sigdip_token');
      localStorage.removeItem('sigdip_user');
      sessionStorage.clear();
      this.$router.push('/login');
    },
  },
};
</script>
