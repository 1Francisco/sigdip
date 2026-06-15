describe('Paginación en listas (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  const buildInspecciones = (count) =>
    Array.from({ length: count }, (_, i) => ({
      id: i + 1,
      folio: `FOL-${String(i + 1).padStart(3, '0')}`,
      fecha: '2025-06-01',
      estado: 'finalizado',
      predio: { id: 1, nombre_rancho: `Rancho ${i + 1}`, productor: { id: 1, nombre: 'Juan', apellido_paterno: 'Pérez' } },
      veterinario: { id: 2, name: 'Dr. López' },
    }))

  it('muestra primera pagina con 10 registros de 15', () => {
    cy.intercept('GET', '**/api/inspecciones', {
      statusCode: 200,
      body: { data: buildInspecciones(15) },
    }).as('getInspecciones')

    cy.visit('/#/inspecciones')
    cy.wait('@getInspecciones', { timeout: 10000 })

    cy.contains('Pág. 1 de 2', { timeout: 5000 }).should('be.visible')
    cy.contains('(15 registros)', { timeout: 5000 }).should('be.visible')
    cy.contains('FOL-001').should('exist')
    cy.contains('FOL-010').should('exist')
    cy.contains('FOL-011').should('not.exist')
  })

  it('navega a la segunda pagina usando next', () => {
    cy.intercept('GET', '**/api/inspecciones', {
      statusCode: 200,
      body: { data: buildInspecciones(15) },
    }).as('getInspecciones')

    cy.visit('/#/inspecciones')
    cy.wait('@getInspecciones', { timeout: 10000 })

    cy.get('.next-btn').click()
    cy.contains('Pág. 2 de 2', { timeout: 5000 }).should('be.visible')
    cy.contains('FOL-011').should('exist')
    cy.contains('FOL-015').should('exist')
  })

  it('deshabilita botones prev y next en los extremos', () => {
    cy.intercept('GET', '**/api/inspecciones', {
      statusCode: 200,
      body: { data: buildInspecciones(1) },
    }).as('getInspecciones')

    cy.visit('/#/inspecciones')
    cy.wait('@getInspecciones', { timeout: 10000 })

    cy.get('.prev-btn').should('be.disabled')
    cy.get('.next-btn').should('be.disabled')
  })
})
