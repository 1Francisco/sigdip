import './commands'

beforeEach(() => {
  cy.intercept('HEAD', '**/api/user', {
    statusCode: 200,
    headers: {
      'access-control-allow-origin': '*',
      'access-control-allow-methods': 'HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS',
      'access-control-allow-headers': '*',
      'access-control-expose-headers': '*'
    },
    body: {}
  }).as('checkConnectivity')

  cy.intercept('POST', '**/api/logout', {
    statusCode: 200,
    body: {}
  }).as('logout')
})

Cypress.on('uncaught:exception', (err) => {
  if (err.message.includes('Failed to fetch dynamically imported module')) {
    return false
  }
})
