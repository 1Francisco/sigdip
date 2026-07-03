import { mount } from 'cypress/vue'
import Toast from '../../src/components/Toast.vue'

describe('Toast', () => {
  it('no se muestra inicialmente', () => {
    mount(Toast)
    cy.get('.toast-container').should('not.exist')
  })

  it('se muestra al llamar show()', () => {
    mount(Toast).then((wrapper) => {
      wrapper.component.show('Operación exitosa')
    })
    cy.contains('Operación exitosa').should('be.visible')
  })

  it('muestra icono check para tipo success', () => {
    mount(Toast).then((wrapper) => {
      wrapper.component.show('Éxito', 'success')
    })
    cy.get('.bi-check-circle-fill').should('exist')
  })

  it('muestra icono exclamation para tipo error', () => {
    mount(Toast).then((wrapper) => {
      wrapper.component.show('Error', 'error')
    })
    cy.get('.bi-exclamation-circle-fill').should('exist')
  })

  it('muestra icono warning para tipo warning', () => {
    mount(Toast).then((wrapper) => {
      wrapper.component.show('Advertencia', 'warning')
    })
    cy.get('.bi-exclamation-triangle-fill').should('exist')
  })

  it('muestra icono info para tipo info', () => {
    mount(Toast).then((wrapper) => {
      wrapper.component.show('Información', 'info')
    })
    cy.get('.bi-info-circle-fill').should('exist')
  })

  it('success usa clase bg-success', () => {
    mount(Toast).then((wrapper) => {
      wrapper.component.show('OK', 'success')
    })
    cy.get('.toast').should('have.class', 'bg-success')
  })

  it('error usa clase bg-danger', () => {
    mount(Toast).then((wrapper) => {
      wrapper.component.show('Error', 'error')
    })
    cy.get('.toast').should('have.class', 'bg-danger')
  })

  it('warning usa clase bg-warning', () => {
    mount(Toast).then((wrapper) => {
      wrapper.component.show('Cuidado', 'warning')
    })
    cy.get('.toast').should('have.class', 'bg-warning')
  })

  it('se oculta al llamar hide()', () => {
    mount(Toast).then((wrapper) => {
      wrapper.component.show('Visible')
      wrapper.component.hide()
    })
    cy.get('.toast-container').should('not.exist')
  })

  it('se oculta al hacer click en boton cerrar', () => {
    mount(Toast).then((wrapper) => {
      wrapper.component.show('Cerrable')
    })
    cy.get('.btn-close').click()
    cy.get('.toast-container').should('not.exist')
  })

  it('se oculta automaticamente despues de duration', () => {
    cy.clock()
    mount(Toast).then((wrapper) => {
      wrapper.component.show('Temporizado', 'success', 100)
    })
    cy.contains('Temporizado').should('be.visible')
    cy.tick(150)
    cy.get('.toast-container').should('not.exist')
    cy.clock().invoke('restore')
  })

  it('no se oculta si duration es 0', () => {
    cy.clock()
    mount(Toast).then((wrapper) => {
      wrapper.component.show('Persistente', 'success', 0)
    })
    cy.tick(5000)
    cy.contains('Persistente').should('be.visible')
    cy.clock().invoke('restore')
  })
})
