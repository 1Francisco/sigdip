import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import InspeccionesView from '../../src/views/InspeccionesView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/inspecciones', name: 'Inspecciones', component: InspeccionesView },
      { path: '/inspecciones/:id', component: EmptyView },
      { path: '/inspeccion/:predioId?', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
      { path: '/productores', component: EmptyView },
      { path: '/predios', component: EmptyView },
      { path: '/visitas', component: EmptyView },
      { path: '/medicos', component: EmptyView },
      { path: '/descargas', component: EmptyView },
      { path: '/sync', component: EmptyView },
      { path: '/scan', component: EmptyView },
    ],
  })
}

const fakePredios = [
  { id: 1, nombre_rancho: 'Rancho El Paraiso', productor: { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia' }, localidad: 'Villahermosa' },
  { id: 2, nombre_rancho: 'Rancho San Jose', productor: { id: 2, nombre: 'Jose', apellido_paterno: 'Martinez' }, localidad: 'Escarcega' },
]

const fakeInspecciones = [
  {
    id: 100,
    folio: 'INSP-2026-001',
    fecha: '2026-06-01',
    estado: 'completada',
    predio_id: 1,
    predio: { id: 1, nombre_rancho: 'Rancho El Paraiso', localidad: 'Villahermosa', productor: { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia' } },
    veterinario: { name: 'Dr. Juan Perez', id: 2 },
    sementales: 2, vacas: 5, vaquillas: 3, becerras: 2, becerros: 4,
  },
  {
    id: 101,
    folio: null,
    fecha: '2026-06-02',
    estado: 'borrador',
    predio_id: 2,
    predio: { id: 2, nombre_rancho: 'Rancho San Jose', localidad: 'Escarcega', productor: { id: 2, nombre: 'Jose', apellido_paterno: 'Martinez' } },
    veterinario: { name: 'Dr. Juan Perez', id: 2 },
    sementales: 1, vacas: 3, vaquillas: 1, becerras: 2, becerros: 1,
  },
]

const fakeBorradoresLocales = [
  {
    folio: null,
    fecha: '2026-06-10',
    estado: 'borrador',
    predio_id: 1,
    veterinario_name: 'Dr. Juan Perez',
    animales: [{ identificador: 'ARETE-L-001', resultado: 'Negativo' }],
    sementales: 0, vacas: 0, vaquillas: 0, becerras: 0, becerros: 0,
  }
]

describe('InspeccionesView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.intercept('HEAD', '**/api/user', { statusCode: 200, body: {} }).as('getUser')
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
  })

  describe('como Administrador', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userAdmin })
    })

    it('renderiza titulo', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.intercept('GET', '**/api/inspecciones*', {
        statusCode: 200,
        body: { data: fakeInspecciones },
      }).as('getInspecciones')

      const router = buildRouter()
      router.push('/inspecciones')
      mount(InspeccionesView, { global: { plugins: [router] } })

      cy.contains('Inspecciones Pecuarias', { timeout: 5000 }).should('be.visible')
      cy.contains('Dictámenes Registrados').should('be.visible')
    })

    it('renderiza lista con completada y borrador', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.intercept('GET', '**/api/inspecciones*', {
        statusCode: 200,
        body: { data: fakeInspecciones },
      }).as('getInspecciones')

      const router = buildRouter()
      router.push('/inspecciones')
      mount(InspeccionesView, { global: { plugins: [router] } })

      cy.wait('@getInspecciones', { timeout: 10000 })
      cy.contains('INSP-2026-001', { timeout: 5000 }).should('be.visible')
      cy.contains('Sin Folio (Borrador)').should('be.visible')
    })

    it('muestra badge Finalizado y Borrador', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.intercept('GET', '**/api/inspecciones*', {
        statusCode: 200,
        body: { data: fakeInspecciones },
      }).as('getInspecciones')

      const router = buildRouter()
      router.push('/inspecciones')
      mount(InspeccionesView, { global: { plugins: [router] } })

      cy.wait('@getInspecciones', { timeout: 10000 })
      cy.contains('Finalizado', { timeout: 5000 }).should('be.visible')
      cy.contains('Borrador').should('be.visible')
    })

    it('muestra borradores locales combinados con inspecciones del servidor', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.seedIndexedDB('inspecciones_pendientes', 'lista', fakeBorradoresLocales)

      cy.intercept('GET', '**/api/inspecciones*', {
        statusCode: 200,
        body: { data: fakeInspecciones },
      }).as('getInspecciones')

      const router = buildRouter()
      router.push('/inspecciones')
      mount(InspeccionesView, { global: { plugins: [router] } })

      cy.wait('@getInspecciones', { timeout: 10000 })
      cy.contains('Sin Folio (Borrador)', { timeout: 5000 }).should('be.visible')
    })

    it('sobrevive fallo de API sin errores no controlados', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.intercept('GET', '**/api/inspecciones*', {
        statusCode: 500,
        body: { message: 'Error interno' },
      }).as('getInspeccionesError')

      const router = buildRouter()
      router.push('/inspecciones')
      mount(InspeccionesView, { global: { plugins: [router] } })

      cy.wait('@getInspeccionesError', { timeout: 10000 })
      cy.contains('Inspecciones Pecuarias', { timeout: 5000 }).should('be.visible')
    })

    it('muestra empty state sin inspecciones', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.intercept('GET', '**/api/inspecciones*', {
        statusCode: 200,
        body: { data: [] },
      }).as('getInspeccionesEmpty')

      const router = buildRouter()
      router.push('/inspecciones')
      mount(InspeccionesView, { global: { plugins: [router] } })

      cy.wait('@getInspeccionesEmpty', { timeout: 10000 })
      cy.contains('No hay inspecciones para mostrar', { timeout: 5000 }).should('be.visible')
    })

    it('muestra badge de conectividad', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.intercept('GET', '**/api/inspecciones*', {
        statusCode: 200,
        body: { data: fakeInspecciones },
      }).as('getInspecciones')

      const router = buildRouter()
      router.push('/inspecciones')
      mount(InspeccionesView, { global: { plugins: [router] } })

      cy.get('.connectivity-badge', { timeout: 5000 }).should('be.visible')
    })

    it('filtra por texto de busqueda', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.intercept('GET', '**/api/inspecciones*', {
        statusCode: 200,
        body: { data: fakeInspecciones },
      }).as('getInspecciones')

      const router = buildRouter()
      router.push('/inspecciones')
      mount(InspeccionesView, { global: { plugins: [router] } })

      cy.wait('@getInspecciones', { timeout: 10000 })
      cy.contains('INSP-2026-001', { timeout: 5000 }).should('be.visible')
      cy.contains('Sin Folio (Borrador)').should('be.visible')

      cy.get('.filter-input[placeholder*="Buscar"]').type('INSP-2026-001', { force: true })
      cy.contains('INSP-2026-001', { timeout: 5000 }).should('be.visible')
      cy.contains('Sin Folio (Borrador)').should('not.exist')
    })

    it('filtra por estado seleccionado', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.intercept('GET', '**/api/inspecciones*', {
        statusCode: 200,
        body: { data: fakeInspecciones },
      }).as('getInspecciones')

      const router = buildRouter()
      router.push('/inspecciones')
      mount(InspeccionesView, { global: { plugins: [router] } })

      cy.wait('@getInspecciones', { timeout: 10000 })
      cy.get('.filter-select').select('borrador')
      cy.contains('Sin Folio (Borrador)').should('be.visible')
      cy.contains('INSP-2026-001').should('not.exist')
    })

    it('muestra empty state filtrado cuando no hay coincidencias', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.intercept('GET', '**/api/inspecciones*', {
        statusCode: 200,
        body: { data: fakeInspecciones },
      }).as('getInspecciones')

      const router = buildRouter()
      router.push('/inspecciones')
      mount(InspeccionesView, { global: { plugins: [router] } })

      cy.wait('@getInspecciones', { timeout: 10000 })
      cy.get('.filter-input[placeholder*="Buscar"]').type('NOEXISTE', { force: true })
      cy.contains('No hay inspecciones que coincidan con los filtros').should('be.visible')
    })

    it('limpia filtros con boton Limpiar', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.intercept('GET', '**/api/inspecciones*', {
        statusCode: 200,
        body: { data: fakeInspecciones },
      }).as('getInspecciones')

      const router = buildRouter()
      router.push('/inspecciones')
      mount(InspeccionesView, { global: { plugins: [router] } })

      cy.wait('@getInspecciones', { timeout: 10000 })
      cy.get('.filter-input[placeholder*="Buscar"]').type('INSP-2026-001', { force: true })
      cy.contains('INSP-2026-001').should('be.visible')

      cy.get('.btn-clear').click()
      cy.get('.filter-input[placeholder*="Buscar"]').should('have.value', '')
      cy.contains('INSP-2026-001', { timeout: 5000 }).should('be.visible')
      cy.contains('Sin Folio (Borrador)').should('be.visible')
    })

    it('navega entre paginas con prevPage y nextPage', () => {
      const manyInspecciones = Array.from({ length: 25 }, (_, i) => ({
        id: i + 1,
        folio: i === 0 ? null : `INSP-${String(i + 1).padStart(4, '0')}`,
        fecha: '2026-06-01',
        estado: i % 2 === 0 ? 'completada' : 'borrador',
        predio_id: 1,
        predio: { id: 1, nombre_rancho: 'Rancho', localidad: 'Localidad', productor: { id: 1, nombre: 'Test' } },
        veterinario: { name: 'Dr. Test', id: 1 },
        sementales: 0, vacas: 0, vaquillas: 0, becerras: 0, becerros: 0,
      }))

      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.intercept('GET', '**/api/inspecciones*', {
        statusCode: 200,
        body: { data: manyInspecciones },
      }).as('getInspecciones')

      const router = buildRouter()
      router.push('/inspecciones')
      mount(InspeccionesView, { global: { plugins: [router] } })

      cy.wait('@getInspecciones', { timeout: 10000 })
      cy.contains('Pág. 1 de 2', { timeout: 5000 }).should('be.visible')

      cy.get('.next-btn').click()
      cy.contains('Pág. 2 de 2', { timeout: 5000 }).should('be.visible')

      cy.get('.prev-btn').click()
      cy.contains('Pág. 1 de 2', { timeout: 5000 }).should('be.visible')
    })

    it('cierra sesion desde menu lateral', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.intercept('GET', '**/api/inspecciones*', {
        statusCode: 200,
        body: { data: fakeInspecciones },
      }).as('getInspecciones')

      const router = buildRouter()
      router.push('/inspecciones')
      mount(InspeccionesView, { global: { plugins: [router] } })

      cy.get('.avatar-circle', { timeout: 5000 }).click()
      cy.get('.logout-btn').click({ force: true })
      cy.wait('@logout', { timeout: 10000 })
      cy.location('hash', { timeout: 5000 }).should('include', '/login')
    })
  })

  describe('como Medico', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userMedico })
    })

    it('renderiza lista de inspecciones para medico', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.intercept('GET', '**/api/inspecciones*', {
        statusCode: 200,
        body: { data: fakeInspecciones },
      }).as('getInspecciones')

      const router = buildRouter()
      router.push('/inspecciones')
      mount(InspeccionesView, { global: { plugins: [router] } })

      cy.wait('@getInspecciones', { timeout: 10000 })
      cy.contains('INSP-2026-001', { timeout: 5000 }).should('be.visible')
    })
  })
})
