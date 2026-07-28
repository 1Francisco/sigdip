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
    cy.location('hash', { timeout: 10000 }).should('eq', '#/dashboard')
  })

  it('valida que se seleccione un predio antes de guardar', () => {
    cy.visit('/#/inspeccion')
    cy.contains('Borrador').click({ force: true })
    cy.location('hash').should('eq', '#/inspeccion')
  })

  it('finaliza dictamen completo con fechas pasadas y animales', () => {
    // Interceptar APIs para evitar llamadas reales al backend
    cy.intercept('GET', '**/api/inspecciones?folio=*', { statusCode: 200, body: { success: true, data: [] } })
    cy.intercept('POST', '**/api/sync/inspecciones', { statusCode: 200, body: { procesados: [] } })

    cy.visit('/#/inspeccion')
    cy.get('select').first().select('1', { force: true })

    // Section IV: agregar animales sin aretes vacíos
    cy.contains('IV: RESULTADOS INDIVIDUALES').click()
    cy.contains('Añadir Animal').click()
    cy.get('.table-responsive tbody tr').should('have.length.at.least', 1)
    cy.get('.table-responsive tbody tr').eq(0).find('input').first().type('MX-E2E-001', { force: true })
    cy.get('input[placeholder*="Registrar arete manualmente"]').type('MX-E2E-002', { force: true })
    cy.contains('button', '+').click({ force: true })
    cy.get('.table-responsive tbody tr').should('have.length.at.least', 2)

    // Section III: establecer fechas pasadas para activar modo lectura
    cy.contains('III: DATOS DE LA PRUEBA').click({ force: true })
    cy.get('input[type="date"]').eq(1).invoke('val', '2026-06-10').trigger('input', { force: true })
    cy.get('input[type="time"]').first().invoke('val', '08:00').trigger('input', { force: true })

    // Section IV: en modo lectura, asignar resultados a ambos animales
    cy.contains('IV: RESULTADOS INDIVIDUALES').click({ force: true })
    cy.get('.table-responsive tbody tr').should('have.length.at.least', 2)
    cy.get('.table-responsive tbody tr').eq(0).find('select').select('Negativo', { force: true })
    cy.get('.table-responsive tbody tr').eq(1).find('select').select('Negativo', { force: true })

    // Stub confirm para el warning de censo
    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(true)
    })

    cy.contains('button', /finalizar/i).first().click({ force: true })
    cy.location('hash', { timeout: 15000 }).should('eq', '#/dashboard')
  })

  it('flujo completo scan batch + formulario + borrador', () => {
    cy.visit('/#/scan')
    cy.get('.form-input', { timeout: 5000 }).type('MX-SCAN-001')
    cy.contains('Agregar Animal').click()
    cy.get('.form-input').type('MX-SCAN-002')
    cy.contains('Agregar Animal').click()
    cy.contains('MX-SCAN-001').should('be.visible')
    cy.contains('MX-SCAN-002').should('be.visible')

    cy.contains('Continuar al Dictamen').click()
    cy.location('hash', { timeout: 5000 }).should('include', '/inspeccion')

    // Verificar que los animales del scan están cargados en el formulario
    cy.get('.table-responsive tbody tr').should('have.length.at.least', 2)
    cy.get('.table-responsive tbody tr').eq(0).find('input').should('have.value', 'MX-SCAN-001')
    cy.get('.table-responsive tbody tr').eq(1).find('input').should('have.value', 'MX-SCAN-002')

    // Seleccionar predio y guardar borrador
    cy.get('select').first().select('1', { force: true })
    cy.contains('Borrador').click({ force: true })
    cy.location('hash', { timeout: 10000 }).should('eq', '#/dashboard')
  })
})
