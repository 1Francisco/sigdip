---
name: create-cypress-test
description: Generate Cypress component + E2E tests for a mobile entity view, following SIGDIP conventions
---

## What it does

Generates Cypress test files for an entity's mobile views:
- `mobile_app/cypress/component/{Entity}ListView.cy.js`
- `mobile_app/cypress/component/{Entity}CreateView.cy.js`
- `mobile_app/cypress/component/{Entity}EditView.cy.js`
- `mobile_app/cypress/component/{Entity}DetailView.cy.js`
- `mobile_app/cypress/e2e/{entity}-flow.cy.js`

## Instructions

### 1. Ask for details

1. **Entity name** (PascalCase, e.g. `Categoria`)
2. **Route prefix** (lowercase plural, e.g. `categorias`)
3. **View files to test** — list, create, edit, detail?
4. **Fixtures needed** — sample data for the entity

### 2. Component Test Pattern (use for each view)

File: `mobile_app/cypress/component/{Entity}{ViewType}View.cy.js`

```js
import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import TargetView from '../../src/views/{entity}/{Entity}{ViewType}View.vue'
import userAdmin from '../fixtures/user-admin.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/{entity}', name: '{Entities}', component: TargetView },
      { path: '/{entity}/nuevo', component: EmptyView },
      { path: '/{entity}/editar/:id', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
}

describe('{Entity} {ViewType}', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })
  })

  it('renderiza correctamente', () => {
    // Mock API
    cy.intercept('GET', '**/api/{entity}', {
      statusCode: 200,
      body: { data: [
        // sample data
      ] },
    }).as('get{Entities}')

    const router = buildRouter()
    router.push('/{entity}')
    mount(TargetView, { global: { plugins: [router] } })

    cy.wait('@get{Entities}', { timeout: 10000 })
    cy.contains('{Spanish title}', { timeout: 5000 }).should('be.visible')
  })

  // For form views:
  it('envia formulario correctamente', () => {
    cy.intercept('POST', '**/api/{entity}', {
      statusCode: 201,
      body: { success: true, data: { id: 1 } },
    }).as('create{Entity}')

    const router = buildRouter()
    router.push('/{entity}/nuevo')
    mount(TargetView, { global: { plugins: [router] } })

    // Fill form fields
    // cy.get('input').first().type('value')
    // cy.get('select').first().select('1')

    cy.get('button[type="submit"]').click()
    cy.wait('@create{Entity}', { timeout: 10000 })
    cy.location('hash', { timeout: 5000 }).should('include', '/{entity}')
  })
})
```

### 3. E2E Test Pattern

File: `mobile_app/cypress/e2e/{entity}-flow.cy.js`

```js
describe('Flujo de {Entity} (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({ user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] } })
  })

  it('navega a la lista', () => {
    cy.intercept('GET', '**/api/{entity}', { statusCode: 200, body: { data: [] } }).as('get{Entities}')
    cy.visit('/#/{entity}')
    cy.wait('@get{Entities}', { timeout: 10000 })
    cy.location('hash').should('eq', '#/{entity}')
  })

  it('crea un registro exitosamente', () => {
    cy.intercept('POST', '**/api/{entity}', {
      statusCode: 201,
      body: { success: true, message: '{Entity} creado correctamente.' },
    }).as('create{Entity}')

    cy.visit('/#/{entity}/nuevo')
    // Fill form
    cy.get('button[type="submit"]').click()
    cy.wait('@create{Entity}', { timeout: 10000 })
    cy.location('hash', { timeout: 5000 }).should('include', '/{entity}')
  })
})
```

### 4. Verify

Run `cd mobile_app && npm run cy:run:component -- --spec "cypress/component/{Entity}*.cy.js"` to verify.
