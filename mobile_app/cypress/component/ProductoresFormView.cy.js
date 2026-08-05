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

    cy.contains('label', /^Clave$/).should('be.visible')
    cy.get('input[placeholder*="BD-123421"]').should('be.visible')
    cy.contains('Zona / Sector').should('be.visible')
    cy.get('[data-testid="zona-select"]').should('be.visible')
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
    cy.get('[data-testid="zona-select"]').should('have.value', 'A')
  })

  it('autoSelectZona asigna B para clave que empieza con B', () => {
    const router = buildRouter('/productores/nuevo')
    mount(ProductoresFormView, { global: { plugins: [router] } })

    cy.get('input[placeholder*="BD-123421"]').type('BP-456')
    cy.get('[data-testid="zona-select"]').should('have.value', 'B')
  })

  it('payload incluye clave para Admin', () => {
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
      expect(interception.request.body.clave).to.eq('AD-999')
      expect(interception.request.body.zona).to.eq('A')
    })
  })

  it('payload NO incluye clave para Medico', () => {
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
      expect(interception.request.body.clave).to.be.null
      expect(interception.request.body.zona).to.be.null
    })
  })

  it('clave se guarda en IndexedDB', () => {
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
      expect(saved.productor.clave).to.eq('BD-789')
      expect(saved.productor.zona).to.eq('B')
    })
  })

  // ---- Flujo "Añadir Productor a uno existente" (vincular=true) ----

  const productorExistente = {
    id: 5,
    nombre: 'Maria',
    apellido_paterno: 'Lopez',
    apellido_materno: 'Ruiz',
    upp: 'UPP-005',
    curp: 'MALR850101HPLRRR01',
    domicilio: 'Calle 1',
    municipio: 'Tepic',
    localidad: 'Tepic',
    estado: 'Nayarit',
    telefono: '311',
    email: 'm@x.com',
    clave: 'AD-999',
    zona: 'A',
    tipo_actividad: 'Barrido',
    medico_id: 1,
    _predio_nombre_rancho: 'El Mirador',
    _predio_clave_unidad_produccion: 'CUP-005',
  }

  function interceptBuscar() {
    cy.intercept('GET', '**/api/productores/buscar?q=*', {
      statusCode: 200,
      body: [productorExistente],
    }).as('buscar')
  }

  it('vincular=true muestra pantalla de seleccion de productor', () => {
    const router = buildRouter('/productores/nuevo?vincular=true')
    cy.wrap(router.isReady()).then(() => {
      mount(ProductoresFormView, { global: { plugins: [router] } })
    })

    cy.contains('Añadir Productor a uno existente', { timeout: 5000 }).should('be.visible')
    cy.get('input[placeholder*="Buscar productor por nombre"]').should('be.visible')
    cy.contains('Continuar al Paso 2').should('not.exist')
  })

  it('selecciona productor existente y prellena el formulario con su clave', () => {
    interceptBuscar()

    const router = buildRouter('/productores/nuevo?vincular=true')
    cy.wrap(router.isReady()).then(() => {
      mount(ProductoresFormView, { global: { plugins: [router] } })
    })

    cy.get('input[placeholder*="Buscar productor por nombre"]').type('Maria')
    cy.wait('@buscar', { timeout: 10000 })
    cy.contains('Maria Lopez Ruiz').click()

    cy.contains('Cantidad de productores a registrar').should('be.visible')
    cy.contains('Comenzar registro').click()

    cy.contains('Vinculando nuevos productores al hato', { timeout: 5000 }).should('be.visible')
    cy.contains('Productor 1 de 1').should('be.visible')
    cy.get('input[placeholder*="BD-123421"]').should('have.value', 'AD-999')
    cy.get('input[placeholder*="Calle"]').should('have.value', 'Calle 1')
    cy.get('input[placeholder*="Pepito"]').type('Juan')
    cy.get('input[placeholder*="Tejeda"]').type('Perez')
    cy.get('input[maxlength="18"]').first().type('JUAP841212HDFRRN01')
    cy.get('input[placeholder*="57625285"]').type('UPP-001')
    cy.contains('Continuar al Paso 2').click()
    cy.get('input[placeholder*="Mirador"]').should('have.value', 'El Mirador')
  })

  it('quita la seleccion y vuelve a la busqueda', () => {
    interceptBuscar()

    const router = buildRouter('/productores/nuevo?vincular=true')
    cy.wrap(router.isReady()).then(() => {
      mount(ProductoresFormView, { global: { plugins: [router] } })
    })

    cy.get('input[placeholder*="Buscar productor por nombre"]').type('Maria')
    cy.wait('@buscar', { timeout: 10000 })
    cy.contains('Maria Lopez Ruiz').click()
    cy.contains('Comenzar registro').should('be.visible')

    cy.get('.btn-outline-danger').click()
    cy.contains('Cantidad de productores a registrar').should('not.exist')
    cy.get('input[placeholder*="Buscar productor por nombre"]').should('be.visible')
  })

  it('guarda un productor vinculado al hato del seleccionado', () => {
    cy.seedIndexedDB('catalogos', 'predios', [])
    interceptBuscar()

    cy.intercept('POST', '**/api/productores', {
      statusCode: 200,
      body: { success: true, productor: { id: 500, nombre: 'Juan', apellido_paterno: 'Perez', curp: 'JUAP841212HDFRRN01' } },
    }).as('storeProductor')

    const router = buildRouter('/productores/nuevo?vincular=true')
    cy.wrap(router.isReady()).then(() => {
      mount(ProductoresFormView, { global: { plugins: [router] } })
    })

    cy.get('input[placeholder*="Buscar productor por nombre"]').type('Maria')
    cy.wait('@buscar', { timeout: 10000 })
    cy.contains('Maria Lopez Ruiz').click()
    cy.contains('Comenzar registro').click()

    cy.get('input[placeholder*="Pepito"]').type('Juan')
    cy.get('input[placeholder*="Tejeda"]').type('Perez')
    cy.get('input[maxlength="18"]').first().type('JUAP841212HDFRRN01')
    cy.get('input[placeholder*="57625285"]').type('UPP-001')
    cy.get('form').submit()

    cy.contains('No tiene predio (Solo Productor)').click()
    cy.wait('@storeProductor', { timeout: 10000 }).then((interception) => {
      expect(interception.request.body.clave).to.eq('AD-999')
      expect(interception.request.body.zona).to.eq('A')
    })
    cy.window().should((win) => {
      expect(win.alert.calledWithMatch(/al hato AD-999/)).to.be.true
    })
    cy.location('hash', { timeout: 5000 }).should('include', '/productores')
  })

  it('registra 2 productores de forma secuencial al mismo hato', () => {
    cy.seedIndexedDB('catalogos', 'predios', [])
    interceptBuscar()

    cy.intercept('POST', '**/api/productores', (req) => {
      const id = req.body.curp === 'JUAP841212HDFRRN01' ? 500 : 501
      req.reply({ statusCode: 200, body: { success: true, productor: { id, nombre: 'X', apellido_paterno: 'Y', curp: req.body.curp } } })
    }).as('storeProductor')

    const router = buildRouter('/productores/nuevo?vincular=true')
    cy.wrap(router.isReady()).then(() => {
      mount(ProductoresFormView, { global: { plugins: [router] } })
    })

    cy.get('input[placeholder*="Buscar productor por nombre"]').type('Maria')
    cy.wait('@buscar', { timeout: 10000 })
    cy.contains('Maria Lopez Ruiz').click()
    cy.get('input[type="number"]').type('{selectall}2')
    cy.contains('Comenzar registro').click()

    // Productor 1 de 2
    cy.contains('Productor 1 de 2', { timeout: 5000 }).should('be.visible')
    cy.get('input[placeholder*="Pepito"]').type('Juan')
    cy.get('input[placeholder*="Tejeda"]').type('Perez')
    cy.get('input[maxlength="18"]').first().type('JUAP841212HDFRRN01')
    cy.get('input[placeholder*="57625285"]').type('UPP-001')
    cy.get('form').submit()
    cy.contains('No tiene predio (Solo Productor)').click()
    cy.wait('@storeProductor', { timeout: 10000 })

    // Productor 2 de 2: formulario limpio con clave heredada
    cy.contains('Productor 2 de 2', { timeout: 5000 }).should('be.visible')
    cy.get('input[placeholder*="Pepito"]').should('have.value', '')
    cy.get('input[placeholder*="BD-123421"]').should('have.value', 'AD-999')
    cy.get('input[placeholder*="Pepito"]').type('Ana')
    cy.get('input[placeholder*="Tejeda"]').type('Garcia')
    cy.get('input[maxlength="18"]').first().type('GARA851212MDFRRN01')
    cy.get('input[placeholder*="57625285"]').type('UPP-002')
    cy.get('form').submit()
    cy.contains('No tiene predio (Solo Productor)').click()

    cy.wait('@storeProductor', { timeout: 10000 }).then((interception) => {
      expect(interception.request.body.clave).to.eq('AD-999')
    })
    cy.window().should((win) => {
      expect(win.alert.calledWithMatch(/2 productor\(es\) registrado\(s\) al hato AD-999/)).to.be.true
    })
    cy.location('hash', { timeout: 5000 }).should('include', '/productores')
  })
})
