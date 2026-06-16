import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import SyncView from '../../src/views/SyncView.vue'
import userAdmin from '../fixtures/user-admin.json'
import userMedico from '../fixtures/user-medico.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/sync', name: 'Sync', component: SyncView },
      { path: '/dashboard', component: EmptyView },
      { path: '/login', component: EmptyView },
      { path: '/productores', component: EmptyView },
      { path: '/predios', component: EmptyView },
      { path: '/inspecciones', component: EmptyView },
      { path: '/visitas', component: EmptyView },
      { path: '/medicos', component: EmptyView },
      { path: '/descargas', component: EmptyView },
      { path: '/inspeccion', component: EmptyView },
      { path: '/scan', component: EmptyView },
    ],
  })
}

const fakePredios = [
  { id: 1, nombre_rancho: 'Rancho El Paraiso', productor: { id: 1, nombre: 'Maria' }, localidad: 'Villahermosa' },
  { id: 2, nombre_rancho: 'Rancho San Jose', productor: { id: 2, nombre: 'Jose' }, localidad: 'Escarcega' },
]

const fakeVisitas = [
  { id: 1, codigo: 'V-001', predio_id: 1, fecha_programada: '2026-06-15', veterinario_id: 2, estado: 'pendiente' },
  { id: 2, codigo: 'V-002', predio_id: 2, fecha_programada: '2026-06-16', veterinario_id: 2, estado: 'pendiente' },
]

const fakeInspeccionesPendientes = [
  {
    folio: 'INSP-001',
    fecha: '2026-06-10',
    predio_id: 1,
    animales: [
      { identificador: 'ARETE-001', resultado: 'Negativo', edad_meses: 24, sexo: 'M', raza: 'Cebu' },
      { identificador: 'ARETE-002', resultado: 'Positivo', edad_meses: 36, sexo: 'H', raza: 'Suizo' },
    ]
  },
  {
    folio: 'INSP-002',
    fecha: '2026-06-11',
    predio_id: 2,
    animales: [
      { identificador: 'ARETE-003', resultado: 'Negativo', edad_meses: 18, sexo: 'H', raza: 'Cebu' },
    ]
  }
]

const fakeVisitasPendientes = [
  {
    codigo: 'V-OFFLINE-001',
    predio_id: 1,
    fecha_programada: '2026-06-20',
    veterinario_id: 2,
    observaciones: 'Creada offline',
    _created_at: new Date().toISOString()
  }
]

