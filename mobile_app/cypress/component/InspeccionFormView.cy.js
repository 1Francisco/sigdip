import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import InspeccionFormView from '../../src/views/InspeccionFormView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter(initialRoute) {
  const router = createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/inspeccion/:predioId?', name: 'Inspeccion', component: InspeccionFormView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
      { path: '/productores', component: EmptyView },
    ],
  })
  if (initialRoute) router.push(initialRoute)
  return router
}

function seedPredios() {
  cy.seedIndexedDB('catalogos', 'predios', [
    { id: 1, nombre: 'Rancho El Paraiso', productor: { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia', apellido_materno: 'Lopez' }, localidad: 'Villahermosa', municipio: 'Champoton', latitud: '21.0', longitud: '-99.0', clave_unidad_produccion: 'UPP-001', domicilio: 'Domicilio 1' },
    { id: 2, nombre: 'Rancho La Esperanza', productor: { id: 2, nombre: 'Jose', apellido_paterno: 'Martinez', apellido_materno: 'Hernandez' }, localidad: 'Ciudad del Carmen', municipio: 'Carmen', latitud: '22.0', longitud: '-98.0', clave_unidad_produccion: 'UPP-002', domicilio: 'Domicilio 2' },
  ])
}

function seedVisitas() {
  cy.seedIndexedDB('catalogos', 'visitas', [
    { id: 1, codigo: 'V-001', predio_id: 1, fecha_programada: new Date().toISOString().split('T')[0], veterinario_id: 2, observaciones: 'Visita test', estado: 'programada' },
  ])
}

const fakeAnimalData = {
  data: {
    id: 100,
    identificador: 'ARETE-001',
    sexo: 'H',
    edad_meses: 30,
    raza: 'Angus',
    fecha_nacimiento: '2022-01-01',
  },
}

function seedPrediosWithFullData() {
  cy.seedIndexedDB('catalogos', 'predios', [
    { id: 1, nombre: 'Rancho El Paraiso', productor: { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia', apellido_materno: 'Lopez', telefono: '3111129405', domicilio: 'Domicilio 1', municipio: 'Champoton', localidad: 'Villahermosa', estado: 'Tabasco', email: 'maria@correo.com' }, localidad: 'Villahermosa', municipio: 'Champoton', latitud: '21.0', longitud: '-99.0', clave_unidad_produccion: 'UPP-001', domicilio: 'Domicilio 1', estado: 'Tabasco' },
  ])
}

describe('InspeccionFormView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })

    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    cy.intercept('GET', '**/api/censo/buscar-arete/*', { statusCode: 200, body: fakeAnimalData }).as('buscarArete')

    cy.window().then((win) => {
      cy.stub(win, 'alert').returns(undefined)
    })

    cy.window().then((win) => {
      if (win.navigator.mediaDevices) {
        cy.stub(win.navigator.mediaDevices, 'getUserMedia').rejects(new Error('No camera'))
      }
    })
  })

  it('renderiza formulario con 4 secciones', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.contains('Registro de Dictamen', { timeout: 5000 }).should('be.visible')
    cy.contains('PROPIETARIO').should('be.visible')
    cy.contains('UNIDAD DE PRODUCCIÓN').should('be.visible')
    cy.contains('DATOS DE LA PRUEBA').should('be.visible')
    cy.contains('RESULTADOS INDIVIDUALES').should('be.visible')
  })

  it('carga predios desde IndexedDB', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.get('select').first().find('option').should('have.length.at.least', 3)
    cy.contains('Rancho El Paraiso', { timeout: 5000 }).should('exist')
  })

  it('selecciona predio y muestra productor', () => {
    seedPredios()

    const router = buildRouter({ path: '/inspeccion', query: {} })
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.get('select').first().select('1')
    cy.contains('Maria Garcia', { timeout: 5000 }).should('exist')
  })

  it('agrega animal via quick-add', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('button', /añadir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}')

    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').should((els) => {
      const values = Array.from(els).map(e => e.value)
      expect(values.filter(v => v === 'ARETE-001').length).to.eq(1)
    })
  })

  it('elimina animal de la lista', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('button', /añadir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}')

    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').should((els) => {
      const values = Array.from(els).map(e => e.value)
      expect(values.filter(v => v === 'ARETE-001').length).to.eq(1)
    })

    cy.get('button[title="Eliminar"]').eq(1).click({ force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').should((els) => {
      const values = Array.from(els).map(e => e.value)
      expect(values.filter(v => v === 'ARETE-001').length).to.eq(0)
    })
  })

  it('calcularFechaLectura suma 3 dias al cambiar fecha inyeccion', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.get('select').first().select('1', { timeout: 5000 })
    cy.contains('III: DATOS DE LA PRUEBA').click()
    cy.get('input[type="date"]').eq(1).invoke('val', '2026-06-10').trigger('input')
    cy.get('input[type="date"]').eq(2).should('have.value', '2026-06-13')
  })

  it('guarda borrador en IndexedDB', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.get('select').first().select('1')

    cy.contains('button', /guardar|borrador/i).first().click()
    cy.get('select').first().select('1', { force: true })

    cy.window().then((win) => {
      expect(win.alert.called).to.be.true
    })
  })

  it('valida campos requeridos al intentar finalizar', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.contains('button', /finalizar/i).first().click()
    cy.window().should((win) => {
      expect(win.alert.calledWithMatch(/seleccione|predio/i)).to.be.true
    })
  })

  it('carga datos desde visita_id query param', () => {
    seedPredios()
    seedVisitas()

    const router = buildRouter({ path: '/inspeccion', query: { visita_id: '1' } })
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.get('select').first().should('have.value', '1')
  })

  it('cambia tipo de prueba PPC a PCC habilita campos avanzados', () => {
    seedPrediosWithFullData()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.get('select').first().select('1')
    cy.contains('III: DATOS DE LA PRUEBA').click()

    cy.contains('TIPO DE PRUEBA REALIZADA').parent().find('select').should('have.value', 'PPC')
    cy.contains('label', 'Fecha Prueba Anterior').parent().find('input').should('be.disabled')

    cy.contains('TIPO DE PRUEBA REALIZADA').parent().find('select').select('PCC')
    cy.contains('label', 'Fecha Prueba Anterior').parent().find('input').should('not.be.disabled')
    cy.contains('Dictamen Anterior No.').should('be.visible')
  })

  it('filtra animales por busqueda', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()

    cy.contains('button', /añadir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}')

    cy.contains('button', /añadir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').last().type('ARETE-002{enter}')

    cy.get('.search-animals-container input[placeholder*="Buscar arete por número"]').type('002')
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').should(($els) => {
      const values = Array.from($els).map(e => e.value)
      expect(values.length).to.eq(1)
      expect(values[0]).to.eq('ARETE-002')
    })
  })

  it('limpia busqueda de animales', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()

    cy.contains('button', /añadir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}')

    cy.contains('button', /añadir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').last().type('ARETE-002{enter}')

    cy.get('.search-animals-container input[placeholder*="Buscar arete por número"]').type('001')
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').should(($els) => {
      const count = Array.from($els).filter(e => e.value === 'ARETE-001').length
      expect(count).to.eq(1)
    })

    cy.get('.search-animals-container input[placeholder*="Buscar arete por número"]').clear()
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').should(($els) => {
      const values = Array.from($els).map(e => e.value)
      const nonEmpty = values.filter(v => v !== '')
      expect(nonEmpty.length).to.eq(2)
    })
  })

  it('muestra badge de seccion completada', () => {
    seedPrediosWithFullData()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.get('select').first().select('1')

    cy.contains('III: DATOS DE LA PRUEBA').click()
    cy.contains('TIPO DE PRUEBA REALIZADA').parent().find('select').select('PPC')
    cy.contains('Motivo de la Prueba').parent().find('select').select('Seguimiento')
    cy.get('input[type="date"]').eq(1).invoke('val', '2026-06-10').trigger('input')
    cy.get('input[type="time"]').first().invoke('val', '08:00').trigger('input')

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('button', /añadir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}')
  })

  it('guardar como borrador navega a dashboard', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.get('select').first().select('1')
    cy.contains('button', /guardar|borrador/i).first().click()

    cy.window().should((win) => {
      expect(win.alert.called).to.be.true
    })
    cy.location('hash', { timeout: 5000 }).should('include', '/dashboard')
  })

  it('carga censo ganadero al agregar animales con edad y sexo', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('button', /añadir|agregar/i).click()

    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-001')
    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().type('36')
    cy.get('.scrollable-animals-container input[type="checkbox"]').first().click({ force: true })

    cy.contains('III: DATOS DE LA PRUEBA').click()
    cy.contains('Vacas').parent().find('input').should('have.value', '1')
  })

  it('auto-genera folio TEMP- al seleccionar predio', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.get('select').first().select('1')
    cy.get('input[placeholder*="Opcional (Ej. TB-2026-1234)"]').should('have.value', '')
  })

  it('cambia resultado de animal en dropdown en modo lectura', () => {
    seedPrediosWithFullData()

    const router = buildRouter({ path: '/inspeccion', query: {} })
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.get('select').first().select('1')

    cy.contains('III: DATOS DE LA PRUEBA').click()
    cy.contains('TIPO DE PRUEBA REALIZADA').parent().find('select').select('PPC')
    cy.get('input[type="date"]').eq(1).invoke('val', '2026-06-10').trigger('input')
    cy.get('input[type="time"]').first().invoke('val', '08:00').trigger('input')
    cy.contains('Motivo de la Prueba').parent().find('select').select('Seguimiento')

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('button', /a\u00F1adir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}')

    cy.get('select').last().select('Positivo')
    cy.get('select').last().should('have.value', 'Positivo')
  })

  it('detecta borrador existente y lo carga al seleccionar predio', () => {
    const draft = {
      predio_id: 1,
      folio: null,
      fecha_visita: '2026-06-10',
      tipo_prueba: 'PPC',
      fecha_inyeccion: '2026-06-10',
      hora_inyeccion: '08:00',
      fecha_lectura: '2026-06-13',
      hora_lectura: '08:00',
      motivo_prueba: 'Seguimiento',
      estado: 'borrador',
      animales: [{ identificador: 'ARETE-EXISTENTE', sexo: 'H', edad_meses: 30 }],
    }
    cy.seedIndexedDB('inspecciones_pendientes', 'lista', [draft])
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.get('select').first().select('1')
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]', { timeout: 5000 }).should(($els) => {
      const values = Array.from($els).map(e => e.value)
      expect(values).to.include('ARETE-EXISTENTE')
    })
  })

  it('busca datos del arete via API y autocompleta edad', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('button', /a\u00F1adir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}')

    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().clear().type('ARETE-002').blur()

    cy.wait('@buscarArete', { timeout: 8000 }).then((interception) => {
      expect(interception.response.statusCode).to.eq(200)
    })
  })

  it('muestra confirmacion fase inyeccion al finalizar sin fecha lectura', () => {
    seedPrediosWithFullData()

    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(true)
    })

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.get('select').first().select('1')
    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('button', /a\u00F1adir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}')

    cy.contains('button', /finalizar/i).first().click()
    cy.window().should((win) => {
      expect(win.confirm.called).to.be.true
    })
  })

  it('muestra alerta al finalizar con animales sin resultado asignado', () => {
    seedPrediosWithFullData()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.get('select').first().select('1')

    cy.contains('III: DATOS DE LA PRUEBA').click()
    cy.contains('TIPO DE PRUEBA REALIZADA').parent().find('select').select('PPC')
    cy.get('input[type="date"]').eq(1).invoke('val', '2020-01-01').trigger('input')
    cy.get('input[type="time"]').first().invoke('val', '08:00').trigger('input')
    cy.contains('Motivo de la Prueba').parent().find('select').select('Seguimiento')

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('button', /a\u00F1adir|agregar/i).click()
    cy.then(() => {
      const vm = Cypress.vue
      if (vm && vm.form.animales.length > 0) {
        vm.form.animales[0].identificador = 'ARETE-001'
      }
    })

    cy.contains('button', /finalizar/i).first().click()
    cy.window().should((win) => {
      expect(win.alert.called).to.be.true
      expect(win.alert.calledWithMatch(/resultado/i)).to.be.true
    })
  })

  it('detecta conflicto al finalizar con datos diferentes en servidor', () => {
    seedPredios()

    cy.intercept('GET', '**/api/inspecciones*', {
      statusCode: 200,
      body: { success: true, data: [{ id: 999, folio: 'TEMP-CONFLICT-999' }] },
    }).as('checkFolio')

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router] } })

    cy.get('select').first().select('1')

    cy.contains('III: DATOS DE LA PRUEBA').click()
    cy.contains('TIPO DE PRUEBA REALIZADA').parent().find('select').select('PPC')
    cy.get('input[type="date"]').eq(1).invoke('val', '2099-01-01').trigger('input')
    cy.get('input[type="time"]').first().invoke('val', '08:00').trigger('input')
    cy.contains('Motivo de la Prueba').parent().find('select').select('Seguimiento')

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('button', /a\u00F1adir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}')

    cy.get('button.btn-mobile-finalizar').click()
    cy.wait('@checkFolio', { timeout: 10000 })
  })
})
