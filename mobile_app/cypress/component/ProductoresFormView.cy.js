import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import ProductoresFormView from '../../src/views/ProductoresFormView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter(initialRoute) {
  const router = createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/productores/nuevo', component: ProductoresFormView },
      { path: '/productores', component: EmptyView },
    ],
  })
  if (initialRoute) router.push(initialRoute)
  return router
}

describe('ProductoresFormView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })

    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    cy.intercept('POST', '**/api/productores', { statusCode: 500, body: { message: 'Offline' } }).as('storeProductor')

    cy.window().then((win) => {
      cy.stub(win, 'alert').returns(undefined)
    })
  })

  it('renderiza paso 1 con campos de productor', () => {
    const router = buildRouter('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.contains('Registrar Productor', { timeout: 5000 }).should('be.visible')
    cy.contains('Continuar al Paso 2').should('be.visible')
    cy.contains('Cancelar').should('be.visible')
    cy.get('input[placeholder*="Ej: Pepito"]').should('be.visible')
  })

  it('valida campos requeridos en paso 1', () => {
    const router = buildRouter('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.get('form').submit()
    cy.window().should((win) => {
      expect(win.alert.calledWithMatch(/campos requeridos/i)).to.be.true
    })
  })

  it('valida formato CURP en paso 1', () => {
    const router = buildRouter('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.get('input[placeholder*="Pepito"]').type('Juan')
    cy.get('input[placeholder*="Tejeda"]').type('Perez')
    cy.get('input[maxlength="18"]').first().type('CURP-INVALIDA')
    cy.get('input[placeholder*="57625285"]').type('UPP-001')
    cy.contains('Continuar al Paso 2').click()
    cy.window().then((win) => {
      expect(win.alert.calledWithMatch(/CURP/i)).to.be.true
    })
  })

  it('navega a paso 2 tras validacion exitosa', () => {
    const router = buildRouter('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.get('input[placeholder*="Pepito"]').type('Juan')
    cy.get('input[placeholder*="Tejeda"]').type('Perez')
    cy.get('input[maxlength="18"]').first().type('JUAP841212HDFRRN01')
    cy.get('input[placeholder*="57625285"]').type('UPP-001')
    cy.contains('Continuar al Paso 2').click()

    cy.contains('Información del Rancho / Predio', { timeout: 5000 }).should('be.visible')
    cy.contains('Finalizar y Guardar Todo').should('be.visible')
    cy.contains('No tiene predio (Solo Productor)').should('be.visible')
  })

  it('guarda solo productor sin rancho', () => {
    cy.seedIndexedDB('catalogos', 'predios', [])

    cy.intercept('POST', '**/api/productores', {
      statusCode: 200,
      body: { success: true, productor: { id: 100, nombre: 'Juan', apellido_paterno: 'Perez', curp: 'JUAP841212HDFRRN01' } },
    }).as('storeProductor')

    const router = buildRouter('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.get('input[placeholder*="Pepito"]').type('Juan')
    cy.get('input[placeholder*="Tejeda"]').type('Perez')
    cy.get('input[maxlength="18"]').first().type('JUAP841212HDFRRN01')
    cy.get('input[placeholder*="57625285"]').type('UPP-001')
    cy.get('form').submit()

    cy.contains('No tiene predio (Solo Productor)').click()
    cy.window().should((win) => {
      expect(win.alert.calledWithMatch(/guardado|productor/i)).to.be.true
    })
  })

  it('guarda productor con rancho', () => {
    cy.seedIndexedDB('catalogos', 'predios', [])

    cy.intercept('POST', '**/api/productores', {
      statusCode: 200,
      body: { success: true, productor: { id: 100, nombre: 'Juan', apellido_paterno: 'Perez', curp: 'JUAP841212HDFRRN01' }, predio: { id: 200 } },
    }).as('storeProductor')

    const router = buildRouter('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.get('input[placeholder*="Pepito"]').type('Juan')
    cy.get('input[placeholder*="Tejeda"]').type('Perez')
    cy.get('input[maxlength="18"]').first().type('JUAP841212HDFRRN01')
    cy.get('input[placeholder*="57625285"]').type('UPP-001')
    cy.get('form').submit()

    cy.get('input[placeholder*="Mirador"]').type('Rancho Nuevo')
    cy.get('input[placeholder*="180104330002"]').type('CUP-001')
    cy.contains('Finalizar y Guardar Todo').click()

    cy.window().should((win) => {
      expect(win.alert.calledWithMatch(/guardado|productor|rancho/i)).to.be.true
    })
  })

  it('navega paso 2 con datos de rancho y guarda con detectGPS mock', () => {
    cy.seedIndexedDB('catalogos', 'predios', [])

    cy.intercept('POST', '**/api/productores', {
      statusCode: 200,
      body: { success: true, productor: { id: 99, nombre: 'Juan', apellido_paterno: 'Perez' } },
    }).as('storeProductor')

    const router = buildRouter()
    router.push('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.get('input').eq(0).type('Juan')
    cy.get('input').eq(1).type('Perez')
    cy.get('input').eq(2).type('Lopez')
    cy.get('input[maxlength="18"]').type('JUAP841212HDFRRN01')
    cy.get('input').eq(4).type('UPP-001')

    cy.get('form').submit()

    cy.contains('Paso 2', { timeout: 5000 }).should('be.visible')

    cy.get('input[placeholder*="El Mirador"]').type('Rancho GPS')
    cy.get('input[placeholder*="180104330002"]').type('GPS-UPP')
    cy.get('input[placeholder*="21.948694"]').first().type('21.5')

    cy.contains('button', /guardar|finalizar/i).click()
    cy.wait('@storeProductor', { timeout: 10000 })
    cy.window().should((win) => {
      expect(win.alert.calledWithMatch(/exitosamente|registrado/i)).to.be.true
    })
  })

  it('cancela navegando a productores', () => {
    const router = buildRouter()
    router.push('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.contains('Cancelar').click()
    cy.location('hash', { timeout: 5000 }).should('include', '/productores')
  })

  // ---- Clave de Cuarentena ----

  it('Admin ve campo Clave de Cuarentena y Zona', () => {
    const router = buildRouter('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.contains('Clave de Cuarentena').should('be.visible')
    cy.get('input[placeholder*="BD-123421"]').should('be.visible')
    cy.contains('Zona / Sector').should('be.visible')
    cy.get('select').should('be.visible')
    cy.contains('option', 'Sector A').should('be.visible')
    cy.contains('option', 'Sector B').should('be.visible')
  })

  it('Medico NO ve campo Clave de Cuarentena', () => {
    cy.setLoginState({ user: userMedico })

    const router = buildRouter('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.contains('Clave de Cuarentena').should('not.exist')
    cy.get('input[placeholder*="BD-123421"]').should('not.exist')
    cy.contains('Zona / Sector').should('not.exist')
  })

  it('autoSelectZona asigna A para clave que empieza con A', () => {
    const router = buildRouter('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.get('input[placeholder*="BD-123421"]').type('AD-123')
    cy.get('select').should('have.value', 'A')
  })

  it('autoSelectZona asigna B para clave que empieza con B', () => {
    const router = buildRouter('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.get('input[placeholder*="BD-123421"]').type('BP-456')
    cy.get('select').should('have.value', 'B')
  })

  it('payload incluye clave_cuarentena para Admin', () => {
    cy.seedIndexedDB('catalogos', 'predios', [])

    cy.intercept('POST', '**/api/productores', {
      statusCode: 200,
      body: { success: true, productor: { id: 200, nombre: 'Juan', apellido_paterno: 'Perez', curp: 'JUAP841212HDFRRN01' } },
    }).as('storeProductor')

    const router = buildRouter('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.get('input[placeholder*="Pepito"]').type('Juan')
    cy.get('input[placeholder*="Tejeda"]').type('Perez')
    cy.get('input[maxlength="18"]').first().type('JUAP841212HDFRRN01')
    cy.get('input[placeholder*="57625285"]').type('UPP-001')
    cy.get('input[placeholder*="BD-123421"]').type('AD-999')
    cy.get('form').submit()

    cy.contains('No tiene predio (Solo Productor)').click()
    cy.wait('@storeProductor').then((interception) => {
      expect(interception.request.body.clave_cuarentena).to.eq('AD-999')
      expect(interception.request.body.zona).to.eq('A')
    })
  })

  it('payload NO incluye clave_cuarentena para Medico', () => {
    cy.setLoginState({ user: userMedico })
    cy.seedIndexedDB('catalogos', 'predios', [])

    cy.intercept('POST', '**/api/productores', {
      statusCode: 200,
      body: { success: true, productor: { id: 200, nombre: 'Juan', apellido_paterno: 'Perez', curp: 'JUAP841212HDFRRN01' } },
    }).as('storeProductor')

    const router = buildRouter('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.get('input[placeholder*="Pepito"]').type('Juan')
    cy.get('input[placeholder*="Tejeda"]').type('Perez')
    cy.get('input[maxlength="18"]').first().type('JUAP841212HDFRRN01')
    cy.get('input[placeholder*="57625285"]').type('UPP-001')
    cy.get('form').submit()

    cy.contains('No tiene predio (Solo Productor)').click()
    cy.wait('@storeProductor').then((interception) => {
      expect(interception.request.body.clave_cuarentena).to.be.null
      expect(interception.request.body.zona).to.be.null
    })
  })

  it('clave_cuarentena se guarda en IndexedDB', () => {
    cy.seedIndexedDB('catalogos', 'predios', [])

    cy.intercept('POST', '**/api/productores', {
      statusCode: 200,
      body: { success: true, productor: { id: 300, nombre: 'Maria', apellido_paterno: 'Lopez', curp: 'MALO850101HPLRRN01' } },
    }).as('storeProductor')

    const router = buildRouter('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.get('input[placeholder*="Pepito"]').type('Maria')
    cy.get('input[placeholder*="Tejeda"]').type('Lopez')
    cy.get('input[maxlength="18"]').first().type('MALO850101HPLRRN01')
    cy.get('input[placeholder*="57625285"]').type('UPP-002')
    cy.get('input[placeholder*="BD-123421"]').type('BD-789')
    cy.get('form').submit()

    cy.contains('No tiene predio (Solo Productor)').click()
    cy.wait('@storeProductor', { timeout: 10000 })

    cy.getIndexedDB('catalogos', 'predios').then((predios) => {
      const saved = predios.find(p => p.productor?.curp === 'MALO850101HPLRRN01')
      expect(saved).to.exist
      expect(saved.productor.clave_cuarentena).to.eq('BD-789')
      expect(saved.productor.zona).to.eq('B')
    })
  })
})
