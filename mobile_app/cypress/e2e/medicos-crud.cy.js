describe('CRUD de Medicos (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('navega a lista de medicos', () => {
    cy.intercept('GET', '**/api/medicos', {
      statusCode: 200,
      body: { data: [] },
    }).as('getMedicos')

    cy.visit('/#/medicos')
    cy.wait('@getMedicos', { timeout: 10000 })
    cy.get('h2').contains('Médicos', { timeout: 5000 }).should('be.visible')
  })

  it('navega a crear medico desde la lista', () => {
    cy.intercept('GET', '**/api/medicos', {
      statusCode: 200,
      body: { data: [] },
    }).as('getMedicos')

    cy.visit('/#/medicos')
    cy.wait('@getMedicos', { timeout: 10000 })
    cy.contains('Nuevo Médico').click()
    cy.location('hash').should('eq', '#/medicos/nuevo')
    cy.contains('Registrar Médico Verificador', { timeout: 5000 }).should('be.visible')
  })

  it('crea un medico exitosamente', () => {
    cy.intercept('POST', '**/api/medicos', {
      statusCode: 200,
      body: { data: { id: 99, name: 'Dr. Nuevo', email: 'nuevo@test.com' } },
    }).as('createMedico')

    cy.visit('/#/medicos/nuevo')
    cy.get('input').first().type('Dr. Nuevo')
    cy.get('input[type="email"]').type('nuevo@test.com')
    cy.get('input[type="password"]').first().type('password123')
    cy.get('input[type="password"]').eq(1).type('password123')

    cy.get('button[type="submit"]').click()
    cy.wait('@createMedico', { timeout: 10000 })
    cy.location('hash', { timeout: 5000 }).should('include', '/medicos')
  })

  it('valida que las contrasenas coincidan', () => {
    cy.visit('/#/medicos/nuevo')
    cy.get('input').first().type('Dr. Error')
    cy.get('input[type="email"]').type('error@test.com')
    cy.get('input[type="password"]').first().type('password123')
    cy.get('input[type="password"]').eq(1).type('different')

    cy.get('button[type="submit"]').click()
    cy.contains('coinciden', { timeout: 5000 }).should('be.visible')
  })
})
