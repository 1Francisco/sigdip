import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import DescargasView from '../../src/views/DescargasView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/descargas', name: 'Descargas', component: DescargasView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
      { path: '/productores', component: EmptyView },
      { path: '/predios', component: EmptyView },
      { path: '/inspecciones', component: EmptyView },
      { path: '/visitas', component: EmptyView },
      { path: '/medicos', component: EmptyView },
      { path: '/sync', component: EmptyView },
      { path: '/scan', component: EmptyView },
      { path: '/inspeccion', component: EmptyView },
    ],
  })
}

const mockFiles = [
  { name: 'dictamenes_pecuarios_5-6-2026.xlsx', size: 245760, mtime: Date.now() - 3600000, isNative: false },
  { name: 'dictamen_DP-2026-001_5-6-2026.pdf', size: 189432, mtime: Date.now() - 7200000, isNative: false },
]

describe('DescargasView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
    cy.intercept('POST', '**/api/logout', { statusCode: 200, body: { message: 'ok' } }).as('logout')
  })

  describe('como Administrador', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userAdmin })
    })

    it('renderiza titulo y estadisticas', () => {
      cy.window().then((win) => {
        win.localStorage.setItem('local_downloads_mock', JSON.stringify(mockFiles))
      })

      const router = buildRouter()
      router.push('/descargas')
      mount(DescargasView, { global: { plugins: [router] } })

      cy.contains('Mis Descargas', { timeout: 5000 }).should('be.visible')
      cy.contains('Total').should('be.visible')
      cy.contains('PDFs').should('be.visible')
      cy.contains('Excel').should('be.visible')
    })

    it('muestra stats correctos desde localStorage', () => {
      cy.window().then((win) => {
        win.localStorage.setItem('local_downloads_mock', JSON.stringify(mockFiles))
      })

      const router = buildRouter()
      router.push('/descargas')
      mount(DescargasView, { global: { plugins: [router] } })

      cy.contains('2', { timeout: 5000 }).should('be.visible')
      cy.contains('1', { timeout: 5000 }).should('be.visible')
    })

    it('filtra archivos por busqueda', () => {
      cy.window().then((win) => {
        win.localStorage.setItem('local_downloads_mock', JSON.stringify(mockFiles))
      })

      const router = buildRouter()
      router.push('/descargas')
      mount(DescargasView, { global: { plugins: [router] } })

      cy.contains('dictamen_DP-2026-001', { timeout: 5000 }).should('be.visible')
      cy.get('input[placeholder*="Buscar archivo"]').type('dictamenes_pecuarios')
      cy.contains('dictamen_DP-2026-001').should('not.exist')
      cy.contains('dictamenes_pecuarios').should('be.visible')
    })

    it('muestra empty state cuando no hay archivos', () => {
      cy.window().then((win) => {
        win.localStorage.setItem('local_downloads_mock', JSON.stringify([]))
      })

      const router = buildRouter()
      router.push('/descargas')
      mount(DescargasView, { global: { plugins: [router] } })

      cy.contains('No hay descargas', { timeout: 5000 }).should('be.visible')
      cy.contains('Ir a Dictámenes').should('be.visible')
    })

    it('abre y cierra preview modal de PDF', () => {
      cy.window().then((win) => {
        win.localStorage.setItem('local_downloads_mock', JSON.stringify(mockFiles))
      })

      const router = buildRouter()
      router.push('/descargas')
      mount(DescargasView, { global: { plugins: [router] } })

      cy.contains('dictamen_DP-2026-001', { timeout: 5000 }).should('be.visible')
      cy.contains('Ver').first().click()
      cy.get('.dl-preview-modal', { timeout: 5000 }).should('be.visible')
      cy.contains('dictamen_DP-2026-001').should('be.visible')

      cy.get('.dl-preview-btn-close').click()
      cy.get('.dl-preview-modal').should('not.exist')
    })

    it('elimina archivo con confirmacion', () => {
      cy.window().then((win) => {
        win.localStorage.setItem('local_downloads_mock', JSON.stringify(mockFiles))
        cy.stub(win, 'confirm').returns(true)
      })

      const router = buildRouter()
      router.push('/descargas')
      mount(DescargasView, { global: { plugins: [router] } })

      cy.contains('Eliminar', { timeout: 5000 }).first().click()
      cy.contains('eliminado correctamente', { timeout: 5000 }).should('be.visible')
    })

    it('cancela eliminacion cuando confirm es false', () => {
      cy.window().then((win) => {
        win.localStorage.setItem('local_downloads_mock', JSON.stringify(mockFiles))
        cy.stub(win, 'confirm').returns(false)
      })

      const router = buildRouter()
      router.push('/descargas')
      mount(DescargasView, { global: { plugins: [router] } })

      cy.contains('Eliminar', { timeout: 5000 }).first().click()
      cy.contains('eliminado correctamente').should('not.exist')
    })
  })

  describe('como Medico', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userMedico })
    })

    it('renderiza vista descargas para medico', () => {
      cy.window().then((win) => {
        win.localStorage.setItem('local_downloads_mock', JSON.stringify(mockFiles))
      })

      const router = buildRouter()
      router.push('/descargas')
      mount(DescargasView, { global: { plugins: [router] } })

      cy.contains('Mis Descargas', { timeout: 5000 }).should('be.visible')
    })
  })
})
