import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import AnimalesListView from '../../src/views/AnimalesListView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/animales', name: 'Animales', component: AnimalesListView },
      { path: '/animales/nuevo', component: EmptyView },
      { path: '/animales/editar/:id', component: EmptyView },
      { path: '/animales/:id', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
}

const fakeAnimales = [
  { id: 1, numero_arete_siniiga: 'MX-001-001', raza: 'Angus', sexo: 'Macho', edad: 3, predio: { id: 1, nombre_rancho: 'Rancho Norte', clave_unidad_produccion: 'CUP-001', productor: { id: 1, nombre: 'Juan', apellido_paterno: 'Perez' } } },
  { id: 2, numero_arete_siniiga: 'MX-001-002', raza: 'Brangus', sexo: 'Hembra', edad: 2, predio: { id: 2, nombre_rancho: 'Rancho Sur', clave_unidad_produccion: 'CUP-002', productor: { id: 2, nombre: 'Maria', apellido_paterno: 'Garcia' } } },
]

describe('AnimalesListView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.intercept('HEAD', '**/api/user', { statusCode: 200, body: {} }).as('getUser')
  })

  describe('como Administrador', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userAdmin })
      cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    })

    it('renderiza titulo y lista de animales', () => {
      cy.intercept('GET', '**/api/animales', {
        statusCode: 200,
        body: { data: fakeAnimales },
      }).as('getAnimales')

      const router = buildRouter()
      router.push('/animales')
      mount(AnimalesListView, { global: { plugins: [router] } })

      cy.wait('@getAnimales', { timeout: 10000 })
      cy.contains('Animales Registrados', { timeout: 5000 }).should('be.visible')
      cy.contains('MX-001-001').should('be.visible')
      cy.contains('MX-001-002').should('be.visible')
    })

    it('muestra boton Nuevo Animal', () => {
      cy.intercept('GET', '**/api/animales', {
        statusCode: 200,
        body: { data: fakeAnimales },
      }).as('getAnimales')

      const router = buildRouter()
      router.push('/animales')
      mount(AnimalesListView, { global: { plugins: [router] } })

      cy.wait('@getAnimales', { timeout: 10000 })
      cy.contains('Nuevo Animal', { timeout: 5000 }).should('be.visible')
    })

    it('filtra animales por busqueda (computed)', () => {
      cy.intercept('GET', '**/api/animales', {
        statusCode: 200,
        body: { data: fakeAnimales },
      }).as('getAnimales')

      const router = buildRouter()
      router.push('/animales')
      mount(AnimalesListView, {
        global: { plugins: [router] },
      })

      cy.wait('@getAnimales', { timeout: 10000 })
      cy.contains('MX-001-001').should('be.visible')
      cy.contains('MX-001-002').should('be.visible')

      cy.contains('MX-001-001').should('be.visible')
      cy.contains('MX-001-002').should('be.visible')
      cy.contains('2 registros').should('be.visible')
    })

    it('muestra informacion de paginacion', () => {
      cy.intercept('GET', '**/api/animales', {
        statusCode: 200,
        body: { data: fakeAnimales },
      }).as('getAnimales')

      const router = buildRouter()
      router.push('/animales')
      mount(AnimalesListView, { global: { plugins: [router] } })

      cy.wait('@getAnimales', { timeout: 10000 })
      cy.contains('Pág. 1 de 1', { timeout: 5000 }).should('be.visible')
      cy.contains('2 registros').should('be.visible')
    })

    it('elimina animal con confirmacion', () => {
      cy.intercept('GET', '**/api/animales', {
        statusCode: 200,
        body: { data: fakeAnimales },
      }).as('getAnimales')

      cy.intercept('DELETE', '**/api/animales/1', {
        statusCode: 200,
        body: { message: 'Eliminado' },
      }).as('deleteAnimal')

      cy.window().then((win) => {
        cy.stub(win, 'confirm').returns(true)
      })

      const router = buildRouter()
      router.push('/animales')
      mount(AnimalesListView, { global: { plugins: [router] } })

      cy.wait('@getAnimales', { timeout: 10000 })
      cy.contains('Eliminar').first().click()
      cy.wait('@deleteAnimal', { timeout: 10000 })
      cy.contains('eliminado del sistema', { timeout: 5000 }).should('be.visible')
    })

    it('muestra empty state sin animales', () => {
      cy.intercept('GET', '**/api/animales', {
        statusCode: 200,
        body: { data: [] },
      }).as('getAnimalesEmpty')

      const router = buildRouter()
      router.push('/animales')
      mount(AnimalesListView, { global: { plugins: [router] } })

      cy.wait('@getAnimalesEmpty', { timeout: 10000 })
      cy.contains('No hay animales registrados', { timeout: 5000 }).should('be.visible')
    })

    it('carga desde cache offline y luego actualiza', () => {
      cy.seedIndexedDB('catalogos', 'animales', fakeAnimales)

      cy.intercept('GET', '**/api/animales', {
        statusCode: 200,
        body: { data: fakeAnimales },
      }).as('getAnimales')

      const router = buildRouter()
      router.push('/animales')
      mount(AnimalesListView, { global: { plugins: [router] } })

      cy.wait('@getAnimales', { timeout: 10000 })
      cy.contains('MX-001-001', { timeout: 5000 }).should('be.visible')
    })
  })

  describe('como Medico (sin acceso)', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userMedico })
    })

    it('redirige a dashboard con alerta', () => {
      cy.window().then((win) => {
        cy.stub(win, 'alert').as('alertStub')
      })

      const router = buildRouter()
      router.push('/animales')
      mount(AnimalesListView, { global: { plugins: [router] } })

      cy.get('@alertStub').should('have.been.calledWithMatch', /Solo administradores/i)
      cy.location('hash', { timeout: 5000 }).should('include', '/dashboard')
    })
  })
})
