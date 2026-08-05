import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import MedicoDetailView from '../../src/views/MedicoDetailView.vue'
import userAdmin from '../fixtures/user-admin.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/medicos/:id', name: 'MedicoDetail', component: MedicoDetailView },
      { path: '/medicos/editar/:id', component: EmptyView },
      { path: '/medicos/asignar/:id', component: EmptyView },
      { path: '/medicos', component: EmptyView },
      { path: '/productores/:id', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
}

const fakeMedico = {
  id: 2,
  name: 'Dr. Juan Perez',
  email: 'juan@sigdip.com',
  zona: 'A',
  actividad: 'Buffer',
  created_at: '01/01/2025',
}

const fakeAsignados = {
  success: true,
  medico: { id: 2, name: 'Dr. Juan Perez', email: 'juan@sigdip.com' },
  asignados: [
    { id: 1, nombre: 'Maria', apellido_paterno: 'Garcia', apellido_materno: 'Lopez', curp: 'GALM800101HDFRRN01', upp: 'UPP-001', predios_count: 2 },
    { id: 2, nombre: 'Pedro', apellido_paterno: 'Sanchez', apellido_materno: 'Ruiz', curp: 'SARP750505HDFRRD02', upp: 'UPP-002', predios_count: 1 },
  ],
  disponibles: [],
}

