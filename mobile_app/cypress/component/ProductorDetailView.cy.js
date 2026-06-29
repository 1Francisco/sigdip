import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import ProductorDetailView from '../../src/views/ProductorDetailView.vue'
import userAdmin from '../fixtures/user-admin.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/productores/:id', name: 'ProductorDetail', component: ProductorDetailView },
      { path: '/productores/editar/:id', component: EmptyView },
      { path: '/productores', component: EmptyView },
      { path: '/predios/:id', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
}

const fakeProductor = {
  id: 1,
  nombre: 'Juan',
  apellido_paterno: 'Perez',
  apellido_materno: 'Lopez',
  curp: 'PELJ800101HDFRRN01',
  upp: 'UPP-001',
  telefono: '9931234567',
  email: 'juan@example.com',
  municipio: 'Centro',
  localidad: 'Villahermosa',
  estado: 'Tabasco',
  domicilio: 'Calle 1',
  predios_count: 2,
  predios: [
    { id: 1, nombre_rancho: 'Rancho Norte', clave_unidad_produccion: 'CUP-001', municipio: 'Centro' },
    { id: 2, nombre_rancho: 'Rancho Sur', clave_unidad_produccion: 'CUP-002', municipio: 'Centro' },
  ],
}

describe('ProductorDetailView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
  })

  it('muestra estado de carga', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      delay: 300,
      body: { data: fakeProductor },
    }).as('getProductorSlow')

    const router = buildRouter()
    router.push('/productores/1')
    mount(ProductorDetailView, { global: { plugins: [router] } })

    cy.contains('Cargando productor...', { timeout: 5000 }).should('be.visible')
    cy.wait('@getProductorSlow')
    cy.contains('Cargando productor...').should('not.exist')
  })

  it('renderiza informacion del productor', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: { data: fakeProductor },
    }).as('getProductor')

    const router = buildRouter()
    router.push('/productores/1')
    mount(ProductorDetailView, { global: { plugins: [router] } })

    cy.wait('@getProductor', { timeout: 10000 })
    cy.contains('Juan Perez Lopez', { timeout: 5000 }).should('be.visible')
    cy.contains('PELJ800101HDFRRN01').should('be.visible')
    cy.contains('2 predios').should('be.visible')
    cy.contains('9931234567').should('be.visible')
    cy.contains('juan@example.com').should('be.visible')
    cy.contains('Centro').should('be.visible')
    cy.contains('Tabasco').should('be.visible')
  })

  it('muestra lista de predios', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: { data: fakeProductor },
    }).as('getProductor')

    const router = buildRouter()
    router.push('/productores/1')
    mount(ProductorDetailView, { global: { plugins: [router] } })

    cy.wait('@getProductor', { timeout: 10000 })
    cy.contains('Rancho Norte', { timeout: 5000 }).should('be.visible')
    cy.contains('Rancho Sur').should('be.visible')
    cy.contains('CUP-001').should('be.visible')
    cy.contains('CUP-002').should('be.visible')
  })

  it('muestra empty state sin predios', () => {
    const sinPredios = { ...fakeProductor, predios: [], predios_count: 0 }

    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: { data: sinPredios },
    }).as('getProductorSinPredios')

    const router = buildRouter()
    router.push('/productores/1')
    mount(ProductorDetailView, { global: { plugins: [router] } })

    cy.wait('@getProductorSinPredios', { timeout: 10000 })
    cy.contains('Sin predios registrados', { timeout: 5000 }).should('be.visible')
  })

  it('navega entre editar y volver', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: { data: fakeProductor },
    }).as('getProductor')

    const router = buildRouter()
    router.push('/productores/1')
    mount(ProductorDetailView, { global: { plugins: [router] } })

    cy.wait('@getProductor', { timeout: 10000 })

    cy.contains('Editar', { timeout: 5000 }).click()
    cy.location('hash', { timeout: 5000 }).should('include', '/productores/editar/1')

    cy.clock()
    router.push('/productores/1')
    mount(ProductorDetailView, { global: { plugins: [router] } })
    cy.wait('@getProductor', { timeout: 10000 })

    cy.contains('Volver').click()
    cy.location('hash', { timeout: 5000 }).should('include', '/productores')
  })

  it('muestra error cuando falla carga', () => {
    cy.intercept('GET', '**/api/productores/999', {
      statusCode: 404,
      body: { message: 'Productor no encontrado' },
    }).as('getProductorError')

    const router = buildRouter()
    router.push('/productores/999')
    mount(ProductorDetailView, { global: { plugins: [router] } })

    cy.wait('@getProductorError', { timeout: 10000 })
    cy.contains('no encontrado', { timeout: 5000 }).should('be.visible')
  })
})
