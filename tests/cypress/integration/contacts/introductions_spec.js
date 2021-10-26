describe('Introduction', function () {
  beforeEach(function () {
    cy.login();
    cy.createContact('John', 'Doe', 'Man');
    cy.createContact('Jane', 'Doe', 'Woman');
    cy.createContact('Joe', 'Shmoe', 'Man');
  });

  it('lets you fill first met without an introducer', function () {
    cy.url().should('include', '/people/h:');

    cy.get('.introductions a[href$="introductions/edit"]').click();
    cy.url().should('include', '/introductions/edit');

    cy.get('textarea[name=first_met_additional_info]').type('Lorem ipsum');
    cy.get('button.btn-primary[type=submit]').click();

    cy.url().should('include', '/people/h:');
    cy.get('.alert-success');
    cy.get('.introductions').contains('Lorem ipsum');
  });

  it('lets you save first met', function () {
    cy.url().should('include', '/people/h:');

    cy.get('.introductions a[href$="introductions/edit"]').click();
    cy.url().should('include', '/introductions/edit');

    cy.get('textarea[name=first_met_additional_info]').type('Lorem ipsum');
    cy.get('#metThrough > .v-select input').click();
    cy.get('#metThrough ul[role="listbox"]').contains('John Doe');
    cy.get('#metThrough ul[role="listbox"]').contains('Jane Doe');
    cy.get('#metThrough ul[role="listbox"]').contains('Joe Shmoe');

    cy.get('#metThrough ul[role="listbox"]').contains('John Doe').click();

    cy.get('button.btn-primary[type=submit]').click();

    cy.url().should('include', '/people/h:');
    cy.get('.alert-success');
    cy.get('.introductions').contains('Lorem ipsum');
    cy.get('.introductions').contains('John Doe');
  });

  it('lets you search first met', function () {
    cy.url().should('include', '/people/h:');

    cy.get('.introductions a[href$="introductions/edit"]').click();
    cy.url().should('include', '/introductions/edit');

    cy.get('textarea[name=first_met_additional_info]').type('Lorem ipsum');
    cy.get('#metThrough input[type=search]').type('John');
    cy.get('#metThrough ul[role="listbox"]').contains('John Doe');
    cy.get('#metThrough ul[role="listbox"]').should('not.contain', 'Joe Shmoe');
    cy.get('#metThrough ul[role="listbox"]').should('not.contain', 'Jane Doe');

    cy.get('#metThrough ul[role="listbox"]').contains('John Doe').click();
    cy.get('button.btn-primary[type=submit]').click();

    cy.url().should('include', '/people/h:');
    cy.get('.introductions').contains('Lorem ipsum');
    cy.get('.introductions').contains('John Doe');
  });

});
