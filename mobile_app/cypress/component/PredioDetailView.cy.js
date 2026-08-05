import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import PredioDetailView from '../../src/views/PredioDetailView.vue'
import userAdmin from '../fixtures/user-admin.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/predios/:id', name: 'PredioDetail', component: PredioDetailView },
      { path: '/predios/editar/:id', component: EmptyView },
      { path: '/predios', component: EmptyView },
      { path: '/animales', component: EmptyView },
      { path: '/animales/:id', component: EmptyView },
      { path: '/productores/:id', component: EmptyView },
      { path: '/inspeccion/:predioId', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
}

const fakePredio = {
  id: 1,
  nombre_rancho: 'Rancho Norte',
  clave_unidad_produccion: 'CUP-001',
  municipio: 'Centro',
  localidad: 'Villahermosa',
  domicilio: 'Calle 1',
  latitud: 18.0,
  longitud: -93.0,
  productor: { id: 1, nombre: 'Juan', apellido_paterno: 'Perez' },
  animales: [
    { id: 1, numero_arete_siniiga: 'MX-001-001', raza: 'Angus', sexo: 'Macho', edad: 3 },
    { id: 2, numero_arete_siniiga: 'MX-001-002', raza: 'Brangus', sexo: 'Hembra', edad: 2 },
  ],
}

describe('PredioDetailView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    cy.intercept('HEAD', '**/api/user', { statusCode: 200, body: {} }).as('getUser')
  })

  it('renderiza informacion del predio', () => {
    cy.intercept('GET', '**/api/predios/1', {
      statusCode: 200,
      body: { data: fakePredio },
    }).as('getPredio')

    const router = buildRouter()
    router.push('/predios/1')
    mount(PredioDetailView, { global: { plugins: [router] } })

    cy.wait('@getPredio', { timeout: 10000 })
    cy.contains('Rancho Norte', { timeout: 5000 }).should('be.visible')
    cy.contains('CUP-001').should('be.visible')
    cy.contains('Juan Perez').should('be.visible')
    cy.contains('Centro').should('be.visible')
    cy.contains('Villahermosa').should('be.visible')
  })

  it('renderiza tabla de animales con filas clickeables', () => {
    cy.intercept('GET', '**/api/predios/1', {
      statusCode: 200,
      body: { data: fakePredio },
    }).as('getPredio')

    const router = buildRouter()
    router.push('/predios/1')
    mount(PredioDetailView, { global: { plugins: [router] } })

    cy.wait('@getPredio', { timeout: 10000 })
    cy.contains('Animales', { timeout: 5000 }).should('be.visible')
    cy.contains('MX-001-001').should('be.visible')
    cy.contains('MX-001-002').should('be.visible')
    cy.get('.clickable-row').should('have.length', 2)
  })

  it('boton Ver todos navega a /animales', () => {
    cy.intercept('GET', '**/api/predios/1', {
      statusCode: 200,
      body: { data: fakePredio },
    }).as('getPredio')

    const router = buildRouter()
    router.push('/predios/1')
    mount(PredioDetailView, { global: { plugins: [router] } })

    cy.wait('@getPredio', { timeout: 10000 })
    cy.contains('Ver todos', { timeout: 5000 }).should('be.visible')
    cy.contains('Ver todos').click()
    cy.location('hash', { timeout: 5000 }).should('include', '/animales')
  })

  it('muestra coordenadas cuando existen', () => {
    cy.intercept('GET', '**/api/predios/1', {
      statusCode: 200,
      body: { data: fakePredio },
    }).as('getPredio')

    const router = buildRouter()
    router.push('/predios/1')
    mount(PredioDetailView, { global: { plugins: [router] } })

    cy.wait('@getPredio', { timeout: 10000 })
    cy.contains('18, -93', { timeout: 5000 }).should('be.visible')
  })

  it('muestra botones de accion: Editar, Nuevo Dictamen, Volver', () => {
    cy.intercept('GET', '**/api/predios/1', {
      statusCode: 200,
      body: { data: fakePredio },
    }).as('getPredio')

    const router = buildRouter()
    router.push('/predios/1')
    mount(PredioDetailView, { global: { plugins: [router] } })

    cy.wait('@getPredio', { timeout: 10000 })
    cy.contains('Editar', { timeout: 5000 }).should('be.visible')
    cy.contains('Nuevo Dictamen').should('be.visible')
    cy.contains('Volver').should('be.visible')
  })

  it('muestra error si falla carga', () => {
    cy.intercept('GET', '**/api/predios/999', {
      statusCode: 404,
      body: { message: 'Predio no encontrado' },
    }).as('getPredioError')

    const router = buildRouter()
    router.push('/predios/999')
    mount(PredioDetailView, { global: { plugins: [router] } })

    cy.wait('@getPredioError', { timeout: 10000 })
    cy.contains('no encontrado', { timeout: 5000 }).should('be.visible')
  })

  it('elimina predio con confirmacion', () => {
    cy.intercept('GET', '**/api/predios/1', {
      statusCode: 200,
      body: { data: fakePredio },
    }).as('getPredio')

    cy.intercept('DELETE', '**/api/predios/1', {
      statusCode: 200,
      body: { success: true },
    }).as('deletePredio')

    const router = buildRouter()
    router.push('/predios/1')
    mount(PredioDetailView, { global: { plugins: [router] } })

    cy.wait('@getPredio', { timeout: 10000 })

    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(true)
    })
    cy.contains('Eliminar', { timeout: 5000 }).click()
    cy.wait('@deletePredio', { timeout: 10000 })
    cy.location('hash', { timeout: 5000 }).should('include', '/predios')
  })

  it('cancela eliminacion cuando confirm es false', () => {
    cy.intercept('GET', '**/api/predios/1', {
      statusCode: 200,
      body: { data: fakePredio },
    }).as('getPredio')

    cy.intercept('DELETE', '**/api/predios/1', {
      statusCode: 200,
      body: { success: true },
    }).as('deletePredio')

    const router = buildRouter()
    router.push('/predios/1')
    mount(PredioDetailView, { global: { plugins: [router] } })

    cy.wait('@getPredio', { timeout: 10000 })

    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(false)
    })
    cy.contains('Eliminar', { timeout: 5000 }).click()
    cy.get('@deletePredio').should('not.exist')
  })
})
