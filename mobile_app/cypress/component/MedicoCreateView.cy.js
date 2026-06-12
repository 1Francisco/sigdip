import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import MedicoCreateView from '../../src/views/MedicoCreateView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/medicos/nuevo', name: 'NuevoMedico', component: MedicoCreateView },
      { path: '/medicos', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
}

describe('MedicoCreateView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
  })

  describe('como Administrador', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userAdmin })
      cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    })

    it('renderiza formulario de registro', () => {
      const router = buildRouter()
      router.push('/medicos/nuevo')
      mount(MedicoCreateView, { global: { plugins: [router] } })

      cy.contains('Registrar Médico Verificador', { timeout: 5000 }).should('be.visible')
      cy.contains('Nombre Completo').should('be.visible')
      cy.contains('Correo Electrónico').should('be.visible')
      cy.contains('Contraseña').should('be.visible')
      cy.contains('Confirmar Contraseña').should('be.visible')
    })

    it('valida campos requeridos con form.submit', () => {
      const router = buildRouter()
      router.push('/medicos/nuevo')
      mount(MedicoCreateView, { global: { plugins: [router] } })

      cy.get('form').submit()
      cy.contains('Nombre Completo', { timeout: 5000 }).should('be.visible')
    })

    it('valida contrasenas no coinciden', () => {
      const router = buildRouter()
      router.push('/medicos/nuevo')
      mount(MedicoCreateView, { global: { plugins: [router] } })

      cy.get('input[placeholder*="Dr. Juan Pérez"]').type('Dr. Test')
      cy.get('input[placeholder*="juan.perez@sigdip.com"]').type('test@sigdip.com')
      cy.get('input[placeholder*="Mínimo 8 caracteres"]').first().type('password123')
      cy.get('input[placeholder*="Mínimo 8 caracteres"]').eq(1).type('different456')
      cy.get('form').submit()

      cy.contains('Las contraseñas no coinciden', { timeout: 5000 }).should('be.visible')
    })

    it('guarda medico exitosamente y redirige', () => {
      cy.intercept('POST', '**/api/medicos', {
        statusCode: 200,
        body: { success: true, message: 'Registrado correctamente' },
      }).as('storeMedico')

      const router = buildRouter()
      router.push('/medicos/nuevo')
      mount(MedicoCreateView, { global: { plugins: [router] } })

      cy.get('input[placeholder*="Dr. Juan Pérez"]').type('Dr. Nuevo Medico')
      cy.get('input[placeholder*="juan.perez@sigdip.com"]').type('nuevo@sigdip.com')
      cy.get('input[placeholder*="Mínimo 8 caracteres"]').first().type('password123')
      cy.get('input[placeholder*="Mínimo 8 caracteres"]').eq(1).type('password123')
      cy.get('form').submit()

      cy.wait('@storeMedico', { timeout: 10000 })
      cy.contains('Médico Verificador registrado correctamente', { timeout: 5000 }).should('be.visible')
      cy.location('hash', { timeout: 5000 }).should('include', '/medicos')
    })

    it('muestra error cuando falla API', () => {
      cy.intercept('POST', '**/api/medicos', {
        statusCode: 500,
        body: { message: 'Error del servidor' },
      }).as('storeMedicoError')

      const router = buildRouter()
      router.push('/medicos/nuevo')
      mount(MedicoCreateView, { global: { plugins: [router] } })

      cy.get('input[placeholder*="Dr. Juan Pérez"]').type('Dr. Fail')
      cy.get('input[placeholder*="juan.perez@sigdip.com"]').type('fail@sigdip.com')
      cy.get('input[placeholder*="Mínimo 8 caracteres"]').first().type('password123')
      cy.get('input[placeholder*="Mínimo 8 caracteres"]').eq(1).type('password123')
      cy.get('form').submit()

      cy.wait('@storeMedicoError', { timeout: 10000 })
      cy.contains('Error del servidor', { timeout: 5000 }).should('be.visible')
    })

    it('navega a /medicos al cancelar', () => {
      const router = buildRouter()
      router.push('/medicos/nuevo')
      mount(MedicoCreateView, { global: { plugins: [router] } })

      cy.contains('Cancelar').click()
      cy.location('hash', { timeout: 5000 }).should('include', '/medicos')
    })
  })

  describe('como Medico (sin acceso)', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userMedico })
    })

    it('redirige a dashboard con alerta', () => {
      cy.window().then((win) => {
        cy.stub(win, 'alert').as('alertStub')
      })

      const router = buildRouter()
      router.push('/medicos/nuevo')
      mount(MedicoCreateView, { global: { plugins: [router] } })

      cy.get('@alertStub').should('have.been.calledWithMatch', /Acceso restringido/i)
      cy.location('hash', { timeout: 5000 }).should('include', '/dashboard')
    })
  })
})
