var _ = require('lodash');

describe('Calls', function () {
  beforeEach(function () {
    cy.login();
    cy.createContact('John', 'Doe', 'Man');
  });

  it('lets you manage a call', function () {
    cy.url().should('include', '/people/h:');
    cy.get('[cy-name=calls-blank-state]').should('exist');
    cy.get('[cy-name=log-call-form]').should('not.be.visible');

    // add a call
    cy.get('[cy-name=add-call-button]').click();
    cy.get('[cy-name=log-call-form]').should('be.visible');
    cy.get('[cy-name=save-call-button]').click();
    cy.get('[cy-name=calls-blank-state]').should('not.exist');

    cy.get('[cy-name=calls-body]').should('be.visible')
      .invoke('attr', 'cy-items').then(function (items) {
        let item = _.last(items.split(','));

        cy.get('[cy-name=call-body-'+item+']').should('exist');
        cy.get('[cy-name=call-body-'+item+']').should('contain', 'John');

        // delete a call
        cy.get('[cy-name=delete-call-button-'+item+']').should('be.visible');
        cy.get('[cy-name=delete-call-button-'+item+']').click();
        cy.get('[cy-name=delete-call-confirm-button-'+item+']').should('be.visible');
        cy.get('[cy-name=delete-call-confirm-button-'+item+']').click();
        cy.get('[cy-name=calls-blank-state]').should('exist');
      });
  });
});
