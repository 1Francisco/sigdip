describe('Creacion de Predio (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('crea un predio exitosamente', () => {
    cy.intercept('GET', '**/api/productores', {
      statusCode: 200,
      body: { data: [
        { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia', apellido_materno: 'Lopez' },
      ]},
    }).as('getProductores')

    cy.intercept('POST', '**/api/predios', {
      statusCode: 200,
      body: { data: { id: 99, nombre_rancho: 'Rancho Nuevo' }, message: 'Predio registrado con éxito' },
    }).as('storePredio')

    cy.visit('/#/predios/nuevo')
    cy.wait('@getProductores', { timeout: 10000 })
    cy.get('input').first().type('Rancho Nuevo')
    cy.get('input[placeholder*="180104330002"]').type('UPP-001')
    cy.get('input[placeholder*="San Juan Corapan"]').type('Tepic')
    cy.get('select').first().select('1')
    cy.get('button[type="submit"]').contains('Guardar Predio').click()
    cy.wait('@storePredio', { timeout: 10000 })
    cy.location('hash', { timeout: 5000 }).should('include', '/predios')
  })

  it('valida campos obligatorios', () => {
    cy.intercept('GET', '**/api/productores', {
      statusCode: 200,
      body: { data: [
        { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia' },
      ]},
    }).as('getProductores')

    cy.visit('/#/predios/nuevo')
    cy.wait('@getProductores', { timeout: 10000 })
    cy.get('button[type="submit"]').contains('Guardar Predio').click()
    cy.location('hash').should('include', '/predios/nuevo')
  })

  it('abre modal y crea productor desde ahi', () => {
    cy.intercept('GET', '**/api/productores', {
      statusCode: 200,
      body: { data: [] },
    }).as('getProductores')

    cy.intercept('POST', '**/api/productores', {
      statusCode: 200,
      body: { data: { id: 99, nombre: 'Nuevo', apellido_paterno: 'Productor' } },
    }).as('storeProductor')

    cy.visit('/#/predios/nuevo')
    cy.wait('@getProductores', { timeout: 10000 })
    cy.contains('Nuevo Productor').click()
    cy.get('.modal-body input').first().type('Nuevo')
    cy.get('.modal-body input[placeholder*="Pérez"]').type('Productor')
    cy.get('.modal-body input[placeholder*="Gómez"]').type('Test')
    cy.contains('Guardar y Seleccionar').click()
    cy.wait('@storeProductor', { timeout: 10000 })
    cy.location('hash').should('include', '/predios/nuevo')
  })
})
