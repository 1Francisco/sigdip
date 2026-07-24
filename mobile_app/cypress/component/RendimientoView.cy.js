import { mount } from 'cypress/vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import RendimientoView from '../../src/views/RendimientoView.vue'
import userAdmin from '../fixtures/user-admin.json'

const EmptyView = { template: '<div>Other</div>' }

function buildRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/reportes/rendimiento', name: 'Rendimiento', component: RendimientoView },
      { path: '/dashboard', component: EmptyView }
    ]
  })
}

const mockRendimientoResponse = {
  success: true,
  filters: {
    years: [2026, 2025],
    localidades: ['Tepic', 'Xalisco'],
    medicos: [
      { id: 2, name: 'Dr. Juan Perez', email: 'juan@test.com' }
    ]
  },
  kpis: {
    total_inspecciones: 15,
    total_visitas: 20,
    medicos_activos: 1,
    total_animales: 150,
    total_reactores: 5
  },
  medicosRendimiento: [
    {
      id: 2,
      name: 'Dr. Juan Perez',
      total_inspecciones: 15,
      total_visitas: 20,
      visitas_completadas: 18,
      predios_atendidos: 10,
      total_animales: 150,
      total_reactores: 5,
      promedio_animales: 10,
      tasa_reactores: 3.3,
      tasa_finalizacion: 90,
      eficiencia_score: 85,
      ultima_inspeccion: '2026-07-10'
    }
  ],
  actividades: [
    { tipo: 'PPC', total: 10 },
    { tipo: 'PCC', total: 5 }
  ],
  nombresPruebas: {
    PPC: 'Prueba de Pliegue Caudal (PPC)',
    PCC: 'Prueba Cervical Comparativa (PCC)'
  },
  zonas: [
    { zona: 'A', total: 12 },
    { zona: 'B', total: 3 }
  ],
  cuarentenasD: [
    { tipo: 'D1', total: 2, detalle: [{ clave_cuarentena: 'D1-A', total: 2 }] }
  ],
  cuarentenasP: [
    { tipo: 'P2', total: 1, detalle: [{ clave_cuarentena: 'P2-B', total: 1 }] }
  ],
  totalSinCuarentena: 12,
  meses: [
    { mes: '2026-06', total: 15, total_anterior: 10 }
  ],
  mensualRows: [
    {
      veterinario_id: 2,
      mes: '2026-06',
      medico_nombre: 'Dr. Juan Perez',
      ppc: 10,
      pcc: 5,
      total_inspecciones: 15,
      predios: 10,
      total_visitas: 20,
      total_animales: 150,
      total_reactores: 5,
      reactores_ppc: 4,
      reactores_pcc: 1
    }
  ],
  selectedYear: 2026
}

describe('RendimientoView Component Tests', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.mockCapacitor()
  })

  it('muestra aviso si no hay conexion a internet (Offline)', () => {
    cy.stub(navigator, 'onLine').value(false)
    const router = buildRouter()
    router.push('/reportes/rendimiento')
    
    cy.setLoginState({ user: userAdmin })
    mount(RendimientoView, { global: { plugins: [router] } })

    cy.contains('Módulo Offline').should('be.visible')
    cy.contains('La sección de rendimiento requiere conexión a Internet').should('be.visible')
  })

  it('se renderiza correctamente online con KPIs y pestañas', () => {
    cy.intercept('GET', '**/api/reportes/rendimiento*', {
      statusCode: 200,
      body: mockRendimientoResponse
    }).as('getRendimiento')

    const router = buildRouter()
    router.push('/reportes/rendimiento')
    
    cy.setLoginState({ user: userAdmin })
    mount(RendimientoView, { global: { plugins: [router] } })

    cy.wait('@getRendimiento')


    
    // Verificar listado de Médicos
    cy.contains('Dr. Juan Perez').should('exist')
    cy.contains('85').should('exist')



    // Cambiar a pestaña Detalle Mensual
    cy.get('.tab-mensual').click()
    cy.contains('Detalle Mensual (2026)').should('be.visible')
    cy.contains('Total General').should('be.visible')
    cy.get('.table-mensual').should('be.visible')
  })

  it('permite colapsar y usar filtros', () => {
    cy.intercept('GET', '**/api/reportes/rendimiento*', {
      statusCode: 200,
      body: mockRendimientoResponse
    }).as('getRendimiento')

    const router = buildRouter()
    router.push('/reportes/rendimiento')
    
    cy.setLoginState({ user: userAdmin })
    mount(RendimientoView, { global: { plugins: [router] } })

    cy.wait('@getRendimiento')

    // Clic en Mostrar Filtros
    cy.get('.btn-filter-toggle').click()
    cy.get('.panel-filters').should('be.visible')

    // Seleccionar año y aplicar filtros
    cy.get('.select-year').select('2026')
    cy.get('.btn-apply-filters').click()
    cy.wait('@getRendimiento')
  })

  it('permite seleccionar un médico para ver sus últimas inspecciones', () => {
    const mockRendimientoWithDetail = {
      ...mockRendimientoResponse,
      detalleMedico: [
        {
          id: 101,
          fecha: '2026-07-10',
          folio: 'FOL-101',
          clave_interna: 'INT-101',
          productor_nombre: 'Productor Test',
          predio_nombre: 'Predio Test',
          tipo_prueba: 'PPC',
          estado: 'sincronizado'
        }
      ]
    }

    cy.intercept('GET', '**/api/reportes/rendimiento*', (req) => {
      const url = new URL(req.url)
      const medicoId = url.searchParams.get('medico_id')
      if (medicoId === '2') {
        req.reply({
          statusCode: 200,
          body: mockRendimientoWithDetail
        })
      } else {
        req.reply({
          statusCode: 200,
          body: mockRendimientoResponse
        })
      }
    }).as('getRendimiento')

    const router = buildRouter()
    router.push('/reportes/rendimiento')
    
    cy.setLoginState({ user: userAdmin })
    mount(RendimientoView, { global: { plugins: [router] } })

    cy.wait('@getRendimiento')

    // Al inicio no debe mostrarse el detalle de inspecciones
    cy.get('.select-medico-details').should('not.exist')

    // Hacer clic en el botón de ver detalle del médico
    cy.get('.btn-select-medico').click()
    cy.wait('@getRendimiento')

    // Ahora debe mostrarse el panel de últimas inspecciones
    cy.get('.select-medico-details').should('be.visible')
    cy.contains('Últimas lecturas').should('be.visible')
    cy.contains('FOL-101').should('be.visible')
    cy.contains('Productor Test').should('be.visible')
  })
})
