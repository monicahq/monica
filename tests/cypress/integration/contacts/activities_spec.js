describe('Activities', function () {
  beforeEach(function () {
    cy.login();
    cy.createContact('John', 'Doe', 'Man');
  });

  it('lets you manage an activity', function () {
    cy.url().should('include', '/people/h:');
    cy.get('[cy-name=activities-blank-state]').should('be.visible');

    // add an activity
    cy.createActivity();

    // edit an activity
    cy.get('[cy-name=activities-body]').should('be.visible')
      .invoke('attr', 'cy-items').then(function (item) {

        cy.get('[cy-name=edit-activity-button-'+item+']').click();

        cy.get('[name=summary]').clear();
        cy.get('[name=summary]').type('This is another summary');
        cy.get('[cy-name=save-activity-button]').click();

        cy.get('[cy-name=activity-body-'+item+']').should('exist');
        cy.get('[cy-name=activity-body-'+item+']').should('contain', 'This is another summary');

        // delete an activity
        cy.get('[cy-name=delete-activity-button-'+item+']').click();
        cy.wait(10);
        cy.get('[cy-name=confirm-delete-activity]').should('be.visible').click();
        cy.get('[cy-name=activities-blank-state]').should('be.visible');
        cy.get('[cy-name=activity-body-'+item+']').should('not.exist');
      });
  });
});
