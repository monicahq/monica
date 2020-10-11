/*

Gift page has change recently a lot, let rewrite this test later ...

###
describe('Gifts', function () {
  beforeEach(function () {
    cy.login();
    cy.createContact('John', 'Doe', 'Man');
  });

  it('lets you manage a gift', function () {
    cy.url().should('include', '/people/h:');

    // add a gift
    cy.get('[cy-name=add-gift-button]').should('be.visible');
    cy.get('[cy-name=add-gift-button]').click();
    cy.url().should('include', '/gifts/create');

    cy.get('[name=name]').type('This is a gift');
    cy.get('[cy-name=save-gift-button]').click();

    cy.url().should('include', '/people/h:');

    cy.get('[cy-name=gift-ideas-body]').should('be.visible')
      .invoke('attr', 'cy-items').then(function (item) {

        cy.get('[cy-name=gift-idea-item-'+item+']').should('exist');
        cy.get('[cy-name=gift-idea-item-'+item+']').should('contain', 'This is a gift');

        // edit a gift
        cy.get('[cy-name=edit-gift-button-'+item+']').click();
        cy.url().should('include', '/gifts/'+item+'/edit');

        cy.get('[name=name]').clear();
        cy.get('[name=name]').type('This is another gift');
        cy.get('[cy-name=save-gift-button]').click();

        cy.get('[cy-name=gift-idea-item-'+item+']').should('exist');
        cy.get('[cy-name=gift-idea-item-'+item+']').should('contain', 'This is another gift');

        // delete an gift
        cy.get('[cy-name=delete-gift-button-'+item+']').click();
        cy.get('[cy-name=modal-delete-gift-button-'+item+']').click();
        cy.get('[cy-name=activities-blank-state]').should('be.visible');
        cy.get('[cy-name=gift-idea-item-'+item+']').should('not.exist');
      });
  });
});
*/
