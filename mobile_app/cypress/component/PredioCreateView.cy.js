import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import PredioCreateView from '../../src/views/PredioCreateView.vue'
import userAdmin from '../fixtures/user-admin.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter(initialRoute) {
  const router = createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/predios/nuevo', name: 'NuevoPredio', component: PredioCreateView },
      { path: '/predios', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
    ],
  })
  if (initialRoute) router.push(initialRoute)
  return router
}

function seedPredios(productorOverride) {
  cy.seedIndexedDB('catalogos', 'predios', [
    {
      id: 1,
      nombre_rancho: 'Rancho Test',
      productor: productorOverride || { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia', apellido_materno: 'Lopez' },
      localidad: 'X',
      municipio: 'Y',
    },
  ])
}

describe('PredioCreateView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })

    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    cy.intercept('GET', '**/api/productores', { statusCode: 500, body: { message: 'Offline' } }).as('getProductores')
  })

  describe('modo creacion', () => {
    it('renderiza formulario create', () => {
      const router = buildRouter('/predios/nuevo')
      mount(PredioCreateView, { global: { plugins: [router] } })

      cy.contains('Registrar Unidad de Producción', { timeout: 5000 }).should('be.visible')
      cy.contains('Nombre del Rancho / Predio').should('be.visible')
      cy.contains('Productor Responsable').should('be.visible')
      cy.contains('Clave de Unidad de Producción').should('be.visible')
    })

    it('muestra validacion requerida al enviar vacio', () => {
      const router = buildRouter('/predios/nuevo')
      mount(PredioCreateView, { global: { plugins: [router] } })

      cy.get('form').submit()
      cy.get('input:invalid').should('exist')
    })

    it('carga productores desde IndexedDB como fallback', () => {
      seedPredios()

      const router = buildRouter('/predios/nuevo')
      mount(PredioCreateView, { global: { plugins: [router] } })

      cy.get('select').first().find('option').should('have.length.at.least', 2)
      cy.contains('Maria Garcia Lopez').should('be.visible')
    })

    it('valida campos requeridos del rancho', () => {
      const router = buildRouter('/predios/nuevo')
      mount(PredioCreateView, { global: { plugins: [router] } })

      cy.get('form').submit()
      cy.contains('Por favor complete todos los campos obligatorios del Rancho', { timeout: 5000 }).should('be.visible')
    })

    it('valida seleccion de productor', () => {
      const router = buildRouter('/predios/nuevo')
      mount(PredioCreateView, { global: { plugins: [router] } })

      cy.get('input').first().type('Rancho Nuevo')
      cy.get('input').eq(1).type('UPP-001')
      cy.get('input').last().type('Localidad Test')
      cy.get('form').submit()
      cy.contains('seleccione un productor', { timeout: 5000 }).should('be.visible')
    })

    it('valida coordenadas fuera de rango', () => {
      seedPredios()

      const router = buildRouter('/predios/nuevo')
      mount(PredioCreateView, { global: { plugins: [router] } })

      cy.get('input').first().type('Rancho Nuevo')
      cy.get('select').first().select('1')
      cy.get('input').eq(1).type('UPP-001')
      cy.get('input').last().type('Localidad Test')

      cy.get('input[placeholder*="21.948694"]').type('-91')
      cy.get('form').submit()
      cy.contains('latitud debe ser un número entre -90 y 90', { timeout: 5000 }).should('be.visible')
    })

    it('guarda predio exitosamente', () => {
      seedPredios()

      cy.intercept('POST', '**/api/predios', {
        statusCode: 200,
        body: { predio: { id: 1, nombre_rancho: 'Rancho Nuevo' }, message: 'Rancho registrado con éxito' },
      }).as('storeRancho')

      const router = buildRouter('/predios/nuevo')
      mount(PredioCreateView, { global: { plugins: [router] } })

      cy.get('input').first().type('Rancho Nuevo')
      cy.get('select').first().select('1')
      cy.get('input').eq(1).type('UPP-001')
      cy.get('input').last().type('Localidad Test')
      cy.get('form').submit()

      cy.wait('@storeRancho', { timeout: 10000 })
      cy.contains('registrado con éxito', { timeout: 5000 }).should('be.visible')
    })

    it('muestra error cuando falla guardado de predio', () => {
      seedPredios()

      cy.intercept('POST', '**/api/predios', {
        statusCode: 500,
        body: { message: 'Error del servidor' },
      }).as('storeRanchoError')

      const router = buildRouter('/predios/nuevo')
      mount(PredioCreateView, { global: { plugins: [router] } })

      cy.get('input').first().type('Rancho Nuevo')
      cy.get('select').first().select('1')
      cy.get('input').eq(1).type('UPP-001')
      cy.get('input').last().type('Localidad Test')
      cy.get('form').submit()

      cy.wait('@storeRanchoError', { timeout: 10000 })
      cy.get('.alert.alert-danger', { timeout: 5000 }).should('be.visible')
    })
  })

  describe('modal nuevo productor', () => {
    it('abre y cierra modal', () => {
      const router = buildRouter('/predios/nuevo')
      mount(PredioCreateView, { global: { plugins: [router] } })

      cy.contains('Nuevo Productor').click()
      cy.contains('Registrar Nuevo Productor', { timeout: 5000 }).should('be.visible')

      cy.get('.btn-close-modal').click()
      cy.contains('Registrar Nuevo Productor').should('not.exist')
    })

    it('valida campos requeridos en modal', () => {
      const router = buildRouter('/predios/nuevo')
      mount(PredioCreateView, { global: { plugins: [router] } })

      cy.contains('Nuevo Productor').click()
      cy.contains('Guardar y Seleccionar').click()
      cy.contains('complete los campos obligatorios', { timeout: 5000 }).should('be.visible')
    })

    it('valida formato CURP en modal', () => {
      const router = buildRouter('/predios/nuevo')
      mount(PredioCreateView, { global: { plugins: [router] } })

      cy.contains('Nuevo Productor').click()
      cy.get('.modal-body input').first().type('Juan')
      cy.get('.modal-body input').eq(1).type('Perez')
      cy.get('input[maxlength="18"]').first().type('CURPINVALIDA')
      cy.contains('Guardar y Seleccionar').click()
      cy.contains('CURP debe tener exactamente 18 caracteres', { timeout: 5000 }).should('exist')
    })

    it('guarda productor y lo selecciona en el dropdown', () => {
      seedPredios()

      cy.intercept('POST', '**/api/productores', {
        statusCode: 200,
        body: { success: true, productor: { id: 99, nombre: 'Nuevo', apellido_paterno: 'Productor' } },
      }).as('storeProductor')

      const router = buildRouter('/predios/nuevo')
      mount(PredioCreateView, { global: { plugins: [router] } })

      cy.contains('Nuevo Productor').click()
      cy.get('.modal-body input').first().type('Juan')
      cy.get('.modal-body input').eq(1).type('Perez')
      cy.get('input[maxlength="18"]').first().type('JUAP841212HDFRRN01')
      cy.contains('Guardar y Seleccionar').click()

      cy.wait('@storeProductor', { timeout: 10000 })
      cy.contains('Nuevo Productor').should('be.visible')
      cy.get('.modal-overlay').should('not.exist')
    })
  })

  describe('modo edicion', () => {
    it('renderiza formulario edit con query param', () => {
      seedPredios({ id: 1, nombre: 'Maria', apellido_paterno: 'Garcia', apellido_materno: 'Lopez' })

      cy.intercept('GET', '**/api/predios', { statusCode: 500, body: { message: 'Offline' } }).as('getPrediosEdit')

      const router = buildRouter({ path: '/predios/nuevo', query: { predio_id: '1' } })
      mount(PredioCreateView, { global: { plugins: [router] } })

      cy.contains('Editar Unidad de Producción', { timeout: 5000 }).should('be.visible')
    })
  })
})
