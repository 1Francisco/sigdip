import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import DashboardView from '../../src/views/DashboardView.vue'
import dashboardStats from '../fixtures/dashboard-stats.json'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildDashboardRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/dashboard', name: 'Dashboard', component: DashboardView },
      { path: '/login', name: 'Login', component: EmptyView },
      { path: '/productores', component: EmptyView },
      { path: '/predios', component: EmptyView },
      { path: '/inspecciones', component: EmptyView },
      { path: '/inspeccion', component: EmptyView },
      { path: '/visitas', component: EmptyView },
      { path: '/medicos', component: EmptyView },
      { path: '/descargas', component: EmptyView },
      { path: '/sync', component: EmptyView },
      { path: '/scan', component: EmptyView },
      { path: '/inspeccion/:predioId', component: EmptyView },
    ],
  })
}

function seedCachedAdminStats() {
  const adminStats = {
    totalInspecciones: 150,
    totalAnimales: 3200,
    totalVisitasPendientes: 45,
    inspeccionesPorLocalidad: [
      { localidad: 'Villahermosa', total: 45 },
      { localidad: 'Escarcega', total: 32 },
      { localidad: 'Ciudad del Carmen', total: 28 },
    ],
    rendimientoVeterinarios: [
      { nombre: 'Dr. Juan Perez', total: 80 },
      { nombre: 'Dra. Ana Garcia', total: 70 },
    ],
    proximasVisitasGlobales: [
      { codigo: 'V-TEST', fecha_programada: '2026-06-15', predio: { nombre_rancho: 'Rancho Test', localidad: 'Test', productor: { nombre: 'Productor' } }, veterinario: { nombre: 'Dr. Juan Perez', name: 'Dr. Juan Perez' } },
    ],
    borradoresGlobales: [],
  }
  cy.seedIndexedDB('catalogos', 'dashboard_data', { adminStats })
}

describe('DashboardView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
  })

  describe('Vista Administrador', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userAdmin })

      cy.intercept('POST', '**/api/logout', {
        statusCode: 200,
        body: { message: 'ok' },
      }).as('logout')
    })

    it('renderiza titulo de admin y tarjetas de estadisticas', () => {
      seedCachedAdminStats()
      const router = buildDashboardRouter()
      router.push('/dashboard')
      mount(DashboardView, { global: { plugins: [router] } })

      cy.contains('Resumen Administrativo', { timeout: 5000 }).should('be.visible')
      cy.contains('TOTAL INSPECCIONES').should('be.visible')
      cy.contains('150').should('be.visible')
      cy.contains('ANIMALES REGISTRADOS').should('be.visible')
      cy.contains('3200').should('be.visible')
      cy.contains('VISITAS EN AGENDA').should('be.visible')
    })

    it('renderiza grafico de barras por localidad', () => {
      seedCachedAdminStats()
      const router = buildDashboardRouter()
      router.push('/dashboard')
      mount(DashboardView, { global: { plugins: [router] } })

      cy.get('.pure-bar-chart', { timeout: 5000 }).should('be.visible')
      cy.get('.chart-bar-wrapper').should('have.length.at.least', 1)
    })

    it('renderiza grafico de doughnut de rendimiento veterinarios', () => {
      seedCachedAdminStats()
      const router = buildDashboardRouter()
      router.push('/dashboard')
      mount(DashboardView, { global: { plugins: [router] } })

      cy.get('.pure-doughnut-chart', { timeout: 5000 }).should('be.visible')
      cy.get('.legend-item').should('have.length.at.least', 1)
    })

    it('renderiza seccion de proximos despliegues a campo', () => {
      const router = buildDashboardRouter()
      router.push('/dashboard')
      mount(DashboardView, { global: { plugins: [router] } })

      cy.contains('Próximos Despliegues a Campo', { timeout: 5000 }).should('be.visible')
    })

    it('renderiza seccion de dictamenes incompletos', () => {
      const router = buildDashboardRouter()
      router.push('/dashboard')
      mount(DashboardView, { global: { plugins: [router] } })

      cy.contains('Dictámenes Incompletos', { timeout: 5000 }).should('be.visible')
    })

    it('muestra badge de conectividad online', () => {
      const router = buildDashboardRouter()
      router.push('/dashboard')
      mount(DashboardView, { global: { plugins: [router] } })

      cy.get('.connectivity-badge', { timeout: 5000 }).should('contain', 'Online')
    })

    it('cierra sesion desde menu lateral', () => {
      cy.intercept('GET', '**/api/dashboard/stats', {
        statusCode: 200,
        body: { total_inspecciones: 0, total_animales: 0, total_visitas: 0, inspecciones_por_localidad: [], rendimiento_veterinarios: [] },
      }).as('getStats')

      const router = buildDashboardRouter()
      router.push('/dashboard')
      mount(DashboardView, { global: { plugins: [router] } })

      cy.wait('@getStats', { timeout: 10000 })
      cy.get('.avatar-circle', { timeout: 5000 }).click()
      cy.contains('Salir').click({ force: true })
      cy.location('hash', { timeout: 5000 }).should('include', '/login')
    })
  })

  describe('Vista Medico', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userMedico })

      cy.intercept('POST', '**/api/logout', {
        statusCode: 200,
        body: { message: 'ok' },
      }).as('logout')
    })

    it('renderiza dashboard del medico con nombre', () => {
      const router = buildDashboardRouter()
      router.push('/dashboard')
      mount(DashboardView, { global: { plugins: [router] } })

      cy.contains('Bienvenido', { timeout: 5000 }).should('be.visible')
      cy.contains('Dr. Juan Perez').should('be.visible')
    })

    it('renderiza tarjetas de acciones rapidas', () => {
      const router = buildDashboardRouter()
      router.push('/dashboard')
      mount(DashboardView, { global: { plugins: [router] } })

      cy.contains('NUEVO DICTAMEN', { timeout: 5000 }).should('be.visible')
      cy.contains('DESCARGAS').should('be.visible')
      cy.contains('SINCRONIZAR').should('be.visible')
    })

    it('renderiza cuadricula de estadisticas del medico', () => {
      const router = buildDashboardRouter()
      router.push('/dashboard')
      mount(DashboardView, { global: { plugins: [router] } })

      cy.get('.small-stat-card', { timeout: 5000 }).should('have.length', 4)
    })
  })
})
