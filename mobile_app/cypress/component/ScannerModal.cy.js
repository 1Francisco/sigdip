import { mount } from 'cypress/vue'
import ScannerModal from '../../src/components/ScannerModal.vue'

describe('ScannerModal', () => {
  it('renderiza modal con titulo Escanear Arete', () => {
    mount(ScannerModal)
    cy.contains('Escanear Arete').should('be.visible')
  })

  it('muestra instruccion de camara', () => {
    mount(ScannerModal)
    cy.contains('Apunta la cámara al código de barras del arete.').should('be.visible')
  })

  it('emite close al hacer click en boton cerrar', () => {
    const onClose = cy.spy().as('onClose')
    mount(ScannerModal, {
      props: { 'onClose': onClose },
    })
    cy.get('.btn-close-scanner').click()
    cy.get('@onClose').should('have.been.calledOnce')
  })

  it('emite scanned al decodificar un codigo', () => {
    const onScanned = cy.spy().as('onScanned')
    mount(ScannerModal, {
      props: { 'onScanned': onScanned },
    }).then((wrapper) => {
      wrapper.component.onScanSuccess('MX-12345')
    })
    cy.get('@onScanned').should('have.been.calledWith', 'MX-12345')
  })

  it('muestra mensaje de permiso denegado cuando falla la camara', () => {
    mount(ScannerModal).then((wrapper) => {
      wrapper.component.permissionDenied = true
    })
    cy.contains('Permiso de cámara denegado').should('be.visible')
  })

  it('boton Cancelar en vista de permiso denegado emite close', () => {
    const onClose = cy.spy().as('onClose')
    mount(ScannerModal, {
      props: { 'onClose': onClose },
    }).then((wrapper) => {
      wrapper.component.permissionDenied = true
    })
    cy.contains('Cancelar').should('be.visible').click()
    cy.get('@onClose').should('have.been.called')
  })
})
