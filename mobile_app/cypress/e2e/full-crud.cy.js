describe('Full CRUD Flow (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin E2E', email: 'admin@sigdip.com', roles: ['Administrador'] },
    })
  })

  it('navega a crear productor desde la lista', () => {
    cy.visit('/#/productores')
    cy.contains('h2', 'Productores', { timeout: 5000 }).should('be.visible')
    cy.contains('Nuevo Productor').should('be.visible')
  })

  it('navega a lista de predios', () => {
    cy.visit('/#/predios')
    cy.contains('h2', 'Predios', { timeout: 5000 }).should('be.visible')
  })

  it('navega a crear visita', () => {
    cy.seedIndexedDB('catalogos', 'predios', [
      { id: 1, nombre_rancho: 'Rancho Test', productor: { id: 1, nombre: 'P', apellido_paterno: 'T' }, localidad: 'X', municipio: 'Y' },
    ])
    cy.seedIndexedDB('catalogos', 'productores', [
      { id: 1, nombre: 'Productor', apellido_paterno: 'Test' },
    ])
    cy.seedIndexedDB('catalogos', 'medicos', [
      { id: 2, name: 'Dr. E2E', email: 'dr@test.com', roles: ['Medico_Campo'] },
    ])

    cy.visit('/#/visitas/nuevo')
    cy.contains('Nueva Visita Programada', { timeout: 5000 }).should('be.visible')
  })

  it('navega a seccion de inspecciones', () => {
    cy.interceptEmptyInspecciones()

    cy.visit('/#/inspecciones')
    cy.wait('@getInspecciones', { timeout: 10000 })
    cy.contains('Inspecciones Pecuarias', { timeout: 5000 }).should('be.visible')
  })

  it('navega todas las vistas principales como admin', () => {
    const views = [
      { path: '/#/dashboard', text: 'Resumen Administrativo' },
      { path: '/#/productores', text: 'Productores' },
      { path: '/#/predios', text: 'Predios / Ranchos' },
      { path: '/#/inspecciones', text: 'Inspecciones Pecuarias' },
      { path: '/#/visitas', text: 'Agenda de Campo' },
      { path: '/#/medicos', text: 'Médicos' },
      { path: '/#/descargas', text: 'Descargas' },
      { path: '/#/sync', text: 'Sincronización' },
    ]

    views.forEach(({ path }) => {
      cy.interceptDashboardStats()
      cy.interceptEmptyInspecciones()
      cy.interceptEmptyMedicos()

      cy.visit(path)
      cy.get('h2', { timeout: 5000 }).should('be.visible')
    })
  })
})
