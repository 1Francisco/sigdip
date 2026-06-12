describe('Editar Productor (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('navega a editar productor desde la lista', () => {
    cy.intercept('GET', '**/api/predios', {
      statusCode: 200,
      body: { data: [
        { id: 1, nombre_rancho: 'Rancho Test', localidad: 'Tepic', municipio: 'Tepic', productor: { id: 1, nombre: 'Juan', apellido_paterno: 'Perez', apellido_materno: 'Lopez' } },
      ]},
    }).as('getPredios')

    cy.seedIndexedDB('catalogos', 'predios', [
      { id: 1, nombre_rancho: 'Rancho Test', localidad: 'X', municipio: 'Y', productor: { id: 1, nombre: 'Juan', apellido_paterno: 'Perez', apellido_materno: 'Lopez' } },
    ])

    cy.visit('/#/productores')
    cy.wait('@getPredios', { timeout: 10000 })
    cy.get('.producer-name-bold').should('be.visible').and('contain.text', 'Juan Perez')
    cy.get('.producer-mobile-footer button[title="Editar Productor"]').click()
    cy.location('hash', { timeout: 5000 }).should('include', '/productores/editar/')
  })

  it('carga el formulario de edicion con datos del productor', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: { data: { id: 1, nombre: 'Juan', apellido_paterno: 'Perez', apellido_materno: 'Lopez', curp: 'PELJ900101HDFLZN01', telefono: '3111234567', municipio: 'Tepic', localidad: 'Tepic', domicilio: 'Calle 123', email: 'juan@test.com' } },
    }).as('getProductor')

    cy.seedIndexedDB('catalogos', 'productores', [
      { id: 1, nombre: 'Juan', apellido_paterno: 'Perez', apellido_materno: 'Lopez' },
    ])

    cy.visit('/#/productores/editar/1')
    cy.wait('@getProductor', { timeout: 10000 })
    cy.contains('Editar Productor', { timeout: 5000 }).should('be.visible')
  })

  it('cancela la edicion y vuelve a productores', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 200,
      body: { data: { id: 1, nombre: 'Juan', apellido_paterno: 'Perez', apellido_materno: 'Lopez', curp: 'PELJ900101HDFLZN01', telefono: '3111234567', municipio: 'Tepic', localidad: 'Tepic', domicilio: 'Calle 123', email: 'juan@test.com' } },
    }).as('getProductor')

    cy.seedIndexedDB('catalogos', 'productores', [
      { id: 1, nombre: 'Juan', apellido_paterno: 'Perez', apellido_materno: 'Lopez' },
    ])

    cy.visit('/#/productores/editar/1')
    cy.wait('@getProductor', { timeout: 10000 })
    cy.contains('Cancelar').click()
    cy.location('hash', { timeout: 5000 }).should('include', '/productores')
  })

  it('carga datos desde IndexedDB cuando API falla', () => {
    cy.intercept('GET', '**/api/productores/1', {
      statusCode: 500,
      body: { message: 'Error' },
    }).as('getProductorFail')

    cy.seedIndexedDB('catalogos', 'productores', [
      { id: 1, nombre: 'Juan', apellido_paterno: 'Perez', apellido_materno: 'Lopez', curp: 'PELJ900101HDFLZN01', telefono: '3111234567', municipio: 'Tepic' },
    ])

    cy.visit('/#/productores/editar/1')
    cy.wait('@getProductorFail', { timeout: 10000 })
    cy.contains('Editar Productor', { timeout: 5000 }).should('be.visible')
  })
})
