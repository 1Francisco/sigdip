import { mount } from 'cypress/vue'
import SearchableSelect from '../../src/components/SearchableSelect.vue'

const options = [
  { value: 1, label: 'Juan Perez' },
  { value: 2, label: 'Maria Garcia' },
  { value: 3, label: 'Carlos Lopez' },
]

describe('SearchableSelect', () => {
  it('renderiza placeholder cuando no hay seleccion', () => {
    mount(SearchableSelect, {
      props: {
        options,
        placeholder: 'Seleccionar productor...',
        modelValue: null,
      },
    })

    cy.contains('Seleccionar productor...', { timeout: 5000 }).should('be.visible')
  })

  it('muestra label seleccionado', () => {
    mount(SearchableSelect, {
      props: {
        options,
        modelValue: 1,
        placeholder: 'Seleccionar...',
      },
    })

    cy.contains('Juan Perez', { timeout: 5000 }).should('be.visible')
  })

  it('abre dropdown al hacer click', () => {
    mount(SearchableSelect, {
      props: {
        options,
        modelValue: null,
      },
    })

    cy.get('.searchable-select-trigger').click()
    cy.get('.searchable-select-dropdown', { timeout: 5000 }).should('be.visible')
    cy.contains('Juan Perez').should('be.visible')
    cy.contains('Maria Garcia').should('be.visible')
  })

  it('filtra opciones por texto de busqueda', () => {
    mount(SearchableSelect, {
      props: {
        options,
        modelValue: null,
      },
    })

    cy.get('.searchable-select-trigger').click()
    cy.get('.dropdown-search-input').type('Maria')
    cy.contains('Maria Garcia').should('be.visible')
    cy.contains('Juan Perez').should('not.exist')
    cy.contains('Carlos Lopez').should('not.exist')
  })

  it('emite update:modelValue al seleccionar opcion', () => {
    const onUpdate = cy.spy().as('onUpdate')

    mount(SearchableSelect, {
      props: {
        options,
        modelValue: null,
        'onUpdate:modelValue': onUpdate,
      },
    })

    cy.get('.searchable-select-trigger').click()
    cy.contains('Maria Garcia').click()
    cy.get('@onUpdate').should('have.been.calledWith', 2)
  })

  it('cierra dropdown al hacer click fuera', () => {
    mount(SearchableSelect, {
      props: {
        options,
        modelValue: null,
      },
    })

    cy.get('.searchable-select-trigger').click()
    cy.get('.searchable-select-dropdown').should('be.visible')

    cy.get('.searchable-select-trigger').then(() => {
      cy.get('body').click(9999, 9999, { force: true })
    })
    cy.get('.searchable-select-dropdown').should('not.exist')
  })

  it('muestra Sin resultados si no hay match', () => {
    mount(SearchableSelect, {
      props: {
        options,
        modelValue: null,
      },
    })

    cy.get('.searchable-select-trigger').click()
    cy.get('.dropdown-search-input').type('ZZZZ')
    cy.contains('Sin resultados', { timeout: 5000 }).should('be.visible')
  })

  it('muestra icono check en opcion seleccionada', () => {
    mount(SearchableSelect, {
      props: {
        options,
        modelValue: 1,
      },
    })

    cy.get('.searchable-select-trigger').click()
    cy.get('.selected-icon', { timeout: 5000 }).should('be.visible')
  })

  it('aplica clase is-invalid cuando hasError es true', () => {
    mount(SearchableSelect, {
      props: {
        options,
        modelValue: null,
        hasError: true,
      },
    })

    cy.get('.searchable-select-trigger').should('have.class', 'is-invalid')
  })
})
