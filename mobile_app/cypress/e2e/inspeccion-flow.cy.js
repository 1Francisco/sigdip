describe('Flujo de Inspeccion (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('navega a formulario de dictamen', () => {
    cy.visit('/#/inspeccion')
    cy.contains('Registro de Dictamen', { timeout: 5000 }).should('be.visible')
  })

  it('navega a inspecciones desde el dashboard', () => {
    cy.interceptEmptyInspecciones()

    cy.visit('/#/inspecciones')
    cy.wait('@getInspecciones', { timeout: 10000 })
    cy.contains('Nuevo Dictamen', { timeout: 5000 }).click()
    cy.location('hash').should('eq', '#/inspeccion')
    cy.contains('Registro de Dictamen', { timeout: 5000 }).should('be.visible')
  })
})
