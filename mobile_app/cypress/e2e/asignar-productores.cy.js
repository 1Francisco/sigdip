describe('Asignación de Productores (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('navega a asignar productores desde la lista de médicos', () => {
    cy.intercept('GET', '**/api/medicos', {
      statusCode: 200,
      body: {
        data: [
          { id: 2, name: 'Dr. Juan', email: 'juan@test.com', created_at: '2025-01-01' },
        ],
      },
    }).as('getMedicos')

    cy.intercept('GET', '**/api/usuarios/2/productores-asignables', {
      statusCode: 200,
      body: {
        success: true,
        medico: { name: 'Dr. Juan' },
        asignados: [],
        disponibles: []
      }
    }).as('getAsignables')

    cy.visit('/#/medicos')
    cy.wait('@getMedicos', { timeout: 10000 })
    cy.get('h2').contains('Médicos', { timeout: 5000 }).should('be.visible')

    // Clic en el botón asignar (force para manejar layout responsive)
    cy.get('button[title="Asignar productores a este médico"]').click({ force: true })
    cy.wait('@getAsignables', { timeout: 10000 })
    cy.location('hash', { timeout: 5000 }).should('eq', '#/medicos/asignar/2')
    cy.get('.header-title').should('contain', 'Asignar Productores')
  })

  it('muestra la lista de productores asignados y disponibles', () => {
    cy.intercept('GET', '**/api/usuarios/2/productores-asignables', {
      statusCode: 200,
      body: {
        success: true,
        medico: { name: 'Dr. Juan' },
        asignados: [
          { id: 10, nombre: 'Pedro', apellido_paterno: 'Asignado', upp: 'UPP-A1', predios_count: 2 }
        ],
        disponibles: [
          { id: 20, nombre: 'Maria', apellido_paterno: 'Disponible', curp: 'CURP-D1', upp: 'UPP-D1', predios_count: 1 }
        ]
      }
    }).as('getAsignables')

    cy.visit('/#/medicos/asignar/2')
    cy.wait('@getAsignables', { timeout: 10000 })

    // Validar asignado
    cy.contains('Pedro Asignado').should('be.visible')
    cy.contains('UPP: UPP-A1 | Predios: 2').should('be.visible')

    // Validar disponible
    cy.contains('Maria Disponible').should('be.visible')
    cy.contains('CURP: CURP-D1 | UPP: UPP-D1 | Predios: 1').should('be.visible')
  })

  it('asigna un productor disponible exitosamente', () => {
    cy.intercept('GET', '**/api/usuarios/2/productores-asignables', {
      statusCode: 200,
      body: {
        success: true,
        medico: { name: 'Dr. Juan' },
        asignados: [],
        disponibles: [
          { id: 20, nombre: 'Maria', apellido_paterno: 'Disponible', curp: 'CURP-D1', upp: 'UPP-D1', predios_count: 1 }
        ]
      }
    }).as('getAsignables')

    cy.intercept('POST', '**/api/usuarios/2/asignar-productores', {
      statusCode: 200,
      body: {
        success: true,
        message: 'Asignación guardada correctamente.'
      }
    }).as('saveAsignacion')

    cy.visit('/#/medicos/asignar/2')
    cy.wait('@getAsignables', { timeout: 10000 })

    // Seleccionar el checkbox del productor disponible
    cy.get('input[type="checkbox"]').first().check()

    // Hacer clic en Guardar Asignación
    cy.contains('Guardar Asignación').click()
    cy.wait('@saveAsignacion', { timeout: 10000 })

    // Se vuelve a cargar data al guardar
    cy.wait('@getAsignables', { timeout: 10000 })
  })

  it('desasigna un productor asignado exitosamente', () => {
    cy.intercept('GET', '**/api/usuarios/2/productores-asignables', {
      statusCode: 200,
      body: {
        success: true,
        medico: { name: 'Dr. Juan' },
        asignados: [
          { id: 10, nombre: 'Pedro', apellido_paterno: 'Asignado', upp: 'UPP-A1', predios_count: 2 }
        ],
        disponibles: []
      }
    }).as('getAsignables')

    cy.intercept('POST', '**/api/usuarios/2/desasignar-productor/10', {
      statusCode: 200,
      body: {
        success: true,
        message: 'Productor desasignado correctamente.'
      }
    }).as('desasignarProductor')

    cy.visit('/#/medicos/asignar/2')
    cy.wait('@getAsignables', { timeout: 10000 })

    // Stub confirm alert
    cy.on('window:confirm', () => true)

    // Clic en el botón desasignar (icono de X roja)
    cy.get('button[title="Desasignar"]').click()
    cy.wait('@desasignarProductor', { timeout: 10000 })

    // Validar que se remueve de la lista de asignados y pasa a disponibles
    cy.contains('Pedro Asignado').should('be.visible')
    cy.contains('No hay productores asignados a este médico.').should('be.visible')
  })

  it('cancela la desasignación en el diálogo de confirmación', () => {
    cy.intercept('GET', '**/api/usuarios/2/productores-asignables', {
      statusCode: 200,
      body: {
        success: true,
        medico: { name: 'Dr. Juan' },
        asignados: [
          { id: 10, nombre: 'Pedro', apellido_paterno: 'Asignado', upp: 'UPP-A1', predios_count: 2 }
        ],
        disponibles: []
      }
    }).as('getAsignables')

    cy.visit('/#/medicos/asignar/2')
    cy.wait('@getAsignables', { timeout: 10000 })

    // Stub confirm alert to return false (cancel)
    cy.on('window:confirm', () => false)

    cy.get('button[title="Desasignar"]').click()

    // Debería seguir estando asignado
    cy.contains('Pedro Asignado').should('be.visible')
    cy.contains('No hay productores asignados a este médico.').should('not.exist')
  })

  it('redirige si el usuario no es Administrador', () => {
    cy.setLoginState({
      user: { id: 2, name: 'Medico Campo', email: 'medico@test.com', roles: ['Medico_Campo'] },
    })

    cy.on('uncaught:exception', () => false)

    cy.visit('/#/medicos/asignar/2')
    cy.location('hash', { timeout: 5000 }).should('eq', '#/medicos')
  })
})
