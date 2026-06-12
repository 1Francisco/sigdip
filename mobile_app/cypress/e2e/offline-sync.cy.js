describe('Offline Sync Flow (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
  })

  it('crea visita offline y la sincroniza al volver online', () => {
    // 1. Login normal (online) para tener sesion
    cy.intercept('POST', '**/api/login', {
      statusCode: 200,
      body: {
        token: 'offline-sync-token',
        user: { id: 1, name: 'Admin Sync', email: 'admin@sigdip.com', roles: ['Administrador'] },
      },
    }).as('loginApi')

    cy.visit('/#/login')
    cy.get('#email').type('admin@sigdip.com')
    cy.get('#password').type('password123')
    cy.get('.btn-login').click()
    cy.wait('@loginApi')
    cy.location('hash', { timeout: 10000 }).should('eq', '#/dashboard')

    // 2. Volverse offline
    cy.window().then((win) => {
      cy.stub(win.navigator, 'onLine').value(false)
      win.dispatchEvent(new Event('offline'))
    })

    // 3. Navegar a crear visita (debe cargar catalogos desde cache)
    cy.seedIndexedDB('catalogos', 'predios', [
      { id: 1, nombre_rancho: 'Rancho Offline', productor: { id: 1, nombre: 'Prod', apellido_paterno: 'Off' }, localidad: 'Tepic', municipio: 'Tepic' },
    ])
    cy.seedIndexedDB('catalogos', 'productores', [
      { id: 1, nombre: 'Prod', apellido_paterno: 'Off', apellido_materno: 'Line' },
    ])
    cy.seedIndexedDB('catalogos', 'medicos', [
      { id: 2, name: 'Dr. Offline', email: 'dr@test.com', roles: ['Medico_Campo'] },
    ])

    cy.intercept('GET', '**/api/predios', {
      statusCode: 500,
      body: { message: 'Offline' },
    }).as('getPrediosFail')

    cy.intercept('GET', '**/api/productores', {
      statusCode: 500,
      body: { message: 'Offline' },
    }).as('getProductoresFail')

    cy.intercept('GET', '**/api/medicos', {
      statusCode: 500,
      body: { message: 'Offline' },
    }).as('getMedicosFail')

    cy.visit('/#/visitas/nuevo')
    cy.wait('@getPrediosFail', { timeout: 10000 })
    cy.wait('@getProductoresFail', { timeout: 10000 })
    cy.wait('@getMedicosFail', { timeout: 10000 })

    // Debe cargar desde cache offline
    cy.get('select').first().should('not.be.disabled')

    // Llenar formulario
    cy.get('select').first().select('1')
    cy.get('select').eq(1).select('1')

    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    const dateStr = tomorrow.toISOString().split('T')[0]
    cy.get('input[type="date"]').type(dateStr)

    cy.get('select').eq(2).select('2')
    cy.get('textarea').type('Creada offline para test de sincronizacion')

    // Enviar en modo offline — la app debe guardar en IndexedDB
    cy.get('button[type="submit"]').click()
    cy.contains('guardada localmente', { timeout: 5000 }).should('be.visible')

    // 4. Volverse online
    cy.window().then((win) => {
      cy.stub(win.navigator, 'onLine').value(true)
      win.dispatchEvent(new Event('online'))
    })

    // 5. Ir a SyncView y sincronizar
    cy.intercept('GET', '**/api/sync/catalogos', {
      statusCode: 200,
      body: { data: { predios: [], productores: [], medicos: [], visitas: [] } },
    }).as('downloadCatalogos')

    cy.intercept('POST', '**/api/sync/visitas', {
      statusCode: 200,
      body: { procesados: [{ codigo: 'V-OFFLINE-E2E' }] },
    }).as('syncVisitas')

    cy.visit('/#/sync')
    cy.contains('Sincronización', { timeout: 5000 }).should('be.visible')
    cy.contains('Visitas Pendientes').click()
    cy.contains('Creada offline', { timeout: 5000 }).should('be.visible')

    cy.contains('Subir Dictámenes Pendientes').click()
    cy.wait('@syncVisitas', { timeout: 10000 })
    cy.contains('Sincronización Completada', { timeout: 10000 }).should('be.visible')
  })

  it('descarga catalogos en SyncView estando online', () => {
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })

    cy.intercept('GET', '**/api/sync/catalogos', {
      statusCode: 200,
      body: {
        data: {
          predios: [{ id: 1, nombre_rancho: 'Nuevo Rancho', localidad: 'Tepic', municipio: 'Tepic', productor: { id: 1, nombre: 'Nuevo', apellido_paterno: 'Productor' } }],
          productores: [{ id: 1, nombre: 'Nuevo', apellido_paterno: 'Productor' }],
          medicos: [{ id: 2, name: 'Dr. Nuevo' }],
          visitas: [{ id: 1, codigo: 'V-NUEVA', fecha_programada: '2026-07-01', predio_id: 1 }],
        },
      },
    }).as('getCatalogos')

    cy.visit('/#/sync')
    cy.contains('Descargar Catálogos del Día', { timeout: 5000 }).click()
    cy.wait('@getCatalogos', { timeout: 10000 })
    cy.contains('Sincronización Completada', { timeout: 5000 }).should('be.visible')
  })

  it('limpia cache local desde SyncView', () => {
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })

    cy.seedIndexedDB('catalogos', 'predios', [{ id: 1, nombre_rancho: 'Temp' }])
    cy.seedIndexedDB('inspecciones_pendientes', 'lista', [{ folio: 'TEMP-INSP', fecha: '2026-06-10' }])

    cy.visit('/#/sync')
    cy.contains('Limpiar Caché Local', { timeout: 5000 }).should('be.visible')
  })
})
