Cypress.Commands.add('login', () => {
    cy.exec('php artisan setup:frontendtestuser').then((result) => {
        cy.visit('/_dusk/login/'+result.stdout+'/')
    });
})

Cypress.Commands.add('setPremium', (accountId) => {
    cy.exec('php artisan account:setpremium ' + accountId)
})