describe('Login', function () {
  it('should not let user sign in with a non-existing account', function () {
    cy.visit('/');

    cy.get('input[name=email]').type('impossibru@test.com');
    cy.get('input[name=password]').type('testtest');
    cy.get('button[type=submit]').click();

    cy.get('.alert').should('exist');
  });
});
