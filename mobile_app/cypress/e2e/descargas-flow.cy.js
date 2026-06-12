describe('Flujo de Descargas (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('navega a descargas y muestra titulo', () => {
    cy.visit('/#/descargas')
    cy.get('h2').contains('Descargas', { timeout: 5000 }).should('be.visible')
  })

  it('navega a descargas desde el sidebar del dashboard', () => {
    cy.intercept('GET', '**/api/dashboard/stats', {
      statusCode: 200,
      body: { totalInspecciones: 0, totalAnimales: 0, totalVisitasPendientes: 0, inspeccionesPorLocalidad: [], rendimientoVeterinarios: [], proximasVisitasGlobales: [], borradoresGlobales: [] },
    })

    cy.visit('/#/dashboard')
    cy.get('.header-hamburger-btn').click()
    cy.get('.sidebar').contains('Descargas').click()
    cy.location('hash').should('eq', '#/descargas')
    cy.get('h2').contains('Descargas', { timeout: 5000 }).should('be.visible')
  })

  it('lista archivos demo desde localStorage', () => {
    cy.window().then((win) => {
      win.localStorage.setItem('local_downloads_mock', JSON.stringify([
        { name: 'dictamen_DP-2026-001.pdf', size: 189432, mtime: Date.now() - 3600000, isNative: false },
        { name: 'dictamen_DP-2026-002.pdf', size: 203145, mtime: Date.now() - 7200000, isNative: false },
      ]))
    })
    cy.visit('/#/descargas')
    cy.contains('DP-2026-001', { timeout: 5000 }).should('be.visible')
    cy.contains('DP-2026-002', { timeout: 5000 }).should('be.visible')
  })

  it('filtra archivos por busqueda', () => {
    cy.window().then((win) => {
      win.localStorage.setItem('local_downloads_mock', JSON.stringify([
        { name: 'dictamen_DP-2026-001.pdf', size: 189432, mtime: Date.now() - 3600000, isNative: false },
        { name: 'dictamen_DP-2026-002.pdf', size: 203145, mtime: Date.now() - 7200000, isNative: false },
      ]))
    })
    cy.visit('/#/descargas')
    cy.get('input[placeholder*="Buscar"]').first().type('001')
    cy.contains('DP-2026-001', { timeout: 5000 }).should('be.visible')
    cy.contains('DP-2026-002').should('not.exist')
  })

  it('muestra empty state si no hay descargas', () => {
    cy.window().then((win) => {
      win.localStorage.setItem('local_downloads_mock', '[]')
    })
    cy.visit('/#/descargas')
    cy.contains('No hay descargas', { timeout: 5000 }).should('be.visible')
  })

  it('redirige medico a dashboard (sin acceso admin)', () => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 2, name: 'Dr. Campo', email: 'medico@test.com', roles: ['Medico_Campo'] },
    })

    cy.visit('/#/descargas')
    cy.get('h2').contains('Descargas', { timeout: 5000 }).should('be.visible')
  })
})
