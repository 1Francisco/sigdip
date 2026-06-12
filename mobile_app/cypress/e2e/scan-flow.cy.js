describe('Flujo de Scan (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('navega a scan directamente', () => {
    cy.visit('/#/scan')
    cy.get('h1').contains('Escáner SINIIGA', { timeout: 5000 }).should('be.visible')
  })

  it('permite ingreso manual de arete en modo single', () => {
    cy.window().then((win) => {
      win.sessionStorage.setItem('scan_target_index', '0')
    })
    cy.visit('/#/scan')
    cy.get('input[type="text"]').first().type('MX-123-456-789')
    cy.contains('Confirmar Arete', { timeout: 5000 }).click()
    cy.location('hash', { timeout: 5000 }).should('include', '/inspeccion')
  })

  it('permite ingreso manual de multiples aretes en modo batch', () => {
    cy.visit('/#/scan?mode=batch')
    cy.get('input[type="text"]').first().type('MX-111-222-333')
    cy.contains('Agregar Animal', { timeout: 5000 }).click()
    cy.contains('MX-111-222-333', { timeout: 5000 }).should('be.visible')

    cy.get('input[type="text"]').first().clear().type('MX-444-555-666')
    cy.contains('Agregar Animal', { timeout: 5000 }).click()
    cy.contains('MX-444-555-666', { timeout: 5000 }).should('be.visible')

    cy.contains('2 animales', { timeout: 5000 }).should('be.visible')
  })

  it('previene aretes duplicados en modo batch', () => {
    cy.visit('/#/scan?mode=batch')
    cy.window().then((win) => {
      cy.stub(win, 'alert').returns(undefined)
    })
    cy.get('input[type="text"]').first().type('MX-DUP-001')
    cy.contains('Agregar Animal').click()
    cy.contains('MX-DUP-001', { timeout: 5000 }).should('be.visible')

    cy.get('input[type="text"]').first().clear().type('MX-DUP-001')
    cy.contains('Agregar Animal').click()
    cy.window().should((win) => {
      expect(win.alert.calledWithMatch(/ya fue escaneado|duplicado/i)).to.be.true
    })
  })

  it('modo batch: navega a inspeccion con aretes', () => {
    cy.visit('/#/scan?mode=batch')
    cy.get('input[type="text"]').first().type('MX-BATCH-001')
    cy.contains('Agregar Animal').click()
    cy.get('input[type="text"]').first().clear().type('MX-BATCH-002')
    cy.contains('Agregar Animal').click()

    cy.contains('Continuar al Dictamen', { timeout: 5000 }).click()
    cy.location('hash', { timeout: 5000 }).should('include', '/inspeccion')
  })
})
