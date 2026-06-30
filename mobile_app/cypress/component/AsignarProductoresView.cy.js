import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import AsignarProductoresView from '../../src/views/AsignarProductoresView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter(id) {
  const router = createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/medicos/asignar/:id', component: AsignarProductoresView },
      { path: '/medicos', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
  if (id) router.push(`/medicos/asignar/${id}`)
  return router
}

const fakeAsignablesResponse = {
  success: true,
  medico: { id: 1, name: 'Dr. Juan Perez', email: 'medico@sigdip.com' },
  asignados: [
    { id: 1, nombre: 'Carlos', apellido_paterno: 'Lopez', apellido_materno: 'Mendoza', curp: 'CALO850101HPLRRN01', upp: 'UPP-001', predios_count: 2 },
    { id: 2, nombre: 'Maria', apellido_paterno: 'Garcia', apellido_materno: 'Ruiz', curp: 'MAGR900101HPLRRN02', upp: 'UPP-002', predios_count: 1 },
  ],
  disponibles: [
    { id: 3, nombre: 'Pedro', apellido_paterno: 'Ramirez', apellido_materno: 'Luna', curp: 'PERL750101HPLRRN03', upp: 'UPP-003', predios_count: 0 },
    { id: 4, nombre: 'Ana', apellido_paterno: 'Martinez', apellido_materno: 'Cruz', curp: 'ANAM800101HPLRRN04', upp: 'UPP-004', predios_count: 3 },
    { id: 5, nombre: 'Luis', apellido_paterno: 'Hernandez', apellido_materno: 'Diaz', curp: 'LUHD650101HPLRRN05', upp: 'UPP-005', predios_count: 1 },
  ],
}

describe('AsignarProductoresView', () => {
  function mountView(id = 1) {
    const router = buildRouter(id)
    mount(AsignarProductoresView, { global: { plugins: [router] } })
  }

  function setupDefaultIntercepts() {
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    cy.intercept('GET', 'http://192.168.1.91:8000/api/usuarios/1/productores-asignables', {
      statusCode: 200,
      body: fakeAsignablesResponse,
    }).as('getAsignables')
  }

  function setupAdminLogin() {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })
    cy.window().then((win) => {
      cy.stub(win, 'alert').returns(undefined)
      cy.stub(win, 'confirm').returns(true)
    })
  }

  it('renderiza los dos paneles con productores asignados y disponibles', () => {
    setupAdminLogin()
    setupDefaultIntercepts()
    mountView()
    cy.contains('Asignados Actualmente', { timeout: 15000 }).should('be.visible')
    cy.contains('Productores Disponibles').should('be.visible')
    cy.contains('Carlos Lopez Mendoza').should('be.visible')
    cy.contains('Maria Garcia Ruiz').should('be.visible')
    cy.contains('Pedro Ramirez Luna').should('be.visible')
    cy.contains('Ana Martinez Cruz').should('be.visible')
    cy.contains('Guardar Asignación').should('be.visible')
    cy.contains('Cancelar').should('be.visible')
  })

  it('redirige a medicos si el usuario no es Admin', () => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userMedico })
    cy.window().then((win) => {
      cy.stub(win, 'alert').returns(undefined)
    })
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    const router = buildRouter(1)
    mount(AsignarProductoresView, { global: { plugins: [router] } })
    cy.location('hash', { timeout: 3000 }).should('include', '/medicos')
  })

  it('muestra mensaje de error si falla la API', () => {
    setupAdminLogin()
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    cy.intercept('GET', 'http://192.168.1.91:8000/api/usuarios/1/productores-asignables', {
      statusCode: 500,
      body: { message: 'Server error' },
    }).as('getAsignablesFail')
    mountView()
    cy.contains('Error de conexión', { timeout: 5000 }).should('be.visible')
  })

  it('filtra disponibles por busqueda', () => {
    setupAdminLogin()
    setupDefaultIntercepts()
    mountView()
    cy.contains('Pedro Ramirez Luna', { timeout: 15000 }).should('be.visible')
    cy.get('input[placeholder*="Filtrar por nombre"]').type('Ana')
    cy.contains('Ana Martinez Cruz').should('be.visible')
    cy.contains('Pedro Ramirez Luna').should('not.exist')
    cy.contains('Luis Hernandez Diaz').should('not.exist')
  })

  it('selecciona y deselecciona productores disponibles', () => {
    setupAdminLogin()
    setupDefaultIntercepts()
    mountView()
    cy.contains('Pedro Ramirez Luna', { timeout: 15000 }).should('be.visible')
    cy.get('.form-check-input').first().check()
    cy.get('.form-check-input').first().should('be.checked')
    cy.get('.form-check-input').first().uncheck()
    cy.get('.form-check-input').first().should('not.be.checked')
  })

  it('selecciona todos con el boton de seleccion multiple', () => {
    setupAdminLogin()
    setupDefaultIntercepts()
    mountView()
    cy.contains('Seleccionar todos', { timeout: 15000 }).should('be.visible').click()
    cy.get('.form-check-input').each(($el) => {
      cy.wrap($el).should('be.checked')
    })
    cy.contains('Deseleccionar todos').click()
    cy.get('.form-check-input').each(($el) => {
      cy.wrap($el).should('not.be.checked')
    })
  })

  it('guarda asignacion via API', () => {
    setupAdminLogin()
    setupDefaultIntercepts()
    cy.intercept('POST', '**/api/usuarios/1/asignar-productores', {
      statusCode: 200,
      body: { success: true, message: 'Se asignaron 2 productores correctamente.' },
    }).as('guardarAsignacion')

    mountView()
    cy.contains('Pedro Ramirez Luna', { timeout: 15000 }).should('be.visible')

    cy.get('.form-check-input').first().check()
    cy.get('.form-check-input').eq(1).check()

    cy.contains('Guardar Asignación').click()
    cy.wait('@guardarAsignacion', { timeout: 10000 }).then((interception) => {
      expect(interception.request.body.productor_ids).to.have.length(2)
    })
  })

  it('desasigna productor via API', () => {
    setupAdminLogin()
    setupDefaultIntercepts()
    cy.intercept('POST', '**/api/usuarios/1/desasignar-productor/1', {
      statusCode: 200,
      body: { success: true, message: 'Productor desasignado correctamente.' },
    }).as('desasignar')

    mountView()
    cy.contains('Carlos Lopez Mendoza', { timeout: 15000 }).should('be.visible')

    cy.get('button[title="Desasignar"]').first().click()
    cy.wait('@desasignar', { timeout: 10000 })
  })

  it('navega a /medicos al cancelar', () => {
    setupAdminLogin()
    setupDefaultIntercepts()
    mountView()
    cy.contains('Cancelar', { timeout: 15000 }).should('be.visible').click()
    cy.location('hash', { timeout: 5000 }).should('include', '/medicos')
  })
})
