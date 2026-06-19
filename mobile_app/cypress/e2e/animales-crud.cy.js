describe('Animales CRUD Flow (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@sigdip.com', roles: ['Administrador'] },
    })
  })

  it('navega a lista de animales', () => {
    cy.intercept('GET', '**/api/animales', { statusCode: 200, body: { data: [] } }).as('getAnimales')

    cy.visit('/#/animales')
    cy.wait('@getAnimales', { timeout: 10000 })
    cy.get('h2', { timeout: 5000 }).should('contain', 'Animales Registrados')
  })

  it('navega a formulario crear animal', () => {
    cy.intercept('GET', '**/api/predios', { statusCode: 200, body: { data: [] } }).as('getPredios')

    cy.visit('/#/animales/nuevo')
    cy.wait('@getPredios', { timeout: 10000 })
    cy.contains('Registrar Animal', { timeout: 5000 }).should('be.visible')
  })

  it('ve el sidebar con enlace a Animales', () => {
    cy.interceptDashboardStats()

    cy.visit('/#/dashboard')
    cy.get('.header-hamburger-btn').click()
    cy.get('.sidebar').should('be.visible')
    cy.get('.sidebar').contains('Animales').should('be.visible')
  })

  it('navegacion como admin desde sidebar a Animales', () => {
    cy.intercept('GET', '**/api/animales', { statusCode: 200, body: { data: [] } }).as('getAnimales')

    cy.visit('/#/dashboard')
    cy.get('.header-hamburger-btn').click()
    cy.get('.sidebar').contains('Animales').click()
    cy.location('hash', { timeout: 5000 }).should('include', '/animales')
  })

  it('navega a todas las vistas de animales', () => {
    cy.intercept('GET', '**/api/animales', { statusCode: 200, body: { data: [] } }).as('getAnimales')

    cy.visit('/#/animales')
    cy.wait('@getAnimales', { timeout: 10000 })
    cy.get('h2', { timeout: 5000 }).should('be.visible')
  })
})
