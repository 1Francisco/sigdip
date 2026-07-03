import { mount } from 'cypress/vue'
import PullToRefresh from '../../src/components/PullToRefresh.vue'

const tallSlot = '<div style="height: 2000px">Contenido largo</div>'

describe('PullToRefresh', () => {
  it('renderiza slot content', () => {
    mount(PullToRefresh, {
      slots: { default: tallSlot },
    })
    cy.contains('Contenido largo').should('be.visible')
  })

  it('indicador sin clase visible inicialmente', () => {
    mount(PullToRefresh, {
      slots: { default: tallSlot },
    })
    cy.get('.ptr-indicator').should('exist')
    cy.get('.ptr-indicator').should('not.have.class', 'visible')
  })

  it('comienza pulling en touchstart', () => {
    mount(PullToRefresh, {
      slots: { default: tallSlot },
    })
    cy.get('.ptr-container').trigger('touchstart', {
      touches: [{ clientY: 100 }],
      force: true,
    })
    cy.get('.ptr-indicator').should('have.class', 'visible')
  })

  it('emite refresh al soltar con distancia suficiente', () => {
    const onRefresh = cy.spy().as('onRefresh')
    mount(PullToRefresh, {
      props: { 'onRefresh': onRefresh },
      slots: { default: tallSlot },
    })

    cy.get('.ptr-container').trigger('touchstart', { touches: [{ clientY: 100 }], force: true })
    cy.get('.ptr-container').trigger('touchmove', { touches: [{ clientY: 300 }], force: true })
    cy.get('.ptr-container').trigger('touchend', { force: true })

    cy.get('@onRefresh').should('have.been.calledOnce')
  })

  it('no emite refresh si no se supera threshold', () => {
    const onRefresh = cy.spy().as('onRefresh')
    mount(PullToRefresh, {
      props: { 'onRefresh': onRefresh },
      slots: { default: tallSlot },
    })

    cy.get('.ptr-container').trigger('touchstart', { touches: [{ clientY: 100 }], force: true })
    cy.get('.ptr-container').trigger('touchmove', { touches: [{ clientY: 120 }], force: true })
    cy.get('.ptr-container').trigger('touchend', { force: true })

    cy.get('@onRefresh').should('not.have.been.called')
  })

  it('no emite refresh si ya esta refrescando', () => {
    const onRefresh = cy.spy().as('onRefresh')
    mount(PullToRefresh, {
      props: { 'onRefresh': onRefresh },
      slots: { default: tallSlot },
    })

    cy.get('.ptr-container').trigger('touchstart', { touches: [{ clientY: 100 }], force: true })
    cy.get('.ptr-container').trigger('touchmove', { touches: [{ clientY: 300 }], force: true })
    cy.get('.ptr-container').trigger('touchend', { force: true })
    cy.get('@onRefresh').should('have.been.calledOnce')

    cy.get('.ptr-container').trigger('touchstart', { touches: [{ clientY: 100 }], force: true })
    cy.get('.ptr-container').trigger('touchmove', { touches: [{ clientY: 300 }], force: true })
    cy.get('.ptr-container').trigger('touchend', { force: true })
    cy.get('@onRefresh').should('have.been.calledOnce')
  })

  it('muestra spinner cuando esta refrescando', () => {
    mount(PullToRefresh, {
      slots: { default: tallSlot },
    })

    cy.get('.ptr-container').trigger('touchstart', { touches: [{ clientY: 100 }], force: true })
    cy.get('.ptr-container').trigger('touchmove', { touches: [{ clientY: 300 }], force: true })
    cy.get('.ptr-container').trigger('touchend', { force: true })

    cy.get('.ptr-spinner').should('exist')
  })
})
