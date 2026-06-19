import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import AreteCensoDetailView from '../../src/views/AreteCensoDetailView.vue'
import userAdmin from '../fixtures/user-admin.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/aretes-censo/:id', name: 'AreteCensoDetail', component: AreteCensoDetailView },
      { path: '/aretes-censo/editar/:id', component: EmptyView },
      { path: '/aretes-censo', component: EmptyView },
      { path: '/productores/:id', component: EmptyView },
      { path: '/predios/:id', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
}

const fakeArete = {
  id: 1,
  numero_arete: 'MX-001-001',
  raza: 'Angus',
  sexo: 'Macho',
  edad_meses: 36,
  fecha_nacimiento: '2023-01-15',
  sacrificio: false,
  productor: { id: 1, nombre: 'Juan', apellido_paterno: 'Perez' },
  predio: { id: 1, nombre_rancho: 'Rancho Norte', clave_unidad_produccion: 'CUP-001', municipio: 'Centro' },
}

describe('AreteCensoDetailView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    cy.intercept('HEAD', '**/api/user', { statusCode: 200, body: {} }).as('getUser')
  })

  it('renderiza datos del arete', () => {
    cy.intercept('GET', '**/api/aretes-censo/1', {
      statusCode: 200,
      body: { data: fakeArete },
    }).as('getArete')

    const router = buildRouter()
    router.push('/aretes-censo/1')
    mount(AreteCensoDetailView, { global: { plugins: [router] } })

    cy.wait('@getArete', { timeout: 10000 })
    cy.contains('Arete MX-001-001', { timeout: 5000 }).should('be.visible')
    cy.contains('Angus').should('be.visible')
    cy.contains('Macho').should('be.visible')
    cy.contains('36').should('be.visible')
    cy.contains('2023-01-15').should('be.visible')
    cy.contains('No').should('be.visible')
  })

  it('muestra info de productor y predio', () => {
    cy.intercept('GET', '**/api/aretes-censo/1', {
      statusCode: 200,
      body: { data: fakeArete },
    }).as('getArete')

    const router = buildRouter()
    router.push('/aretes-censo/1')
    mount(AreteCensoDetailView, { global: { plugins: [router] } })

    cy.wait('@getArete', { timeout: 10000 })
    cy.contains('Juan Perez', { timeout: 5000 }).should('be.visible')
    cy.contains('Rancho Norte').should('be.visible')
    cy.contains('Centro').should('be.visible')
  })

  it('muestra boton de editar y volver', () => {
    cy.intercept('GET', '**/api/aretes-censo/1', {
      statusCode: 200,
      body: { data: fakeArete },
    }).as('getArete')

    const router = buildRouter()
    router.push('/aretes-censo/1')
    mount(AreteCensoDetailView, { global: { plugins: [router] } })

    cy.wait('@getArete', { timeout: 10000 })
    cy.contains('Editar', { timeout: 5000 }).should('be.visible')
    cy.contains('Volver').should('be.visible')
  })

  it('muestra error si falla carga', () => {
    cy.intercept('GET', '**/api/aretes-censo/999', {
      statusCode: 404,
      body: { message: 'Arete no encontrado' },
    }).as('getAreteError')

    const router = buildRouter()
    router.push('/aretes-censo/999')
    mount(AreteCensoDetailView, { global: { plugins: [router] } })

    cy.wait('@getAreteError', { timeout: 10000 })
    cy.contains('no encontrado', { timeout: 5000 }).should('be.visible')
  })
})
