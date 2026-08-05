import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import InspeccionDetailView from '../../src/views/InspeccionDetailView.vue'
import userAdmin from '../fixtures/user-admin.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter(id) {
  const router = createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/inspecciones/:id', name: 'InspeccionDetail', component: InspeccionDetailView },
      { path: '/inspecciones', component: EmptyView },
      { path: '/inspeccion/:predioId?', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
  if (id) router.push(`/inspecciones/${id}`)
  return router
}

const fakeInspeccion = {
  id: 100,
  clave_interna: 'INSP-2026-001',
  fecha: '2026-06-01',
  estado: 'completada',
  predio_id: 1,
  predio: { id: 1, nombre_rancho: 'Rancho El Paraiso', localidad: 'Villahermosa' },
  veterinario: { name: 'Dr. Juan Perez', id: 2 },
  tipo_prueba: 'PPC',
  motivo_prueba: 'Rastreo',
  funcion_zootecnica: 'Produccion de Carne',
  detalles: [],
}

describe('DIAGNOSE delete flow v3', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.setLoginState({ user: userAdmin })
  })

  it('pinpoints where the chain breaks', () => {
    const borrador = { ...fakeInspeccion, id: 101, estado: 'borrador', folio: null, clave_interna: null }

    cy.intercept('GET', '**/api/inspecciones/101', {
      statusCode: 200,
      body: { data: borrador },
    }).as('getBorrador')

    cy.intercept('DELETE', '**/api/inspecciones/999', {
      statusCode: 200,
      body: { success: true },
    }).as('deleteInspeccion999')

    const router = buildRouter(101)
    mount(InspeccionDetailView, { global: { plugins: [router] } })

    cy.wait('@getBorrador', { timeout: 10000 })

    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(true)
      const origFetch = win.fetch
      cy.stub(win, 'fetch', (input, init) => {
        Cypress.log({ name: 'DIAG', message: `fetch ${init && init.method} ${String(input)}` })
        return origFetch(input, init)
      }).as('fetchSpy')
    })

    cy.contains('Eliminar', { timeout: 5000 }).click()

    cy.window().then((win) => {
      expect(win.confirm.called, 'STEP 1: confirm() debe haberse llamado').to.be.true
      expect(win.confirm.returnValues[0], 'STEP 2: confirm() debe devolver true').to.be.true
      const alertShown = document.body.innerText.includes('No se pudo eliminar')
      expect(alertShown, 'STEP 3: no debe mostrar errorMsg').to.be.false
    })

    cy.get('@fetchSpy').then((spy) => {
      const allCalls = spy.getCalls().map((c) => `${c.args[1] && c.args[1].method} ${String(c.args[0])}`)
      const deleteCalls = spy.getCalls().filter((c) => (c.args[1] && c.args[1].method) === 'DELETE')
      expect(deleteCalls.length, `STEP 4: debe haber un fetch DELETE (todos: ${JSON.stringify(allCalls)})`).to.equal(1)
      const url = String(deleteCalls[0].args[0])
      expect(url, `STEP 5: URL DELETE recibida: ${url}`).to.contain('/api/inspecciones/101')
    })

    cy.wait('@deleteInspeccion999', { timeout: 5000 })

    cy.wait('@deleteInspeccion', { timeout: 10000 })
  })
})
