describe('Journal entries', function () {
  beforeEach(function () {
    cy.login();
    cy.createContact('John', 'Doe', 'Man');
  });

  it('lets you manage a journal entry', function () {
    cy.visit('/journal');
    cy.get('[cy-name=journal-blank-state]').should('be.visible');

    // add a journal entry
    cy.get('[cy-name=add-entry-button]').should('be.visible');
    cy.get('[cy-name=add-entry-button]').click();
    cy.url().should('include', '/journal/add');

    cy.get('[name=entry]').type('This is an entry');
    cy.get('[cy-name=save-entry-button]').click();

    cy.url().should('include', '/journal');
    cy.get('[cy-name=journal-blank-state]').should('not.be.visible');

    cy.get('[cy-name=journal-entries-body]').should('be.visible')
      .invoke('attr', 'cy-items').then(function (item) {
        cy.get('[cy-name=journal-entries-body]')
          .invoke('attr', 'cy-object-items').then(function (objItem) {

            cy.get('[cy-name=entry-body-'+item+']').should('exist');
            cy.get('[cy-name=entry-body-'+item+']').should('contain', 'This is an entry');

            // delete a journal entry
            cy.get('[cy-name=entry-delete-button-'+objItem+']').click();
            cy.url().should('include', '/journal');
            cy.get('[cy-name=entry-body-'+item+']').should('not.exist');
          });
      });
  });

  it('creates a journal entry when creating an activity', function () {
    cy.createActivity();

    cy.visit('/journal');

    cy.get('[cy-name=journal-blank-state]').should('not.be.visible');

    cy.get('[cy-name=journal-entries-body]').should('be.visible').then((entries) => {
      let item = entries[0].getAttribute('cy-items');
      let objItem = entries[0].getAttribute('cy-object-items');

      cy.get('[cy-name=entry-body-'+item+']').should('exist');
      cy.get('[cy-name=entry-body-'+item+']').should('contain', 'This is a summary');
      cy.get('[cy-name=entry-delete-button-'+objItem+']').should('not.exist');
    });
  });

  it('lets you rate your day', function () {
    cy.visit('/journal');

    cy.get('[cy-name=journal-blank-state]').should('be.visible');

    cy.get('[cy-name=sad-reaction-button]').click();
    cy.wait(10);

    cy.get('[cy-name=comment]').should('be.visible');
    cy.get('[cy-name=save-entry-button]').click();

    cy.get('[cy-name=journal-entries-body]').should('be.visible').then((entries) => {
      let item = entries[0].getAttribute('cy-items');

      cy.get('[cy-name=entry-body-'+item+']').should('exist');
      cy.get('[cy-name=entry-delete-button-'+item+']').should('exist');
    });
  });
});
