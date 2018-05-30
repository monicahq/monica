describe('Login', function () {
  beforeEach(function () {
    cy.exec('php artisan setup:frontendtesting')
  })

  it('should sign in into the application', function () {
    cy.visit('/')
    cy.get('button[type=submit]').should('contain', 'Login')
    cy.get('[data-cy=login-input-email]').type('admin@admin.com')
    cy.get('[data-cy=login-input-password]').type('admin')
    cy.get('[data-cy=login-button-submit]').click()

    cy.url().should('include', '/dashboard')
  })
})
