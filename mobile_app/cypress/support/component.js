import './commands'
import { mockCapacitorModules } from './mocks'
import api from '../../src/services/api.js'

beforeEach(() => {
  mockCapacitorModules(cy)
  api.invalidateConnectivityCache()
  cy.window().then((win) => {
    if (win && win.navigator) {
      try {
        delete win.navigator.onLine
      } catch (e) {}
    }
  })
})
