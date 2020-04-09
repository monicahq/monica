Cypress.Commands.add('login', () => {
  cy.exec('php artisan setup:frontendtestuser').then((result) => {
    cy.visit('/_dusk/login/'+result.stdout+'/');
  });
});

Cypress.Commands.add('setPremium', (accountId) => {
  cy.exec('php artisan account:setpremium ' + accountId);
});

Cypress.Commands.add('register', (firstName, lastName, password, email, policy) => {
  cy.visit('/register');

  cy.get('.alert').should('not.exist');
  cy.get('input[name=email]').type(email);
  cy.get('input[name=first_name]').type(firstName);
  cy.get('input[name=last_name]').type(lastName);
  cy.get('input[name=password]').type(password);
  cy.get('input[name=password_confirmation]').type(password);
  if (policy) {
    cy.get('input[name=policy]').click();
  }
  cy.get('button[type=submit]').click();
});
