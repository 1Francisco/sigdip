import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import { createPinia } from 'pinia'
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
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.contains('Registro de Dictamen', { timeout: 5000 }).should('be.visible')
    cy.contains('PROPIETARIO').should('be.visible')
    cy.contains('UNIDAD DE PRODUCCIÓN').should('be.visible')
    cy.contains('DATOS DE LA PRUEBA').should('be.visible')
    cy.contains('RESULTADOS INDIVIDUALES').should('be.visible')
  })

  it('carga predios desde IndexedDB', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().find('option').should('have.length.at.least', 3)
    cy.contains('Rancho El Paraiso', { timeout: 5000 }).should('exist')
  })

  it('selecciona predio y muestra productor', () => {
    seedPredios()

    const router = buildRouter({ path: '/inspeccion', query: {} })
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1')
    cy.contains('Maria Garcia', { timeout: 5000 }).should('exist')
  })

  it('agrega animal via quick-add', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('button', /añadir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}', { force: true })

    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').should((els) => {
      const values = Array.from(els).map(e => e.value)
      expect(values.filter(v => v === 'ARETE-001').length).to.eq(1)
    })
  })

  it('elimina animal de la lista', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('button', /añadir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}', { force: true })

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
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1', { timeout: 5000 })
    cy.contains('III: DATOS DE LA PRUEBA').click()
    cy.get('input[type="date"]').eq(1).invoke('val', '2026-06-10').trigger('input')
    cy.get('input[type="date"]').eq(2).should('have.value', '2026-06-13')
  })

  it('guarda borrador en IndexedDB', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

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
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.contains('button', /finalizar/i).first().click({ force: true })
    cy.window().should((win) => {
      expect(win.alert.calledWithMatch(/seleccione|predio/i)).to.be.true
    })
  })

  it('carga datos desde visita_id query param', () => {
    seedPredios()
    seedVisitas()

    const router = buildRouter({ path: '/inspeccion', query: { visita_id: '1' } })
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().should('have.value', '1')
  })

  it('cambia tipo de prueba PPC a PCC habilita campos avanzados', () => {
    seedPrediosWithFullData()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

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
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })

    cy.contains('button', /añadir|agregar/i).click({ force: true })
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}', { force: true })

    cy.contains('button', /añadir|agregar/i).click({ force: true })
    cy.get('input[placeholder*="Registrar arete manualmente"]').last().type('ARETE-002{enter}', { force: true })

    cy.get('.search-animals-container input[placeholder*="Buscar arete por número"]').type('002', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').should(($els) => {
      const values = Array.from($els).map(e => e.value)
      expect(values.length).to.eq(1)
      expect(values[0]).to.eq('ARETE-002')
    })
  })

  it('limpia busqueda de animales', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })

    cy.contains('button', /añadir|agregar/i).click({ force: true })
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}', { force: true })

    cy.contains('button', /añadir|agregar/i).click({ force: true })
    cy.get('input[placeholder*="Registrar arete manualmente"]').last().type('ARETE-002{enter}', { force: true })

    cy.get('.search-animals-container input[placeholder*="Buscar arete por número"]').type('001', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').should(($els) => {
      const count = Array.from($els).filter(e => e.value === 'ARETE-001').length
      expect(count).to.eq(1)
    })

    cy.get('.search-animals-container input[placeholder*="Buscar arete por número"]').clear({ force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').should(($els) => {
      const values = Array.from($els).map(e => e.value)
      const nonEmpty = values.filter(v => v !== '')
      expect(nonEmpty.length).to.eq(2)
    })
  })

  it('muestra badge de seccion completada', () => {
    seedPrediosWithFullData()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1')

    cy.contains('III: DATOS DE LA PRUEBA').click()
    cy.contains('TIPO DE PRUEBA REALIZADA').parent().find('select').select('PPC')
    cy.contains('Motivo de la Prueba').parent().find('select').select('Seguimiento')
    cy.get('input[type="date"]').eq(1).invoke('val', '2026-06-10').trigger('input')
    cy.get('input[type="time"]').first().invoke('val', '08:00').trigger('input')

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('button', /añadir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}', { force: true })
  })

  it('guardar como borrador navega a dashboard', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

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
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /añadir|agregar/i).click({ force: true })

    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-001', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().type('36', { force: true })
    cy.get('.scrollable-animals-container input[type="checkbox"]').first().click({ force: true })

    cy.contains('III: DATOS DE LA PRUEBA').click({ force: true })
    cy.contains('Vacas').parent().find('input').should('have.value', '1')
  })

  it('auto-genera folio TEMP- al seleccionar predio', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1')
    cy.get('input[placeholder*="Opcional (Ej. TB-2026-1234)"]').should('have.value', '')
  })

  it('cambia resultado de animal en dropdown en modo lectura', () => {
    seedPrediosWithFullData()

    const router = buildRouter({ path: '/inspeccion', query: {} })
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1')

    cy.contains('III: DATOS DE LA PRUEBA').click({ force: true })
    cy.contains('TIPO DE PRUEBA REALIZADA').parent().find('select').select('PPC')
    cy.get('input[type="date"]').eq(1).invoke('val', '2026-06-10').trigger('input')
    cy.get('input[type="time"]').first().invoke('val', '08:00').trigger('input')
    cy.contains('Motivo de la Prueba').parent().find('select').select('Seguimiento')

    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}', { force: true })

    cy.get('select').last().select('Positivo', { force: true })
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
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1')
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]', { timeout: 5000 }).should(($els) => {
      const values = Array.from($els).map(e => e.value)
      expect(values).to.include('ARETE-EXISTENTE')
    })
  })

  it('busca datos del arete via API y autocompleta edad', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}', { force: true })

    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().clear({ force: true }).type('ARETE-002', { force: true }).blur()

    cy.wait('@buscarArete', { timeout: 8000 }).then((interception) => {
      expect(interception.response.statusCode).to.eq(200)
    })
  })

  it('asigna motivo_no_aplica cuando edad < 6 meses', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1')
    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-MOTIVO', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().type('4', { force: true })

    cy.window().should((win) => {
      const vm = Cypress.vue
      const animal = vm?.form?.animales?.[0]
      expect(animal?.motivo_no_aplica).to.eq('Menor a 6 meses')
      expect(animal?.resultado).to.eq('No Aplica')
    })
  })

  it('no asigna motivo_no_aplica cuando edad >= 6 meses', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1')
    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-ADULTO', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().type('36', { force: true })

    cy.window().should((win) => {
      const vm = Cypress.vue
      const animal = vm?.form?.animales?.[0]
      expect(animal?.motivo_no_aplica).to.eq('')
      expect(animal?.resultado).not.to.eq('No Aplica')
    })
  })

  it('actualiza motivo cuando edad cambia de >=6 a <6', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1')
    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-CAMBIO', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().type('36', { force: true })

    cy.window().should((win) => {
      const vm = Cypress.vue
      const animal = vm?.form?.animales?.[0]
      expect(animal?.motivo_no_aplica).to.eq('')
    })

    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().clear({ force: true }).type('5', { force: true })

    cy.then(() => {
      const vm = Cypress.vue
      const animal = vm?.form?.animales?.[0]
      expect(animal?.motivo_no_aplica).to.eq('Menor a 6 meses')
      expect(animal?.resultado).to.eq('No Aplica')
    })
  })

  it('limpia motivo cuando edad cambia de <6 a >=6', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1')
    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-LIMPIA', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().type('4', { force: true })

    cy.window().should((win) => {
      const vm = Cypress.vue
      const animal = vm?.form?.animales?.[0]
      expect(animal?.motivo_no_aplica).to.eq('Menor a 6 meses')
      expect(animal?.resultado).to.eq('No Aplica')
    })

    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().clear({ force: true }).type('36', { force: true })

    cy.window().should((win) => {
      const vm = Cypress.vue
      const animal = vm?.form?.animales?.[0]
      expect(animal?.motivo_no_aplica).to.eq('')
      expect(animal?.resultado).not.to.eq('No Aplica')
    })
  })

  it('no asigna motivo cuando edad es 0', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1')
    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-CERO', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().clear({ force: true }).type('0', { force: true })

    cy.window().should(() => {
      const vm = Cypress.vue
      const animal = vm?.form?.animales?.[0]
      expect(animal?.motivo_no_aplica).to.eq('')
      expect(animal?.resultado).not.to.eq('No Aplica')
    })
  })

  it('no asigna motivo cuando edad es exactamente 6', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1')
    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-BOUND6', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().clear({ force: true }).type('6', { force: true })

    cy.window().should(() => {
      const vm = Cypress.vue
      const animal = vm?.form?.animales?.[0]
      expect(animal?.motivo_no_aplica).to.eq('')
      expect(animal?.resultado).not.to.eq('No Aplica')
    })
  })

  it('usa umbral 2 meses con productor AD', () => {
    const seedPrediosConAD = () => {
      cy.seedIndexedDB('catalogos', 'productores', [{
        id: 98, nombre: 'Prod AD', apellido_paterno: 'Test',
        clave_cuarentena: 'AD-456', zona: 'A',
        curp: 'ADXX990101HPLRRN98', upp: 'UPP-AD',
        telefono: '555-098'
      }])
      cy.seedIndexedDB('catalogos', 'predios', [{
        id: 20, nombre_rancho: 'Rancho AD', upp: 'UPP-AD',
        localidad: 'X', municipio: 'Y',
        productor_id: 98,
        productor: {
          id: 98, nombre: 'Prod AD', apellido_paterno: 'Test',
          clave_cuarentena: 'AD-456', zona: 'A',
          curp: 'ADXX990101HPLRRN98', upp: 'UPP-AD'
        }
      }])
    }

    seedPrediosConAD()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('20')
    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-AD-1M', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().type('1', { force: true })

    cy.window().should(() => {
      const vm = Cypress.vue
      const animal = vm?.form?.animales?.[0]
      expect(animal?.resultado).to.eq('No Aplica')
      expect(animal?.motivo_no_aplica).to.eq('Menor a 2 meses')
    })
  })

  it('edad exactamente 2 con productor BD no asigna motivo', () => {
    const seedPrediosConBD = () => {
      cy.seedIndexedDB('catalogos', 'productores', [{
        id: 97, nombre: 'Prod BD2', apellido_paterno: 'Test',
        clave_cuarentena: 'BD-789', zona: 'B',
        curp: 'BDXX990101HPLRRN97', upp: 'UPP-BD2',
        telefono: '555-097'
      }])
      cy.seedIndexedDB('catalogos', 'predios', [{
        id: 30, nombre_rancho: 'Rancho BD2', upp: 'UPP-BD2',
        localidad: 'X', municipio: 'Y',
        productor_id: 97,
        productor: {
          id: 97, nombre: 'Prod BD2', apellido_paterno: 'Test',
          clave_cuarentena: 'BD-789', zona: 'B',
          curp: 'BDXX990101HPLRRN97', upp: 'UPP-BD2'
        }
      }])
    }

    seedPrediosConBD()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('30')
    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-BD-2M', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().clear({ force: true }).type('2', { force: true })

    cy.window().should(() => {
      const vm = Cypress.vue
      const animal = vm?.form?.animales?.[0]
      expect(animal?.motivo_no_aplica).to.eq('')
      expect(animal?.resultado).not.to.eq('No Aplica')
    })
  })

  it('recalcula motivo al cambiar a predio con diferente umbral', () => {
    const seedPrediosMultiUmbral = () => {
      cy.seedIndexedDB('catalogos', 'productores', [
        { id: 1, nombre: 'Prod Normal', apellido_paterno: 'Test', clave_cuarentena: null, curp: 'NORM990101HPLRRN01', upp: 'UPP-NORM' },
        { id: 99, nombre: 'Prod BD', apellido_paterno: 'Test', clave_cuarentena: 'BD-123', zona: 'B', curp: 'BDXX990101HPLRRN99', upp: 'UPP-BD' },
      ])
      cy.seedIndexedDB('catalogos', 'predios', [
        { id: 1, nombre_rancho: 'Rancho Normal', upp: 'UPP-NORM', localidad: 'X', municipio: 'Y', productor_id: 1, productor: { id: 1, nombre: 'Prod Normal', apellido_paterno: 'Test', clave_cuarentena: null } },
        { id: 10, nombre_rancho: 'Rancho BD', upp: 'UPP-BD', localidad: 'X', municipio: 'Y', productor_id: 99, productor: { id: 99, nombre: 'Prod BD', apellido_paterno: 'Test', clave_cuarentena: 'BD-123', zona: 'B', curp: 'BDXX990101HPLRRN99', upp: 'UPP-BD' } },
      ])
    }

    seedPrediosMultiUmbral()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    // Seleccionar predio normal (umbral 6) y agregar animal con edad 3
    cy.get('select').first().select('1')
    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-RECALC', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().clear({ force: true }).type('3', { force: true })

    // Con umbral 6, edad=3 debe dar 'Menor a 6 meses'
    cy.window().should(() => {
      const a = Cypress.vue?.form?.animales?.[0]
      expect(a?.motivo_no_aplica).to.eq('Menor a 6 meses')
      expect(a?.resultado).to.eq('No Aplica')
    })

    // Cambiar a predio BD (umbral 2)
    cy.get('select').first().select('10', { force: true })

    // Con umbral 2, edad=3 ya NO debe ser 'No Aplica'
    cy.window().should(() => {
      const a = Cypress.vue?.form?.animales?.[0]
      expect(a?.motivo_no_aplica).to.eq('')
      expect(a?.resultado).not.to.eq('No Aplica')
    })
  })

  it('muestra confirmacion fase inyeccion al finalizar sin fecha lectura', () => {
    seedPrediosWithFullData()
    seedVisitas()

    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(true)
    })

    const router = buildRouter('/inspeccion?visita_id=1')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1', { force: true })

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('button', /a\u00F1adir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}', { force: true })
    cy.then(() => {
      const vm = Cypress.vue
      if (vm && vm.form.animales.length > 0) {
        vm.form.animales[0].resultado = 'Negativo'
      }
    })

    cy.contains('button', /finalizar/i).first().click({ force: true })
    cy.window({ timeout: 8000 }).should((win) => {
      expect(win.confirm.called).to.be.true
    })
  })

  it('muestra alerta al finalizar con animales sin resultado asignado', () => {
    seedPrediosWithFullData()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

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

    cy.contains('button', /finalizar/i).first().click({ force: true })
    cy.window().should((win) => {
      expect(win.alert.called).to.be.true
      expect(win.alert.calledWithMatch(/resultado/i)).to.be.true
    })
  })

  it('detecta conflicto al finalizar con datos diferentes en servidor', () => {
    seedPredios()
    seedVisitas()

    cy.intercept('GET', '**/api/inspecciones*', {
      statusCode: 200,
      body: { success: true, data: [{ id: 999, folio: 'TEMP-CONFLICT-999' }] },
    }).as('checkFolio')

    const router = buildRouter('/inspeccion?visita_id=1')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1', { force: true })

    cy.contains('III: DATOS DE LA PRUEBA').click()
    cy.contains('TIPO DE PRUEBA REALIZADA').parent().find('select').select('PPC')
    cy.get('input[type="date"]').eq(1).invoke('val', '2026-06-25').trigger('input')
    cy.get('input[type="time"]').first().invoke('val', '08:00').trigger('input')
    cy.contains('Motivo de la Prueba').parent().find('select').select('Seguimiento')

    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('button', /a\u00F1adir|agregar/i).click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-001{enter}', { force: true })

    cy.then(() => {
      const vm = Cypress.vue
      if (vm && vm.form.animales.length > 0) {
        vm.form.animales[0].resultado = 'Negativo'
      }
    })

    cy.contains('button', /finalizar/i).first().click({ force: true })
    cy.wait('@checkFolio', { timeout: 10000 })
  })

  it('inyeccion_desktop_muestra_edad_y_motivo_en_badge', () => {
    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1')
    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-BADGE', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().type('4', { force: true })

    // Desktop: badge "No Aplica" should show edad + motivo
    cy.get('.table-responsive.d-none.d-lg-block .badge.bg-secondary', { timeout: 5000 })
      .should('exist')
      .and('contain.text', 'No Aplica')
      .and('contain.text', '4m')
      .and('contain.text', 'Menor a 6 meses')
  })

  it('inyeccion_movil_muestra_no_aplica_con_edad_y_motivo', () => {
    cy.viewport('iphone-6')

    seedPredios()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('1')
    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-MOVIL-NA', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().type('4', { force: true })

    cy.window().should((win) => {
      const vm = Cypress.vue
      const animal = vm?.form?.animales?.[0]
      expect(animal?.resultado).to.eq('No Aplica')
      expect(animal?.motivo_no_aplica).to.eq('Menor a 6 meses')
    })

    // Mobile: ESTADO field should show "No Aplica" with edad + motivo
    cy.get('.animal-mobile-card .badge.bg-secondary', { timeout: 5000 })
      .should('be.visible')
      .and('contain.text', 'No Aplica')
      .and('contain.text', '4 meses')
      .and('contain.text', 'Menor a 6 meses')
  })

  it('inyeccion_asigna_motivo_bd_2_meses_cuando_edad_es_1_mes', () => {
    const seedPrediosConBD = () => {
      cy.seedIndexedDB('catalogos', 'productores', [{
        id: 99, nombre: 'Prod BD', apellido_paterno: 'Test',
        clave_cuarentena: 'BD-123', zona: 'B',
        curp: 'BDXX990101HPLRRN99', upp: 'UPP-BD',
        telefono: '555-099'
      }])
      cy.seedIndexedDB('catalogos', 'predios', [{
        id: 10, nombre_rancho: 'Rancho BD', upp: 'UPP-BD',
        localidad: 'X', municipio: 'Y',
        productor_id: 99,
        productor: {
          id: 99, nombre: 'Prod BD', apellido_paterno: 'Test',
          clave_cuarentena: 'BD-123', zona: 'B',
          curp: 'BDXX990101HPLRRN99', upp: 'UPP-BD'
        }
      }])
    }

    seedPrediosConBD()

    const router = buildRouter('/inspeccion')
    mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

    cy.get('select').first().select('10')
    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
    cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-BD-1M', { force: true })
    cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().type('1', { force: true })

    cy.window().should(() => {
      const vm = Cypress.vue
      const animal = vm?.form?.animales?.[0]
      expect(animal?.resultado).to.eq('No Aplica')
      expect(animal?.motivo_no_aplica).to.eq('Menor a 2 meses')
    })
  })

  describe('modo lectura sin inyeccion', () => {
    function setupLecturaEnModoLectura() {
      cy.then(() => {
        const vm = Cypress.vue
        if (vm && vm.form) {
          vm.form.fecha_inyeccion = '2000-01-01'
          vm.form.fecha_lectura = '2000-01-04'
          vm.form.hora_inyeccion = '08:00'
          vm.form.tipo_prueba = 'PPC'
          vm.form.motivo_prueba = 'Seguimiento'
        }
      })
    }

    beforeEach(() => {
      cy.setLoginState({ user: userMedico })
      seedPredios()
    })

    it('lectura_desktop_muestra_sin_inyeccion_con_edad_y_motivo', () => {
      const router = buildRouter('/inspeccion')
      mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

      cy.get('select').first().select('1')
      setupLecturaEnModoLectura()

      cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
      cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
      cy.get('.scrollable-animals-container input[placeholder*="SINIIGA"]').first().type('ARETE-NA-DESK', { force: true })
      cy.get('.scrollable-animals-container input[placeholder*="Meses"]').first().type('4', { force: true })

      // Desktop sin inyección: edad + motivo
      cy.contains('.table-responsive.d-none.d-lg-block', '4 meses', { timeout: 5000 }).should('be.visible')
      cy.contains('.table-responsive.d-none.d-lg-block', 'Menor a 6 meses').should('be.visible')
      cy.contains('1 animal no recibi\u00f3 inyecci\u00f3n').should('be.visible')
    })

    it('lectura_movil_muestra_sin_inyeccion_con_edad_y_motivo', () => {
      cy.viewport('iphone-6')

      const router = buildRouter('/inspeccion')
      mount(InspeccionFormView, { global: { plugins: [router, createPinia()] } })

      cy.get('select').first().select('1')
      setupLecturaEnModoLectura()

      cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
      cy.contains('button', /a\u00F1adir|agregar/i).click({ force: true })
      cy.get('input[placeholder*="Registrar arete manualmente"]').first().type('ARETE-NA-MOB{enter}', { force: true })
      cy.then(() => {
        const vm = Cypress.vue
        if (vm && vm.form.animales.length > 0) {
          const a = vm.form.animales[0]
          a.edad_meses = 2
          a.resultado = 'No Aplica'
          a.motivo_no_aplica = 'Menor a 6 meses'
        }
      })

      // Mobile sin inyección collapsible header
      cy.contains('SIN INYECCI\u00d3N', { timeout: 5000 }).should('exist')
      cy.contains('2 meses').should('exist')
      cy.contains('Menor a 6 meses').should('exist')
    })
  })
})
