describe('Editar Visita (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('navega a editar visita y carga datos existentes', () => {
    cy.intercept('GET', '**/api/predios', {
      statusCode: 200,
      body: {
        data: [
          { id: 10, nombre_rancho: 'Rancho Edit', productor: { id: 5, nombre: 'Juan', apellido_paterno: 'Pérez' }, localidad: 'Local', municipio: 'Muni' },
        ],
      },
    }).as('getPredios')

    cy.intercept('GET', '**/api/productores', {
      statusCode: 200,
      body: {
        data: [
          { id: 5, nombre: 'Juan', apellido_paterno: 'Pérez', apellido_materno: '' },
        ],
      },
    }).as('getProductores')

    cy.intercept('GET', '**/api/medicos', {
      statusCode: 200,
      body: {
        data: [
          { id: 3, name: 'Dr. Edit', email: 'edit@test.com', roles: ['Medico_Campo'] },
        ],
      },
    }).as('getMedicos')

    cy.intercept('GET', '**/api/visitas/50', {
      statusCode: 200,
      body: {
        data: {
          id: 50, codigo: 'V-EDIT-001',
          predio_id: 10, fecha_programada: '2025-07-15',
          veterinario_id: 3, observaciones: 'Visita de prueba',
        },
      },
    }).as('getVisita')

    cy.visit('/#/visitas/editar/50')
    cy.wait('@getProductores', { timeout: 10000 })
    cy.wait('@getPredios', { timeout: 10000 })
    cy.wait('@getMedicos', { timeout: 10000 })
    cy.wait('@getVisita', { timeout: 10000 })

    cy.contains('Editar Visita Programada', { timeout: 5000 }).should('be.visible')
    cy.get('textarea').should('have.value', 'Visita de prueba')
  })

  it('edita y guarda la visita exitosamente', () => {
    cy.intercept('GET', '**/api/predios', {
      statusCode: 200,
      body: {
        data: [
          { id: 10, nombre_rancho: 'Rancho Edit', productor: { id: 5, nombre: 'Juan', apellido_paterno: 'Pérez' }, localidad: 'Local', municipio: 'Muni' },
        ],
      },
    }).as('getPredios')

    cy.intercept('GET', '**/api/productores', {
      statusCode: 200,
      body: {
        data: [
          { id: 5, nombre: 'Juan', apellido_paterno: 'Pérez', apellido_materno: '' },
        ],
      },
    }).as('getProductores')

    cy.intercept('GET', '**/api/medicos', {
      statusCode: 200,
      body: {
        data: [
          { id: 3, name: 'Dr. Edit', email: 'edit@test.com', roles: ['Medico_Campo'] },
        ],
      },
    }).as('getMedicos')

    cy.intercept('GET', '**/api/visitas/50', {
      statusCode: 200,
      body: {
        data: {
          id: 50, codigo: 'V-EDIT-001',
          predio_id: 10, fecha_programada: '2025-07-15',
          veterinario_id: 3, observaciones: 'Visita de prueba',
        },
      },
    }).as('getVisita')

    cy.intercept('PUT', '**/api/visitas/50', {
      statusCode: 200,
      body: { data: { id: 50 }, message: 'Visita actualizada con éxito' },
    }).as('updateVisita')

    cy.visit('/#/visitas/editar/50')
    cy.wait('@getProductores', { timeout: 10000 })
    cy.wait('@getPredios', { timeout: 10000 })
    cy.wait('@getMedicos', { timeout: 10000 })
    cy.wait('@getVisita', { timeout: 10000 })

    cy.get('textarea').clear().type('Nota actualizada E2E')
    cy.get('button[type="submit"]').click()
    cy.wait('@updateVisita', { timeout: 10000 })
    cy.contains('actualizada con éxito', { timeout: 5000 }).should('be.visible')
  })

  it('muestra error si falla la API al cargar la visita', () => {
    cy.intercept('GET', '**/api/predios', { statusCode: 200, body: { data: [] } }).as('getPredios')
    cy.intercept('GET', '**/api/productores', { statusCode: 200, body: { data: [] } }).as('getProductores')
    cy.intercept('GET', '**/api/medicos', { statusCode: 200, body: { data: [] } }).as('getMedicos')

    cy.intercept('GET', '**/api/visitas/999', {
      statusCode: 500,
      body: { message: 'Error al cargar visita' },
    }).as('getVisitaFail')

    cy.visit('/#/visitas/editar/999')
    cy.wait('@getProductores', { timeout: 10000 })
    cy.wait('@getPredios', { timeout: 10000 })
    cy.wait('@getMedicos', { timeout: 10000 })
    cy.wait('@getVisitaFail', { timeout: 10000 })

    cy.contains('No se pudo cargar la visita', { timeout: 5000 }).should('be.visible')
  })

  it('muestra error si falla la API al guardar la edicion', () => {
    cy.intercept('GET', '**/api/predios', {
      statusCode: 200,
      body: {
        data: [
          { id: 10, nombre_rancho: 'Rancho Edit', productor: { id: 5, nombre: 'Juan', apellido_paterno: 'Pérez' }, localidad: 'Local', municipio: 'Muni' },
        ],
      },
    }).as('getPredios')

    cy.intercept('GET', '**/api/productores', {
      statusCode: 200,
      body: {
        data: [
          { id: 5, nombre: 'Juan', apellido_paterno: 'Pérez', apellido_materno: '' },
        ],
      },
    }).as('getProductores')

    cy.intercept('GET', '**/api/medicos', {
      statusCode: 200,
      body: {
        data: [
          { id: 3, name: 'Dr. Edit', email: 'edit@test.com', roles: ['Medico_Campo'] },
        ],
      },
    }).as('getMedicos')

    cy.intercept('GET', '**/api/visitas/50', {
      statusCode: 200,
      body: {
        data: {
          id: 50, codigo: 'V-EDIT-001',
          predio_id: 10, fecha_programada: '2025-07-15',
          veterinario_id: 3, observaciones: 'Visita de prueba',
        },
      },
    }).as('getVisita')

    cy.intercept('PUT', '**/api/visitas/50', {
      statusCode: 500,
      body: { message: 'Error al actualizar' },
    }).as('updateVisitaFail')

    cy.visit('/#/visitas/editar/50')
    cy.wait('@getProductores', { timeout: 10000 })
    cy.wait('@getPredios', { timeout: 10000 })
    cy.wait('@getMedicos', { timeout: 10000 })
    cy.wait('@getVisita', { timeout: 10000 })

    cy.get('textarea').clear().type('Nota que fallara')
    cy.get('button[type="submit"]').click()
    cy.wait('@updateVisitaFail', { timeout: 10000 })

    cy.location('hash', { timeout: 5000 }).should('include', '/visitas/editar/50')
  })
})
