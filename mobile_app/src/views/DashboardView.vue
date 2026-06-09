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
          <a class="nav-link active" @click.prevent="sidebarActive = false">
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
          <a class="nav-link" @click.prevent="$router.push('/descargas')">
            <i class="bi bi-download"></i> Descargas
          </a>
          <a class="nav-link" @click.prevent="$router.push('/inspecciones?downloadExcel=true')">
            <i class="bi bi-file-earmark-excel"></i> Sábana Excel
          </a>
        </template>
        <template v-else>
          <a class="nav-link active" @click.prevent="sidebarActive = false">
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

    <!-- Main Content -->
    <main class="app-content main-content bg-light">
      <!-- Welcome Header -->
      <div class="welcome-header mb-4 text-start">
        <h2 class="h4 fw-bold mb-1">{{ isAdmin ? 'Resumen Administrativo' : 'Dashboard' }}</h2>
        <p class="text-secondary small mb-0">{{ isAdmin ? 'Estado actual de las inspecciones pecuarias' : ('Bienvenido, ' + userName) }}</p>
      </div>

      <!-- Offline Cache Warning for Admin -->
      <div v-if="isAdmin && !isOnline" class="alert alert-warning-custom mb-4 d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
        <div class="small text-start">
          <strong>Modo Offline:</strong> Mostrando datos del último guardado local. Conéctate a internet para ver estadísticas reales del estado.
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- 1. VISTA ADMINISTRADOR (CLON DE DISEÑO DE LA WEB)               -->
      <!-- ============================================================== -->
      <div v-if="isAdmin">
        <!-- Tarjetas de Estadísticas Administrativas -->
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-4">
            <div class="card p-4 bg-primary text-white border-0 overflow-hidden position-relative stats-card-premium shadow-sm">
              <div class="decor-icon-white">
                <i class="bi bi-clipboard-data"></i>
              </div>
              <h6 class="text-white-50 small text-uppercase fw-bold ls-wide text-start mb-1">TOTAL INSPECCIONES</h6>
              <h2 class="display-6 fw-bold mb-0 text-start text-white">{{ adminStats.totalInspecciones }}</h2>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="card p-4 bg-white border-0 shadow-sm stats-card-premium border-slate-100">
              <h6 class="text-secondary small text-uppercase fw-bold ls-wide text-start mb-1">ANIMALES REGISTRADOS</h6>
              <h2 class="display-6 fw-bold mb-0 text-start text-dark">{{ adminStats.totalAnimales }}</h2>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="card p-4 bg-white border-0 shadow-sm position-relative overflow-hidden stats-card-premium border-slate-100">
              <div class="decor-icon-blue">
                <i class="bi bi-calendar-event"></i>
              </div>
              <h6 class="text-secondary small text-uppercase fw-bold ls-wide text-start mb-1">VISITAS EN AGENDA</h6>
              <h2 class="display-6 fw-bold mb-0 text-start text-primary">{{ adminStats.totalVisitasPendientes }}</h2>
            </div>
          </div>
        </div>

        <!-- Contenedores de Gráficos (HTML/CSS Autogenerados Offline-Safe) -->
        <div class="row g-4 mb-4">
          <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100 chart-card-wrapper">
              <div class="card-header bg-white border-bottom border-slate-100 d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold text-dark"><i class="bi bi-bar-chart-fill me-2 text-primary"></i> Inspecciones por Localidad</span>
              </div>
              <div class="card-body">
                <div class="pure-bar-chart">
                  <!-- Grid Lines -->
                  <div class="chart-grid-lines">
                    <div class="grid-line" v-for="n in 5" :key="n"></div>
                  </div>
                  <!-- Bars -->
                  <div class="chart-bars-container">
                    <div class="chart-bar-wrapper" v-for="item in adminStats.inspeccionesPorLocalidad" :key="item.localidad">
                      <div class="bar-value">{{ item.total }}</div>
                      <div class="bar-outer">
                        <div class="bar-inner" :style="{ height: getBarHeightPercent(item.total) + '%' }"></div>
                      </div>
                      <div class="bar-label" :title="item.localidad">{{ item.localidad }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100 chart-card-wrapper">
              <div class="card-header bg-white border-bottom border-slate-100 d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold text-dark"><i class="bi bi-pie-chart-fill me-2 text-primary"></i> Rendimiento Veterinarios</span>
              </div>
              <div class="card-body d-flex flex-column justify-content-center">
                <div class="pure-doughnut-chart-wrapper">
                  <div class="pure-doughnut-chart" :style="{ background: getDoughnutGradient() }">
                    <div class="inner-hole"></div>
                  </div>
                  <!-- Legend -->
                  <div class="chart-legend-grid">
                    <div class="legend-item" v-for="(item, idx) in adminStats.rendimientoVeterinarios" :key="item.name">
                      <span class="legend-dot" :style="{ backgroundColor: getDoughnutColor(idx) }"></span>
                      <span class="legend-text">{{ item.name }}: <strong class="text-dark">{{ item.total }}</strong></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Listas Globales (Admin) -->
        <div class="row g-4 mt-1 mb-5">
          <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100 custom-list-card">
              <div class="card-header bg-white fw-bold text-primary d-flex justify-content-between align-items-center py-3 list-card-header">
                <span><i class="bi bi-geo-alt me-2"></i> Próximos Despliegues a Campo</span>
              </div>
              <div class="card-body p-0 border-top text-start">
                <div v-if="!adminStats.proximasVisitasGlobales || adminStats.proximasVisitasGlobales.length === 0" class="text-center p-5 text-muted">
                  <i class="bi bi-calendar-x display-6 d-block mb-2 text-muted"></i>
                  <p class="mb-0 small text-secondary">No hay visitas pendientes programadas en todo el estado.</p>
                </div>
                <div v-else class="list-group list-group-flush">
                  <div v-for="visita in adminStats.proximasVisitasGlobales" :key="visita.id" class="list-group-item p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <div class="fw-bold fs-6 text-dark text-start">{{ visita.predio?.productor?.nombre || 'Sin Productor' }}</div>
                      <span class="badge bg-light text-dark border font-mono small-badge"><i class="bi bi-calendar3"></i> {{ formatDate(visita.fecha_programada) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                      <small class="text-secondary text-start"><i class="bi bi-pin-map"></i> {{ visita.predio?.nombre_rancho || visita.predio?.nombre }} ({{ visita.predio?.localidad }})</small>
                      <small class="fw-semibold text-primary font-mono"><i class="bi bi-person-badge"></i> MVZ. {{ visita.veterinario?.name }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100 custom-list-card">
              <div class="card-header bg-white fw-bold text-warning d-flex justify-content-between align-items-center py-3 list-card-header border-warning-bottom">
                <span><i class="bi bi-journal-x me-2 text-warning"></i> Dictámenes Incompletos</span>
                <span class="badge bg-warning text-dark rounded-pill">{{ adminStats.borradoresGlobales ? adminStats.borradoresGlobales.length : 0 }}</span>
              </div>
              <div class="card-body p-0 border-top text-start">
                <div v-if="!adminStats.borradoresGlobales || adminStats.borradoresGlobales.length === 0" class="text-center p-5 text-muted">
                  <i class="bi bi-check-circle display-6 d-block mb-2 text-success"></i>
                  <p class="mb-0 small text-secondary">Todos los dictámenes están finalizados.</p>
                </div>
                <div v-else class="list-group list-group-flush">
                  <div v-for="borrador in adminStats.borradoresGlobales" :key="borrador.id" class="list-group-item p-3">
                    <div class="fw-bold text-dark text-start">{{ borrador.predio?.nombre_rancho || borrador.predio?.nombre }}</div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                      <small class="text-secondary">
                        <span v-if="!borrador.folio || borrador.folio.startsWith('TB-')" class="text-muted fst-italic">Sin Folio (Borrador)</span>
                        <span v-else class="font-mono">Folio: {{ borrador.folio }}</span>
                      </small>
                      <small class="text-muted fst-italic">{{ borrador.veterinario?.name }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- 2. VISTA MÉDICO / VETERINARIO (CLON DE DISEÑO DE LA WEB)       -->
      <!-- ============================================================== -->
      <div v-else>
        <!-- Stats Resumen Operativo (Puntos clave) -->
        <div class="stats-grid-small mb-4">
          <div class="small-stat-card shadow-sm">
            <div class="value">{{ pendientesSync }}</div>
            <div class="label">Pendientes Sync</div>
          </div>
          <div class="small-stat-card shadow-sm">
            <div class="value">{{ totalInspeccionesDoc }}</div>
            <div class="label">Mis Inspecciones</div>
          </div>
          <div class="small-stat-card shadow-sm">
            <div class="value">{{ totalAnimalesDoc }}</div>
            <div class="label">Mis Animales</div>
          </div>
          <div class="small-stat-card shadow-sm">
            <div class="value text-slate-500 font-mono" style="font-size: 0.75rem;">{{ lastSyncDate || 'Nunca' }}</div>
            <div class="label">Último Sync</div>
          </div>
        </div>

        <!-- Acciones Rápidas Táctiles Gigantes -->
        <div class="section-title text-start mb-2">Acciones Rápidas</div>
        <div class="row g-3 mb-4 touch-cards-grid-row">
          <div class="col-4">
            <a @click.prevent="$router.push('/inspeccion')" class="text-decoration-none action-card-link-new">
              <div class="card p-3 border-0 shadow-sm text-center bg-primary text-white h-100 hover-lift d-flex flex-column align-items-center justify-content-center gap-2 rounded-4">
                <i class="bi bi-file-earmark-plus-fill display-6 mb-1 text-white"></i>
                <h4 class="fw-bold mb-0 fs-7 tracking-wide text-white">NUEVO DICTAMEN</h4>
              </div>
            </a>
          </div>
          <div class="col-4">
            <a @click.prevent="$router.push('/descargas')" class="text-decoration-none action-card-link-new">
              <div class="card p-3 border-0 shadow-sm text-center bg-white border border-slate-100 h-100 hover-lift d-flex flex-column align-items-center justify-content-center gap-2 rounded-4">
                <i class="bi bi-download text-success display-6 mb-1"></i>
                <h4 class="fw-bold text-dark mb-0 fs-7 tracking-wide">DESCARGAS</h4>
              </div>
            </a>
          </div>
          <div class="col-4">
            <a @click.prevent="$router.push('/sync')" class="text-decoration-none action-card-link-new">
              <div class="card p-3 border-0 shadow-sm text-center bg-white border border-slate-100 h-100 hover-lift d-flex flex-column align-items-center justify-content-center gap-2 rounded-4">
                <i class="bi bi-arrow-repeat text-primary display-6 mb-1"></i>
                <h4 class="fw-bold text-dark mb-0 fs-7 tracking-wide">SINCRONIZAR</h4>
              </div>
            </a>
          </div>
        </div>

        <!-- Mis Visitas Pendientes -->
        <div class="card border-0 shadow-sm mt-4 custom-list-card">
          <div class="card-header bg-white fw-bold text-primary d-flex justify-content-between align-items-center py-3 list-card-header">
            <span><i class="bi bi-calendar-event me-2"></i> Mis Visitas Pendientes</span>
            <span class="badge bg-primary-soft text-primary rounded-pill px-2-5 py-1 font-mono">{{ visitas.length }}</span>
          </div>
          <div class="card-body p-0 border-top text-start">
            <div v-if="visitas.length === 0" class="text-center p-5 text-muted">
              <i class="bi bi-check-circle display-6 text-success d-block mb-2"></i>
              <p class="mb-0 small fw-semibold text-secondary">No tienes visitas programadas para hoy</p>
              <p class="text-muted small">Todo al corriente en tu agenda</p>
            </div>
            <div v-else class="list-group list-group-flush">
              <div 
                v-for="visita in visitas" 
                :key="visita.id" 
                class="list-group-item p-3 d-flex justify-content-between align-items-center list-item-cloned"
              >
                <div class="text-start">
                  <div class="fw-bold list-item-name text-dark">{{ visita.predio?.productor?.nombre || 'Sin Productor' }}</div>
                  <small class="text-secondary d-block mb-1 list-item-desc">
                    <i class="bi bi-pin-map"></i> {{ visita.predio?.nombre || 'Rancho ' + visita.predio_id }} ({{ visita.predio?.localidad || 'Localidad' }})
                  </small>
                  <small class="text-muted list-item-date font-mono"><i class="bi bi-calendar3"></i> {{ formatDate(visita.fecha_programada) }}</small>
                </div>
                <button 
                  class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1-5 list-item-action-btn"
                  @click="startInspeccion(visita)"
                >
                  Iniciar Dictamen
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Mis Borradores Pausados -->
        <div class="card border-0 shadow-sm mt-4 custom-list-card mb-5">
          <div class="card-header bg-white fw-bold text-warning d-flex justify-content-between align-items-center py-3 list-card-header border-warning-bottom">
            <span><i class="bi bi-journal-bookmark me-2 text-warning"></i> Mis Borradores Pausados</span>
            <span class="badge bg-warning-subtle text-warning rounded-pill px-2-5 py-1 font-mono">{{ borradores.length }}</span>
          </div>
          <div class="card-body p-0 border-top text-start">
            <div v-if="borradores.length === 0" class="text-center p-5 text-muted">
              <i class="bi bi-journal-x display-6 text-muted d-block mb-2"></i>
              <p class="mb-0 small fw-semibold text-secondary">No tienes dictámenes en estado de borrador</p>
              <p class="text-muted small">Todos los dictámenes están sincronizados</p>
            </div>
            <div v-else class="list-group list-group-flush">
              <div 
                v-for="borrador in borradores" 
                :key="borrador.folio" 
                class="list-group-item p-3 d-flex justify-content-between align-items-center list-item-cloned"
              >
                <div class="text-start">
                  <div class="fw-bold list-item-name text-dark">{{ getPredioNombre(borrador.predio_id) }}</div>
                  <small class="text-secondary d-block list-item-desc font-mono">
                    <i class="bi bi-file-earmark-text"></i> {{ borrador.folio }} | Incompleto
                  </small>
                </div>
                <button 
                  class="btn btn-sm btn-warning rounded-pill px-3 py-1-5 list-item-action-btn text-dark fw-bold"
                  @click="continueBorrador(borrador)"
                >
                  <i class="bi bi-pencil-square"></i> Continuar
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
  </div>
</template>

<script>
import { Network } from '@capacitor/network';
import Chart from 'chart.js/auto';
import api from '../services/api.js';
import db from '../services/db.js';

export default {
  name: 'DashboardView',
  data() {
    return {
      userName: '',
      isAdmin: false,
      sidebarActive: false,
      isOnline: true,
      networkListener: null,
      
      // Datos Generales
      lastSyncDate: '',
      visitas: [],
      borradores: [],
      prediosList: [],
      
      // Estadísticas Médico
      pendientesSync: 0,
      totalInspeccionesDoc: 0,
      totalAnimalesDoc: 0,

      // Estadísticas Administrador
      adminStats: {
        totalInspecciones: 0,
        totalAnimales: 0,
        totalVisitasPendientes: 0,
        inspeccionesPorLocalidad: [],
        rendimientoVeterinarios: [],
        proximasVisitasGlobales: [],
        borradoresGlobales: []
      },

      // Gráficos
      chartLocalidadesInstance: null,
      chartVeterinariosInstance: null,
      chartError: null
    };
  },
  async mounted() {
    // 1. Cargar datos del usuario autenticado
    const user = api.getCurrentUser();
    this.userName = user?.name || 'Médico';
    this.isAdmin = user?.roles && user.roles.includes('Administrador');

    // 2. Cargar datos offline de la agenda e inspecciones del médico
    const localInspecciones = await db.getInspeccionesPendientes();
    this.pendientesSync = localInspecciones.filter(i => i.estado === 'borrador').length;
    this.borradores = localInspecciones.filter(i => i.estado === 'borrador');
    this.visitas = await db.getVisitas();
    this.prediosList = await db.getPredios();

    const sync = await db.getLastSync();
    if (sync) {
      const d = new Date(sync);
      this.lastSyncDate = d.toLocaleDateString('es-MX', { 
        day: '2-digit', 
        month: 'short', 
        hour: '2-digit', 
        minute: '2-digit' 
      });
    }

    // 3. Inicializar estado de conectividad nativa
    try {
      const status = await Network.getStatus();
      this.isOnline = status.connected;

      this.networkListener = await Network.addListener('networkStatusChange', (status) => {
        const wasOffline = !this.isOnline;
        this.isOnline = status.connected;
        if (this.isOnline && wasOffline) {
          // Si vuelve a conectarse, recargar en vivo
          this.loadLiveData();
        }
      });
    } catch (e) {
      console.warn('Network API no disponible en navegador, usando fallback.', e);
      this.isOnline = navigator.onLine;
      window.addEventListener('online', () => {
        this.isOnline = true;
        this.loadLiveData();
      });
      window.addEventListener('offline', () => this.isOnline = false);
    }

    // 4. Cargar caché de IndexedDB para renderizar instantáneamente (Offline-First)
    await this.loadCachedStats();

    // 5. Cargar datos en vivo si está conectado
    if (this.isOnline) {
      await this.loadLiveData();
    }
  },
  beforeUnmount() {
    if (this.networkListener) {
      this.networkListener.remove();
    }
  },
  methods: {
    alertWebOnly(seccion) {
      alert(`La sección de ${seccion} es una función administrativa exclusiva del portal web.`);
      this.sidebarActive = false;
    },
    formatDate(dateStr) {
      if (!dateStr) return '';
      try {
        const parts = dateStr.split('-');
        if (parts.length === 3) {
          // Asumiendo formato YYYY-MM-DD
          return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
      } catch (e) {
        return dateStr;
      }
    },
    getPredioNombre(id) {
      const pred = this.prediosList.find(p => p.id === id);
      return pred ? pred.nombre : 'Rancho ' + id;
    },
    startInspeccion(visita) {
      this.$router.push(`/inspeccion/${visita.predio_id}?visita_id=${visita.id}`);
    },
    continueBorrador(borrador) {
      this.$router.push(`/inspeccion/${borrador.predio_id}?inspeccion_id=${borrador.id || borrador.folio}${borrador.visita_id ? `&visita_id=${borrador.visita_id}` : ''}`);
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

    // Cargar caché local de IndexedDB
    async loadCachedStats() {
      const cached = await db.getDashboardData();
      if (cached) {
        if (this.isAdmin && cached.adminStats) {
          this.adminStats = cached.adminStats;
        } else if (!this.isAdmin && cached.doctorStats) {
          this.totalInspeccionesDoc = cached.doctorStats.totalInspecciones || 0;
          this.totalAnimalesDoc = cached.doctorStats.totalAnimales || 0;
        }
      }
    },

    // Cargar estadísticas en vivo del backend SIGDIP
    async loadLiveData() {
      if (!this.isOnline) return;

      try {
        const res = await api.getDashboardStats();
        if (res) {
          if (this.isAdmin) {
            this.adminStats = {
              totalInspecciones: res.totalInspecciones || 0,
              totalAnimales: res.totalAnimales || 0,
              totalVisitasPendientes: res.totalVisitasPendientes || 0,
              inspeccionesPorLocalidad: res.inspeccionesPorLocalidad || [],
              rendimientoVeterinarios: res.rendimientoVeterinarios || [],
              proximasVisitasGlobales: res.proximasVisitasGlobales || [],
              borradoresGlobales: res.borradoresGlobales || []
            };
            
            // Guardar en base de datos local
            await db.saveDashboardData({ adminStats: this.adminStats });
          } else {
            this.totalInspeccionesDoc = res.totalInspecciones || 0;
            this.totalAnimalesDoc = res.totalAnimales || 0;

            // Guardar en base de datos local
            await db.saveDashboardData({ 
              doctorStats: {
                totalInspecciones: this.totalInspeccionesDoc,
                totalAnimales: this.totalAnimalesDoc
              }
            });
          }
        }
      } catch (err) {
        console.warn('Fallo al obtener estadísticas en vivo (Posible servidor apagado):', err);
      }
    },

    getBarHeightPercent(total) {
      if (!this.adminStats.inspeccionesPorLocalidad || this.adminStats.inspeccionesPorLocalidad.length === 0) return 0;
      const maxVal = Math.max(...this.adminStats.inspeccionesPorLocalidad.map(item => item.total), 1);
      return (total / maxVal) * 100;
    },

    getDoughnutColor(idx) {
      const colors = ['#2563eb', '#60a5fa', '#93c5fd', '#bfdbfe', '#e2e8f0'];
      return colors[idx % colors.length];
    },

    getDoughnutGradient() {
      if (!this.adminStats.rendimientoVeterinarios || this.adminStats.rendimientoVeterinarios.length === 0) {
        return '#f1f5f9';
      }
      const totalSum = this.adminStats.rendimientoVeterinarios.reduce((acc, item) => acc + item.total, 0);
      if (totalSum === 0) return '#f1f5f9';

      let currentPercent = 0;
      const segments = [];
      
      this.adminStats.rendimientoVeterinarios.forEach((item, idx) => {
        const percent = (item.total / totalSum) * 100;
        const color = this.getDoughnutColor(idx);
        segments.push(`${color} ${currentPercent}% ${currentPercent + percent}%`);
        currentPercent += percent;
      });

      return `conic-gradient(${segments.join(', ')})`;
    }
  }
};
</script>

<style scoped>
.app-container {
  display: flex;
  flex-direction: column;
  min-height: 100dvh;
  min-height: 100vh;
}

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
  width: 40px !important;
  height: 40px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 0 !important;
  background: #f1f5f9 !important;
  border: none !important;
  color: #64748b !important;
  cursor: pointer;
}

.header-hamburger-btn:active {
  background: #e2e8f0 !important;
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
  width: 40px !important;
  height: 40px !important;
  background-color: var(--color-primary-light) !important;
  color: var(--color-primary) !important;
  font-size: 1.2rem !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  flex-shrink: 0 !important;
  border-radius: 50% !important;
}

/* Sidebar Drawer */
.sidebar {
  width: 280px;
  height: 100dvh;
  height: 100vh;
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

/* Connectivity Badge */
.connectivity-badge {
  font-size: 0.72rem;
  padding: 4px 10px;
  border: 1px solid;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.bg-success-subtle {
  background-color: #d1fae5 !important;
}

.border-success-subtle {
  border-color: #a7f3d0 !important;
}

.bg-danger-subtle {
  background-color: #fee2e2 !important;
}

.border-danger-subtle {
  border-color: #fca5a5 !important;
}

.pulse-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}

.bg-success {
  background-color: var(--color-success) !important;
  animation: pulse-green 2s infinite;
}

.bg-danger {
  background-color: var(--color-danger) !important;
}

@keyframes pulse-green {
  0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(16, 185, 129, 0); }
  100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

/* Alert Offline */
.alert-warning-custom {
  background-color: #fffbeb;
  border: 1px solid #fef3c7;
  color: #b45309;
  border-radius: 12px;
  padding: 12px 16px;
}

/* Premium Web Statistics Cards (Clon literal de la web) */
.stats-card-premium {
  border-radius: 16px !important;
  border: 1px solid #f1f5f9;
  padding: 24px !important;
  transition: transform 0.2s ease;
  position: relative !important;
  overflow: hidden !important;
}

.bg-primary {
  background-color: var(--color-primary) !important;
  background: var(--color-primary) !important;
}

.text-white {
  color: #ffffff !important;
}

.decor-icon-white {
  font-size: 5rem !important;
  transform: translate(20%, 20%) !important;
  line-height: 1 !important;
  position: absolute !important;
  right: 15px !important;
  bottom: 10px !important;
  opacity: 1 !important;
  color: #ffffff !important;
  pointer-events: none !important;
}

.decor-icon-blue {
  font-size: 5rem !important;
  transform: translate(10%, 10%) !important;
  line-height: 1 !important;
  position: absolute !important;
  right: 15px !important;
  bottom: 10px !important;
  opacity: 1 !important;
  color: var(--color-primary) !important;
  pointer-events: none !important;
}

.ls-wide {
  letter-spacing: 0.05em;
}

/* Chart Styles */
.chart-card-wrapper {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.chart-canvas-container {
  position: relative;
  height: 250px;
  width: 100%;
}

.chart-canvas-container canvas {
  display: block;
}

.doughnut-container {
  height: 200px;
}

/* Small Stats Resumen (Vista Médico) */
.stats-grid-small {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}

.small-stat-card {
  background: white;
  border-radius: 12px;
  padding: 10px 8px;
  border: 1px solid #f1f5f9;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.small-stat-card .value {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-primary);
}

.small-stat-card .label {
  font-size: 0.58rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.2px;
  margin-top: 2px;
}

/* Tarjetas Táctiles Gigantes (Vista Médico - Clon literal web) */
.touch-cards-grid-row {
  margin-bottom: 24px;
}

.action-card-link-new {
  display: block;
  height: 100%;
}

.hover-lift {
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.hover-lift:active {
  transform: scale(0.97) !important;
}

.small-desc {
  font-size: 0.7rem;
  opacity: 0.8;
}

/* Custom list cards clone from web */
.custom-list-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.list-card-header {
  border-bottom: 1.5px solid var(--color-primary-light);
}

.border-warning-bottom {
  border-bottom: 1.5px solid var(--color-accent-light) !important;
}

.list-group-item {
  border-bottom: 1px solid #f1f5f9;
  background: white;
  transition: background-color 0.15s ease;
}

.list-group-item:last-child {
  border-bottom: none;
}

.list-item-cloned {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.list-item-cloned:hover {
  background-color: #f8fafc;
}

.list-item-name {
  font-size: 0.95rem;
  color: #1e293b;
  margin-bottom: 2px;
}

.list-item-desc {
  font-size: 0.78rem;
  color: #64748b;
}

.list-item-date {
  font-size: 0.72rem;
  color: #94a3b8;
}

.list-item-action-btn {
  font-size: 0.78rem !important;
  padding: 6px 14px !important;
  border-radius: 20px !important;
}

.small-badge {
  font-size: 0.65rem;
  padding: 2px 6px;
}

/* Bottom Navigation adjustments */

/* ===== Pure HTML/CSS/SVG Premium Charting ===== */
.pure-bar-chart {
  position: relative;
  height: 220px;
  width: 100%;
  margin-top: 15px;
}
.chart-grid-lines {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 30px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  pointer-events: none;
}
.grid-line {
  width: 100%;
  border-top: 1px dashed #f1f5f9;
}
.chart-bars-container {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  display: flex;
  justify-content: space-around;
  align-items: flex-end;
}
.chart-bar-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-grow: 1;
  max-width: 50px;
  height: 100%;
  justify-content: flex-end;
  z-index: 2;
}
.bar-value {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-primary);
  margin-bottom: 4px;
}
.bar-outer {
  width: 20px;
  height: 140px;
  background-color: #f1f5f9;
  border-radius: 6px;
  display: flex;
  align-items: flex-end;
  overflow: hidden;
}
.bar-inner {
  width: 100%;
  background-color: var(--color-primary);
  border-top-left-radius: 6px;
  border-top-right-radius: 6px;
  transition: height 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
.bar-label {
  font-size: 0.65rem;
  color: var(--text-secondary);
  font-weight: 600;
  margin-top: 6px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 55px;
}

.pure-doughnut-chart-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  width: 100%;
  padding: 10px 0;
}
.pure-doughnut-chart {
  width: 130px;
  height: 130px;
  border-radius: 50%;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--shadow-sm);
  transition: background 0.4s ease;
}
.inner-hole {
  width: 90px;
  height: 90px;
  background-color: white;
  border-radius: 50%;
}
.chart-legend-grid {
  display: flex;
  flex-direction: column;
  gap: 6px;
  width: 100%;
  padding: 0 10px;
  text-align: left;
}
.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.75rem;
  color: var(--text-secondary);
}
.legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}
.legend-text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
