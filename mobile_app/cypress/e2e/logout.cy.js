describe('Cierre de Sesion (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('cierra sesion desde el sidebar del dashboard', () => {
    cy.interceptDashboardStats()

    cy.visit('/#/dashboard')
    cy.get('.header-hamburger-btn').click()
    cy.get('.sidebar').contains('Salir').click()
    cy.location('hash', { timeout: 5000 }).should('eq', '#/login')
  })

  it('redirige a login al intentar acceder a dashboard tras logout', () => {
    cy.interceptDashboardStats()

    cy.visit('/#/dashboard')
    cy.get('.header-hamburger-btn').click()
    cy.get('.sidebar').contains('Salir').click()
    cy.location('hash', { timeout: 5000 }).should('eq', '#/login')

    cy.visit('/#/dashboard')
    cy.location('hash').should('eq', '#/login')
  })
})
