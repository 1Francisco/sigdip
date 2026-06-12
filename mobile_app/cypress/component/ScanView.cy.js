import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import ScanView from '../../src/views/ScanView.vue'
import userAdmin from '../fixtures/user-admin.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/scan', name: 'Scan', component: ScanView },
      { path: '/inspeccion', component: EmptyView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
    ],
  })
}

describe('ScanView', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({ user: userAdmin })
    cy.mockCapacitor()

    cy.window().then((win) => {
      cy.stub(win.navigator.mediaDevices, 'getUserMedia').rejects(new Error('No camera'))
    })

    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(false)
    })

    cy.window().then((win) => {
      cy.stub(win, 'alert').returns(undefined)
    })

    cy.window().then((win) => {
      win.sessionStorage.clear()
    })
  })

  it('renderiza header', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router] } })

    cy.contains('Escáner SINIIGA', { timeout: 5000 }).should('be.visible')
  })

  it('renderiza input manual', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router] } })

    cy.get('.form-input', { timeout: 5000 }).should('be.visible')
  })

  it('permite ingreso manual de arete en modo batch', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-001')
    cy.contains('Agregar Animal').click()

    cy.contains('ARETE-001').should('be.visible')
    cy.get('.form-input').should('have.value', '')
  })

  it('muestra multiple aretes agregados manualmente', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-001')
    cy.contains('Agregar Animal').click()

    cy.get('.form-input').type('ARETE-002')
    cy.contains('Agregar Animal').click()

    cy.contains('ARETE-001').should('be.visible')
    cy.contains('ARETE-002').should('be.visible')
  })

  it('previene aretes duplicados', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-001')
    cy.contains('Agregar Animal').click()

    cy.get('.form-input').type('ARETE-001')
    cy.contains('Agregar Animal').click()

    cy.get('.animal-row').should('have.length', 1)
  })

  it('elimina arete de la lista', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-001')
    cy.contains('Agregar Animal').click()
    cy.contains('ARETE-001').should('be.visible')

    cy.get('.animal-row button').click()
    cy.contains('ARETE-001').should('not.exist')
  })

  it('modo single: guarda arete en sessionStorage y navega a inspeccion', () => {
    cy.window().then((win) => {
      win.sessionStorage.setItem('scan_target_index', '0')
    })

    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-SINGLE')
    cy.contains('Confirmar Arete').click()

    cy.window().then((win) => {
      expect(win.sessionStorage.getItem('scanned_single_arete')).to.eq('ARETE-SINGLE')
    })
    cy.location('hash').should('eq', '#/inspeccion')
  })

  it('modo batch: guarda en sessionStorage y navega a inspeccion', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-001')
    cy.contains('Agregar Animal').click()
    cy.get('.form-input').type('ARETE-002')
    cy.contains('Agregar Animal').click()

    cy.contains('Continuar al Dictamen').click()
    cy.location('hash').should('eq', '#/inspeccion')

    cy.window().then((win) => {
      const saved = JSON.parse(win.sessionStorage.getItem('scanned_animals'))
      expect(saved).to.have.length(2)
    })
  })

  it('modo single: goBack limpia sessionStorage y navega', () => {
    cy.window().then((win) => {
      win.sessionStorage.setItem('scan_target_index', '0')
      win.sessionStorage.setItem('scanned_single_arete', 'SOME-ARETE')
    })

    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router] } })

    cy.get('header button').last().click({ force: true })
    cy.window().then((win) => {
      expect(win.sessionStorage.getItem('scan_target_index')).to.be.null
      expect(win.sessionStorage.getItem('scanned_single_arete')).to.be.null
    })
    cy.location('hash').should('eq', '#/inspeccion')
  })

  it('modo batch: goBack navega a dashboard', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router] } })

    cy.get('header button').last().click({ force: true })
    cy.location('hash').should('eq', '#/dashboard')
  })

  it('checkAndRequestCameraPermission rechaza sin camara', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router] } })

    cy.contains('Escáner SINIIGA', { timeout: 5000 }).should('be.visible')
  })

  it('modo batch: permite ingresar multiples aretes y navegar', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-BATCH-1{enter}')
    cy.get('.form-input').type('ARETE-BATCH-2{enter}')
    cy.contains('ARETE-BATCH-1').should('be.visible')
    cy.contains('ARETE-BATCH-2').should('be.visible')

    cy.contains('Continuar al Dictamen').click()
    cy.location('hash', { timeout: 5000 }).should('include', '/inspeccion')
  })
})
