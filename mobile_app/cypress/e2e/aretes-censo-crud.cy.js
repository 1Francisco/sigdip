describe('Aretes Censo CRUD Flow (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@sigdip.com', roles: ['Administrador'] },
    })
  })

  it('navega a lista de aretes del censo', () => {
    cy.intercept('GET', '**/api/aretes-censo', { statusCode: 200, body: { data: [] } }).as('getAretes')

    cy.visit('/#/aretes-censo')
    cy.wait('@getAretes', { timeout: 10000 })
    cy.get('h2', { timeout: 5000 }).should('contain', 'Aretes del Censo')
  })

  it('navega a formulario crear arete', () => {
    cy.intercept('GET', '**/api/productores', { statusCode: 200, body: { data: [] } }).as('getProductores')
    cy.intercept('GET', '**/api/predios', { statusCode: 200, body: { data: [] } }).as('getPredios')

    cy.visit('/#/aretes-censo/nuevo')
    cy.wait('@getProductores', { timeout: 10000 })
    cy.wait('@getPredios', { timeout: 10000 })
    cy.contains('Registrar Arete del Censo', { timeout: 5000 }).should('be.visible')
  })

  it('ve el sidebar sin enlace a Aretes del Censo', () => {
    cy.interceptDashboardStats()

    cy.visit('/#/dashboard')
    cy.get('.header-hamburger-btn').click()
    cy.get('.sidebar').should('be.visible')
    cy.get('.sidebar').contains('Aretes del Censo').should('not.exist')
  })

  it('navega a todas las vistas de aretes', () => {
    cy.intercept('GET', '**/api/aretes-censo', { statusCode: 200, body: { data: [] } }).as('getAretes')

    cy.visit('/#/aretes-censo')
    cy.wait('@getAretes', { timeout: 10000 })
    cy.get('h2', { timeout: 5000 }).should('be.visible')
  })

  it('crea un arete censo exitosamente', () => {
    cy.intercept('GET', '**/api/productores', {
      statusCode: 200,
      body: { data: [{ id: 1, nombre: 'Juan', apellido_paterno: 'Perez' }] },
    }).as('getProductores')

    cy.intercept('GET', '**/api/predios', {
      statusCode: 200,
      body: { data: [{ id: 1, nombre_rancho: 'Rancho Test' }] },
    }).as('getPredios')

    cy.intercept('POST', '**/api/aretes-censo', {
      statusCode: 200,
      body: { data: { id: 99 }, message: 'Arete del censo registrado correctamente.' },
    }).as('storeArete')

    cy.visit('/#/aretes-censo/nuevo')
    cy.wait('@getProductores', { timeout: 10000 })
    cy.wait('@getPredios', { timeout: 10000 })

    cy.get('input[placeholder*="MX-001-1234"]').type('MX-001-9999')
    cy.get('select').eq(0).select('1')
    cy.get('select').eq(1).select('1')
    cy.get('input[placeholder*="Angus, Hereford"]').type('Hereford')
    cy.get('select').eq(2).select('Hembra')
    cy.get('input[type="date"]').type('2025-01-15')
    cy.get('input[type="number"]').type('18')

    cy.contains('button[type="submit"]', 'Guardar Arete').click()
    cy.wait('@storeArete', { timeout: 10000 })
    cy.get('.alert-success', { timeout: 5000 }).should('contain', 'Arete del censo registrado correctamente.')
  })

  it('valida campos obligatorios al crear arete censo', () => {
    cy.intercept('GET', '**/api/productores', {
      statusCode: 200,
      body: { data: [{ id: 1, nombre: 'Juan', apellido_paterno: 'Perez' }] },
    }).as('getProductores')

    cy.intercept('GET', '**/api/predios', {
      statusCode: 200,
      body: { data: [{ id: 1, nombre_rancho: 'Rancho Test' }] },
    }).as('getPredios')

    cy.visit('/#/aretes-censo/nuevo')
    cy.wait('@getProductores', { timeout: 10000 })
    cy.wait('@getPredios', { timeout: 10000 })

    cy.contains('button[type="submit"]', 'Guardar Arete').click()
    cy.get('input[required]:invalid').should('exist')
  })

  it('edita un arete censo exitosamente', () => {
    cy.intercept('GET', '**/api/productores', {
      statusCode: 200,
      body: { data: [{ id: 1, nombre: 'Juan', apellido_paterno: 'Perez' }] },
    }).as('getProductores')

    cy.intercept('GET', '**/api/predios', {
      statusCode: 200,
      body: { data: [{ id: 1, nombre_rancho: 'Rancho Test' }] },
    }).as('getPredios')

    cy.intercept('GET', '**/api/aretes-censo/1', {
      statusCode: 200,
      body: { data: { id: 1, numero_arete: 'MX-001-9999', productor_id: 1, predio_id: 1, raza: 'Hereford', sexo: 'Hembra', fecha_nacimiento: '2025-01-15', edad_meses: 18, sacrificio: false } },
    }).as('getArete')

    cy.intercept('PUT', '**/api/aretes-censo/1', {
      statusCode: 200,
      body: { data: { id: 1 }, message: 'Arete del censo actualizado correctamente.' },
    }).as('updateArete')

    cy.visit('/#/aretes-censo/editar/1')
    cy.wait('@getProductores', { timeout: 10000 })
    cy.wait('@getPredios', { timeout: 10000 })
    cy.wait('@getArete', { timeout: 10000 })

    cy.get('input[placeholder*="MX-001-1234"]').should('have.value', 'MX-001-9999')
    cy.get('input[placeholder*="Angus, Hereford"]').clear().type('Brangus')

    cy.contains('button[type="submit"]', 'Actualizar Arete').click()
    cy.wait('@updateArete', { timeout: 10000 })
    cy.get('.alert-success', { timeout: 5000 }).should('contain', 'Arete del censo actualizado correctamente.')
  })
})