describe('SyncView', () => {
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
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.seedIndexedDB('catalogos', 'visitas', fakeVisitas)
      cy.seedIndexedDB('inspecciones_pendientes', 'lista', fakeInspeccionesPendientes)
      cy.seedIndexedDB('visitas_pendientes', 'lista', fakeVisitasPendientes)

      const router = buildRouter()
      router.push('/sync')
      mount(SyncView, { global: { plugins: [router] } })

      cy.contains('Sincronización', { timeout: 5000 }).should('be.visible')
      cy.contains('Ranchos').should('be.visible')
      cy.contains('Visitas').should('be.visible')
      cy.contains('Por subir').should('be.visible')
      cy.contains('Último Sync').should('be.visible')
    })

    it('muestra stats correctos desde IndexedDB', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      cy.seedIndexedDB('catalogos', 'visitas', fakeVisitas)
      cy.seedIndexedDB('inspecciones_pendientes', 'lista', fakeInspeccionesPendientes)
      cy.seedIndexedDB('visitas_pendientes', 'lista', fakeVisitasPendientes)

      const router = buildRouter()
      router.push('/sync')
      mount(SyncView, { global: { plugins: [router] } })

      cy.contains('2', { timeout: 5000 }).should('be.visible')
      cy.contains('3').should('be.visible')
    })

    it('muestra boton descargar catalogos habilitado cuando online', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)

      const router = buildRouter()
      router.push('/sync')
      mount(SyncView, { global: { plugins: [router] } })

      cy.contains('Descargar Catálogos del Día', { timeout: 5000 }).should('be.visible').should('not.be.disabled')
    })

    it('muestra pestania de dictamenes pendientes', () => {
      cy.seedIndexedDB('inspecciones_pendientes', 'lista', fakeInspeccionesPendientes)

      const router = buildRouter()
      router.push('/sync')
      mount(SyncView, { global: { plugins: [router] } })

      cy.contains('Dictámenes Pendientes (2)', { timeout: 5000 }).should('be.visible')
      cy.contains('INSP-001').should('be.visible')
      cy.contains('INSP-002').should('be.visible')
    })

    it('cambia a pestania de visitas pendientes', () => {
      cy.seedIndexedDB('visitas_pendientes', 'lista', fakeVisitasPendientes)

      const router = buildRouter()
      router.push('/sync')
      mount(SyncView, { global: { plugins: [router] } })

      cy.contains('Visitas Pendientes (1)', { timeout: 5000 }).should('be.visible').click()
      cy.contains('V-OFFLINE-001').should('be.visible')
      cy.contains('Creada offline').should('be.visible')
    })

    it('muestra boton subir deshabilitado cuando no hay pendientes', () => {
      const router = buildRouter()
      router.push('/sync')
      mount(SyncView, { global: { plugins: [router] } })

      cy.contains('Subir Dictámenes Pendientes', { timeout: 5000 }).should('be.visible')
        .should('be.disabled')
    })

    it('descarga catalogos al hacer click', () => {
      cy.intercept('GET', '**/api/sync/catalogos', {
        statusCode: 200,
        body: {
          data: {
            predios: fakePredios,
            productores: [],
            medicos: [],
            visitas: fakeVisitas
          }
        }
      }).as('downloadCatalogos')

      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      const router = buildRouter()
      router.push('/sync')
      mount(SyncView, { global: { plugins: [router] } })

      cy.contains('Descargar Catálogos del Día', { timeout: 5000 }).click()
      cy.wait('@downloadCatalogos', { timeout: 10000 })
      cy.contains('Sincronización Completada', { timeout: 5000 }).should('be.visible')
    })

    it('muestra error de descarga cuando falla API', () => {
      cy.intercept('GET', '**/api/sync/catalogos', {
        statusCode: 500,
        body: { message: 'Error del servidor' }
      }).as('downloadFail')

      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      const router = buildRouter()
      router.push('/sync')
      mount(SyncView, { global: { plugins: [router] } })

      cy.contains('Descargar Catálogos del Día', { timeout: 5000 }).click()
      cy.wait('@downloadFail', { timeout: 10000 })
      cy.contains('Error en Operación', { timeout: 5000 }).should('be.visible')
    })

    it('sube visitas pendientes al hacer click', () => {
      cy.intercept('POST', '**/api/sync/visitas', {
        statusCode: 200,
        body: { procesados: [{ codigo: 'V-OFFLINE-001' }] },
      }).as('uploadVisitas')

      cy.seedIndexedDB('visitas_pendientes', 'lista', [{
        codigo: 'V-OFFLINE-001',
        predio_id: 1,
        fecha_programada: '2026-06-20',
        veterinario_id: 2,
        observaciones: 'Test offline',
        _created_at: new Date().toISOString()
      }])

      const router = buildRouter()
      router.push('/sync')
      mount(SyncView, { global: { plugins: [router] } })

      cy.contains('Visitas Pendientes (1)', { timeout: 5000 }).click()
      cy.contains('Subir Dictámenes Pendientes (1)').click()
      cy.wait('@uploadVisitas', { timeout: 10000 })
      cy.contains('Sincronización Completada', { timeout: 10000 }).should('be.visible')
    })

    it('sube inspecciones pendientes al hacer click en subir dictamen', () => {
      cy.intercept('GET', '**/api/sync/catalogos', {
        statusCode: 200,
        body: { data: { predios: fakePredios, productores: [], medicos: [], visitas: fakeVisitas } }
      }).as('downloadCatalogos')

      cy.intercept('GET', '**/api/inspecciones?folio=*', {
        statusCode: 200,
        body: { success: true, data: [] }
      }).as('checkFolio')

      cy.intercept('POST', '**/api/sync/inspecciones', {
        statusCode: 200,
        body: { procesados: [{ folio: 'INSP-001' }] }
      }).as('uploadInspecciones')

      cy.seedIndexedDB('inspecciones_pendientes', 'lista', fakeInspeccionesPendientes)
      const router = buildRouter()
      router.push('/sync')
      mount(SyncView, { global: { plugins: [router] } })

      cy.contains('Subir Dictámenes Pendientes (2)', { timeout: 5000 }).click()
      cy.wait('@checkFolio', { timeout: 10000 })
      cy.wait('@uploadInspecciones', { timeout: 10000 })
      cy.contains('Sincronización Completada', { timeout: 10000 }).should('be.visible')
    })

    it('muestra modal de conflicto al detectar diferencias con servidor', () => {
      cy.seedIndexedDB('inspecciones_pendientes', 'lista', [{
        folio: 'INSP-CONF-001',
        fecha: '2026-06-10',
        predio_id: 1,
        animales: [
          { identificador: 'ARETE-LOCAL', resultado: 'Negativo', edad_meses: 24, sexo: 'M', raza: 'Cebu' },
        ]
      }])

      cy.intercept('GET', '**/api/inspecciones?folio=*', {
        statusCode: 200,
        body: { success: true, data: [{ id: 999, folio: 'INSP-CONF-001' }] }
      }).as('checkFolioConflicto')

      cy.intercept('GET', '**/api/inspecciones/999', {
        statusCode: 200,
        body: {
          success: true,
          data: {
            id: 999,
            folio: 'INSP-CONF-001',
            detalles: [
              { id: 10, animal: { numero_arete_siniiga: 'ARETE-SERVER' }, resultado_prueba: 'Positivo' }
            ]
          }
        }
      }).as('getDetailConflicto')

      const router = buildRouter()
      router.push('/sync')
      mount(SyncView, { global: { plugins: [router] } })

      cy.contains('Subir Dictámenes Pendientes (1)', { timeout: 5000 }).click()
      cy.wait('@checkFolioConflicto', { timeout: 10000 })
      cy.wait('@getDetailConflicto', { timeout: 10000 })

      // Verificar que el modal de conflicto se muestra
      cy.contains('Conflicto de Sincronización Detectado', { timeout: 5000 }).should('be.visible')
      cy.contains('ARETE-LOCAL').should('be.visible')
      cy.contains('ARETE-SERVER').should('be.visible')
    })

    it('resuelve conflicto con Sobrescribir Servidor', () => {
      cy.seedIndexedDB('inspecciones_pendientes', 'lista', [{
        folio: 'INSP-OW-001',
        fecha: '2026-06-10',
        predio_id: 1,
        animales: [
          { identificador: 'ARETE-LOCAL', resultado: 'Negativo', edad_meses: 24, sexo: 'M', raza: 'Cebu' },
        ]
      }])

      cy.intercept('GET', '**/api/inspecciones?folio=*', {
        statusCode: 200,
        body: { success: true, data: [{ id: 998, folio: 'INSP-OW-001' }] }
      }).as('checkFolioOW')

      cy.intercept('GET', '**/api/inspecciones/998', {
        statusCode: 200,
        body: {
          success: true,
          data: {
            id: 998,
            folio: 'INSP-OW-001',
            detalles: [
              { id: 20, animal: { numero_arete_siniiga: 'ARETE-SERVER' }, resultado_prueba: 'Positivo' }
            ]
          }
        }
      }).as('getDetailOW')

      cy.intercept('POST', '**/api/sync/inspecciones', {
        statusCode: 200,
        body: { procesados: [{ folio: 'INSP-OW-001' }] }
      }).as('uploadOW')

      const router = buildRouter()
      router.push('/sync')
      mount(SyncView, { global: { plugins: [router] } })

      cy.contains('Subir Dictámenes Pendientes (1)', { timeout: 5000 }).click()
      cy.wait('@checkFolioOW', { timeout: 10000 })
      cy.wait('@getDetailOW', { timeout: 10000 })

      cy.contains('Conflicto de Sincronización Detectado', { timeout: 5000 }).should('be.visible')
      cy.contains('Sobrescribir Servidor').click()

      cy.wait('@uploadOW', { timeout: 10000 })
      cy.contains('Sincronizados', { timeout: 5000 }).should('be.visible')
    })

    it('resuelve conflicto con Conservar Servidor', () => {
      cy.seedIndexedDB('inspecciones_pendientes', 'lista', [{
        folio: 'INSP-KS-001',
        fecha: '2026-06-10',
        predio_id: 1,
        animales: [
          { identificador: 'ARETE-LOCAL', resultado: 'Negativo', edad_meses: 24, sexo: 'M', raza: 'Cebu' },
        ]
      }])

      cy.intercept('GET', '**/api/inspecciones?folio=*', {
        statusCode: 200,
        body: { success: true, data: [{ id: 997, folio: 'INSP-KS-001' }] }
      }).as('checkFolioKS')

      cy.intercept('GET', '**/api/inspecciones/997', {
        statusCode: 200,
        body: {
          success: true,
          data: {
            id: 997,
            folio: 'INSP-KS-001',
            detalles: [
              { id: 30, animal: { numero_arete_siniiga: 'ARETE-SERVER' }, resultado_prueba: 'Positivo' }
            ]
          }
        }
      }).as('getDetailKS')

      const router = buildRouter()
      router.push('/sync')
      mount(SyncView, { global: { plugins: [router] } })

      cy.contains('Subir Dictámenes Pendientes (1)', { timeout: 5000 }).click()
      cy.wait('@checkFolioKS', { timeout: 10000 })
      cy.wait('@getDetailKS', { timeout: 10000 })

      cy.contains('Conflicto de Sincronización Detectado', { timeout: 5000 }).should('be.visible')
      cy.contains('Conservar Servidor').click()

      // No debe llamar a upload, solo limpiar local
      cy.contains('Sincronizados', { timeout: 5000 }).should('be.visible')
    })
  })

  describe('como Medico', () => {
    beforeEach(() => {
      cy.setLoginState({ user: userMedico })
    })

    it('renderiza vista sync para medico', () => {
      cy.seedIndexedDB('catalogos', 'predios', fakePredios)
      const router = buildRouter()
      router.push('/sync')
      mount(SyncView, { global: { plugins: [router] } })

      cy.contains('Sincronización', { timeout: 5000 }).should('be.visible')
    })
  })
})
