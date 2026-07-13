describe('Modo Offline (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
  })

  it('muestra badge offline y permite llenar formulario sin conexion', () => {
    cy.visit('/#/login', {
      onBeforeLoad(win) {
        cy.stub(win.navigator, 'onLine').value(false)
      },
    })

    cy.get('.offline-badge', { timeout: 5000 }).should('be.visible')
    cy.contains('Modo Offline').should('be.visible')
    cy.get('#email').should('be.enabled')
    cy.get('#password').should('be.enabled')
    cy.get('.btn-login').should('be.enabled')
  })

  it('carga dashboard con datos cacheados cuando offline', () => {
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })

    cy.seedIndexedDB('catalogos', 'last_sync', new Date().toISOString())
    cy.seedIndexedDB('catalogos', 'dashboard_data', { adminStats: { totalInspecciones: 42, totalAnimales: 500, totalVisitasPendientes: 5, inspeccionesPorLocalidad: [], rendimientoVeterinarios: [], proximasVisitasGlobales: [], borradoresGlobales: [] } })
    cy.seedIndexedDB('inspecciones_pendientes', 'pending', [])
    cy.seedIndexedDB('visitas_pendientes', 'pending', [])

    cy.visit('/#/dashboard', {
      onBeforeLoad(win) {
        cy.stub(win.navigator, 'onLine').value(false)
      },
    })

    cy.contains('Resumen Administrativo', { timeout: 5000 }).should('be.visible')
  })

  it('permite navegar a secciones offline sin error', () => {
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })

    cy.visit('/#/dashboard', {
      onBeforeLoad(win) {
        cy.stub(win.navigator, 'onLine').value(false)
      },
    })

    cy.get('.bottom-nav-link', { timeout: 5000 }).contains('Productores').click({ force: true })
    cy.location('hash', { timeout: 5000 }).should('include', 'productores')
  })

  it('muestra indicador offline al navegar sin conexion', () => {
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })

    cy.visit('/#/dashboard', {
      onBeforeLoad(win) {
        cy.stub(win.navigator, 'onLine').value(false)
      },
    })

    cy.contains('Modo Offline', { timeout: 5000 }).should('be.visible')
    cy.contains('Mostrando datos del último guardado local', { timeout: 5000 }).should('be.visible')
  })
})
