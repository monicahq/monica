describe('Login', function () {
  beforeEach(function () {
    cy.exec('php artisan setup:frontendtesting')
  })

  it('should not let user sign in with a non-existing account', function () {
    cy.visit('/')

    cy.get('input[name=email]').type('impossibru@test.com')
    cy.get('input[name=password]').type('testtest')
    cy.get('button[type=submit]').click()

    cy.get('.alert').should('exist')
  })

  it('should sign in into the application', function () {
    cy.visit('/')

    cy.get('input[name=email]').type('admin@admin.com')
    cy.get('input[name=password]').type('admin')
    cy.get('button[type=submit]').click()

    cy.url().should('include', '/dashboard')
  })
})
