describe('Aretes Censo CRUD Flow (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@sigdip.com', roles: ['Administrador'] },
    })
  })

  it('navega a lista de aretes del censo', () => {
    cy.intercept('GET', '**/api/aretes-censo', { statusCode: 200, body: { data: [] } }).as('getAretes')

    cy.visit('/#/aretes-censo')
    cy.wait('@getAretes', { timeout: 10000 })
    cy.get('h2', { timeout: 5000 }).should('contain', 'Aretes del Censo')
  })

  it('navega a formulario crear arete', () => {
    cy.intercept('GET', '**/api/productores', { statusCode: 200, body: { data: [] } }).as('getProductores')
    cy.intercept('GET', '**/api/predios', { statusCode: 200, body: { data: [] } }).as('getPredios')

    cy.visit('/#/aretes-censo/nuevo')
    cy.wait('@getProductores', { timeout: 10000 })
    cy.wait('@getPredios', { timeout: 10000 })
    cy.contains('Registrar Arete del Censo', { timeout: 5000 }).should('be.visible')
  })

  it('ve el sidebar con enlace a Aretes del Censo', () => {
    cy.interceptDashboardStats()

    cy.visit('/#/dashboard')
    cy.get('.header-hamburger-btn').click()
    cy.get('.sidebar').should('be.visible')
    cy.get('.sidebar').contains('Aretes del Censo').should('be.visible')
  })

  it('navega a todas las vistas de aretes', () => {
    cy.intercept('GET', '**/api/aretes-censo', { statusCode: 200, body: { data: [] } }).as('getAretes')

    cy.visit('/#/aretes-censo')
    cy.wait('@getAretes', { timeout: 10000 })
    cy.get('h2', { timeout: 5000 }).should('be.visible')
  })
})
