import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import AnimalDetailView from '../../src/views/AnimalDetailView.vue'
import userAdmin from '../fixtures/user-admin.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/animales/:id', name: 'AnimalDetail', component: AnimalDetailView },
      { path: '/animales/editar/:id', component: EmptyView },
      { path: '/animales', component: EmptyView },
      { path: '/predios/:id', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
}

const fakeAnimal = {
  id: 1,
  numero_arete_siniiga: 'MX-001-001',
  raza: 'Angus',
  sexo: 'Macho',
  edad: 3,
  predio: {
    id: 1,
    nombre_rancho: 'Rancho Norte',
    clave_unidad_produccion: 'CUP-001',
    municipio: 'Centro',
    productor: { id: 1, nombre: 'Juan', apellido_paterno: 'Perez' },
  },
}

describe('AnimalDetailView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    cy.intercept('HEAD', '**/api/user', { statusCode: 200, body: {} }).as('getUser')
  })

  it('renderiza datos del animal', () => {
    cy.intercept('GET', '**/api/animales/1', {
      statusCode: 200,
      body: { data: fakeAnimal },
    }).as('getAnimal')

    const router = buildRouter()
    router.push('/animales/1')
    mount(AnimalDetailView, { global: { plugins: [router] } })

    cy.wait('@getAnimal', { timeout: 10000 })
    cy.contains('MX-001-001', { timeout: 5000 }).should('be.visible')
    cy.contains('Angus').should('be.visible')
    cy.contains('Macho').should('be.visible')
    cy.contains('Rancho Norte').should('be.visible')
    cy.contains('Juan Perez').should('be.visible')
  })

  it('muestra boton de editar', () => {
    cy.intercept('GET', '**/api/animales/1', {
      statusCode: 200,
      body: { data: fakeAnimal },
    }).as('getAnimal')

    const router = buildRouter()
    router.push('/animales/1')
    mount(AnimalDetailView, { global: { plugins: [router] } })

    cy.wait('@getAnimal', { timeout: 10000 })
    cy.contains('Editar', { timeout: 5000 }).should('be.visible')
  })

  it('muestra error si falla la carga', () => {
    cy.intercept('GET', '**/api/animales/999', {
      statusCode: 404,
      body: { message: 'Animal no encontrado' },
    }).as('getAnimalError')

    const router = buildRouter()
    router.push('/animales/999')
    mount(AnimalDetailView, { global: { plugins: [router] } })

    cy.wait('@getAnimalError', { timeout: 10000 })
    cy.contains('no encontrado', { timeout: 5000 }).should('be.visible')
  })
})
