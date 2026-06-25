describe('Role-based Navigation (E2E)', () => {
  beforeEach(() => {
    cy.resetAppState()
  })

  describe('como Administrador', () => {
    beforeEach(() => {
      cy.setLoginState({
        user: { id: 1, name: 'Admin', email: 'admin@sigdip.com', roles: ['Administrador'] },
      })
    })

    it('ve enlaces del sidebar de admin', () => {
      cy.interceptDashboardStats()

      cy.visit('/#/dashboard')
      cy.get('.header-hamburger-btn').click()

      cy.get('.sidebar').should('be.visible')
      cy.get('.sidebar').contains('Dashboard').should('be.visible')
      cy.get('.sidebar').contains('Productores').should('be.visible')
      cy.get('.sidebar').contains('Predios').should('be.visible')
      cy.get('.sidebar').contains('Inspecciones').should('be.visible')
      cy.get('.sidebar').contains('Agenda / Visitas').should('be.visible')
      cy.get('.sidebar').contains('Médicos').should('be.visible')
      cy.get('.sidebar').contains('Descargas').should('be.visible')
      cy.get('.sidebar').contains('Sábana Excel').should('be.visible')

      cy.get('.sidebar').contains('Animales').should('not.exist')
      cy.get('.sidebar').contains('Aretes del Censo').should('not.exist')
      cy.get('.sidebar').contains('Importar Excel').should('not.exist')
    })

    it('ve menu de Medicos en sidebar', () => {
      cy.visit('/#/dashboard')
      cy.get('.header-hamburger-btn').click()
      cy.get('.sidebar').contains('Médicos').should('be.visible')
    })

    it('ve Sabana Excel en sidebar', () => {
      cy.visit('/#/dashboard')
      cy.get('.header-hamburger-btn').click()
      cy.get('.sidebar').contains('Sábana Excel').should('be.visible')
    })

    it('navega a Dashboard', () => {
      cy.interceptDashboardStats({ totalInspecciones: 10, totalAnimales: 100, totalVisitasPendientes: 3 })

      cy.visit('/#/dashboard')
      cy.get('h2', { timeout: 5000 }).should('be.visible')
    })

    it('navega a Productores', () => {
      cy.seedIndexedDB('catalogos', 'predios', [])
      cy.visit('/#/productores')
      cy.get('h2', { timeout: 5000 }).should('contain', 'Productores')
    })

    it('navega a Predios', () => {
      cy.visit('/#/predios')
      cy.get('h2', { timeout: 5000 }).should('contain', 'Predios')
    })

    it('navega a Visitas', () => {
      cy.visit('/#/visitas')
      cy.get('h2', { timeout: 5000 }).should('contain', 'Agenda de Campo')
    })

    it('navega a Inspecciones', () => {
      cy.interceptEmptyInspecciones()

      cy.visit('/#/inspecciones')
      cy.wait('@getInspecciones', { timeout: 10000 })
      cy.get('h2', { timeout: 5000 }).should('contain', 'Inspecciones Pecuarias')
    })

    it('navega a Descargas', () => {
      cy.visit('/#/descargas')
      cy.get('h2', { timeout: 5000 }).should('be.visible')
    })

    it('navega a Sync', () => {
      cy.visit('/#/sync')
      cy.get('h1', { timeout: 5000 }).should('contain', 'Sincronización')
    })

    it('navega a Medicos', () => {
      cy.interceptEmptyMedicos()

      cy.visit('/#/medicos')
      cy.wait('@getMedicos', { timeout: 10000 })
      cy.get('h2', { timeout: 5000 }).should('be.visible')
    })

    it('navega a Animales', () => {
      cy.intercept('GET', '**/api/animales', { statusCode: 200, body: { data: [] } }).as('getAnimales')

      cy.visit('/#/animales')
      cy.wait('@getAnimales', { timeout: 10000 })
      cy.get('h2', { timeout: 5000 }).should('contain', 'Animales Registrados')
    })

    it('navega a Aretes del Censo', () => {
      cy.intercept('GET', '**/api/aretes-censo', { statusCode: 200, body: { data: [] } }).as('getAretes')

      cy.visit('/#/aretes-censo')
      cy.wait('@getAretes', { timeout: 10000 })
      cy.get('h2', { timeout: 5000 }).should('contain', 'Aretes del Censo')
    })
  })

  describe('como Medico', () => {
    beforeEach(() => {
      cy.setLoginState({
        user: { id: 2, name: 'Dr. Campo', email: 'medico@sigdip.com', roles: ['Medico_Campo'] },
      })
    })

    it('ve enlaces del sidebar de medico (sin admin)', () => {
      cy.interceptDashboardStats()

      cy.visit('/#/dashboard')
      cy.get('.header-hamburger-btn').click()
      cy.get('.sidebar').should('be.visible')

      cy.get('.sidebar').contains('Dashboard').should('be.visible')
      cy.get('.sidebar').contains('Productores').should('be.visible')
      cy.get('.sidebar').contains('Predios').should('be.visible')
      cy.get('.sidebar').contains('Agenda / Visitas').should('be.visible')
      cy.get('.sidebar').contains('Inspecciones').should('be.visible')
      cy.get('.sidebar').contains('Nuevo Dictamen').should('be.visible')
      cy.get('.sidebar').contains('Descargas').should('be.visible')
      cy.get('.sidebar').contains('Sincronizar').should('be.visible')

      cy.get('.sidebar').contains('Médicos').should('not.exist')
      cy.get('.sidebar').contains('Animales').should('not.exist')
      cy.get('.sidebar').contains('Aretes del Censo').should('not.exist')
      cy.get('.sidebar').contains('Importar Excel').should('not.exist')
      cy.get('.sidebar').contains('Sábana Excel').should('not.exist')
    })

    it('navega a Dashboard y ve su nombre', () => {
      cy.interceptDashboardStats()

      cy.visit('/#/dashboard')
      cy.contains('Bienvenido', { timeout: 5000 }).should('be.visible')
      cy.contains('Dr. Campo').should('be.visible')
    })

    it('navega a Productores', () => {
      cy.seedIndexedDB('catalogos', 'predios', [])
      cy.visit('/#/productores')
      cy.get('h2', { timeout: 5000 }).should('contain', 'Productores')
    })

    it('navega a Predios', () => {
      cy.visit('/#/predios')
      cy.get('h2', { timeout: 5000 }).should('contain', 'Predios')
    })

    it('navega a Visitas', () => {
      cy.visit('/#/visitas')
      cy.get('h2', { timeout: 5000 }).should('contain', 'Agenda de Campo')
    })

    it('navega a Inspecciones', () => {
      cy.interceptEmptyInspecciones()

      cy.visit('/#/inspecciones')
      cy.wait('@getInspecciones', { timeout: 10000 })
      cy.get('h2', { timeout: 5000 }).should('contain', 'Inspecciones Pecuarias')
    })

    it('navega a Descargas', () => {
      cy.visit('/#/descargas')
      cy.get('h2', { timeout: 5000 }).should('be.visible')
    })

    it('navega a Sync', () => {
      cy.visit('/#/sync')
      cy.get('h1', { timeout: 5000 }).should('contain', 'Sincronización')
    })

    it('es redirigido al dashboard al visitar Medicos', () => {
      cy.intercept('GET', '**/api/medicos', {
        statusCode: 403,
        body: { message: 'No autorizado' },
      })

      cy.visit('/#/medicos')
      cy.location('hash', { timeout: 5000 }).should('eq', '#/dashboard')
    })

    it('es redirigido al dashboard al visitar Animales', () => {
      cy.visit('/#/animales')
      cy.location('hash', { timeout: 5000 }).should('eq', '#/dashboard')
    })

    it('es redirigido al dashboard al visitar Aretes del Censo', () => {
      cy.visit('/#/aretes-censo')
      cy.location('hash', { timeout: 5000 }).should('eq', '#/dashboard')
    })
  })

  describe('sin sesion', () => {
    it('redirige a login al intentar acceder a dashboard', () => {
      cy.visit('/#/dashboard')
      cy.location('hash').should('eq', '#/login')
    })

    it('redirige a login al intentar acceder a cualquier ruta protegida', () => {
      const rutas = ['/productores', '/predios', '/inspecciones', '/visitas', '/sync', '/medicos', '/descargas', '/inspeccion', '/animales', '/aretes-censo']
      rutas.forEach(ruta => {
        cy.visit('/#' + ruta)
        cy.location('hash').should('eq', '#/login')
      })
    })
  })
})
