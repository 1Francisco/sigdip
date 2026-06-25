import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import AretesCensoListView from '../../src/views/AretesCensoListView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/aretes-censo', name: 'AretesCenso', component: AretesCensoListView },
      { path: '/aretes-censo/nuevo', component: EmptyView },
      { path: '/aretes-censo/editar/:id', component: EmptyView },
      { path: '/aretes-censo/:id', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
}

const fakeAretes = [
  { id: 1, numero_arete: 'MX-001-001', raza: 'Angus', sexo: 'Macho', edad_meses: 36, fecha_nacimiento: '2023-01-15', sacrificio: false, productor: { id: 1, nombre: 'Juan', apellido_paterno: 'Perez' }, predio: { id: 1, nombre_rancho: 'Rancho Norte', clave_unidad_produccion: 'CUP-001' } },
  { id: 2, numero_arete: 'MX-001-002', raza: 'Brangus', sexo: 'Hembra', edad_meses: 24, fecha_nacimiento: '2024-03-20', sacrificio: true, productor: { id: 2, nombre: 'Maria', apellido_paterno: 'Garcia' }, predio: { id: 2, nombre_rancho: 'Rancho Sur', clave_unidad_produccion: 'CUP-002' } },
]

describe('AretesCensoListView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.intercept('HEAD', '**/api/user', { statusCode: 200, body: {} }).as('getUser')
  })

  describe('como Administrador', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userAdmin })
      cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
    })

    it('renderiza titulo y lista de aretes', () => {
      cy.intercept('GET', '**/api/aretes-censo', {
        statusCode: 200,
        body: { data: fakeAretes },
      }).as('getAretes')

      const router = buildRouter()
      router.push('/aretes-censo')
      mount(AretesCensoListView, { global: { plugins: [router] } })

      cy.wait('@getAretes', { timeout: 10000 })
      cy.contains('Aretes del Censo', { timeout: 5000 }).should('be.visible')
      cy.contains('MX-001-001').should('be.visible')
      cy.contains('MX-001-002').should('be.visible')
    })

    it('muestra boton Nuevo Arete', () => {
      cy.intercept('GET', '**/api/aretes-censo', {
        statusCode: 200,
        body: { data: fakeAretes },
      }).as('getAretes')

      const router = buildRouter()
      router.push('/aretes-censo')
      mount(AretesCensoListView, { global: { plugins: [router] } })

      cy.wait('@getAretes', { timeout: 10000 })
      cy.contains('Nuevo Arete', { timeout: 5000 }).should('be.visible')
    })

    it('muestra search bar con placeholder correcto', () => {
      cy.intercept('GET', '**/api/aretes-censo', {
        statusCode: 200,
        body: { data: fakeAretes },
      }).as('getAretes')

      const router = buildRouter()
      router.push('/aretes-censo')
      mount(AretesCensoListView, { global: { plugins: [router] } })

      cy.wait('@getAretes', { timeout: 10000 })
      cy.contains('MX-001-001').should('be.visible')
      cy.contains('MX-001-002').should('be.visible')

      cy.get('input[placeholder*="Buscar"]').should('have.attr', 'placeholder', 'Buscar por número de arete, raza o productor...')
      cy.contains('2 registros').should('be.visible')
    })

    it('elimina arete con confirmacion', () => {
      cy.intercept('GET', '**/api/aretes-censo', {
        statusCode: 200,
        body: { data: fakeAretes },
      }).as('getAretes')

      cy.intercept('DELETE', '**/api/aretes-censo/1', {
        statusCode: 200,
        body: { message: 'Eliminado' },
      }).as('deleteArete')

      cy.window().then((win) => {
        cy.stub(win, 'confirm').returns(true)
      })

      const router = buildRouter()
      router.push('/aretes-censo')
      mount(AretesCensoListView, { global: { plugins: [router] } })

      cy.wait('@getAretes', { timeout: 10000 })
      cy.contains('Eliminar').first().click()
      cy.wait('@deleteArete', { timeout: 10000 })
      cy.contains('eliminado del censo', { timeout: 5000 }).should('be.visible')
    })

    it('carga desde cache offline', () => {
      cy.seedIndexedDB('catalogos', 'aretes_censo', fakeAretes)

      cy.intercept('GET', '**/api/aretes-censo', {
        statusCode: 200,
        body: { data: fakeAretes },
      }).as('getAretes')

      const router = buildRouter()
      router.push('/aretes-censo')
      mount(AretesCensoListView, { global: { plugins: [router] } })

      cy.wait('@getAretes', { timeout: 10000 })
      cy.contains('MX-001-001', { timeout: 5000 }).should('be.visible')
    })

    it('navega entre paginas de aretes', () => {
      const manyAretes = Array.from({ length: 25 }, (_, i) => ({
        id: i + 1,
        numero_arete: `MX-${String(i + 1).padStart(3, '0')}`,
        raza: 'Angus',
        sexo: i % 2 === 0 ? 'Macho' : 'Hembra',
        edad_meses: (i % 48) + 1,
        fecha_nacimiento: '2023-01-15',
        sacrificio: false,
        productor: { id: 1, nombre: 'Juan', apellido_paterno: 'Perez' },
        predio: { id: 1, nombre_rancho: 'Rancho Norte', clave_unidad_produccion: 'CUP-001' },
      }))

      cy.intercept('GET', '**/api/aretes-censo', {
        statusCode: 200,
        body: { data: manyAretes },
      }).as('getAretes')

      const router = buildRouter()
      router.push('/aretes-censo')
      mount(AretesCensoListView, { global: { plugins: [router] } })

      cy.wait('@getAretes', { timeout: 10000 })
      cy.contains('Pág. 1 de 2', { timeout: 5000 }).should('be.visible')

      cy.get('.next-btn').click({ force: true })
      cy.contains('Pág. 2 de 2', { timeout: 5000 }).should('be.visible')

      cy.get('.prev-btn').click({ force: true })
      cy.contains('Pág. 1 de 2', { timeout: 5000 }).should('be.visible')
    })

    it('muestra empty state sin aretes', () => {
      cy.intercept('GET', '**/api/aretes-censo', {
        statusCode: 200,
        body: { data: [] },
      }).as('getAretesEmpty')

      const router = buildRouter()
      router.push('/aretes-censo')
      mount(AretesCensoListView, { global: { plugins: [router] } })

      cy.wait('@getAretesEmpty', { timeout: 10000 })
      cy.contains('No hay aretes registrados', { timeout: 5000 }).should('be.visible')
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
      router.push('/aretes-censo')
      mount(AretesCensoListView, { global: { plugins: [router] } })

      cy.get('@alertStub').should('have.been.calledWithMatch', /Solo administradores/i)
      cy.location('hash', { timeout: 5000 }).should('include', '/dashboard')
    })
  })
})
