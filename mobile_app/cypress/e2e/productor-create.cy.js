describe('Creacion de Productor (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('crea productor sin rancho', () => {
    cy.intercept('POST', '**/api/productores', {
      statusCode: 201,
      body: { success: true, productor: { id: 99, nombre: 'Juan', apellido_paterno: 'Perez' } },
    }).as('storeProductor')

    cy.visit('/#/productores/nuevo')
    cy.contains('Paso 1', { timeout: 5000 }).should('be.visible')

    cy.get('input[placeholder*="Pepito"]').type('Juan')
    cy.get('input[placeholder*="Tejeda"]').type('Perez')
    cy.get('input[placeholder*="Figueroa"]').type('Lopez')
    cy.get('input[maxlength="18"]').type('PELJ900101HDFLZN01')
    cy.get('input[placeholder*="57625285"]').type('UPP-12345')
    cy.get('input[type="tel"]').type('3111234567')
    cy.contains('Continuar al Paso 2').click()
    cy.contains('Paso 2', { timeout: 5000 }).should('be.visible')

    cy.contains('No tiene predio').click()
    cy.wait('@storeProductor', { timeout: 10000 })
    cy.location('hash', { timeout: 5000 }).should('include', '/productores')
  })

  it('valida CURP invalido', () => {
    cy.visit('/#/productores/nuevo')
    cy.contains('Paso 1', { timeout: 5000 }).should('be.visible')

    cy.get('input').first().type('Juan')
    cy.get('input[placeholder*="Tejeda"]').type('Perez')
    cy.get('input[maxlength="18"]').type('INVALIDO')
    cy.get('input[placeholder*="57625285"]').type('UPP-12345')
    cy.contains('Continuar al Paso 2').click()
    cy.location('hash').should('include', '/productores/nuevo')
  })
})
