describe('Signup', function () {
  beforeEach(function () {
    cy.exec('php artisan setup:frontendtesting')
  })

  it('should sign up and logout', function () {
    cy.visit('/register')
    cy.get('[data-cy=signup-input-email]').type('test@test.com')
    cy.get('[data-cy=signup-input-firstname]').type('test')
    cy.get('[data-cy=signup-input-lastname]').type('test')
    cy.get('[data-cy=signup-input-password]').type('testTtest')
    cy.get('[data-cy=signup-input-password-confirmation]').type('testTtest')
    cy.get('[data-cy=signup-checkbox-policy]').click()
    cy.get('[data-cy=signup-button-submit]').click()

    cy.url().should('include', '/dashboard')

    cy.get('[data-cy=header-link-logout]').click()

    cy.contains('Login to your account')
  })
})
