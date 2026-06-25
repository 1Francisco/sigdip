import './commands'
import { mockCapacitorModules } from './mocks'
import api from '../../src/services/api.js'

beforeEach(() => {
  mockCapacitorModules(cy)
  api.invalidateConnectivityCache()
  // NOTA: navigator.onLine no se puede mockear en Electron 138 (propiedad
  // no-configurable). Los tests de conectividad offline deben usar cy.intercept
  // para simular fallos de red en lugar de cambiar navigator.onLine.
  // Ver: https://github.com/cypress-io/cypress/issues/27538
})
