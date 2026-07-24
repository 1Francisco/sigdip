import { mount } from 'cypress/vue'
import AppLayout from '../../src/components/AppLayout.vue'

function mountLayout({ route = '/dashboard', roles = ['Administrador'] } = {}) {
  const $route = { path: route, fullPath: route }
  const $router = {
    push: cy.spy().as('routerPush'),
  }
  cy.setLoginState({
    token: 'test-token',
    user: { id: 1, name: 'Test User', email: 'test@test.com', roles },
  })
  mount(AppLayout, {
    global: {
      mocks: { $route, $router },
      stubs: ['router-link'],
    },
    slots: { default: '<div class="slot-content">Contenido</div>' },
  })
}

describe('AppLayout', () => {
  it('renderiza slot content', () => {
    mountLayout()
    cy.contains('Contenido').should('be.visible')
  })

  it('muestra logo y titulo en header', () => {
    mountLayout()
    cy.get('.brand-title').should('contain', 'SIGDIP')
  })

  it('muestra badge online cuando hay conexion', () => {
    mountLayout()
    cy.get('.connectivity-badge').should('contain', 'Online')
  })

  it('muestra sidebar con enlaces para administrador', () => {
    mountLayout()
    cy.get('.header-hamburger-btn').click()
    cy.get('.sidebar').should('have.class', 'active')
    cy.contains('Dashboard').should('be.visible')
    cy.contains('Productores').should('be.visible')
    cy.contains('Predios').should('be.visible')
    cy.contains('Lecturas').should('be.visible')
    cy.contains('Médicos').should('be.visible')
    cy.contains('Rendimiento').should('be.visible')
    cy.contains('Descargas').should('be.visible')
  })

  it('sidebar no muestra Medicos para medico role', () => {
    mountLayout({ roles: ['Medico_Campo'] })
    cy.get('.header-hamburger-btn').click()
    cy.contains('Médicos').should('not.exist')
    cy.contains('Nuevo Dictamen').should('be.visible')
    cy.contains('Sincronizar').should('be.visible')
  })

  it('navegacion al hacer click en enlace del sidebar', () => {
    mountLayout()
    cy.get('.header-hamburger-btn').click()
    cy.contains('Productores').click()
    cy.get('@routerPush').should('have.been.calledWith', '/productores')
  })

  it('cierra sidebar al hacer click en overlay', () => {
    mountLayout()
    cy.get('.header-hamburger-btn').click()
    cy.get('.sidebar').should('have.class', 'active')
    cy.get('.sidebar-overlay').click({ force: true })
    cy.get('.sidebar').should('not.have.class', 'active')
  })

  it('bottom-nav se renderiza con enlaces principales', () => {
    mountLayout()
    cy.get('.bottom-nav').should('be.visible')
    cy.contains('Inicio').should('be.visible')
    cy.contains('Productores').should('be.visible')
    cy.contains('Predios').should('be.visible')
    cy.contains('Dictámenes').should('be.visible')
    cy.contains('Sincronizar').should('be.visible')
  })

  it('resalta enlace activo en bottom-nav', () => {
    mountLayout()
    cy.get('.bottom-nav-link.active').should('contain', 'Inicio')
  })
})
