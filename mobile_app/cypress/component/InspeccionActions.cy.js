import { mount } from 'cypress/vue'
import InspeccionActions from '../../src/components/InspeccionActions.vue'

describe('InspeccionActions', () => {
  it('renderiza botones para escritorio', () => {
    mount(InspeccionActions, {
      props: { finalizarBloqueado: false, inyeccionConfirmada: false },
    })
    cy.get('.btn-borrador-card').should('be.visible')
    cy.get('.btn-finalizar-row').should('be.visible')
  })

  it('boton finalizar muestra Finalizar Inyeccion cuando no confirmada', () => {
    mount(InspeccionActions, {
      props: { finalizarBloqueado: false, inyeccionConfirmada: false },
    })
    cy.get('.btn-finalizar-row').should('contain', 'Finalizar Inyección')
  })

  it('boton finalizar muestra Finalizar cuando inyeccion confirmada', () => {
    mount(InspeccionActions, {
      props: { finalizarBloqueado: false, inyeccionConfirmada: true },
    })
    cy.get('.btn-finalizar-row').should('contain', 'Finalizar')
  })

  it('boton finalizar bloqueado y deshabilitado', () => {
    mount(InspeccionActions, {
      props: { finalizarBloqueado: true, inyeccionConfirmada: false },
    })
    cy.get('.btn-finalizar-row').should('be.disabled')
    cy.get('.btn-finalizar-row').should('contain', 'Bloqueado')
  })

  it('emite save con borrador al hacer click en Borrador', () => {
    const onSave = cy.spy().as('onSave')
    mount(InspeccionActions, {
      props: { finalizarBloqueado: false, 'onSave': onSave },
    })
    cy.get('.btn-borrador-card').click()
    cy.get('@onSave').should('have.been.calledWith', 'borrador')
  })

  it('emite save con sincronizado al hacer click en Finalizar', () => {
    const onSave = cy.spy().as('onSave')
    mount(InspeccionActions, {
      props: { finalizarBloqueado: false, inyeccionConfirmada: true, 'onSave': onSave },
    })
    cy.get('.btn-finalizar-row').click()
    cy.get('@onSave').should('have.been.calledWith', 'sincronizado')
  })

  it('renderiza FAB para añadir animal en mobile', () => {
    mount(InspeccionActions, {
      props: { finalizarBloqueado: false },
    })
    cy.get('.btn-fab-add').should('be.visible')
  })

  it('emite add-animal al hacer click en FAB', () => {
    const onAdd = cy.spy().as('onAdd')
    mount(InspeccionActions, {
      props: { finalizarBloqueado: false, 'onAddAnimal': onAdd },
    })
    cy.get('.btn-fab-add').click()
    cy.get('@onAdd').should('have.been.calledOnce')
  })
})
