var faker = require('faker');

describe('Signup', function () {
  // @TODO: get emails from Mailtrap with their API and click on the confirmation
  // link
  //
  // it('should sign up and logout', function () {
  //   cy.visit('/register')

  //   cy.get('input[name=email]').type('test@test.com')
  //   cy.get('input[name=first_name]').type('test')
  //   cy.get('input[name=last_name]').type('test')
  //   cy.get('input[name=password]').type('testtest')
  //   cy.get('input[name=password_confirmation]').type('testtest')

  //   cy.get('input[name=policy]').click()
  //   cy.get('button[type=submit]').click()

  //   cy.url().should('include', '/dashboard')

  //   cy.get('[data-cy=header-link-logout]').click()

  //   cy.contains('Login to your account')
  // })

  //it('should block registration if policy is not accepted', function () {
  //  cy.register(faker.name.firstName(), faker.name.lastName(), faker.internet.password(), faker.internet.email(), false);
  //  cy.get('.alert').should('exist');
  //});

  it('should block registration if email is already used', function () {
    const email = faker.internet.email();

    // test email address
    cy.register(faker.name.firstName(), faker.name.lastName(), faker.internet.password(), email, true);
    cy.get('.alert').should('not.exist');

    cy.get('[data-cy=header-link-logout]').click();

    cy.register(faker.name.firstName(), faker.name.lastName(), faker.internet.password(), email, true);
    cy.get('.alert').should('exist');
  });
});
