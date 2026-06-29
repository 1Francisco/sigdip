import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import VisitaDetailView from '../../src/views/VisitaDetailView.vue'
import userAdmin from '../fixtures/user-admin.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/visitas/:id', name: 'VisitaDetail', component: VisitaDetailView },
      { path: '/visitas/editar/:id', component: EmptyView },
      { path: '/visitas', component: EmptyView },
      { path: '/predios/:id', component: EmptyView },
      { path: '/inspecciones/:id', component: EmptyView },
      { path: '/productores/:id', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
}

const fakeVisita = {
  id: 1,
  codigo: 'V-TEST-001',
  predio_id: 1,
  predio: {
    id: 1,
    nombre_rancho: 'Rancho El Paraiso',
    localidad: 'Villahermosa',
    municipio: 'Centro',
    clave_unidad_produccion: 'CUP-001',
    productor: { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia' },
  },
  veterinario_id: 2,
  veterinario: { id: 2, name: 'Dr. Juan Perez' },
  fecha_programada: '2026-06-15',
  estado: 'pendiente',
  observaciones: 'Visita de rutina',
  inyeccion: false,
  inspeccion: null,
}

describe('VisitaDetailView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
  })

  it('muestra estado de carga', () => {
    cy.intercept('GET', '**/api/visitas/1', {
      statusCode: 200,
      delay: 300,
      body: { data: fakeVisita },
    }).as('getVisitaSlow')

    const router = buildRouter()
    router.push('/visitas/1')
    mount(VisitaDetailView, { global: { plugins: [router] } })

    cy.contains('Cargando visita...', { timeout: 5000 }).should('be.visible')
    cy.wait('@getVisitaSlow')
    cy.contains('Cargando visita...').should('not.exist')
  })

  it('renderiza informacion de la visita pendiente', () => {
    cy.intercept('GET', '**/api/visitas/1', {
      statusCode: 200,
      body: { data: fakeVisita },
    }).as('getVisita')

    const router = buildRouter()
    router.push('/visitas/1')
    mount(VisitaDetailView, { global: { plugins: [router] } })

    cy.wait('@getVisita', { timeout: 10000 })
    cy.contains('V-TEST-001', { timeout: 5000 }).should('be.visible')
    cy.contains('Rancho El Paraiso').should('be.visible')
    cy.contains('Dr. Juan Perez').should('be.visible')
    cy.contains('15/06/2026').should('be.visible')
    cy.contains('No').should('be.visible')
    cy.contains('Pendiente').should('be.visible')
    cy.get('.badge.bg-warning').should('be.visible')
  })

  it('renderiza visita completada con dictamen asociado', () => {
    const completada = {
      ...fakeVisita,
      id: 2,
      codigo: 'V-TEST-002',
      estado: 'completada',
      inyeccion: true,
      inspeccion: { id: 10, folio: 'INSP-001', estado: 'completada' },
    }

    cy.intercept('GET', '**/api/visitas/2', {
      statusCode: 200,
      body: { data: completada },
    }).as('getVisitaCompletada')

    const router = buildRouter()
    router.push('/visitas/2')
    mount(VisitaDetailView, { global: { plugins: [router] } })

    cy.wait('@getVisitaCompletada', { timeout: 10000 })
    cy.contains('V-TEST-002', { timeout: 5000 }).should('be.visible')
    cy.contains('Sí').should('be.visible')
    cy.get('.badge.bg-success').should('be.visible')
    cy.contains('INSP-001').should('be.visible')
    cy.contains('Ver Dictamen').should('be.visible')
  })

  it('muestra estado cancelada', () => {
    const cancelada = { ...fakeVisita, id: 3, estado: 'cancelada' }

    cy.intercept('GET', '**/api/visitas/3', {
      statusCode: 200,
      body: { data: cancelada },
    }).as('getVisitaCancelada')

    const router = buildRouter()
    router.push('/visitas/3')
    mount(VisitaDetailView, { global: { plugins: [router] } })

    cy.wait('@getVisitaCancelada', { timeout: 10000 })
    cy.contains('Cancelada', { timeout: 5000 }).should('be.visible')
    cy.get('.badge.bg-danger').should('be.visible')
  })

  it('navega al detalle del predio', () => {
    cy.intercept('GET', '**/api/visitas/1', {
      statusCode: 200,
      body: { data: fakeVisita },
    }).as('getVisita')

    const router = buildRouter()
    router.push('/visitas/1')
    mount(VisitaDetailView, { global: { plugins: [router] } })

    cy.wait('@getVisita', { timeout: 10000 })
    cy.get('a').contains('Rancho El Paraiso', { timeout: 5000 }).click()
    cy.location('hash', { timeout: 5000 }).should('include', '/predios/1')
  })

  it('navega entre botones editar y volver', () => {
    cy.intercept('GET', '**/api/visitas/1', {
      statusCode: 200,
      body: { data: fakeVisita },
    }).as('getVisita')

    const router = buildRouter()
    router.push('/visitas/1')
    mount(VisitaDetailView, { global: { plugins: [router] } })

    cy.wait('@getVisita', { timeout: 10000 })

    cy.contains('Editar', { timeout: 5000 }).click()
    cy.location('hash', { timeout: 5000 }).should('include', '/visitas/editar/1')

    cy.clock()
    router.push('/visitas/1')
    mount(VisitaDetailView, { global: { plugins: [router] } })
    cy.wait('@getVisita', { timeout: 10000 })

    cy.contains('Volver').click()
    cy.location('hash', { timeout: 5000 }).should('include', '/visitas')
  })

  it('muestra error cuando falla carga', () => {
    cy.intercept('GET', '**/api/visitas/999', {
      statusCode: 404,
      body: { message: 'Visita no encontrada' },
    }).as('getVisitaError')

    const router = buildRouter()
    router.push('/visitas/999')
    mount(VisitaDetailView, { global: { plugins: [router] } })

    cy.wait('@getVisitaError', { timeout: 10000 })
    cy.contains('no encontrada', { timeout: 5000 }).should('be.visible')
  })
})
