import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import InspeccionDetailView from '../../src/views/InspeccionDetailView.vue'
import userAdmin from '../fixtures/user-admin.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter(id) {
  const router = createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/inspecciones/:id', name: 'InspeccionDetail', component: InspeccionDetailView },
      { path: '/inspecciones', component: EmptyView },
      { path: '/inspeccion/:predioId?', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
  if (id) router.push(`/inspecciones/${id}`)
  return router
}

const fakeInspeccion = {
  id: 100,
  clave_interna: 'INSP-2026-001',
  fecha: '2026-06-01',
  estado: 'completada',
  predio_id: 1,
  predio: { id: 1, nombre_rancho: 'Rancho El Paraiso', localidad: 'Villahermosa' },
  veterinario: { name: 'Dr. Juan Perez', id: 2 },
  tipo_prueba: 'PPC',
  motivo_prueba: 'Rastreo',
  funcion_zootecnica: 'Produccion de Carne',
  fecha_inyeccion: '2026-06-01',
  hora_inyeccion: '08:00',
  fecha_lectura: '2026-06-04',
  hora_lectura: '08:00',
  sementales: 2,
  vacas: 5,
  vaquillas: 3,
  becerras: 2,
  becerros: 4,
  detalles: [
    { id: 1, animal: { numero_arete_siniiga: 'ARETE-001', raza: 'Cebu', sexo: 'M' }, raza: 'Cebu', sexo: 'M', resultado_prueba: 'Negativo', observaciones_animal: '' },
    { id: 2, animal: { numero_arete_siniiga: 'ARETE-002', raza: 'Suizo', sexo: 'H' }, raza: 'Suizo', sexo: 'H', resultado_prueba: 'Positivo', observaciones_animal: 'Observado' },
  ],
}

