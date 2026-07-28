describe('Dashboard Rendimiento (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('navega al dashboard y ve el grafico de rendimiento', () => {
    cy.intercept('GET', '**/api/dashboard/stats', {
      statusCode: 200,
      body: {
        totalInspecciones: 150,
        totalAnimales: 3200,
        totalVisitasPendientes: 45,
        inspeccionesPorLocalidad: [
          { localidad: 'Villahermosa', total: 45 },
          { localidad: 'Escarcega', total: 32 },
        ],
        rendimientoVeterinarios: [
          { nombre: 'Dr. Juan Perez', total: 80 },
          { nombre: 'Dra. Ana Garcia', total: 70 },
        ],
        proximasVisitasGlobales: [],
        borradoresGlobales: [],
      },
    }).as('getStats')

    cy.visit('/#/dashboard')
    cy.location('hash').should('eq', '#/dashboard')
    cy.contains('Resumen Administrativo', { timeout: 10000 }).should('be.visible')
    cy.contains('Rendimiento Veterinarios').should('be.visible')
    cy.get('canvas').should('be.visible')
  })

  it('maneja estado vacio de rendimiento sin errores', () => {
    cy.intercept('GET', '**/api/dashboard/stats', {
      statusCode: 200,
      body: {
        totalInspecciones: 0,
        totalAnimales: 0,
        totalVisitasPendientes: 0,
        inspeccionesPorLocalidad: [],
        rendimientoVeterinarios: [],
        proximasVisitasGlobales: [],
        borradoresGlobales: [],
      },
    }).as('getStatsEmpty')

    cy.visit('/#/dashboard')
    cy.contains('Resumen Administrativo', { timeout: 10000 }).should('be.visible')
    cy.contains('Rendimiento Veterinarios').should('be.visible')
    cy.contains('TOTAL LECTURAS').should('be.visible')
    cy.contains('0').should('be.visible')
  })

  it('muestra rendimiento desde cache offline cuando el servidor no responde', () => {
    cy.intercept('GET', '**/api/user', {
      statusCode: 200,
    }).as('getUser')

    cy.intercept('GET', '**/api/dashboard/stats', {
      statusCode: 500,
      body: { message: 'Server error' },
    }).as('getStatsFail')

    cy.seedIndexedDB('catalogos', 'dashboard_data', {
      adminStats: {
        totalInspecciones: 200,
        totalAnimales: 4000,
        totalVisitasPendientes: 15,
        inspeccionesPorLocalidad: [],
        rendimientoVeterinarios: [
          { nombre: 'Dr. Juan Perez', total: 100 },
          { nombre: 'Dra. Ana Garcia', total: 100 },
        ],
        proximasVisitasGlobales: [],
        borradoresGlobales: [],
      },
    })

    cy.visit('/#/dashboard')
    cy.contains('Rendimiento Veterinarios', { timeout: 10000 }).should('be.visible')
    cy.contains('200').should('be.visible')
  })
})
