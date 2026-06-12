import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import PrediosView from '../../src/views/PrediosView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/predios', name: 'Predios', component: PrediosView },
      { path: '/predios/nuevo', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
      { path: '/productores', component: EmptyView },
      { path: '/inspecciones', component: EmptyView },
      { path: '/visitas', component: EmptyView },
      { path: '/medicos', component: EmptyView },
      { path: '/descargas', component: EmptyView },
      { path: '/sync', component: EmptyView },
      { path: '/scan', component: EmptyView },
    ],
  })
}

const fakePredios = [
  {
    id: 1,
    nombre_rancho: 'Rancho El Paraiso',
    clave_unidad_produccion: 'CUP-001',
    municipio: 'Champoton',
    localidad: 'Villahermosa',
    productor: { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia', apellido_materno: 'Lopez' },
    productor_id: 1,
  },
  {
    id: 2,
    nombre_rancho: 'Rancho La Esperanza',
    clave_unidad_produccion: 'CUP-002',
    municipio: 'Carmen',
    localidad: 'Ciudad del Carmen',
    productor: { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia', apellido_materno: 'Lopez' },
    productor_id: 1,
  },
  {
    id: 3,
    nombre_rancho: 'Rancho San Jose',
    clave_unidad_produccion: 'CUP-003',
    municipio: 'Escarcega',
    localidad: 'Escarcega',
    productor: { id: 2, nombre: 'Jose', apellido_paterno: 'Martinez', apellido_materno: 'Hernandez' },
    productor_id: 2,
  },
]

describe('PrediosView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')

    cy.intercept('GET', '**/api/predios', {
      statusCode: 200,
      body: { data: fakePredios },
    }).as('getPredios')
  })

  describe('como Administrador', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userAdmin })
    })

    it('renderiza titulo y boton nuevo predio', () => {
      const router = buildRouter()
      router.push('/predios')
      mount(PrediosView, { global: { plugins: [router] } })

      cy.contains('Predios / Ranchos', { timeout: 5000 }).should('be.visible')
      cy.contains('Nuevo Predio').should('be.visible')
    })

    it('renderiza lista de predios desde API', () => {
      const router = buildRouter()
      router.push('/predios')
      mount(PrediosView, { global: { plugins: [router] } })

      cy.wait('@getPredios', { timeout: 10000 })
      cy.contains('Rancho El Paraiso', { timeout: 5000 }).should('be.visible')
      cy.contains('Rancho La Esperanza').should('be.visible')
      cy.contains('Rancho San Jose').should('be.visible')
    })

    it('filtra predios por nombre en busqueda', () => {
      const router = buildRouter()
      router.push('/predios')
      mount(PrediosView, { global: { plugins: [router] } })

      cy.wait('@getPredios', { timeout: 10000 })
      cy.get('input[placeholder*="Buscar"]').first().type('Paraiso')
      cy.contains('Rancho El Paraiso').should('be.visible')
      cy.contains('Rancho San Jose').should('not.exist')
    })

    it('muestra empty state cuando busqueda no coincide', () => {
      const router = buildRouter()
      router.push('/predios')
      mount(PrediosView, { global: { plugins: [router] } })

      cy.wait('@getPredios', { timeout: 10000 })
      cy.get('input[placeholder*="Buscar"]').first().type('ZZZZNOEXISTE')
      cy.contains('No hay predios para mostrar', { timeout: 5000 }).should('be.visible')
    })

    it('navega a nuevo predio', () => {
      const router = buildRouter()
      router.push('/predios')
      mount(PrediosView, { global: { plugins: [router] } })

      cy.contains('Nuevo Predio').click()
      cy.location('hash').should('eq', '#/predios/nuevo')
    })

    it('muestra badge de conectividad', () => {
      const router = buildRouter()
      router.push('/predios')
      mount(PrediosView, { global: { plugins: [router] } })

      cy.get('.connectivity-badge', { timeout: 5000 }).should('be.visible')
    })

    it('navega entre paginas de predios', () => {
      const manyPredios = Array.from({ length: 15 }, (_, i) => ({
        id: i + 1,
        nombre_rancho: `Rancho Test ${i + 1}`,
        productor: { id: i + 1, nombre: 'Test' },
        localidad: 'Localidad',
      }))

      cy.intercept('GET', '**/api/predios', {
        statusCode: 200,
        body: { data: manyPredios },
      }).as('getPrediosPaged')

      const router = buildRouter()
      router.push('/predios')
      mount(PrediosView, { global: { plugins: [router] } })

      cy.wait('@getPrediosPaged', { timeout: 10000 })
      cy.contains('Showing 1 to 10 of 15 results', { timeout: 5000 }).should('be.visible')
    })

    it('cierra sesion desde menu lateral', () => {
      const router = buildRouter()
      router.push('/predios')
      mount(PrediosView, { global: { plugins: [router] } })

      cy.wait('@getPredios', { timeout: 10000 })
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

    it('renderiza lista de predios para medico', () => {
      const router = buildRouter()
      router.push('/predios')
      mount(PrediosView, { global: { plugins: [router] } })

      cy.wait('@getPredios', { timeout: 10000 })
      cy.contains('Rancho El Paraiso', { timeout: 5000 }).should('be.visible')
    })
  })
})