describe('InspeccionDetailView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
  })

  it('renderiza detalle de inspeccion completada', () => {
    cy.intercept('GET', '**/api/inspecciones/100', {
      statusCode: 200,
      body: { data: fakeInspeccion },
    }).as('getInspeccion')

    const router = buildRouter(100)
    mount(InspeccionDetailView, { global: { plugins: [router] } })

    cy.wait('@getInspeccion', { timeout: 10000 })
    cy.contains('INSP-2026-001', { timeout: 5000 }).should('be.visible')
    cy.contains('Rancho El Paraiso').should('be.visible')
    cy.contains('Dr. Juan Perez').should('be.visible')
    cy.contains('Finalizado').should('be.visible')
  })

  it('muestra badge Borrador y boton continuar edicion', () => {
    const borrador = { ...fakeInspeccion, estado: 'borrador', folio: null, clave_interna: null }

    cy.intercept('GET', '**/api/inspecciones/101', {
      statusCode: 200,
      body: { data: borrador },
    }).as('getBorrador')

    const router = buildRouter(101)
    mount(InspeccionDetailView, { global: { plugins: [router] } })

    cy.wait('@getBorrador', { timeout: 10000 })
    cy.contains('Sin Folio (Borrador)', { timeout: 5000 }).should('be.visible')
    cy.contains('Borrador').should('be.visible')
    cy.contains('Continuar edición').should('be.visible')
  })

  it('muestra datos generales', () => {
    cy.intercept('GET', '**/api/inspecciones/100', {
      statusCode: 200,
      body: { data: fakeInspeccion },
    }).as('getInspeccion')

    const router = buildRouter(100)
    mount(InspeccionDetailView, { global: { plugins: [router] } })

    cy.wait('@getInspeccion', { timeout: 10000 })
    cy.contains('Datos Generales', { timeout: 5000 }).should('be.visible')
    cy.contains('PPC').should('be.visible')
    cy.contains('Rastreo').should('be.visible')
    cy.contains('01/06/2026').should('be.visible')
  })

  it('muestra censo ganadero', () => {
    cy.intercept('GET', '**/api/inspecciones/100', {
      statusCode: 200,
      body: { data: fakeInspeccion },
    }).as('getInspeccion')

    const router = buildRouter(100)
    mount(InspeccionDetailView, { global: { plugins: [router] } })

    cy.wait('@getInspeccion', { timeout: 10000 })
    cy.contains('Censo Ganadero', { timeout: 5000 }).should('be.visible')
    cy.contains('2').should('be.visible')
    cy.contains('5').should('be.visible')
  })

  it('muestra animales con resultados', () => {
    cy.intercept('GET', '**/api/inspecciones/100', {
      statusCode: 200,
      body: { data: fakeInspeccion },
    }).as('getInspeccion')

    const router = buildRouter(100)
    mount(InspeccionDetailView, { global: { plugins: [router] } })

    cy.wait('@getInspeccion', { timeout: 10000 })
    cy.contains('Animales', { timeout: 5000 }).should('be.visible')
    cy.contains('ARETE-001').should('be.visible')
    cy.contains('ARETE-002').should('be.visible')
    cy.contains('Negativo').should('be.visible')
    cy.contains('Positivo').should('be.visible')
  })

  it('navega a continuar edicion para borrador', () => {
    const borrador = { ...fakeInspeccion, estado: 'borrador', folio: null, clave_interna: null }

    cy.intercept('GET', '**/api/inspecciones/101', {
      statusCode: 200,
      body: { data: borrador },
    }).as('getBorrador')

    const router = buildRouter(101)
    mount(InspeccionDetailView, { global: { plugins: [router] } })

    cy.wait('@getBorrador', { timeout: 10000 })
      cy.contains('Continuar edición').click()
      cy.location('hash', { timeout: 5000 }).should('include', '/inspeccion/1')
  })

  it('abre PDF en nueva pestania', () => {
    const pdfBlob = new Blob(['%PDF-fake'], { type: 'application/pdf' })

    cy.intercept('GET', '**/api/inspecciones/100', {
      statusCode: 200,
      body: { data: fakeInspeccion },
    }).as('getInspeccion')

    cy.intercept('GET', '**/api/inspecciones/100/pdf', {
      statusCode: 200,
      body: pdfBlob,
    }).as('getPdf')

    cy.window().then((win) => {
      cy.stub(win, 'open').as('windowOpen')
    })

    const router = buildRouter(100)
    mount(InspeccionDetailView, { global: { plugins: [router] } })

    cy.wait('@getInspeccion', { timeout: 10000 })
    cy.contains('PDF').click()
    cy.wait('@getPdf', { timeout: 10000 })
    cy.get('@windowOpen').should('have.been.calledOnce')
  })

  it('muestra motivo_no_aplica para resultado No Aplica', () => {
    const inspeccionConMotivo = {
      ...fakeInspeccion,
      id: 102,
      detalles: [
        { id: 1, animal: { numero_arete_siniiga: 'ARETE-NA', raza: 'Cebu', sexo: 'M' }, raza: 'Cebu', sexo: 'M', resultado_prueba: 'No Aplica', motivo_no_aplica: 'Menor a 5 meses', observaciones_animal: '' },
        { id: 2, animal: { numero_arete_siniiga: 'ARETE-NEG', raza: 'Suizo', sexo: 'H' }, raza: 'Suizo', sexo: 'H', resultado_prueba: 'Negativo', observaciones_animal: '' },
      ],
    }

    cy.intercept('GET', '**/api/inspecciones/102', {
      statusCode: 200,
      body: { data: inspeccionConMotivo },
    }).as('getInspeccionConMotivo')

    const router = buildRouter(102)
    mount(InspeccionDetailView, { global: { plugins: [router] } })

    cy.wait('@getInspeccionConMotivo', { timeout: 10000 })
    cy.contains('No Aplica', { timeout: 5000 }).should('be.visible')
    cy.contains('Menor a 5 meses').should('be.visible')
  })

  it('muestra error cuando falla carga de inspeccion', () => {
    cy.intercept('GET', '**/api/inspecciones/999', {
      statusCode: 404,
      body: { message: 'No se encontro el dictamen' },
    }).as('getInspeccionError')

    const router = buildRouter(999)
    mount(InspeccionDetailView, { global: { plugins: [router] } })

    cy.wait('@getInspeccionError', { timeout: 10000 })
      cy.contains('No se encontro el dictamen', { timeout: 5000 }).should('be.visible')
  })
})
