import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import AreteCensoFormView from '../../src/views/AreteCensoFormView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter(initialRoute) {
  const router = createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/aretes-censo/nuevo', name: 'NuevoAreteCenso', component: AreteCensoFormView },
      { path: '/aretes-censo/editar/:id', name: 'EditarAreteCenso', component: AreteCensoFormView },
      { path: '/aretes-censo', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
  if (initialRoute) router.push(initialRoute)
  return router
}

const fakeSelects = {
  productores: { data: [{ id: 1, nombre: 'Juan', apellido_paterno: 'Perez' }] },
  predios: { data: [{ id: 1, nombre_rancho: 'Rancho Norte', clave_unidad_produccion: 'CUP-001' }] },
}

describe('AreteCensoFormView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    cy.intercept('HEAD', '**/api/user', { statusCode: 200, body: {} }).as('getUser')
  })

  describe('modo creacion', () => {
    it('renderiza formulario create', () => {
      cy.intercept('GET', '**/api/productores', fakeSelects.productores).as('getProductores')
      cy.intercept('GET', '**/api/predios', fakeSelects.predios).as('getPredios')

      const router = buildRouter('/aretes-censo/nuevo')
      mount(AreteCensoFormView, { global: { plugins: [router] } })

      cy.wait('@getProductores', { timeout: 10000 })
      cy.wait('@getPredios', { timeout: 10000 })
      cy.contains('Registrar Arete del Censo', { timeout: 5000 }).should('be.visible')
      cy.contains('Número de Arete').should('be.visible')
      cy.contains('Productor').should('be.visible')
      cy.contains('Predio').should('be.visible')
    })

    it('guarda arete exitosamente', () => {
      cy.intercept('GET', '**/api/productores', fakeSelects.productores).as('getProductores')
      cy.intercept('GET', '**/api/predios', fakeSelects.predios).as('getPredios')

      cy.intercept('POST', '**/api/aretes-censo', {
        statusCode: 200,
        body: { message: 'Arete del censo registrado correctamente.' },
      }).as('storeArete')

      const router = buildRouter('/aretes-censo/nuevo')
      mount(AreteCensoFormView, { global: { plugins: [router] } })

      cy.wait('@getProductores', { timeout: 10000 })
      cy.wait('@getPredios', { timeout: 10000 })

      cy.get('input').first().type('MX-001-003')
      cy.get('select').eq(0).select('1')
      cy.get('select').eq(1).select('1')
      cy.get('form').submit()

      cy.wait('@storeArete', { timeout: 10000 })
      cy.contains('registrado correctamente', { timeout: 5000 }).should('be.visible')
    })
  })

  describe('control de acceso', () => {
    it('redirige medico a dashboard', () => {
      cy.setLoginState({ user: userMedico })

      cy.window().then((win) => {
        cy.stub(win, 'alert').as('alertStub')
      })

      const router = buildRouter('/aretes-censo/nuevo')
      mount(AreteCensoFormView, { global: { plugins: [router] } })

      cy.get('@alertStub').should('have.been.calledWithMatch', /Solo administradores/i)
      cy.location('hash', { timeout: 5000 }).should('include', '/dashboard')
    })
  })
})
