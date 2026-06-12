describe('Flujo de Visita (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('navega a crear visita desde el sidebar', () => {
    cy.intercept('GET', '**/api/dashboard/stats', {
      statusCode: 200,
      body: {
        totalInspecciones: 0, totalAnimales: 0, totalVisitasPendientes: 0,
        inspeccionesPorLocalidad: [], rendimientoVeterinarios: [],
        proximasVisitasGlobales: [], borradoresGlobales: [],
      },
    })

    cy.visit('/#/dashboard')
    cy.get('.header-hamburger-btn').click()
    cy.contains('Agenda / Visitas').click()
    cy.location('hash').should('eq', '#/visitas')
  })

  it('crea una visita exitosamente', () => {
    cy.intercept('GET', '**/api/predios', {
      statusCode: 200,
      body: {
        data: [
          { id: 1, nombre_rancho: 'Rancho Test', productor: { id: 1, nombre: 'Productor' }, localidad: 'Test', municipio: 'Test' },
        ],
      },
    }).as('getPredios')

    cy.intercept('GET', '**/api/productores', {
      statusCode: 200,
      body: {
        data: [
          { id: 1, nombre: 'Productor', apellido_paterno: 'Test', apellido_materno: '' },
        ],
      },
    }).as('getProductores')

    cy.intercept('GET', '**/api/medicos', {
      statusCode: 200,
      body: {
        data: [
          { id: 2, name: 'Dr. Juan', email: 'juan@test.com', roles: ['Medico_Campo'] },
        ],
      },
    }).as('getMedicos')

    cy.intercept('POST', '**/api/visitas', {
      statusCode: 200,
      body: {
        data: { id: 99, codigo: 'V-TEST-001' },
        message: 'Visita programada exitosamente',
      },
    }).as('saveVisita')

    cy.intercept('GET', '**/api/visitas/check-codigo/**', {
      statusCode: 200,
      body: { exists: false },
    }).as('checkCodigo')

    cy.visit('/#/visitas/nuevo')

    cy.wait('@getProductores', { timeout: 10000 })
    cy.wait('@getPredios', { timeout: 10000 })
    cy.wait('@getMedicos', { timeout: 10000 })

    cy.get('select').first().select('1')
    cy.get('select').eq(1).select('1')

    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    const dateStr = tomorrow.toISOString().split('T')[0]
    cy.get('input[type="date"]').type(dateStr)

    cy.get('select').eq(2).select('2')
    cy.get('textarea').type('Visita de prueba E2E')

    cy.get('button[type="submit"]').click()
    cy.wait('@checkCodigo', { timeout: 10000 })
    cy.wait('@saveVisita', { timeout: 10000 })
    cy.contains('programada con éxito', { timeout: 5000 }).should('be.visible')
  })
})
