describe('Creacion de Dictamen (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
    cy.seedIndexedDB('catalogos', 'predios', [
      { id: 1, nombre_rancho: 'Rancho Prueba', localidad: 'Tepic', municipio: 'Tepic', productor: { id: 1, nombre: 'Juan', apellido_paterno: 'Perez', apellido_materno: 'Lopez', telefono: '3111234567', domicilio: 'Calle 123', email: 'juan@test.com' } },
    ])
  })

  it('guarda borrador seleccionando predio', () => {
    cy.visit('/#/inspeccion')
    cy.get('select').first().select('1', { force: true })
    cy.contains('Borrador', { timeout: 5000 }).click({ force: true })
    cy.location('hash', { timeout: 5000 }).should('eq', '#/dashboard')
  })

  it('agrega un animal via quick-add y guarda borrador', () => {
    cy.visit('/#/inspeccion')
    cy.get('select').first().select('1', { force: true })
    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('Añadir Animal').click()
    cy.get('input[placeholder*="Registrar arete manualmente"]').type('MX-TEST-001', { force: true })
    cy.contains('button', '+').click({ force: true })
    cy.get('.animal-mobile-card').should('have.length.at.least', 2)
    cy.get('.animal-mobile-card').eq(1).find('input[placeholder*="SINIIGA"]').should('have.value', 'MX-TEST-001')
    cy.contains('Borrador').click({ force: true })
    cy.location('hash', { timeout: 5000 }).should('eq', '#/dashboard')
  })

  it('valida que se seleccione un predio antes de guardar', () => {
    cy.visit('/#/inspeccion')
    cy.contains('Borrador').click({ force: true })
    cy.location('hash').should('eq', '#/inspeccion')
  })
})
