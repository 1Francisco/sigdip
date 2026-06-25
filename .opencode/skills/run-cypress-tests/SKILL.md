---
name: run-cypress-tests
description: Run Cypress component and/or E2E tests for the mobile app
---

## What it does

Runs Cypress tests in the `mobile_app/` directory — component tests, E2E tests, or both.

## Instructions

### 1. Ask the user

1. **Test type**: component, e2e, or both?
2. **Spec filter** (optional) — e.g. `medicos`, `visitas`
3. **Headless or interactive?** (default: headless)
4. **Browser** (default: electron)

### 2. Run the commands

#### Component tests (headless)
```bash
cd mobile_app && npm run cy:run:component
```

With spec filter:
```bash
cd mobile_app && npm run cy:run:component -- --spec "cypress/component/{filter}*.cy.js"
```

#### E2E tests (headless, requires dev server running)
```bash
cd mobile_app && npm run cy:run:e2e
```

With spec filter:
```bash
cd mobile_app && npm run cy:run:e2e -- --spec "cypress/e2e/{filter}*.cy.js"
```

#### Interactive mode
```bash
cd mobile_app && npm run cy:open
```

### 3. Notes

- E2E tests require the Vite dev server running at `http://localhost:5173`
- Component tests use their own dev server (auto-started by Cypress)
- Videos and screenshots are saved on failure (`cypress/videos/`, `cypress/screenshots/`)
