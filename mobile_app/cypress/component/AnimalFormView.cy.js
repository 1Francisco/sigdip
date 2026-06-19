import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import AnimalFormView from '../../src/views/AnimalFormView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter(initialRoute) {
  const router = createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/animales/nuevo', name: 'NuevoAnimal', component: AnimalFormView },
      { path: '/animales/editar/:id', name: 'EditarAnimal', component: AnimalFormView },
      { path: '/animales', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
  if (initialRoute) router.push(initialRoute)
  return router
}

const fakePredios = [
  { id: 1, nombre_rancho: 'Rancho Norte', clave_unidad_produccion: 'CUP-001' },
  { id: 2, nombre_rancho: 'Rancho Sur', clave_unidad_produccion: 'CUP-002' },
]

describe('AnimalFormView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    cy.intercept('HEAD', '**/api/user', { statusCode: 200, body: {} }).as('getUser')
  })

  describe('modo creacion', () => {
    it('renderiza formulario create', () => {
      cy.intercept('GET', '**/api/predios', {
        statusCode: 200,
        body: { data: fakePredios },
      }).as('getPredios')

      const router = buildRouter('/animales/nuevo')
      mount(AnimalFormView, { global: { plugins: [router] } })

      cy.wait('@getPredios', { timeout: 10000 })
      cy.contains('Registrar Animal', { timeout: 5000 }).should('be.visible')
      cy.contains('Número Arete SINIIGA').should('be.visible')
      cy.contains('Predio').should('be.visible')
    })

    it('guarda animal exitosamente', () => {
      cy.intercept('GET', '**/api/predios', {
        statusCode: 200,
        body: { data: fakePredios },
      }).as('getPredios')

      cy.intercept('POST', '**/api/animales', {
        statusCode: 200,
        body: { data: { id: 1 }, message: 'Animal registrado correctamente.' },
      }).as('storeAnimal')

      const router = buildRouter('/animales/nuevo')
      mount(AnimalFormView, { global: { plugins: [router] } })

      cy.wait('@getPredios', { timeout: 10000 })
      cy.get('input').first().type('MX-001-003')
      cy.get('select').eq(0).select('Macho')
      cy.get('input[type="number"]').type('4')
      cy.get('select').eq(1).select('1')
      cy.get('form').submit()

      cy.wait('@storeAnimal', { timeout: 10000 })
      cy.contains('registrado correctamente', { timeout: 5000 }).should('be.visible')
    })
  })

  describe('modo edicion', () => {
    it('carga datos existentes para editar', () => {
      cy.intercept('GET', '**/api/predios', {
        statusCode: 200,
        body: { data: fakePredios },
      }).as('getPredios')

      cy.intercept('GET', '**/api/animales/1', {
        statusCode: 200,
        body: { data: { id: 1, numero_arete_siniiga: 'MX-001-001', raza: 'Angus', sexo: 'Macho', edad: 3, predio_id: 1 } },
      }).as('getAnimal')

      const router = buildRouter('/animales/editar/1')
      mount(AnimalFormView, { global: { plugins: [router] } })

      cy.wait('@getPredios', { timeout: 10000 })
      cy.wait('@getAnimal', { timeout: 10000 })
      cy.contains('Editar Animal', { timeout: 5000 }).should('be.visible')
    })

    it('actualiza animal exitosamente', () => {
      cy.intercept('GET', '**/api/predios', {
        statusCode: 200,
        body: { data: fakePredios },
      }).as('getPredios')

      cy.intercept('GET', '**/api/animales/1', {
        statusCode: 200,
        body: { data: { id: 1, numero_arete_siniiga: 'MX-001-001', raza: 'Angus', sexo: 'Macho', edad: 3, predio_id: 1 } },
      }).as('getAnimal')

      cy.intercept('PUT', '**/api/animales/1', {
        statusCode: 200,
        body: { message: 'Animal actualizado correctamente.' },
      }).as('updateAnimal')

      const router = buildRouter('/animales/editar/1')
      mount(AnimalFormView, { global: { plugins: [router] } })

      cy.wait('@getPredios', { timeout: 10000 })
      cy.wait('@getAnimal', { timeout: 10000 })
      cy.get('form').submit()

      cy.wait('@updateAnimal', { timeout: 10000 })
      cy.contains('actualizado correctamente', { timeout: 5000 }).should('be.visible')
    })
  })

  describe('control de acceso', () => {
    it('redirige medico a dashboard', () => {
      cy.setLoginState({ user: userMedico })

      cy.window().then((win) => {
        cy.stub(win, 'alert').as('alertStub')
      })

      const router = buildRouter('/animales/nuevo')
      mount(AnimalFormView, { global: { plugins: [router] } })

      cy.get('@alertStub').should('have.been.calledWithMatch', /Solo administradores/i)
      cy.location('hash', { timeout: 5000 }).should('include', '/dashboard')
    })
  })
})
