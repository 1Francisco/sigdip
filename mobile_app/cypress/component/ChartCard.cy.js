import { mount } from 'cypress/vue'
import ChartCard from '../../src/components/ChartCard.vue'

describe('ChartCard', () => {
  beforeEach(() => {
    cy.mockCapacitor()
  })

  it('renderiza titulo e icono', () => {
    mount(ChartCard, {
      props: {
        title: 'Inspecciones por Localidad',
        type: 'bar',
        iconClass: 'bi bi-bar-chart-fill',
        labels: [],
        datasets: [],
      },
    })

    cy.contains('Inspecciones por Localidad', { timeout: 5000 }).should('be.visible')
    cy.get('.bi-bar-chart-fill').should('exist')
  })

  it('renderiza canvas para grafico de barras', () => {
    mount(ChartCard, {
      props: {
        title: 'Test Bar',
        type: 'bar',
        labels: ['Enero', 'Febrero'],
        datasets: [{ label: 'Test', data: [10, 20], backgroundColor: ['#2563eb', '#10b981'] }],
      },
    })

    cy.get('canvas', { timeout: 5000 }).should('exist')
  })

  it('renderiza canvas para grafico doughnut', () => {
    mount(ChartCard, {
      props: {
        title: 'Test Doughnut',
        type: 'doughnut',
        labels: ['A', 'B'],
        datasets: [{ label: 'Test', data: [30, 70], backgroundColor: ['#2563eb', '#10b981'] }],
      },
    })

    cy.get('canvas', { timeout: 5000 }).should('exist')
  })

  it('no crea chart si labels estan vacios', () => {
    mount(ChartCard, {
      props: {
        title: 'Empty Chart',
        type: 'bar',
        labels: [],
        datasets: [],
      },
    })

    cy.get('canvas', { timeout: 5000 }).should('exist')
  })

  it('usa altura personalizada via prop', () => {
    mount(ChartCard, {
      props: {
        title: 'Tall Chart',
        type: 'bar',
        height: 400,
        labels: ['A'],
        datasets: [{ data: [1] }],
      },
    })

    cy.get('.chart-canvas-container').should('have.css', 'height', '400px')
  })
})
