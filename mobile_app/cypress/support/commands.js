import { mockCapacitorModules } from './mocks'

Cypress.Commands.add('mockCapacitor', () => {
  mockCapacitorModules(cy)
})

Cypress.Commands.add('setLoginState', ({ token, user }) => {
  cy.window().then((win) => {
    win.localStorage.setItem('sigdip_token', token || 'fake-token-123')
    win.localStorage.setItem('sigdip_user', JSON.stringify(user || {
      id: 1,
      name: 'Admin Test',
      email: 'admin@test.com',
      roles: ['Administrador'],
    }))
  })
})

Cypress.Commands.add('setOfflineCredentials', (creds) => {
  cy.window().then((win) => {
    win.localStorage.setItem('sigdip_offline_credentials', JSON.stringify(creds || {
      email: 'admin@test.com',
      hash: 'fakehash',
      user: { id: 1, name: 'Admin Offline', roles: ['Administrador'] },
      token: 'fake-offline-token',
    }))
  })
})

Cypress.Commands.add('seedIndexedDB', (storeName, key, data) => {
  cy.window().then(async (win) => {
    const lf = await import('localforage')
    const create = lf.createInstance || (lf.default || lf).createInstance
    const instance = create({ name: 'sigdip_mobile', storeName })
    await instance.setItem(key, data)
  })
})

Cypress.Commands.add('clearIndexedDB', () => {
  cy.window().then(async (win) => {
    const lf = await import('localforage')
    const create = lf.createInstance || (lf.default || lf).createInstance
    const stores = ['catalogos', 'inspecciones_pendientes', 'visitas_pendientes']
    for (const storeName of stores) {
      const instance = create({ name: 'sigdip_mobile', storeName })
      await instance.clear()
    }
  })
})

Cypress.Commands.add('wipeLocalStorage', () => {
  cy.window().then((win) => {
    win.localStorage.clear()
  })
})

Cypress.Commands.add('resetAppState', () => {
  cy.wipeLocalStorage()
  cy.clearIndexedDB()
  cy.window().then((win) => {
    win.sessionStorage.clear()
  })
})

Cypress.Commands.add('mockLoginApi', ({ email, password, response }) => {
  cy.intercept('POST', '**/api/login', {
    statusCode: 200,
    body: response || {
      token: 'fake-token-123',
      user: {
        id: 1,
        name: 'Admin Test',
        email: 'admin@test.com',
        roles: ['Administrador'],
      },
    },
  }).as('loginRequest')
})

Cypress.Commands.add('mockApiError', (method, url, statusCode = 500, body = {}) => {
  cy.intercept(method, url, {
    statusCode,
    body: { message: 'Error simulado', ...body },
  }).as('apiError')
})

Cypress.Commands.add('mockCatalogos', (data) => {
  cy.intercept('GET', '**/api/sync/catalogos', {
    statusCode: 200,
    body: data || {
      productores: [],
      predios: [],
      medicos: [],
      visitas: [],
    },
  }).as('getCatalogos')
})

const DASHBOARD_STATS_EMPTY = {
  totalInspecciones: 0,
  totalAnimales: 0,
  totalVisitasPendientes: 0,
  inspeccionesPorLocalidad: [],
  rendimientoVeterinarios: [],
  proximasVisitasGlobales: [],
  borradoresGlobales: [],
}

Cypress.Commands.add('interceptDashboardStats', (overrides = {}) => {
  cy.intercept('GET', '**/api/dashboard/stats', {
    statusCode: 200,
    body: { ...DASHBOARD_STATS_EMPTY, ...overrides },
  }).as('getDashboardStats')
})

Cypress.Commands.add('interceptEmptyInspecciones', () => {
  cy.intercept('GET', '**/api/inspecciones', { statusCode: 200, body: { data: [] } }).as('getInspecciones')
})

Cypress.Commands.add('interceptEmptyMedicos', () => {
  cy.intercept('GET', '**/api/medicos', { statusCode: 200, body: { data: [] } }).as('getMedicos')
})
