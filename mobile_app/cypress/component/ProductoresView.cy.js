import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import ProductoresView from '../../src/views/ProductoresView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/productores', name: 'Productores', component: ProductoresView },
      { path: '/productores/nuevo', component: EmptyView },
      { path: '/productores/editar/:id', component: EmptyView },
      { path: '/predios/nuevo', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
      { path: '/predios', component: EmptyView },
      { path: '/inspecciones', component: EmptyView },
      { path: '/visitas', component: EmptyView },
      { path: '/medicos', component: EmptyView },
      { path: '/descargas', component: EmptyView },
      { path: '/sync', component: EmptyView },
    ],
  })
}

const fakePredios = [
  {
    id: 1,
    nombre_rancho: 'Rancho El Paraiso',
    upp: 'UPP-001',
    localidad: 'Villahermosa',
    municipio: 'Champoton',
    productor_id: 1,
    productor: {
      id: 1, nombre: 'Maria', apellido_paterno: 'Garcia', apellido_materno: 'Lopez',
      curp: 'GALM800101HPLRRN01', upp: 'UPP-001', telefono: '555-0101'
    }
  },
  {
    id: 2,
    nombre_rancho: 'Rancho La Esperanza',
    upp: 'UPP-001',
    localidad: 'Ciudad del Carmen',
    municipio: 'Carmen',
    productor_id: 1,
    productor: {
      id: 1, nombre: 'Maria', apellido_paterno: 'Garcia', apellido_materno: 'Lopez',
      curp: 'GALM800101HPLRRN01', upp: 'UPP-001', telefono: '555-0101'
    }
  },
  {
    id: 3,
    nombre_rancho: 'Rancho San Jose',
    upp: 'UPP-002',
    localidad: 'Escarcega',
    municipio: 'Escarcega',
    productor_id: 2,
    productor: {
      id: 2, nombre: 'Jose', apellido_paterno: 'Martinez', apellido_materno: 'Hernandez',
      curp: 'MAHJ850515HPLRRN02', upp: 'UPP-002', telefono: '555-0102'
    }
  }
]

function seedPrediosInDB() {
  cy.seedIndexedDB('catalogos', 'predios', fakePredios)
}

