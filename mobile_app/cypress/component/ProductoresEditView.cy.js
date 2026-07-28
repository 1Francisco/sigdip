import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import ProductoresEditView from '../../src/views/ProductoresEditView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter(id) {
  const router = createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/productores/editar/:id', name: 'EditarProductor', component: ProductoresEditView },
      { path: '/productores', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
  if (id) router.push(`/productores/editar/${id}`)
  return router
}

const fakeProductorAPI = {
  data: {
    id: 1,
    nombre: 'Juan',
    apellido_paterno: 'Perez',
    apellido_materno: 'Lopez',
    curp: 'JUAP841212HDFRRN01',
    upp: 'UPP-001',
    domicilio: 'Calle Principal 123',
    municipio: 'Tepic',
    localidad: 'Centro',
    estado: 'Nayarit',
    telefono: '3111129405',
    email: 'juan@correo.com',
    clave: 'BD-789',
    zona: 'B',
    predios: [
      {
        id: 1,
        nombre_rancho: 'Rancho El Test',
        clave_unidad_produccion: 'UPP-001',
        localidad: 'Centro',
        municipio: 'Tepic',
        domicilio: 'Calle Principal 123',
        latitud: '21.0',
        longitud: '-104.0',
      }
    ]
  }
}

const fakePredioIndexedDB = {
  id: 1,
  nombre: 'Rancho El Test',
  nombre_rancho: 'Rancho El Test',
  clave_unidad_produccion: 'UPP-001',
  localidad: 'Centro',
  municipio: 'Tepic',
  domicilio: 'Calle Principal 123',
  latitud: '21.0',
  longitud: '-104.0',
  productor: {
    id: 1,
    nombre: 'Juan',
    apellido_paterno: 'Perez',
    apellido_materno: 'Lopez',
    curp: 'JUAP841212HDFRRN01',
    upp: 'UPP-001',
    domicilio: 'Calle Principal 123',
    municipio: 'Tepic',
    localidad: 'Centro',
    estado: 'Nayarit',
    telefono: '3111129405',
    email: 'juan@correo.com',
    clave: 'BD-789',
    zona: 'B',
  }
}

