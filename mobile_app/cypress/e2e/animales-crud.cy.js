describe('Animales CRUD Flow (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@sigdip.com', roles: ['Administrador'] },
    })
  })

  it('navega a lista de animales', () => {
    cy.intercept('GET', '**/api/animales', { statusCode: 200, body: { data: [] } }).as('getAnimales')

    cy.visit('/#/animales')
    cy.wait('@getAnimales', { timeout: 10000 })
    cy.get('h2', { timeout: 5000 }).should('contain', 'Animales Registrados')
  })

  it('navega a formulario crear animal', () => {
    cy.intercept('GET', '**/api/predios', { statusCode: 200, body: { data: [] } }).as('getPredios')

    cy.visit('/#/animales/nuevo')
    cy.wait('@getPredios', { timeout: 10000 })
    cy.contains('Registrar Animal', { timeout: 5000 }).should('be.visible')
  })

  it('ve el sidebar sin enlace a Animales', () => {
    cy.interceptDashboardStats()

    cy.visit('/#/dashboard')
    cy.get('.header-hamburger-btn').click()
    cy.get('.sidebar').should('be.visible')
    cy.get('.sidebar').contains('Animales').should('not.exist')
  })

  it('navega a todas las vistas de animales', () => {
    cy.intercept('GET', '**/api/animales', { statusCode: 200, body: { data: [] } }).as('getAnimales')

    cy.visit('/#/animales')
    cy.wait('@getAnimales', { timeout: 10000 })
    cy.get('h2', { timeout: 5000 }).should('be.visible')
  })
      
  it('crea un animal exitosamente', () => {
    cy.intercept('GET', '**/api/predios', {
      statusCode: 200,
      body: { data: [{ id: 1, nombre_rancho: 'Rancho Test', clave_unidad_produccion: 'UPP-001' }] },
    }).as('getPredios')

    cy.intercept('POST', '**/api/animales', {
      statusCode: 200,
      body: { data: { id: 99, numero_arete_siniiga: 'MX-001-0001' }, message: 'Animal registrado correctamente.' },
    }).as('storeAnimal')

    cy.visit('/#/animales/nuevo')
    cy.wait('@getPredios', { timeout: 10000 })
    cy.get('input[placeholder*="MX-123-456-789"]').type('MX-001-0001')
    cy.get('input[placeholder*="Angus, Brangus"]').type('Brangus')
    cy.get('select').eq(0).select('Macho')
    cy.get('input[type="number"]').type('3')
    cy.get('select').eq(1).select('1')

    cy.contains('button[type="submit"]', 'Guardar Animal').click()
    cy.wait('@storeAnimal', { timeout: 10000 })
    cy.get('.alert-success', { timeout: 5000 }).should('contain', 'Animal registrado correctamente.')
  })

  it('valida campos obligatorios al crear animal', () => {
    cy.intercept('GET', '**/api/predios', {
      statusCode: 200,
      body: { data: [{ id: 1, nombre_rancho: 'Rancho Test' }] },
    }).as('getPredios')

    cy.visit('/#/animales/nuevo')
    cy.wait('@getPredios', { timeout: 10000 })

    cy.contains('button[type="submit"]', 'Guardar Animal').click()
    cy.get('input[required]:invalid').should('exist')
  })

  it('edita un animal exitosamente', () => {
    cy.intercept('GET', '**/api/predios', {
      statusCode: 200,
      body: { data: [{ id: 1, nombre_rancho: 'Rancho Test', clave_unidad_produccion: 'UPP-001' }] },
    }).as('getPredios')

    cy.intercept('GET', '**/api/animales/1', {
      statusCode: 200,
      body: { data: { id: 1, numero_arete_siniiga: 'MX-001-0001', raza: 'Brangus', sexo: 'Macho', edad: 3, predio_id: 1 } },
    }).as('getAnimal')

    cy.intercept('PUT', '**/api/animales/1', {
      statusCode: 200,
      body: { data: { id: 1 }, message: 'Animal actualizado correctamente.' },
    }).as('updateAnimal')

    cy.visit('/#/animales/editar/1')
    cy.wait('@getPredios', { timeout: 10000 })
    cy.wait('@getAnimal', { timeout: 10000 })

    cy.get('input[placeholder*="MX-123-456-789"]').should('have.value', 'MX-001-0001')
    cy.get('input[placeholder*="Angus, Brangus"]').clear().type('Angus')

    cy.contains('button[type="submit"]', 'Actualizar Animal').click()
    cy.wait('@updateAnimal', { timeout: 10000 })
    cy.get('.alert-success', { timeout: 5000 }).should('contain', 'Animal actualizado correctamente.')
  })
})
