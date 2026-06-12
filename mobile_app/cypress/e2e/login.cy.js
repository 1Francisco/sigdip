describe('Login Flow (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
  })

  it('carga la pagina de login', () => {
    cy.visit('/#/login')
    cy.get('h1').should('contain', 'SIGDIP')
    cy.get('#email').should('be.visible')
    cy.get('#password').should('be.visible')
    cy.get('.btn-login').should('contain', 'Ingresar al Panel')
  })

  it('redirige a login si no hay token', () => {
    cy.visit('/#/dashboard')
    cy.location('hash').should('eq', '#/login')
  })

  it('loguea exitosamente y redirige a dashboard', () => {
    cy.intercept('POST', '**/api/login', {
      statusCode: 200,
      body: {
        token: 'e2e-test-token',
        user: {
          id: 1,
          name: 'Admin E2E',
          email: 'admin@sigdip.com',
          roles: ['Administrador'],
        },
      },
    }).as('loginApi')

    cy.visit('/#/login')
    cy.get('#email').type('admin@sigdip.com')
    cy.get('#password').type('password123')
    cy.get('.btn-login').click()

    cy.wait('@loginApi')
    cy.location('hash', { timeout: 10000 }).should('eq', '#/dashboard')
  })

  it('muestra error con credenciales invalidas', () => {
    cy.intercept('POST', '**/api/login', {
      statusCode: 401,
      body: { message: 'Credenciales incorrectas' },
    }).as('loginFail')

    cy.visit('/#/login')
    cy.get('#email').type('wrong@email.com')
    cy.get('#password').type('wrongpass')
    cy.get('.btn-login').click()

    cy.wait('@loginFail')
    cy.get('.error-message').should('be.visible')
    cy.location('hash').should('eq', '#/login')
  })

  it('permite navegar al dashboard si ya hay sesion', () => {
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })

    cy.intercept('GET', '**/api/dashboard/stats', {
      statusCode: 200,
      body: {
        totalInspecciones: 10,
        totalAnimales: 200,
        totalVisitasPendientes: 3,
        inspeccionesPorLocalidad: [],
        rendimientoVeterinarios: [],
        proximasVisitasGlobales: [],
        borradoresGlobales: [],
      },
    }).as('getStats')

    cy.visit('/#/dashboard')
    cy.location('hash').should('eq', '#/dashboard')
    cy.contains('Resumen Administrativo').should('be.visible')
  })
})
