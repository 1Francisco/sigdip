import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import SabanaExcelView from '../../src/views/SabanaExcelView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/sabana-excel', name: 'SabanaExcel', component: SabanaExcelView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
      { path: '/inspecciones/:id', component: EmptyView },
      { path: '/productores', component: EmptyView },
      { path: '/predios', component: EmptyView },
      { path: '/visitas', component: EmptyView },
      { path: '/medicos', component: EmptyView },
      { path: '/descargas', component: EmptyView },
      { path: '/sync', component: EmptyView },
      { path: '/scan', component: EmptyView },
      { path: '/inspeccion', component: EmptyView },
    ],
  })
}

const mockKpis = {
  total_inspecciones: 15,
  total_probados: 80,
  total_negativos: 65,
  total_reactores: 15,
}

const mockInspecciones = [
  {
    id: 1,
    clave: 'DP-2026-001',
    predio: 'Rancho El Paraiso',
    upp: 'UPP-001',
    productor: 'Maria Garcia',
    municipio: 'Centro',
    localidad: 'Villahermosa',
    prueba: 'Tuberculina',
    funcion_zootecnica: 'Engorda',
    fecha: '01/06/2026',
    probados: 5,
    negativos: 4,
    reactores: 1,
    latitud: '17.99',
    longitud: '-92.93',
    observaciones: null,
    veterinario: 'Dr. Juan Perez',
  },
  {
    id: 2,
    clave: 'DP-2026-002',
    predio: 'Rancho San Jose',
    upp: 'UPP-002',
    productor: 'Jose Martinez',
    municipio: 'Escarcega',
    localidad: 'Escarcega',
    prueba: 'Barrido',
    funcion_zootecnica: 'Leche',
    fecha: '02/06/2026',
    probados: 3,
    negativos: 3,
    reactores: 0,
    latitud: '18.61',
    longitud: '-90.74',
    observaciones: 'Todo normal',
    veterinario: 'Dr. Pedro Lopez',
  },
]

const mockFilterOptions = {
  zonas: ['A', 'B'],
  tipos_actividad: ['Cuarentenas Definitivas', 'Barrido', 'Seguimiento'],
  medicos: [
    { id: 2, name: 'Dr. Juan Perez' },
    { id: 3, name: 'Dr. Pedro Lopez' },
  ],
}

const mockPagination = {
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 2,
  from: 1,
  to: 2,
}

function mockApiResponse(isAdmin = true) {
  return {
    kpis: mockKpis,
    inspecciones: mockInspecciones,
    pagination: mockPagination,
    filter_options: mockFilterOptions,
    is_admin: isAdmin,
  }
}

