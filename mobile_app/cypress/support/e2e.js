import './commands'

beforeEach(() => {
  cy.intercept('HEAD', '**/api/user', { statusCode: 200, body: {} })
    .as('checkConnectivity')
})

Cypress.on('uncaught:exception', (err) => {
  if (err.message.includes('Failed to fetch dynamically imported module')) {
    return false
  }
})
