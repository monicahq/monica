var _ = require('lodash');

describe('Settings: activity types', function () {
  it('doesn\'t let you manage activity types if user is not premium', function () {
    cy.login();
    cy.visit('/settings/personalization');
    cy.get('[cy-name=activity-type-premium-message]').should('be.visible');
    cy.get('[cy-name=activity-type-edit-button]').should('not.exist');
  });

  it('lets you manage an activity type category and activity type', function () {
    cy.login();
    cy.visit('/');

    // set account as premium
    cy.get('body').invoke('attr', 'data-account-id').then(function ($accountId) {
      cy.setPremium($accountId);
    });

    // make sure that going premium removes restrictions
    cy.visit('/settings/personalization');
    cy.get('[cy-name=activity-type-premium-message]').should('not.be.visible');
    cy.get('[cy-name=activity-types]').should('contain', 'played a sport together');

    // add an activity type category
    cy.get('[cy-name=add-activity-type-category-button]').click();
    cy.get('.sweet-modal-overlay').should('be.visible');
    cy.get('[name=add-category-name]').type('This is an activity type category');
    cy.get('[cy-name=add-activity-type-category-save-button]').click();
    cy.wait(10);

    cy.get('[cy-name=activity-types]').should('contain', 'This is an activity type category');
    cy.get('[cy-name=activity-type-categories]').invoke('attr', 'cy-items').then((items) => {
      let item = _.last(items.split(','));

      // edit an activity type category
      cy.get('[cy-name=activity-type-category-edit-button-'+item+']').click();
      cy.get('.sweet-modal-overlay').should('be.visible');
      cy.get('[name=update-category-name]').clear();
      cy.get('[name=update-category-name]').type('This is still an activity type category');
      cy.get('[cy-name=update-activity-type-category-button]').click();
      cy.get('[cy-name=activity-types]').should('contain', 'This is still an activity type category');

      // add an activity type
      cy.get('[cy-name=add-activity-type-button-for-category-'+item+']').click();
      cy.get('.sweet-modal-overlay').should('be.visible');
      cy.get('[name=add-type-name]').type('This is activity type 1');
      cy.get('[cy-name=add-type-button]').click();
      cy.get('[cy-name=activity-types]').should('contain', 'This is activity type 1');

      // edit an activity type
      cy.get('[cy-name=activity-types-'+item+']').invoke('attr', 'cy-items').then((items) => {
        let aitem = _.last(items.split(','));

        cy.get('[cy-name=activity-type-edit-button-'+aitem+']').click();
        cy.get('.sweet-modal-overlay').should('be.visible');
        cy.get('[name=update-type-name]').clear();
        cy.get('[name=update-type-name]').type('This is modified activity type 1');
        cy.get('[cy-name=update-type-button]').click();
        cy.wait(10);
        cy.get('[cy-name=activity-types]').should('contain', 'This is modified activity type 1');
      });

      // delete an activity type
      cy.get('[cy-name=add-activity-type-button-for-category-'+item+']').click();
      cy.get('.sweet-modal-overlay').should('be.visible');
      cy.get('[name=add-type-name]').type('This is activity type 2');
      cy.get('[cy-name=add-type-button]').click();
      cy.get('[cy-name=activity-types]').should('contain', 'This is activity type 2');

      cy.get('[cy-name=activity-types-'+item+']').invoke('attr', 'cy-items').then((items) => {
        let aitem = _.last(items.split(','));

        cy.get('[cy-name=activity-type-delete-button-'+aitem+']').click();
        cy.get('[cy-name=delete-type-button]').click();
        cy.get('[cy-name=activity-types]').should('not.contain', 'This is activity type 2');
      });

      // now delete the activity type category and make sure it also deletes
      // the activity type that belonged to it
      cy.get('[cy-name=activity-type-category-delete-button-'+item+']').click();
      cy.get('[cy-name=delete-category-button]').click();
      cy.get('[cy-name=activity-types]').should('not.contain', 'This is still an activity type category');
      cy.get('[cy-name=activity-types]').should('not.contain', 'This is modified activity type 1');
    });
  });

  it('lets you add an activity type and use it', function () {
    cy.login();
    cy.visit('/');

    // set account as premium
    cy.get('body').invoke('attr', 'data-account-id').then(function ($accountId) {
      cy.setPremium($accountId);
    });

    // make sure that going premium removes restrictions
    cy.visit('/settings/personalization');
    cy.get('[cy-name=activity-type-premium-message]').should('not.be.visible');
    cy.get('[cy-name=activity-types]').should('contain', 'played a sport together');

    // add an activity type category
    cy.get('[cy-name=add-activity-type-category-button]').click();
    cy.get('.sweet-modal-overlay').should('be.visible');
    cy.get('[name=add-category-name]').type('This is an activity type category');
    cy.get('[cy-name=add-activity-type-category-save-button]').click();

    cy.get('[cy-name=activity-types]').should('contain', 'This is an activity type category');
    cy.get('[cy-name=activity-type-categories]').invoke('attr', 'cy-items').then((items) => {
      let item = _.last(items.split(','));

      // edit an activity type category
      cy.get('[cy-name=activity-type-category-edit-button-'+item+']').click();
      cy.get('.sweet-modal-overlay').should('be.visible');
      cy.get('[name=update-category-name]').clear();
      cy.get('[name=update-category-name]').type('This is still an activity type category');
      cy.get('[cy-name=update-activity-type-category-button]').click();
      cy.get('[cy-name=activity-types]').should('contain', 'This is still an activity type category');

      // add an activity type
      cy.get('[cy-name=add-activity-type-button-for-category-'+item+']').click();
      cy.get('.sweet-modal-overlay').should('be.visible');
      cy.get('[name=add-type-name]').type('This is activity type 1');
      cy.get('[cy-name=add-type-button]').click();
      cy.get('[cy-name=activity-types]').should('contain', 'This is activity type 1');

      // make sure the activity type exists on the Add activity page
      cy.createContact('John', 'Doe', 'Man');
      cy.get('[cy-name=add-activity-button]').click();
      cy.get('[cy-name=activities_add_category]').click();
      cy.get('[name=activity-type-list]').should('contain', 'This is activity type 1');
    });
  });
});