describe('SabanaExcelView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    cy.intercept('HEAD', '**/api/user', { statusCode: 200, body: {} }).as('getUser')
  })

  describe('como Administrador', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userAdmin })
    })

    it('renderiza titulo y KPIs', () => {
      cy.intercept('GET', '**/api/reportes/sabana-excel/data*', {
        statusCode: 200,
        body: mockApiResponse(true),
      }).as('getSabanaData')

      const router = buildRouter()
      router.push('/sabana-excel')
      mount(SabanaExcelView, { global: { plugins: [router] } })

      cy.wait('@getSabanaData', { timeout: 10000 })
      cy.contains('Sábana General', { timeout: 5000 }).should('be.visible')
      cy.contains('15').should('be.visible')
      cy.contains('80').should('be.visible')
      cy.contains('65').should('be.visible')
      cy.contains('15').should('be.visible')
    })

    it('renderiza tabla con dictámenes', () => {
      cy.intercept('GET', '**/api/reportes/sabana-excel/data*', {
        statusCode: 200,
        body: mockApiResponse(true),
      }).as('getSabanaData')

      const router = buildRouter()
      router.push('/sabana-excel')
      mount(SabanaExcelView, { global: { plugins: [router] } })

      cy.wait('@getSabanaData', { timeout: 10000 })
      cy.contains('DP-2026-001', { timeout: 5000 }).should('be.visible')
      cy.contains('Rancho El Paraiso').should('be.visible')
      cy.contains('DP-2026-002').should('be.visible')
      cy.contains('Rancho San Jose').should('be.visible')
    })

    it('muestra empty state sin datos', () => {
      const empty = { ...mockApiResponse(true), inspecciones: [], pagination: { ...mockPagination, total: 0, from: 0, to: 0 } }
      cy.intercept('GET', '**/api/reportes/sabana-excel/data*', {
        statusCode: 200,
        body: empty,
      }).as('getSabanaDataEmpty')

      const router = buildRouter()
      router.push('/sabana-excel')
      mount(SabanaExcelView, { global: { plugins: [router] } })

      cy.wait('@getSabanaDataEmpty', { timeout: 10000 })
      cy.contains('No se encontraron dictámenes', { timeout: 5000 }).should('be.visible')
    })

    it('muestra filtros al hacer click en Mostrar Filtros', () => {
      cy.intercept('GET', '**/api/reportes/sabana-excel/data*', {
        statusCode: 200,
        body: mockApiResponse(true),
      }).as('getSabanaData')

      const router = buildRouter()
      router.push('/sabana-excel')
      mount(SabanaExcelView, { global: { plugins: [router] } })

      cy.wait('@getSabanaData', { timeout: 10000 })
      cy.contains('Mostrar Filtros', { timeout: 5000 }).click()
      cy.contains('Ocultar Filtros').should('be.visible')
      cy.contains('Zona').should('be.visible')
      cy.contains('Tipo Actividad').should('be.visible')
      cy.contains('Médico').should('be.visible')
    })

    it('filtra por zona', () => {
      cy.intercept('GET', '**/api/reportes/sabana-excel/data*', {
        statusCode: 200,
        body: mockApiResponse(true),
      }).as('getSabanaData')

      const router = buildRouter()
      router.push('/sabana-excel')
      mount(SabanaExcelView, { global: { plugins: [router] } })

      cy.wait('@getSabanaData', { timeout: 10000 })
      cy.contains('Mostrar Filtros', { timeout: 5000 }).click()
      cy.get('.form-select').first().select('A', { force: true })
      cy.contains('Filtrar').click()
      cy.wait('@getSabanaData', { timeout: 10000 })
        .its('request.query.zona').should('eq', 'A')
    })

    it('limpia filtros con boton Limpiar', () => {
      cy.intercept('GET', '**/api/reportes/sabana-excel/data*', {
        statusCode: 200,
        body: mockApiResponse(true),
      }).as('getSabanaData')

      const router = buildRouter()
      router.push('/sabana-excel')
      mount(SabanaExcelView, { global: { plugins: [router] } })

      cy.wait('@getSabanaData', { timeout: 10000 })
      cy.contains('Mostrar Filtros', { timeout: 5000 }).click()
      cy.get('select').first().select('A')

      cy.intercept('GET', '**/api/reportes/sabana-excel/data*', {
        statusCode: 200,
        body: mockApiResponse(true),
      }).as('getSabanaCleared')

      cy.contains('Limpiar Filtros').click()
      cy.wait('@getSabanaCleared', { timeout: 10000 })
    })

    it('navega entre paginas', () => {
      const manyInspecciones = Array.from({ length: 25 }, (_, i) => ({
        id: i + 1,
        clave: `DP-${String(i + 1).padStart(4, '0')}`,
        predio: `Rancho ${i + 1}`,
        upp: `UPP-${String(i + 1).padStart(3, '0')}`,
        productor: `Productor ${i + 1}`,
        municipio: 'Centro',
        localidad: 'Villahermosa',
        prueba: 'Tuberculina',
        funcion_zootecnica: 'Engorda',
        fecha: '01/06/2026',
        probados: 5,
        negativos: 4,
        reactores: 1,
        latitud: null,
        longitud: null,
        observaciones: null,
        veterinario: 'Dr. Juan Perez',
      }))

      cy.intercept('GET', '**/api/reportes/sabana-excel/data*', (req) => {
        const page = parseInt(req.query.page || '1', 10)
        const perPage = 20
        const total = 25
        const start = (page - 1) * perPage
        const end = Math.min(start + perPage, total)
        req.reply({
          statusCode: 200,
          body: {
            kpis: mockKpis,
            inspecciones: manyInspecciones.slice(start, end),
            pagination: {
              current_page: page,
              last_page: 2,
              per_page: perPage,
              total,
              from: start + 1,
              to: end,
            },
            filter_options: mockFilterOptions,
            is_admin: true,
          },
        })
      }).as('getSabanaPage')

      const router = buildRouter()
      router.push('/sabana-excel')
      mount(SabanaExcelView, { global: { plugins: [router] } })

      cy.wait('@getSabanaPage', { timeout: 10000 })
      cy.contains('Pág. 1 de 2', { timeout: 5000 }).should('be.visible')

      cy.get('.next-btn').click({ force: true })
      cy.wait('@getSabanaPage', { timeout: 10000 })
      cy.contains('Pág. 2 de 2', { timeout: 5000 }).should('be.visible')

      cy.get('.prev-btn').click({ force: true })
      cy.wait('@getSabanaPage', { timeout: 10000 })
      cy.contains('Pág. 1 de 2', { timeout: 5000 }).should('be.visible')
    })

    it('sobrevive fallo de API', () => {
      cy.intercept('GET', '**/api/reportes/sabana-excel/data*', {
        statusCode: 500,
        body: { message: 'Error interno' },
      }).as('getSabanaError')

      const router = buildRouter()
      router.push('/sabana-excel')
      mount(SabanaExcelView, { global: { plugins: [router] } })

      cy.wait('@getSabanaError', { timeout: 10000 })
      cy.contains('Sábana General', { timeout: 5000 }).should('be.visible')
    })

    it('navega a detalle de inspeccion al hacer clic en fila', () => {
      cy.intercept('GET', '**/api/reportes/sabana-excel/data*', {
        statusCode: 200,
        body: mockApiResponse(true),
      }).as('getSabanaData')

      const router = buildRouter()
      router.push('/sabana-excel')
      mount(SabanaExcelView, { global: { plugins: [router] } })

      cy.wait('@getSabanaData', { timeout: 10000 })
      cy.contains('DP-2026-001', { timeout: 5000 }).click()
      cy.location('hash', { timeout: 5000 }).should('include', '/inspecciones/1')
    })

    it('descarga PDFs con filtros al hacer clic en boton PDFs', () => {
      cy.intercept('GET', '**/api/reportes/sabana-excel/data*', {
        statusCode: 200,
        body: mockApiResponse(true),
      }).as('getSabanaData')

      cy.intercept('GET', '**/api/reportes/sabana-excel/pdf*', {
        statusCode: 200,
        headers: { 'Content-Type': 'application/pdf' },
        body: new Blob(['%PDF-1.4 test'], { type: 'application/pdf' }),
      }).as('getSabanaPdf')

      const router = buildRouter()
      router.push('/sabana-excel')
      mount(SabanaExcelView, { global: { plugins: [router] } })

      cy.wait('@getSabanaData', { timeout: 10000 })

      cy.contains('Mostrar Filtros', { timeout: 5000 }).click()
      cy.get('.form-select').first().select('A', { force: true })
      cy.get('.form-select').eq(1).select('Barrido', { force: true })
      cy.get('.form-select').eq(2).select('2', { force: true })
      cy.contains('Filtrar').click()
      cy.wait('@getSabanaData', { timeout: 10000 })

      cy.contains('PDFs', { timeout: 5000 }).click()
      cy.wait('@getSabanaPdf', { timeout: 10000 })
        .its('request.query')
        .should('deep.include', { zona: 'A', tipo_actividad: 'Barrido', medico_id: '2' })
    })

    it('cierra sesion desde menu lateral', () => {
      cy.intercept('GET', '**/api/reportes/sabana-excel/data*', {
        statusCode: 200,
        body: mockApiResponse(true),
      }).as('getSabanaData')

      const router = buildRouter()
      router.push('/sabana-excel')
      mount(SabanaExcelView, { global: { plugins: [router] } })

      cy.wait('@getSabanaData', { timeout: 10000 })
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

    it('renderiza y no ve filtro de medico', () => {
      cy.intercept('GET', '**/api/reportes/sabana-excel/data*', {
        statusCode: 200,
        body: mockApiResponse(false),
      }).as('getSabanaData')

      const router = buildRouter()
      router.push('/sabana-excel')
      mount(SabanaExcelView, { global: { plugins: [router] } })

      cy.wait('@getSabanaData', { timeout: 10000 })
      cy.contains('Sábana General', { timeout: 5000 }).should('be.visible')

      cy.contains('Mostrar Filtros', { timeout: 5000 }).click()
      cy.get('.form-label').contains('Médico').should('not.exist')
    })

    it('ve boton de PDFs', () => {
      cy.intercept('GET', '**/api/reportes/sabana-excel/data*', {
        statusCode: 200,
        body: mockApiResponse(false),
      }).as('getSabanaData')

      const router = buildRouter()
      router.push('/sabana-excel')
      mount(SabanaExcelView, { global: { plugins: [router] } })

      cy.wait('@getSabanaData', { timeout: 10000 })
      cy.contains('PDFs', { timeout: 5000 }).should('be.visible')
    })
  })
})