describe('ProductoresEditView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
  })

  it('renderiza formulario con datos del productor', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: fakeProductorAPI,
    }).as('getProductor')

    const router = buildRouter(1)
    mount(ProductoresEditView, { global: { plugins: [router] } })

    cy.wait('@getProductor', { timeout: 10000 })
    cy.contains('Editar Productor', { timeout: 5000 }).should('be.visible')
    cy.get('input').eq(0).should('have.value', 'Juan')
    cy.get('input').eq(1).should('have.value', 'Perez')
    cy.get('input').eq(2).should('have.value', 'Lopez')
    cy.get('input').eq(3).should('have.value', 'JUAP841212HDFRRN01')
    cy.get('input').eq(4).should('have.value', 'UPP-001')
  })

  it('actualiza productor exitosamente via API y redirige', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: fakeProductorAPI,
    }).as('getProductor')

    cy.intercept('PUT', '**/api/productores/1', {
      statusCode: 200,
      body: { success: true, message: 'Actualizado' },
    }).as('updateProductor')

    const router = buildRouter(1)
    mount(ProductoresEditView, { global: { plugins: [router] } })

    cy.wait('@getProductor', { timeout: 10000 })

    cy.get('input').eq(0).clear().type('Juan Carlos')
    cy.get('form').submit()

    cy.wait('@updateProductor', { timeout: 10000 })
    cy.location('hash', { timeout: 5000 }).should('include', '/productores')
  })

  it('navega a /productores al cancelar', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: fakeProductorAPI,
    }).as('getProductor')

    const router = buildRouter(1)
    mount(ProductoresEditView, { global: { plugins: [router] } })

    cy.wait('@getProductor', { timeout: 10000 })
    cy.contains('Cancelar').click()
    cy.location('hash', { timeout: 5000 }).should('include', '/productores')
  })

  it('carga datos desde IndexedDB cuando API falla', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 500,
      body: { message: 'Error' },
    }).as('getProductorFail')

    cy.seedIndexedDB('catalogos', 'predios', [fakePredioIndexedDB])

    const router = buildRouter(1)
    mount(ProductoresEditView, { global: { plugins: [router] } })

    cy.wait('@getProductorFail', { timeout: 10000 })
    cy.get('input', { timeout: 5000 }).eq(0).should('have.value', 'Juan')
    cy.get('input').eq(1).should('have.value', 'Perez')
  })

  it('guarda en IndexedDB despues de actualizar via API', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: fakeProductorAPI,
    }).as('getProductor')

    cy.intercept('PUT', '**/api/productores/1', {
      statusCode: 200,
      body: { success: true, message: 'Actualizado' },
    }).as('updateProductor')

    cy.seedIndexedDB('catalogos', 'predios', [fakePredioIndexedDB])

    const router = buildRouter(1)
    mount(ProductoresEditView, { global: { plugins: [router] } })

    cy.wait('@getProductor', { timeout: 10000 })
    cy.get('input').eq(0).clear().type('Juan Carlos')
    cy.get('form').submit()

    cy.wait('@updateProductor', { timeout: 10000 })
    cy.location('hash', { timeout: 5000 }).should('include', '/productores')
  })

  // ---- Clave de Cuarentena ----

  it('Admin ve campo Clave de Cuarentena con datos cargados', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: fakeProductorAPI,
    }).as('getProductor')

    const router = buildRouter(1)
    mount(ProductoresEditView, { global: { plugins: [router] } })

    cy.wait('@getProductor', { timeout: 10000 })
    cy.contains('Editar Productor', { timeout: 5000 }).should('be.visible')

    cy.get('input[placeholder*="BD-123421"]').should('have.value', 'BD-789')
    cy.get('select').should('have.value', 'B')
  })

  it('Medico NO ve campo Clave de Cuarentena', () => {
    cy.setLoginState({ user: userMedico })

    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: fakeProductorAPI,
    }).as('getProductor')

    const router = buildRouter(1)
    mount(ProductoresEditView, { global: { plugins: [router] } })

    cy.wait('@getProductor', { timeout: 10000 })
    cy.contains('Editar Productor', { timeout: 5000 }).should('be.visible')

    cy.contains('Clave de Cuarentena').should('not.exist')
    cy.get('input[placeholder*="BD-123421"]').should('not.exist')
    cy.contains('Zona / Sector').should('not.exist')
  })

  it('autoSelectZona asigna A/B segun primera letra de la clave', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: fakeProductorAPI,
    }).as('getProductor')

    const router = buildRouter(1)
    mount(ProductoresEditView, { global: { plugins: [router] } })

    cy.wait('@getProductor', { timeout: 10000 })

    cy.get('input[placeholder*="BD-123421"]').should('have.value', 'BD-789')
    cy.get('select').should('have.value', 'B')

    cy.get('input[placeholder*="BD-123421"]').clear().type('AP-555')
    cy.get('select').should('have.value', 'A')
  })

  it('actualiza clave via API', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: fakeProductorAPI,
    }).as('getProductor')

    cy.intercept('PUT', '**/api/productores/1', {
      statusCode: 200,
      body: { success: true, message: 'Actualizado' },
    }).as('updateProductor')

    cy.seedIndexedDB('catalogos', 'predios', [fakePredioIndexedDB])

    const router = buildRouter(1)
    mount(ProductoresEditView, { global: { plugins: [router] } })

    cy.wait('@getProductor', { timeout: 10000 })

    cy.get('input[placeholder*="BD-123421"]').clear().type('AD-001')
    cy.get('form').submit()

    cy.wait('@updateProductor', { timeout: 10000 }).then((interception) => {
      expect(interception.request.body.clave).to.eq('AD-001')
      expect(interception.request.body.zona).to.eq('A')
    })
  })

  it('carga clave desde IndexedDB offline', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 500,
      body: { message: 'Error' },
    }).as('getProductorFail')

    cy.seedIndexedDB('catalogos', 'predios', [fakePredioIndexedDB])

    const router = buildRouter(1)
    mount(ProductoresEditView, { global: { plugins: [router] } })

    cy.wait('@getProductorFail', { timeout: 10000 })

    cy.get('input[placeholder*="BD-123421"]', { timeout: 5000 }).should('have.value', 'BD-789')
    cy.get('select').should('have.value', 'B')
  })

  it('IndexedDB actualizado con nueva clave', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: fakeProductorAPI,
    }).as('getProductor')

    cy.intercept('PUT', '**/api/productores/1', {
      statusCode: 200,
      body: { success: true, message: 'Actualizado' },
    }).as('updateProductor')

    cy.seedIndexedDB('catalogos', 'predios', [fakePredioIndexedDB])

    const router = buildRouter(1)
    mount(ProductoresEditView, { global: { plugins: [router] } })

    cy.wait('@getProductor', { timeout: 10000 })

    cy.get('input[placeholder*="BD-123421"]').clear().type('AP-999')
    cy.get('form').submit()

    cy.wait('@updateProductor', { timeout: 10000 })
    cy.location('hash', { timeout: 5000 }).should('include', '/productores')

    cy.getIndexedDB('catalogos', 'predios').then((predios) => {
      const updated = predios.find(p => p.productor?.id === 1)
      expect(updated.productor.clave).to.eq('AP-999')
      expect(updated.productor.zona).to.eq('A')
    })
  })
})
