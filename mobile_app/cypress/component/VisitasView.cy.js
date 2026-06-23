import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import VisitasView from '../../src/views/VisitasView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/visitas', name: 'Visitas', component: VisitasView },
      { path: '/visitas/nuevo', component: EmptyView },
      { path: '/visitas/editar/:id', component: EmptyView },
      { path: '/inspeccion/:predioId', component: EmptyView },
      { path: '/inspecciones/:id', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
      { path: '/productores', component: EmptyView },
      { path: '/predios', component: EmptyView },
      { path: '/inspecciones', component: EmptyView },
      { path: '/medicos', component: EmptyView },
      { path: '/descargas', component: EmptyView },
      { path: '/sync', component: EmptyView },
      { path: '/scan', component: EmptyView },
    ],
  })
}

const fakePredios = [
  { id: 1, nombre_rancho: 'Rancho El Paraiso', localidad: 'Villahermosa', productor: { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia' } },
  { id: 3, nombre_rancho: 'Rancho San Jose', localidad: 'Escarcega', productor: { id: 2, nombre: 'Jose', apellido_paterno: 'Martinez' } },
]

const fakeVisitas = [
  {
    id: 1,
    codigo: 'V-TEST-001',
    predio_id: 1,
    predio: { id: 1, nombre_rancho: 'Rancho El Paraiso', localidad: 'Villahermosa', productor: { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia' } },
    veterinario_id: 2,
    veterinario: { id: 2, name: 'Dr. Juan Perez' },
    fecha_programada: '2026-06-15',
    estado: 'pendiente',
    observaciones: 'Visita de rutina',
    inyeccion: false,
    inspeccion: null
  },
  {
    id: 2,
    codigo: 'V-TEST-002',
    predio_id: 3,
    predio: { id: 3, nombre_rancho: 'Rancho San Jose', localidad: 'Escarcega', productor: { id: 2, nombre: 'Jose', apellido_paterno: 'Martinez' } },
    veterinario_id: 2,
    veterinario: { id: 2, name: 'Dr. Juan Perez' },
    fecha_programada: '2026-06-16',
    estado: 'completada',
    observaciones: '',
    inyeccion: true,
    inspeccion: { id: 10, estado: 'completada', fecha_inyeccion: '2026-06-15', hora_inyeccion: '10:00', fecha_lectura: '2026-06-15', hora_lectura: '10:30' }
  }
]

describe('VisitasView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.intercept('HEAD', '**/api/user', { statusCode: 200, body: {} }).as('getUser')
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')

    cy.intercept('GET', '**/api/predios*', {
      statusCode: 200,
      body: { data: fakePredios },
    }).as('getPredios')

    cy.intercept('GET', '**/api/visitas*', {
      statusCode: 200,
      body: { data: fakeVisitas },
    }).as('getVisitas')

    cy.intercept('GET', '**/api/medicos*', {
      statusCode: 200,
      body: { data: [] },
    }).as('getMedicos')
  })

  describe('como Administrador', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userAdmin })
    })

    it('renderiza titulo y boton programar visita', () => {
      const router = buildRouter()
      router.push('/visitas')
      mount(VisitasView, { global: { plugins: [router] } })

      cy.contains('Agenda de Campo', { timeout: 5000 }).should('be.visible')
      cy.contains('Programar Visita').should('be.visible')
    })

    it('renderiza lista de visitas desde API', () => {
      const router = buildRouter()
      router.push('/visitas')
      mount(VisitasView, { global: { plugins: [router] } })

      cy.wait('@getVisitas', { timeout: 10000 })
      cy.contains('V-TEST-001', { timeout: 5000 }).should('be.visible')
      cy.contains('V-TEST-002').should('be.visible')
    })

    it('navega a programar visita al hacer click en boton', () => {
      const router = buildRouter()
      router.push('/visitas')
      mount(VisitasView, { global: { plugins: [router] } })

      cy.contains('Programar Visita').click()
      cy.location('hash').should('eq', '#/visitas/nuevo')
    })

    it('navega a iniciar dictamen desde boton', () => {
      const router = buildRouter()
      router.push('/visitas')
      mount(VisitasView, { global: { plugins: [router] } })

      cy.wait('@getVisitas', { timeout: 10000 })
      cy.contains('Iniciar', { timeout: 5000 }).first().click({ force: true })
      cy.location('hash').should('match', /#\/inspeccion\/\d+/)
    })

    it('muestra badge de conectividad', () => {
      const router = buildRouter()
      router.push('/visitas')
      mount(VisitasView, { global: { plugins: [router] } })

      cy.get('.connectivity-badge', { timeout: 5000 }).should('be.visible')
    })

    it('filtra por fecha cambiando input date', () => {
      const router = buildRouter()
      router.push('/visitas')
      mount(VisitasView, { global: { plugins: [router] } })

      cy.get('input[type="date"]').type('2026-06-15')
      cy.wait(500)
    })

    it('navega entre paginas de visitas', () => {
      const manyVisitas = Array.from({ length: 15 }, (_, i) => ({
        id: i + 1,
        codigo: `V-${String(i + 1).padStart(3, '0')}`,
        predio_id: 1,
        fecha_programada: '2026-06-15',
        veterinario_id: 2,
        estado: 'pendiente',
      }))

      cy.intercept('GET', '**/api/visitas*', {
        statusCode: 200,
        body: { data: manyVisitas },
      }).as('getVisitasPaged')

      const router = buildRouter()
      router.push('/visitas')
      mount(VisitasView, { global: { plugins: [router] } })

      cy.wait('@getVisitasPaged', { timeout: 10000 })
      cy.contains('Pág. 1 de 2', { timeout: 5000 }).should('be.visible')

      cy.get('.next-btn').should('not.be.disabled').click()
      cy.contains('Pág. 2 de 2', { timeout: 5000 }).should('be.visible')
    })

    it('cierra sesion desde menu lateral', () => {
      cy.intercept('GET', '**/api/visitas*', {
        statusCode: 200,
        body: { data: fakeVisitas },
      }).as('getVisitas')

      const router = buildRouter()
      router.push('/visitas')
      mount(VisitasView, { global: { plugins: [router] } })

      cy.wait('@getVisitas', { timeout: 10000 })
      cy.get('.avatar-circle', { timeout: 5000 }).click()
      cy.get('.logout-btn').click({ force: true })
      cy.wait('@logout', { timeout: 10000 })
      cy.location('hash', { timeout: 5000 }).should('include', '/login')
    })
  })

  describe('como Medico', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userMedico })
    })

    it('renderiza lista de visitas para medico', () => {
      const router = buildRouter()
      router.push('/visitas')
      mount(VisitasView, { global: { plugins: [router] } })

      cy.wait('@getVisitas', { timeout: 10000 })
      cy.contains('V-TEST-001', { timeout: 5000 }).should('be.visible')
    })
  })
})
