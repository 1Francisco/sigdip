import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import LoginView from '../../src/views/LoginView.vue'

const DashboardStub = { template: '<div>Dashboard</div>' }

function createRouterInstance() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/login', name: 'Login', component: LoginView },
      { path: '/dashboard', name: 'Dashboard', component: DashboardStub },
    ],
  })
}

describe('LoginView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.window().then((win) => {
      win.navigator.__defineGetter__('onLine', () => true)
    })
  })

  it('renderiza el formulario de login', () => {
    const router = createRouterInstance()
    router.push('/login')

    mount(LoginView, {
      global: { plugins: [router] },
    })

    cy.get('.brand-logo').should('be.visible')
    cy.get('h1').should('contain', 'SIGDIP')
    cy.get('#email').should('be.visible')
    cy.get('#password').should('be.visible')
    cy.get('.btn-login').should('contain', 'Ingresar al Panel')
  })

  it('muestra error si se intenta enviar con campos vacios', () => {
    const router = createRouterInstance()
    router.push('/login')

    mount(LoginView, {
      global: { plugins: [router] },
    })

    cy.get('.btn-login').click()
    cy.get('.error-message').should('contain', 'Ingresa tu correo y contraseña')
  })

  it('navega a dashboard al loguearse exitosamente', () => {
    const router = createRouterInstance()
    router.push('/login')

    cy.intercept('POST', '**/api/login', {
      statusCode: 200,
      body: {
        token: 'test-token-123',
        user: { id: 1, name: 'Admin Test', email: 'admin@test.com', roles: ['Administrador'] },
      },
    }).as('loginRequest')

    mount(LoginView, {
      global: { plugins: [router] },
    })

    cy.get('#email').type('admin@test.com')
    cy.get('#password').type('password123')
    cy.get('.btn-login').click()

    cy.wait('@loginRequest')
    cy.get('[class*="loader"]').should('not.exist')
    cy.location('hash').should('eq', '#/dashboard')
  })

  it('muestra mensaje de error con credenciales invalidas', () => {
    const router = createRouterInstance()
    router.push('/login')

    cy.intercept('POST', '**/api/login', {
      statusCode: 401,
      body: { message: 'Credenciales incorrectas' },
    }).as('loginFailed')

    mount(LoginView, {
      global: { plugins: [router] },
    })

    cy.get('#email').type('mal@email.com')
    cy.get('#password').type('wrongpass')
    cy.get('.btn-login').click()

    cy.wait('@loginFailed')
    cy.get('.error-message').should('be.visible')
    cy.location('hash').should('not.eq', '#/dashboard')
  })

  it('toggle show/hide password', () => {
    const router = createRouterInstance()
    router.push('/login')

    mount(LoginView, {
      global: { plugins: [router] },
    })

    cy.get('#password').should('have.attr', 'type', 'password')

    cy.get('#password').parent().find('button').click()
    cy.get('#password').should('have.attr', 'type', 'text')

    cy.get('#password').parent().find('button').click()
    cy.get('#password').should('have.attr', 'type', 'password')
  })

  it('muestra badge offline cuando no hay conexion', () => {
    const router = createRouterInstance()
    router.push('/login')

    cy.window().then((win) => {
      win.navigator.__defineGetter__('onLine', () => false)
      win.dispatchEvent(new Event('offline'))
    })

    mount(LoginView, {
      global: { plugins: [router] },
    })

    cy.get('.offline-badge').should('be.visible')
    cy.get('.offline-badge').should('contain', 'Modo Offline')
  })

  it('inicia sesion offline con credenciales guardadas', () => {
    cy.window().then((win) => {
      win.localStorage.setItem('sigdip_offline_credentials', JSON.stringify({
        email: 'admin@test.com',
        hash: '5323a259d7eca7e317248d9cb94217838059973d31fc57219864f57bf3ee28bd',
        user: { id: 1, name: 'Admin Offline', roles: ['Administrador'] },
        token: 'offline-token-123',
      }))
    })

    cy.intercept('POST', '**/api/login', {
      statusCode: 0,
      body: null,
    }).as('loginOffline')

    const router = createRouterInstance()
    router.push('/login')
    mount(LoginView, { global: { plugins: [router] } })

    cy.get('input[type="email"]').type('admin@test.com')
    cy.get('input[type="password"]').type('anypassword')
    cy.get('.btn-login').click()

    cy.location('hash', { timeout: 5000 }).should('include', '/dashboard')
  })
})
