import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import MedicoEditView from '../../src/views/MedicoEditView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/medicos/editar/:id', name: 'EditarMedico', component: MedicoEditView },
      { path: '/medicos', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
}

const fakeMedico = { id: 1, name: 'Dr. Juan Perez', email: 'juan.perez@sigdip.com' }

describe('MedicoEditView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
  })

  describe('como Administrador', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userAdmin })
      cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    })

    it('muestra spinner mientras carga datos del medico', () => {
      cy.intercept('GET', '**/api/medicos/1', {
        statusCode: 200,
        delay: 300,
        body: { data: fakeMedico },
      }).as('getMedicoSlow')

      const router = buildRouter()
      router.push('/medicos/editar/1')
      mount(MedicoEditView, { global: { plugins: [router] } })

      cy.get('.spinner-border', { timeout: 5000 }).should('be.visible')
      cy.contains('Cargando datos del médico...').should('be.visible')
      cy.wait('@getMedicoSlow')
      cy.get('.spinner-border').should('not.exist')
    })

    it('renderiza formulario con datos del medico', () => {
      cy.intercept('GET', '**/api/medicos/1', {
        statusCode: 200,
        body: { data: fakeMedico },
      }).as('getMedico')

      const router = buildRouter()
      router.push('/medicos/editar/1')
      mount(MedicoEditView, { global: { plugins: [router] } })

      cy.wait('@getMedico', { timeout: 10000 })
      cy.get('input[placeholder*="Dr. Juan Pérez"]', { timeout: 5000 }).should('have.value', 'Dr. Juan Perez')
      cy.get('input[placeholder*="juan.perez@sigdip.com"]').should('have.value', 'juan.perez@sigdip.com')
      cy.contains('Actualizar Médico').should('be.visible')
    })

    it('valida contrasenas no coinciden', () => {
      cy.intercept('GET', '**/api/medicos/1', {
        statusCode: 200,
        body: { data: fakeMedico },
      }).as('getMedico')

      const router = buildRouter()
      router.push('/medicos/editar/1')
      mount(MedicoEditView, { global: { plugins: [router] } })

      cy.wait('@getMedico', { timeout: 10000 })
      cy.get('input[placeholder*="Mínimo 8 caracteres"]').type('password123')
      cy.get('input[placeholder*="Repetir contraseña"]').type('different456')
      cy.get('form').submit()

      cy.contains('Las contraseñas no coinciden', { timeout: 5000 }).should('be.visible')
    })

    it('valida contrasena menor a 8 caracteres', () => {
      cy.intercept('GET', '**/api/medicos/1', {
        statusCode: 200,
        body: { data: fakeMedico },
      }).as('getMedico')

      const router = buildRouter()
      router.push('/medicos/editar/1')
      mount(MedicoEditView, { global: { plugins: [router] } })

      cy.wait('@getMedico', { timeout: 10000 })
      cy.get('input[placeholder*="Mínimo 8 caracteres"]').type('1234567')
      cy.get('input[placeholder*="Repetir contraseña"]').type('1234567')
      cy.get('form').submit()

      cy.contains('al menos 8 caracteres', { timeout: 5000 }).should('be.visible')
    })

    it('guarda cambios exitosamente y redirige', () => {
      cy.intercept('GET', '**/api/medicos/1', {
        statusCode: 200,
        body: { data: fakeMedico },
      }).as('getMedico')

      cy.intercept('PUT', '**/api/medicos/1', {
        statusCode: 200,
        body: { success: true, message: 'Actualizado correctamente' },
      }).as('updateMedico')

      const router = buildRouter()
      router.push('/medicos/editar/1')
      mount(MedicoEditView, { global: { plugins: [router] } })

      cy.wait('@getMedico', { timeout: 10000 })
      cy.get('input[placeholder*="Dr. Juan Pérez"]').clear().type('Dr. Editado')
      cy.get('form').submit()

      cy.wait('@updateMedico', { timeout: 10000 })
      cy.contains('Médico actualizado correctamente', { timeout: 5000 }).should('be.visible')
      cy.location('hash', { timeout: 5000 }).should('include', '/medicos')
    })

    it('muestra error cuando falla carga de datos', () => {
      cy.intercept('GET', '**/api/medicos/999', {
        statusCode: 500,
        body: { message: 'Error del servidor' },
      }).as('getMedicoError')

      const router = buildRouter()
      router.push('/medicos/editar/999')
      mount(MedicoEditView, { global: { plugins: [router] } })

      cy.wait('@getMedicoError', { timeout: 10000 })
      cy.contains('Error del servidor', { timeout: 5000 }).should('be.visible')
    })

    it('muestra error cuando falla guardado', () => {
      cy.intercept('GET', '**/api/medicos/1', {
        statusCode: 200,
        body: { data: fakeMedico },
      }).as('getMedico')

      cy.intercept('PUT', '**/api/medicos/1', {
        statusCode: 500,
        body: { message: 'Error al guardar' },
      }).as('updateMedicoError')

      const router = buildRouter()
      router.push('/medicos/editar/1')
      mount(MedicoEditView, { global: { plugins: [router] } })

      cy.wait('@getMedico', { timeout: 10000 })
      cy.get('input[placeholder*="Dr. Juan Pérez"]').clear().type('Dr. Fail')
      cy.get('form').submit()

      cy.wait('@updateMedicoError', { timeout: 10000 })
      cy.contains('Error al guardar', { timeout: 5000 }).should('be.visible')
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
      router.push('/medicos/editar/1')
      mount(MedicoEditView, { global: { plugins: [router] } })

      cy.get('@alertStub', { timeout: 5000 }).should('have.been.calledWithMatch', /Acceso restringido/i)
      cy.location('hash', { timeout: 5000 }).should('include', '/dashboard')
    })
  })
})
