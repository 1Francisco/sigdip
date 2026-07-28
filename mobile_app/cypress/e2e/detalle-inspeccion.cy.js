describe('Detalle de Inspección (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
    cy.setLoginState({
      user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['Administrador'] },
    })
  })

  it('navega al detalle desde la lista de inspecciones', () => {
    cy.intercept('GET', /\/api\/inspecciones(\?.*)?$/, {
      statusCode: 200,
      body: {
        data: [
          {
            id: 1, folio: 'FOL-001', clave_interna: 'FOL-001', fecha: '2025-06-01', estado: 'finalizado',
            predio: { id: 1, nombre_rancho: 'Rancho Test', productor: { id: 1, nombre: 'Juan', apellido_paterno: 'Pérez' } },
            veterinario: { id: 2, name: 'Dr. López' },
          },
        ],
      },
    }).as('getInspecciones')

    cy.visit('/#/inspecciones')
    cy.wait('@getInspecciones', { timeout: 10000 })
    cy.get('h2').contains('Lecturas Pecuarias', { timeout: 5000 }).should('be.visible')

    cy.get('[title="Detalles"]').first().click({ force: true })
    cy.location('hash', { timeout: 5000 }).should('match', /\/inspecciones\/1/)
  })

  it('muestra detalle de una inspeccion finalizada', () => {
    cy.intercept('GET', '**/api/inspecciones/1', {
      statusCode: 200,
      body: {
        data: {
          id: 1, folio: 'FOL-001', clave_interna: 'FOL-001', fecha: '2025-06-01', estado: 'finalizado',
          tipo_prueba: 'Tuberculina', motivo_prueba: 'Rastreo', funcion_zootecnica: 'Engorda',
          fecha_inyeccion: '2025-06-01', hora_inyeccion: '08:00',
          fecha_lectura: '2025-06-04', hora_lectura: '08:00',
          sementales: 2, vacas: 10, vaquillas: 5, becerras: 3, becerros: 4,
          predio: { id: 1, nombre_rancho: 'Rancho Test', localidad: 'Nayarit' },
          veterinario: { id: 2, name: 'Dr. López' },
          detalles: [
            {
              id: 1, raza: 'Holstein', sexo: 'Hembra', resultado_prueba: 'Negativo',
              observaciones_animal: '',
              animal: { numero_arete_siniiga: 'MX-001' },
            },
            {
              id: 2, raza: 'Angus', sexo: 'Macho', resultado_prueba: 'Positivo',
              observaciones_animal: 'Aislar',
              animal: { numero_arete_siniiga: 'MX-002' },
            },
          ],
        },
      },
    }).as('getInspeccion')

    cy.visit('/#/inspecciones/1')
    cy.wait('@getInspeccion', { timeout: 10000 })

    cy.contains('FOL-001', { timeout: 5000 }).should('be.visible')
    cy.contains('Finalizado').should('be.visible')
    cy.contains('Tuberculina').should('be.visible')
    cy.contains('Holstein').should('be.visible')
    cy.contains('MX-001').should('be.visible')
    cy.contains('Positivo').should('be.visible')
  })

  it('muestra boton de continuar edicion para borrador', () => {
    cy.intercept('GET', '**/api/inspecciones/2', {
      statusCode: 200,
      body: {
        data: {
          id: 2, folio: null, fecha: '2025-06-01', estado: 'borrador',
          tipo_prueba: 'Tuberculina', motivo_prueba: 'Rastreo',
          predio_id: 2,
          fecha_inyeccion: '2025-06-01', hora_inyeccion: '08:00',
          fecha_lectura: '2025-06-04', hora_lectura: '08:00',
          sementales: 0, vacas: 0, vaquillas: 0, becerras: 0, becerros: 0,
          predio: { id: 2, nombre_rancho: 'Rancho Draft', localidad: 'Test' },
          veterinario: { id: 2, name: 'Dr. López' },
          detalles: [],
        },
      },
    }).as('getInspeccionBorrador')

    cy.visit('/#/inspecciones/2')
    cy.wait('@getInspeccionBorrador', { timeout: 10000 })

    cy.contains('Sin Folio (Borrador)', { timeout: 5000 }).should('be.visible')
    cy.contains('Borrador').should('be.visible')
    cy.contains('Continuar edición', { timeout: 5000 }).should('be.visible')
  })

  it('muestra error si falla la API al cargar detalle', () => {
    cy.intercept('GET', '**/api/inspecciones/999', {
      statusCode: 500,
      body: { message: 'Error del servidor' },
    }).as('getInspeccionFail')

    cy.visit('/#/inspecciones/999')
    cy.wait('@getInspeccionFail', { timeout: 10000 })

    cy.contains('Error del servidor', { timeout: 5000 }).should('be.visible')
  })
})
