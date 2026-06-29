import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import VisitaCreateView from '../../src/views/VisitaCreateView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'
import medicos from '../fixtures/medicos.json'

const EmptyView = { template: '<div>Other</div>' }

const fakePredios = [
  { id: 1, nombre_rancho: 'Rancho El Paraiso', productor: { id: 1, nombre: 'Maria' }, localidad: 'Villahermosa', municipio: 'Champoton' },
  { id: 2, nombre_rancho: 'Rancho La Esperanza', productor: { id: 1, nombre: 'Maria' }, localidad: 'Ciudad del Carmen', municipio: 'Carmen' },
  { id: 3, nombre_rancho: 'Rancho San Jose', productor: { id: 2, nombre: 'Jose' }, localidad: 'Escarcega', municipio: 'Escarcega' },
]

const fakeProductores = [
  { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia', apellido_materno: 'Lopez' },
  { id: 2, nombre: 'Jose', apellido_paterno: 'Martinez', apellido_materno: 'Hernandez' },
]

function buildVisitaRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/visitas/nuevo', name: 'NuevaVisita', component: VisitaCreateView },
      { path: '/visitas', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
}

function mockCatalogApis() {
  cy.intercept('GET', '**/api/predios*', {
    statusCode: 200,
    body: { data: fakePredios },
  }).as('getPredios')

  cy.intercept('GET', '**/api/productores*', {
    statusCode: 200,
    body: { data: fakeProductores },
  }).as('getProductores')

  cy.intercept('GET', '**/api/medicos*', {
    statusCode: 200,
    body: { data: medicos },
  }).as('getMedicos')

  cy.intercept('POST', '**/api/logout', {
    statusCode: 200,
    body: { message: 'ok' },
  }).as('logout')
}

describe('VisitaCreateView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.intercept('HEAD', '**/api/user', { statusCode: 200, body: {} }).as('getUser')
  })

  describe('como Administrador', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userAdmin })
      mockCatalogApis()
    })

    it('renderiza el formulario con titulo correcto', () => {
      const router = buildVisitaRouter()
      router.push('/visitas/nuevo')
      mount(VisitaCreateView, { global: { plugins: [router] } })

      cy.contains('Nueva Visita Programada', { timeout: 5000 }).should('be.visible')
      cy.contains('Productor (Persona)').should('be.visible')
      cy.contains('Predio (Rancho a Visitar)').should('be.visible')
      cy.contains('Fecha Programada').should('be.visible')
      cy.contains('Médico Veterinario Asignado').should('be.visible')
    })

    it('carga catalogos desde la API', () => {
      const router = buildVisitaRouter()
      router.push('/visitas/nuevo')
      mount(VisitaCreateView, { global: { plugins: [router] } })

      cy.wait('@getPredios', { timeout: 10000 })
      cy.wait('@getProductores', { timeout: 10000 })
      cy.wait('@getMedicos', { timeout: 10000 })

      cy.get('select').first().find('option').should('have.length.at.least', 3)
    })

    it('permite seleccionar productor y filtra predios', () => {
      const router = buildVisitaRouter()
      router.push('/visitas/nuevo')
      mount(VisitaCreateView, { global: { plugins: [router] } })

      cy.wait('@getProductores', { timeout: 10000 })

      cy.get('select').first().select('1')
      cy.get('select').eq(1).find('option').should('have.length.at.least', 2)
      cy.get('select').eq(1).should('not.be.disabled')
    })

    it('veterinario select habilitado para admin', () => {
      const router = buildVisitaRouter()
      router.push('/visitas/nuevo')
      mount(VisitaCreateView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })
      cy.get('select').eq(2).should('not.be.disabled')
    })

    it('valida campos obligatorios al enviar', () => {
      const router = buildVisitaRouter()
      router.push('/visitas/nuevo')
      mount(VisitaCreateView, { global: { plugins: [router] } })

      cy.get('form').submit()
      cy.contains('complete todos los campos', { timeout: 5000 }).should('be.visible')
    })

    it('guarda visita exitosamente via API', () => {
      cy.intercept('GET', '**/api/visitas/check-codigo/*', {
        statusCode: 200,
        body: { exists: false },
      }).as('checkVisitaCodigo')

      cy.intercept('POST', '**/api/visitas', {
        statusCode: 200,
        body: { success: true, visita: { id: 99, codigo: 'V-099' } },
      }).as('createVisita')

      cy.window().then((win) => {
        cy.stub(win, 'alert').returns(undefined)
      })

      const router = buildVisitaRouter()
      router.push('/visitas/nuevo')
      mount(VisitaCreateView, { global: { plugins: [router] } })

      cy.contains('.form-label-custom', 'Productor', { timeout: 5000 }).should('be.visible')
      cy.get('select').first().select('1')
      cy.get('select').eq(1).select('1')
      cy.get('select').eq(2).select('2')
      cy.get('input[type="date"]').first().invoke('val', '2030-06-20').trigger('input')

      cy.get('form').submit()
      cy.wait('@createVisita', { timeout: 10000 })
      cy.contains('Visita programada con éxito', { timeout: 5000 }).should('be.visible')
    })

    it('muestra error cuando falla guardado de visita', () => {
      cy.intercept('POST', '**/api/visitas', {
        statusCode: 500,
        body: { message: 'Error del servidor' },
      }).as('createVisitaError')

      cy.window().then((win) => {
        cy.stub(win, 'alert').returns(undefined)
      })

      const router = buildVisitaRouter()
      router.push('/visitas/nuevo')
      mount(VisitaCreateView, { global: { plugins: [router] } })

      cy.contains('.form-label-custom', 'Productor', { timeout: 5000 }).should('be.visible')
      cy.get('select').first().select('1')
      cy.get('select').eq(1).select('1')
      cy.get('select').eq(2).select('2')
      cy.get('input[type="date"]').first().invoke('val', '2030-06-20').trigger('input')

      cy.get('form').submit()
      cy.wait('@createVisitaError', { timeout: 10000 })
      cy.get('.alert.alert-danger', { timeout: 5000 }).should('be.visible')
    })
  })

  describe('como Medico', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userMedico })
      mockCatalogApis()
    })

    it('veterinario select deshabilitado para medico', () => {
      const router = buildVisitaRouter()
      router.push('/visitas/nuevo')
      mount(VisitaCreateView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })
      cy.get('select').eq(2).should('be.disabled')
    })

    it('asigna automaticamente el veterinario al medico logueado', () => {
      const router = buildVisitaRouter()
      router.push('/visitas/nuevo')
      mount(VisitaCreateView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })
      cy.get('select').eq(2).should('have.value', '2')
    })
  })
})
