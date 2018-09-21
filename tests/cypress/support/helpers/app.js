Cypress.Commands.add('login', () => {
    cy.exec('php artisan setup:frontendtesting -vvv')

    cy.visit('/')

    cy.get('input[name=email]').type('admin@admin.com')
    cy.get('input[name=password]').type('admin')
    cy.get('button[type=submit]').click()

    cy.url().should('include', '/dashboard')
})

Cypress.Commands.add('setPremium', (accountId) => {
    cy.exec('php artisan account:setpremium ' + accountId)
})