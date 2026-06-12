import './commands'

Cypress.on('uncaught:exception', (err) => {
  if (err.message.includes('Failed to fetch dynamically imported module')) {
    return false
  }
})