describe('MedicoDetailView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
  })

  it('muestra estado de carga', () => {
    cy.intercept('GET', '**/api/medicos/2', {
      statusCode: 200,
      delay: 300,
      body: { data: fakeMedico },
    }).as('getMedicoSlow')

    cy.intercept('GET', '**/api/usuarios/2/productores-asignables', {
      statusCode: 200,
      delay: 300,
      body: fakeAsignados,
    }).as('getAsignablesSlow')

    const router = buildRouter()
    router.push('/medicos/2')
    mount(MedicoDetailView, { global: { plugins: [router] } })

    cy.contains('Cargando médico...', { timeout: 5000 }).should('be.visible')
    cy.wait('@getMedicoSlow')
    cy.wait('@getAsignablesSlow')
    cy.contains('Cargando médico...').should('not.exist')
  })

  it('renderiza informacion del medico', () => {
    cy.intercept('GET', '**/api/medicos/2', {
      statusCode: 200,
      body: { data: fakeMedico },
    }).as('getMedico')

    cy.intercept('GET', '**/api/usuarios/2/productores-asignables', {
      statusCode: 200,
      body: fakeAsignados,
    }).as('getAsignables')

    const router = buildRouter()
    router.push('/medicos/2')
    mount(MedicoDetailView, { global: { plugins: [router] } })

    cy.wait('@getMedico', { timeout: 10000 })
    cy.wait('@getAsignables', { timeout: 10000 })

    cy.contains('Dr. Juan Perez', { timeout: 5000 }).should('be.visible')
    cy.contains('juan@sigdip.com').should('be.visible')
    cy.contains('Zona', { timeout: 5000 }).should('be.visible')
    cy.contains('A').should('be.visible')
    cy.contains('Actividad').should('be.visible')
    cy.contains('Buffer').should('be.visible')
  })

  it('muestra lista de productores asignados con contador', () => {
    cy.intercept('GET', '**/api/medicos/2', {
      statusCode: 200,
      body: { data: fakeMedico },
    }).as('getMedico')

    cy.intercept('GET', '**/api/usuarios/2/productores-asignables', {
      statusCode: 200,
      body: fakeAsignados,
    }).as('getAsignables')

    const router = buildRouter()
    router.push('/medicos/2')
    mount(MedicoDetailView, { global: { plugins: [router] } })

    cy.wait('@getMedico', { timeout: 10000 })
    cy.wait('@getAsignables', { timeout: 10000 })

    cy.contains('2 productores', { timeout: 5000 }).should('be.visible')
    cy.contains('Maria Garcia Lopez').should('be.visible')
    cy.contains('Pedro Sanchez Ruiz').should('be.visible')
    cy.contains('UPP-001').should('be.visible')
    cy.contains('UPP-002').should('be.visible')
  })

  it('muestra empty state sin productores asignados', () => {
    cy.intercept('GET', '**/api/medicos/2', {
      statusCode: 200,
      body: { data: fakeMedico },
    }).as('getMedico')

    cy.intercept('GET', '**/api/usuarios/2/productores-asignables', {
      statusCode: 200,
      body: { success: true, medico: fakeMedico, asignados: [], disponibles: [] },
    }).as('getAsignablesVacio')

    const router = buildRouter()
    router.push('/medicos/2')
    mount(MedicoDetailView, { global: { plugins: [router] } })

    cy.wait('@getMedico', { timeout: 10000 })
    cy.wait('@getAsignablesVacio', { timeout: 10000 })

    cy.contains('Sin productores asignados', { timeout: 5000 }).should('be.visible')
  })

  it('navega a editar, asignar y volver', () => {
    cy.intercept('GET', '**/api/medicos/2', {
      statusCode: 200,
      body: { data: fakeMedico },
    }).as('getMedico')

    cy.intercept('GET', '**/api/usuarios/2/productores-asignables', {
      statusCode: 200,
      body: fakeAsignados,
    }).as('getAsignables')

    const router = buildRouter()
    router.push('/medicos/2')
    mount(MedicoDetailView, { global: { plugins: [router] } })

    cy.wait('@getMedico', { timeout: 10000 })
    cy.wait('@getAsignables', { timeout: 10000 })

    cy.contains('Editar', { timeout: 5000 }).click()
    cy.location('hash', { timeout: 5000 }).should('include', '/medicos/editar/2')

    router.push('/medicos/2')
    mount(MedicoDetailView, { global: { plugins: [router] } })
    cy.wait('@getMedico', { timeout: 10000 })
    cy.wait('@getAsignables', { timeout: 10000 })

    cy.contains('Asignar', { timeout: 5000 }).click()
    cy.location('hash', { timeout: 5000 }).should('include', '/medicos/asignar/2')

    router.push('/medicos/2')
    mount(MedicoDetailView, { global: { plugins: [router] } })
    cy.wait('@getMedico', { timeout: 10000 })
    cy.wait('@getAsignables', { timeout: 10000 })

    cy.contains('Volver', { timeout: 5000 }).click()
    cy.location('hash', { timeout: 5000 }).should('include', '/medicos')
  })

  it('elimina medico con confirmacion', () => {
    cy.intercept('GET', '**/api/medicos/2', {
      statusCode: 200,
      body: { data: fakeMedico },
    }).as('getMedico')

    cy.intercept('GET', '**/api/usuarios/2/productores-asignables', {
      statusCode: 200,
      body: fakeAsignados,
    }).as('getAsignables')

    cy.intercept('DELETE', '**/api/medicos/2', {
      statusCode: 200,
      body: { success: true },
    }).as('deleteMedico')

    const router = buildRouter()
    router.push('/medicos/2')
    mount(MedicoDetailView, { global: { plugins: [router] } })

    cy.wait('@getMedico', { timeout: 10000 })
    cy.wait('@getAsignables', { timeout: 10000 })

    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(true)
    })
    cy.contains('Eliminar', { timeout: 5000 }).click()
    cy.wait('@deleteMedico', { timeout: 10000 })
    cy.location('hash', { timeout: 5000 }).should('include', '/medicos')
  })

  it('muestra error cuando falla carga', () => {
    cy.intercept('GET', '**/api/medicos/999', {
      statusCode: 404,
      body: { message: 'Medico no encontrado' },
    }).as('getMedicoError')

    cy.intercept('GET', '**/api/usuarios/999/productores-asignables', {
      statusCode: 200,
      body: { success: true, medico: null, asignados: [], disponibles: [] },
    }).as('getAsignables')

    const router = buildRouter()
    router.push('/medicos/999')
    mount(MedicoDetailView, { global: { plugins: [router] } })

    cy.wait('@getMedicoError', { timeout: 10000 })
    cy.contains('no encontrado', { timeout: 5000 }).should('be.visible')
  })
})
