import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import MedicosView from '../../src/views/MedicosView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/medicos', name: 'Medicos', component: MedicosView },
      { path: '/medicos/nuevo', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
      { path: '/productores', component: EmptyView },
      { path: '/predios', component: EmptyView },
      { path: '/inspecciones', component: EmptyView },
      { path: '/visitas', component: EmptyView },
      { path: '/descargas', component: EmptyView },
      { path: '/sync', component: EmptyView },
      { path: '/scan', component: EmptyView },
      { path: '/inspeccion', component: EmptyView },
    ],
  })
}

const fakeMedicos = [
  { id: 1, name: 'Dr. Juan Perez Lopez', email: 'juan.perez@sigdip.com', created_at: '01/01/2026', productores_count: 5 },
  { id: 2, name: 'Dra. Maria Garcia Hernandez', email: 'maria.garcia@sigdip.com', created_at: '15/02/2026', productores_count: 0 },
  { id: 3, name: 'Dr. Carlos Martinez Ruiz', email: 'carlos.martinez@sigdip.com', created_at: '20/03/2026', productores_count: 12 },
]

describe('MedicosView', () => {
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

    it('renderiza titulo y lista de medicos', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: fakeMedicos },
      }).as('getMedicos')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })
      cy.contains('Médicos Verificadores', { timeout: 5000 }).should('be.visible')
      cy.contains('Dr. Juan Perez Lopez').should('be.visible')
      cy.contains('Dra. Maria Garcia Hernandez').should('be.visible')
      cy.contains('Dr. Carlos Martinez Ruiz').should('be.visible')
    })

    it('muestra spinner de carga mientras obtiene datos', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: fakeMedicos },
        delayMs: 500,
      }).as('getMedicosSlow')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.contains('Cargando médicos verificadores', { timeout: 3000 }).should('be.visible')
    })

    it('muestra error cuando falla carga de medicos', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 500,
        body: { message: 'Error' },
      }).as('getMedicosError')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicosError', { timeout: 10000 })
      cy.get('.alert.alert-danger', { timeout: 5000 }).should('be.visible')
    })

    it('muestra boton Nuevo Medico', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: fakeMedicos },
      }).as('getMedicos')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })
      cy.contains('Nuevo Médico', { timeout: 5000 }).should('be.visible')
    })

    it('muestra tabla con columnas correctas', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: fakeMedicos },
      }).as('getMedicos')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })
      cy.contains('Nombre Completo').should('be.visible')
      cy.contains('Correo de Acceso').should('be.visible')
      cy.contains('Productores').should('be.visible')
      cy.contains('Fecha Regist').should('be.visible')
      cy.contains('Acciones').should('be.visible')
    })

    it('muestra conteo de productores asignados en tabla', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: fakeMedicos },
      }).as('getMedicos')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })
      cy.contains('5').should('be.visible')
      cy.contains('12').should('be.visible')
    })

    it('muestra informacion de paginacion', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: fakeMedicos },
      }).as('getMedicos')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })
      cy.contains('Pág. 1 de 1', { timeout: 5000 }).should('be.visible')
      cy.contains('3 registros').should('be.visible')
    })

    it('navega a pagina siguiente y anterior', () => {
      const manyMedicos = Array.from({ length: 25 }, (_, i) => ({
        id: i + 1,
        name: `Dr. Test ${i + 1}`,
        email: `test${i + 1}@sigdip.com`,
        created_at: '01/01/2026',
      }))

      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: manyMedicos },
      }).as('getMedicos')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })
      cy.contains('Pág. 1 de 2', { timeout: 5000 }).should('be.visible')
      cy.contains('Mostrando 1 a 20 de 25 registros').should('be.visible')

      cy.get('.next-btn').click()
      cy.contains('Pág. 2 de 2', { timeout: 5000 }).should('be.visible')
      cy.contains('Mostrando 21 a 25 de 25 registros').should('be.visible')

      cy.get('.prev-btn').click()
      cy.contains('Pág. 1 de 2', { timeout: 5000 }).should('be.visible')
      cy.contains('Mostrando 1 a 20 de 25 registros').should('be.visible')
    })

    it('elimina medico con confirmacion', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: fakeMedicos },
      }).as('getMedicos')

      cy.intercept('DELETE', '**/api/medicos/1', {
        statusCode: 200,
        body: { message: 'Eliminado' },
      }).as('deleteMedico')

      cy.window().then((win) => {
        cy.stub(win, 'confirm').returns(true)
      })

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })
      cy.contains('Eliminar').first().click()
      cy.wait('@deleteMedico', { timeout: 10000 })
      cy.contains('eliminado del sistema', { timeout: 5000 }).should('be.visible')
    })

    it('muestra empty state sin medicos', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: [] },
      }).as('getMedicosEmpty')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicosEmpty', { timeout: 10000 })
      cy.contains('No hay médicos registrados', { timeout: 5000 }).should('be.visible')
    })

    it('muestra mensaje cuando busqueda no encuentra resultados', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: fakeMedicos },
      }).as('getMedicos')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })

      cy.window().then(() => {
        const el = document.querySelector('input[placeholder*="Buscar"]')
        if (el) {
          el.value = 'ZZZZ'
          el.dispatchEvent(new Event('input'))
        }
      })
      cy.contains('No se encontraron médicos con el criterio buscado.', { timeout: 5000 }).should('be.visible')
    })

    it('muestra badge de conectividad', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: fakeMedicos },
      }).as('getMedicos')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })
      cy.get('.connectivity-badge', { timeout: 5000 }).should('be.visible')
    })

    it('carga medicos desde cache offline cuando API falla', () => {
      cy.seedIndexedDB('catalogos', 'medicos', fakeMedicos)
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 500,
        body: { message: 'Error' },
      }).as('getMedicosError')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicosError', { timeout: 10000 })
      cy.contains('Dr. Juan Perez Lopez', { timeout: 5000 }).should('be.visible')
    })

    it('filtra medicos por busqueda de nombre', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: fakeMedicos },
      }).as('getMedicos')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })

      cy.window().then(() => {
        const el = document.querySelector('input[placeholder*="Buscar"]')
        if (el) {
          el.value = 'Juan'
          el.dispatchEvent(new Event('input'))
        }
      })
      cy.contains('Dr. Juan Perez Lopez').should('be.visible')
      cy.contains('Dra. Maria Garcia Hernandez').should('not.exist')
    })

    it('filtra medicos por busqueda de correo', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: fakeMedicos },
      }).as('getMedicos')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })

      cy.window().then(() => {
        const el = document.querySelector('input[placeholder*="Buscar"]')
        if (el) {
          el.value = 'maria.garcia'
          el.dispatchEvent(new Event('input'))
        }
      })
      cy.contains('Dra. Maria Garcia Hernandez').should('be.visible')
      cy.contains('Dr. Juan Perez Lopez').should('not.exist')
    })

    it('muestra todos los medicos cuando search se limpia', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: fakeMedicos },
      }).as('getMedicos')

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.wait('@getMedicos', { timeout: 10000 })

      cy.window().then(() => {
        const el = document.querySelector('input[placeholder*="Buscar"]')
        if (el) {
          el.value = 'ZZZZ'
          el.dispatchEvent(new Event('input'))
        }
      })
      cy.contains('Dr. Juan Perez Lopez').should('not.exist')

      cy.window().then(() => {
        const el = document.querySelector('input[placeholder*="Buscar"]')
        if (el) {
          el.value = ''
          el.dispatchEvent(new Event('input'))
        }
      })
      cy.contains('Dr. Juan Perez Lopez').should('be.visible')
    })
  })

  describe('como Medico (sin acceso)', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userMedico })
    })

    it('redirige a dashboard con alerta', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 200,
        body: { data: [] },
      }).as('getMedicos')

      cy.window().then((win) => {
        cy.stub(win, 'alert').as('alertStub')
      })

      const router = buildRouter()
      router.push('/medicos')
      mount(MedicosView, { global: { plugins: [router] } })

      cy.get('@alertStub').should('have.been.calledWithMatch', /Acceso restringido/i)
      cy.location('hash', { timeout: 5000 }).should('include', '/dashboard')
    })
  })
})
