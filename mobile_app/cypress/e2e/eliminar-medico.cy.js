describe('Eliminar Médico (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('elimina un medico exitosamente con confirmacion', () => {
    cy.intercept('GET', '**/api/medicos', {
      statusCode: 200,
      body: {
        data: [
          { id: 1, name: 'Dr. Juan', email: 'juan@test.com', created_at: '2025-01-01' },
        ],
      },
    }).as('getMedicos')

    cy.intercept('DELETE', '**/api/medicos/1', {
      statusCode: 200,
      body: { message: 'Médico eliminado correctamente' },
    }).as('deleteMedico')

    cy.visit('/#/medicos')
    cy.wait('@getMedicos', { timeout: 10000 })
    cy.get('h2').contains('Médicos', { timeout: 5000 }).should('be.visible')

    cy.contains('Eliminar').click({ force: true })
    cy.wait('@deleteMedico', { timeout: 10000 })

    cy.contains('eliminado del sistema', { timeout: 5000 }).should('be.visible')
  })

  it('cancela la eliminacion en el dialogo de confirmacion', () => {
    cy.intercept('GET', '**/api/medicos', {
      statusCode: 200,
      body: {
        data: [
          { id: 1, name: 'Dr. Juan', email: 'juan@test.com', created_at: '2025-01-01' },
        ],
      },
    }).as('getMedicos')

    cy.visit('/#/medicos')
    cy.wait('@getMedicos', { timeout: 10000 })

    cy.on('window:confirm', () => false)
    cy.contains('Eliminar').click({ force: true })

    cy.contains('Dr. Juan', { timeout: 5000 }).should('exist')
    cy.get('h2').contains('Médicos', { timeout: 5000 }).should('be.visible')
  })

  it('muestra error si la API falla al eliminar medico', () => {
    cy.intercept('GET', '**/api/medicos', {
      statusCode: 200,
      body: {
        data: [
          { id: 1, name: 'Dr. Juan', email: 'juan@test.com', created_at: '2025-01-01' },
        ],
      },
    }).as('getMedicos')

    cy.intercept('DELETE', '**/api/medicos/1', {
      statusCode: 500,
      body: { message: 'Error al eliminar medico' },
    }).as('deleteMedicoFail')

    cy.visit('/#/medicos')
    cy.wait('@getMedicos', { timeout: 10000 })

    cy.contains('Eliminar').click({ force: true })
    cy.wait('@deleteMedicoFail', { timeout: 10000 })

    cy.contains('Error al eliminar', { timeout: 5000 }).should('be.visible')
  })
})