describe('ProductoresView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
  })

  describe('como Administrador', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userAdmin })
    })

    it('renderiza titulo y boton nuevo productor', () => {
      seedPrediosInDB()
      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.contains('h2', 'Productores', { timeout: 5000 }).should('be.visible')
      cy.contains('Nuevo Productor').should('be.visible')
    })

    it('renderiza lista de productores desde IndexedDB', () => {
      seedPrediosInDB()
      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.contains('Maria Garcia Lopez', { timeout: 8000 }).should('be.visible')
      cy.contains('Jose Martinez Hernandez').should('be.visible')
    })

    it('filtra productores por nombre en la busqueda', () => {
      seedPrediosInDB()
      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.contains('Maria Garcia Lopez', { timeout: 8000 }).should('be.visible')
      cy.get('input[placeholder*="Buscar productor"]').type('Maria')
      cy.contains('Maria Garcia Lopez').should('be.visible')
      cy.contains('Jose Martinez Hernandez').should('not.exist')
    })

    it('muestra empty state cuando no hay resultados', () => {
      seedPrediosInDB()
      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.get('input[placeholder*="Buscar productor"]').type('ZZZZNOEXISTE')
      cy.contains('No se encontraron productores').should('be.visible')
    })

    it('abre modal crear productor desde boton en listado', () => {
      seedPrediosInDB()
      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.contains('Nuevo Productor').click()
      cy.contains('Nuevo Productor', { timeout: 1000 }).should('be.visible')
    })

    it('navega a nuevo productor via boton card-header', () => {
      seedPrediosInDB()
      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.get('button').contains('Nuevo Productor').click()
      cy.location('hash').should('eq', '#/productores/nuevo')
    })

    it('navega a editar productor desde boton de edicion', () => {
      seedPrediosInDB()
      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.contains('Maria Garcia Lopez', { timeout: 8000 }).should('be.visible')
      cy.get('button[title="Editar Productor"]').first().click({ force: true })
      cy.location('hash').should('match', /#\/productores\/editar\/\d+/)
    })

    it('navega a añadir rancho desde boton', () => {
      seedPrediosInDB()
      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.contains('Maria Garcia Lopez', { timeout: 8000 }).should('be.visible')
      cy.get('button[title="Añadir Rancho"]').first().click({ force: true })
      cy.location('hash').should('match', /#\/predios\/nuevo\?productor_id=\d+/)
    })

    it('muestra badge de conectividad', () => {
      seedPrediosInDB()
      cy.window().then(w => { w.navigator.__defineGetter__('onLine', () => true) })
      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.get('.connectivity-badge', { timeout: 5000 }).should('be.visible')
    })

    it('navega entre paginas con paginacion', () => {
      const manyProductores = Array.from({ length: 15 }, (_, i) => ({
        id: i + 1,
        nombre: `Productor ${i + 1}`,
        apellido_paterno: 'Apellido',
      }))

      cy.seedIndexedDB('catalogos', 'predios', manyProductores.map(p => ({
        id: p.id,
        nombre: 'Rancho',
        productor: p,
        localidad: 'Localidad',
      })))

      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.contains('Showing 1 to 10 of 15 results', { timeout: 5000 }).should('be.visible')
    })

    it('cierra sesion desde menu lateral', () => {
      cy.seedIndexedDB('catalogos', 'predios', [{
        id: 1,
        nombre: 'Rancho',
        productor: { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia' },
        localidad: 'Localidad',
      }])

      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

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

    it('renderiza lista de productores para medico', () => {
      seedPrediosInDB()
      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.contains('Maria Garcia Lopez', { timeout: 5000 }).should('be.visible')
    })

    it('filtra productores por medico_id cuando carga desde db dedicada', () => {
      const productorAsignado = {
        id: 10, nombre: 'Mi Productor Asignado', nombreRaw: 'Mi', apellido_paterno: 'Productor', apellido_materno: 'Asignado',
        curp: 'ASIG800101HPLRRN01', upp: 'UPP-010', telefono: '555-010', prediosCount: 1, ranchos: [],
        medico_id: 2
      }
      const productorNoAsignado = {
        id: 20, nombre: 'Otro Productor', nombreRaw: 'Otro', apellido_paterno: 'Productor', apellido_materno: '',
        curp: 'OTRO850515HPLRRN02', upp: 'UPP-020', telefono: '555-020', prediosCount: 0, ranchos: [],
        medico_id: null
      }
      cy.seedIndexedDB('catalogos', 'productores', [productorAsignado, productorNoAsignado])

      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.contains('Mi Productor Asignado', { timeout: 5000 }).should('be.visible')
      cy.contains('Otro Productor').should('not.exist')
    })
  })

  describe('flujo offline crear productor', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userAdmin })
      cy.window().then(win => { cy.stub(win, 'alert').returns(true) })
    })

    it('crea productor offline sin rancho y persiste en predios + productores store', () => {
      cy.window().then(w => { w.navigator.__defineGetter__('onLine', () => false) })

      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.contains('Nuevo Productor').click()
      cy.get('input[placeholder*="Ej: Pepito"]').type('Juan')
      cy.get('input[placeholder*="Ej: Tejeda"]').type('Perez')
      cy.get('input[placeholder*="18 caracteres"]').type('PEJL900101HPLRRN01')
      cy.get('input[placeholder*="57625285"]').first().type('UPP-001')

      cy.contains('Guardar Datos').click()

      cy.getIndexedDB('catalogos', 'productores').should('have.length', 1)
        .its('0.prediosCount').should('eq', 0)
      cy.getIndexedDB('catalogos', 'predios').should('have.length', 1)
    })

    it('crea productor offline con rancho y persiste en predios + productores store', () => {
      cy.window().then(w => { w.navigator.__defineGetter__('onLine', () => false) })

      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.contains('Nuevo Productor').click()
      cy.get('input[placeholder*="Ej: Pepito"]').type('Maria')
      cy.get('input[placeholder*="Ej: Tejeda"]').type('Garcia')
      cy.get('input[placeholder*="18 caracteres"]').type('GALM800101HPLRRN01')
      cy.get('input[placeholder*="57625285"]').first().type('UPP-001')

      cy.get('#registrarPredioCheck').check({ force: true })
      cy.get('input[placeholder*="El Refugio"]').type('Rancho Nuevo')
      cy.get('input[placeholder*="57625285"]').eq(1).type('UPP-RANCHO')

      cy.contains('Guardar Datos').click()

      cy.getIndexedDB('catalogos', 'productores').should('have.length', 1)
        .its('0.prediosCount').should('eq', 1)
      cy.getIndexedDB('catalogos', 'productores').its('0.ranchos').should('have.length', 1)
      cy.getIndexedDB('catalogos', 'predios').should('have.length', 1)
    })
  })

  describe('carga desde store dedicada de productores', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userAdmin })
    })

    it('carga productores desde db.getProductores() cuando existen', () => {
      const fakeProductores = [
        { id: 10, nombre: 'Juan Perez Lopez', nombreRaw: 'Juan', apellido_paterno: 'Perez', apellido_materno: 'Lopez', curp: 'PELJ800101HPLRRN01', upp: 'UPP-010', telefono: '555-010', prediosCount: 2, ranchos: [] },
        { id: 20, nombre: 'Ana Garcia Ruiz', nombreRaw: 'Ana', apellido_paterno: 'Garcia', apellido_materno: 'Ruiz', curp: 'GARA850515HPLRRN02', upp: 'UPP-020', telefono: '555-020', prediosCount: 0, ranchos: [] },
      ]
      cy.seedIndexedDB('catalogos', 'productores', fakeProductores)

      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.contains('Juan Perez Lopez', { timeout: 5000 }).should('be.visible')
      cy.contains('Ana Garcia Ruiz', { timeout: 5000 }).should('be.visible')
    })

    it('carga productores con prediosCount 0 desde la store dedicada', () => {
      const productorSinPredios = [
        { id: 99, nombre: 'Sin Ranchos Test', nombreRaw: 'Sin Ranchos', apellido_paterno: 'Test', apellido_materno: '', curp: 'TEST990101HPLRRN99', upp: 'UPP-099', telefono: '555-099', prediosCount: 0, ranchos: [] },
      ]
      cy.seedIndexedDB('catalogos', 'productores', productorSinPredios)

      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.contains('Sin Ranchos Test', { timeout: 5000 }).should('be.visible')
      cy.contains('0 ranchos').should('be.visible')
    })

    it('fallback a predios cuando no hay productores dedicados', () => {
      // Only seed predios, no productores
      seedPrediosInDB()

      const router = buildRouter()
      router.push('/productores')
      mount(ProductoresView, { global: { plugins: [router] } })

      cy.contains('Maria Garcia Lopez', { timeout: 8000 }).should('be.visible')
      cy.contains('Jose Martinez Hernandez', { timeout: 5000 }).should('be.visible')
      // Maria has 2 predios in the seed data
      cy.contains('2 ranchos').should('be.visible')
    })
  })
})
