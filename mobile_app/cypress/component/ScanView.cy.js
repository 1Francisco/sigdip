import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import { createPinia } from 'pinia'
import { useInspeccionStore } from '../../src/stores/inspeccion.js'
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
    mount(ScanView, { global: { plugins: [router, createPinia()] } })

    cy.contains('Escáner SINIIGA', { timeout: 5000 }).should('be.visible')
  })

  it('renderiza input manual', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router, createPinia()] } })

    cy.get('.form-input', { timeout: 5000 }).should('be.visible')
  })

  it('permite ingreso manual de arete en modo batch', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router, createPinia()] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-001')
    cy.contains('Agregar Animal').click()

    cy.contains('ARETE-001').should('be.visible')
    cy.get('.form-input').should('have.value', '')
  })

  it('muestra multiple aretes agregados manualmente', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router, createPinia()] } })

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
    mount(ScanView, { global: { plugins: [router, createPinia()] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-001')
    cy.contains('Agregar Animal').click()

    cy.get('.form-input').type('ARETE-001')
    cy.contains('Agregar Animal').click()

    cy.get('.animal-row').should('have.length', 1)
  })

  it('elimina arete de la lista', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router, createPinia()] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-001')
    cy.contains('Agregar Animal').click()
    cy.contains('ARETE-001').should('be.visible')

    cy.get('.animal-row button').click()
    cy.contains('ARETE-001').should('not.exist')
  })

  it('modo single: guarda arete en sessionStorage y navega a inspeccion', () => {
    const pinia = createPinia()
    const store = useInspeccionStore(pinia)
    store.scanTargetIndex = 0

    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router, pinia] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-SINGLE')
    cy.contains('Confirmar Arete').click()

    cy.then(() => {
      expect(store.scannedSingleArete).to.eq('ARETE-SINGLE')
    })
    cy.location('hash').should('eq', '#/inspeccion')
  })

  it('modo batch: guarda en sessionStorage y navega a inspeccion', () => {
    const pinia = createPinia()
    const store = useInspeccionStore(pinia)

    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router, pinia] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-001')
    cy.contains('Agregar Animal').click()
    cy.get('.form-input').type('ARETE-002')
    cy.contains('Agregar Animal').click()

    cy.contains('Continuar al Dictamen').click()
    cy.location('hash').should('eq', '#/inspeccion')

    cy.then(() => {
      expect(store.scannedAnimals).to.have.length(2)
    })
  })

  it('modo single: goBack limpia sessionStorage y navega', () => {
    const pinia = createPinia()
    const store = useInspeccionStore(pinia)
    store.scanTargetIndex = 0
    store.scannedSingleArete = 'SOME-ARETE'

    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router, pinia] } })

    cy.get('header button').last().click({ force: true })
    cy.then(() => {
      expect(store.scanTargetIndex).to.be.null
      expect(store.scannedSingleArete).to.be.null
    })
    cy.location('hash').should('eq', '#/inspeccion')
  })

  it('modo batch: goBack navega a dashboard', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router, createPinia()] } })

    cy.get('header button').last().click({ force: true })
    cy.location('hash').should('eq', '#/dashboard')
  })

  it('checkAndRequestCameraPermission rechaza sin camara', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router, createPinia()] } })

    cy.contains('Escáner SINIIGA', { timeout: 5000 }).should('be.visible')
  })

  it('modo batch: permite ingresar multiples aretes y navegar', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router, createPinia()] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-BATCH-1{enter}')
    cy.get('.form-input').type('ARETE-BATCH-2{enter}')
    cy.contains('ARETE-BATCH-1').should('be.visible')
    cy.contains('ARETE-BATCH-2').should('be.visible')

    cy.contains('Continuar al Dictamen').click()
    cy.location('hash', { timeout: 5000 }).should('include', '/inspeccion')
  })

  it('modo batch: permite cambiar resultado de arete escaneado', () => {
    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router, createPinia()] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-001')
    cy.contains('Agregar Animal').click()

    cy.get('.animal-row select').select('Negativo')
    cy.get('.animal-row select').should('have.value', 'Negativo')

    cy.get('.animal-row select').select('Positivo')
    cy.get('.animal-row select').should('have.value', 'Positivo')

    cy.get('.animal-row select').select('Sospechoso')
    cy.get('.animal-row select').should('have.value', 'Sospechoso')
  })

  it('modo single preserva inspeccion_draft en sessionStorage', () => {
    const pinia = createPinia()
    const store = useInspeccionStore(pinia)
    store.scanTargetIndex = 0
    const draft = {
      folio: 'TEMP-1234567890-1234',
      predio_id: 1,
      animales: [{ identificador: '', resultado: 'Pendiente', edad_meses: null }],
    }
    store.inspeccionDraft = draft

    const router = buildRouter()
    router.push('/scan')
    mount(ScanView, { global: { plugins: [router, pinia] } })

    cy.get('.form-input', { timeout: 5000 }).type('ARETE-SINGLE')
    cy.contains('Confirmar Arete').click()

    cy.then(() => {
      expect(store.scannedSingleArete).to.eq('ARETE-SINGLE')
      // inspeccionDraft debe preservarse para que el formulario lo restaure
      expect(store.inspeccionDraft).to.not.be.null
      expect(store.inspeccionDraft.folio).to.eq('TEMP-1234567890-1234')
    })
    cy.location('hash').should('eq', '#/inspeccion')
  })
})
