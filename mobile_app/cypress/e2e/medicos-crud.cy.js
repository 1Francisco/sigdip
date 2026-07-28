describe('CRUD de Medicos (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('navega a lista de medicos', () => {
    cy.interceptEmptyMedicos()

    cy.visit('/#/medicos')
    cy.wait('@getMedicos', { timeout: 10000 })
    cy.get('h2').contains('Médicos', { timeout: 5000 }).should('be.visible')
  })

  it('navega a crear medico desde la lista', () => {
    cy.interceptEmptyMedicos()

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
    cy.get('select').eq(0).select('A')
    cy.get('select').eq(1).select('Buffer')
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
    cy.get('select').eq(0).select('A')
    cy.get('select').eq(1).select('Buffer')
    cy.get('input[type="password"]').first().type('password123')
    cy.get('input[type="password"]').eq(1).type('different')

    cy.get('button[type="submit"]').click()
    cy.contains('no coinciden', { timeout: 5000 }).should('be.visible')
  })

  it('muestra empty state en medicos', () => {
    cy.interceptEmptyMedicos()

    cy.visit('/#/medicos')
    cy.wait('@getMedicos', { timeout: 10000 })
    cy.contains('No hay médicos registrados', { timeout: 5000 }).should('be.visible')
  })

  it('muestra error si la API falla al crear medico', () => {
    cy.intercept('POST', '**/api/medicos', {
      statusCode: 500,
      body: { message: 'Error del servidor' },
    }).as('createMedicoFail')

    cy.visit('/#/medicos/nuevo')
    cy.get('input').first().type('Dr. Fail')
    cy.get('input[type="email"]').type('fail@test.com')
    cy.get('select').eq(0).select('A')
    cy.get('select').eq(1).select('Buffer')
    cy.get('input[type="password"]').first().type('password123')
    cy.get('input[type="password"]').eq(1).type('password123')

    cy.get('button[type="submit"]').click()
    cy.wait('@createMedicoFail', { timeout: 10000 })
    cy.location('hash').should('include', '/medicos/nuevo')
  })

  it('navega a editar medico desde la lista', () => {
    cy.intercept('GET', '**/api/medicos', {
      statusCode: 200,
      body: {
        data: [
          { id: 2, name: 'Dr. Juan', email: 'juan@test.com', created_at: '2025-01-01' },
        ],
      },
    }).as('getMedicos')

    cy.intercept('GET', '**/api/medicos/2', {
      statusCode: 200,
      body: { data: { id: 2, name: 'Dr. Juan', email: 'juan@test.com', zona: 'A', actividad: 'Buffer' } },
    }).as('getMedico')

    cy.visit('/#/medicos')
    cy.wait('@getMedicos', { timeout: 10000 })
    cy.get('button[title="Editar médico"]').first().click({ force: true })
    cy.wait('@getMedico', { timeout: 10000 })
    cy.location('hash', { timeout: 5000 }).should('eq', '#/medicos/editar/2')
    cy.contains('Editar Médico Verificador', { timeout: 5000 }).should('be.visible')
  })

  it('edita un medico exitosamente', () => {
    cy.intercept('GET', '**/api/medicos/2', {
      statusCode: 200,
      body: { data: { id: 2, name: 'Dr. Juan', email: 'juan@test.com', zona: 'A', actividad: 'Buffer' } },
    }).as('getMedico')

    cy.intercept('PUT', '**/api/medicos/2', {
      statusCode: 200,
      body: { data: { id: 2, name: 'Dr. Juan Modificado', email: 'juan.modificado@test.com' } },
    }).as('updateMedico')

    cy.visit('/#/medicos/editar/2')
    cy.wait('@getMedico', { timeout: 10000 })

    cy.get('input').first().clear().type('Dr. Juan Modificado')
    cy.get('input[type="email"]').clear().type('juan.modificado@test.com')

    cy.get('button[type="submit"]').click()
    cy.wait('@updateMedico', { timeout: 10000 })
    cy.contains('actualizado correctamente', { timeout: 5000 }).should('be.visible')
  })

  it('muestra error si la API falla al editar medico', () => {
    cy.intercept('GET', '**/api/medicos/2', {
      statusCode: 200,
      body: { data: { id: 2, name: 'Dr. Juan', email: 'juan@test.com', zona: 'A', actividad: 'Buffer' } },
    }).as('getMedico')

    cy.intercept('PUT', '**/api/medicos/2', {
      statusCode: 500,
      body: { message: 'Error al actualizar médico' },
    }).as('updateMedicoFail')

    cy.visit('/#/medicos/editar/2')
    cy.wait('@getMedico', { timeout: 10000 })

    cy.get('button[type="submit"]').click()
    cy.wait('@updateMedicoFail', { timeout: 10000 })
    cy.contains('Error al actualizar médico', { timeout: 5000 }).should('be.visible')
  })
})

