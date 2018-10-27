describe('Contacts', function () {
  beforeEach(function () {
    cy.login()
  })

  it('lets you add a contact', function () {
    cy.createContact('John', 'Doe', 'Man')
    cy.url().should('include', '/people/h:')
    cy.get('h3').should('contain', 'John Doe')
  })

  it('lets you add two contacts in a row', function () {
    cy.createContact('John', 'Doe', 'Man', 'save_and_add_another')
    cy.url().should('include', '/people/add')
  })

  it('requires at least a firstname and a gender to add a contact', function () {
    cy.visit('/people')
    cy.get('#button-add-contact').click()
    cy.get('button[name=save]').click()
    cy.url().should('include', '/people/add')

    cy.get('input[name=first_name]').type('John')
    cy.get('button[name=save]').click()
    cy.url().should('include', '/people/add')

    cy.get('select[name=gender]').select('Man')
    cy.get('button[name=save]').click()

    cy.url().should('include', '/people/h:')
    cy.get('h3').should('contain', 'John')
  })

  it('lets you edit a contact', function () {
    cy.createContact('John', 'Doe', 'Man')

    cy.get('#button-edit-contact').click();
    cy.url().should('include', '/edit')

    cy.get('input[name=firstname]').should('have.value', 'John')
    cy.get('input[name=lastname]').should('have.value', 'Doe')
    cy.get('select[name=gender]').should('have.value', '1')

    cy.get('input[name=firstname]').clear()
    cy.get('input[name=firstname]').type('Jane')
    cy.get('input[name=lastname]').clear()
    cy.get('button[name=save]').click()

    cy.url().should('include', '/people/h:')
    cy.get('h3').should('contain', 'Jane')
  })

  it('lets you delete a contact', function () {
    cy.createContact('John', 'Doe', 'Man')

    cy.visit('/people')
    cy.get('.people-list-item-name').should('contain', 'John Doe')

    // this gets the first content of the list
    cy.get('li.people-list-item.bg-white.pointer').click()

    cy.get('#link-delete-contact').click()

    // cypress auto accepts window alerts (confirm or alert)
    cy.url().should('include', '/people')

    cy.visit('/people')
    cy.get('.people-list-item-name').should('not.exist')
  })

  it('lets you add a contact as favorite', function () {
    cy.createContact('John', 'Doe', 'Man')

    cy.visit('/people')

    // this gets the first content of the list
    cy.get('li.people-list-item.bg-white.pointer').click()

    // tests if the favorite button can be toggled
    cy.get('[cy-name=set-favorite]').should('be.visible')
    cy.get('[cy-name=set-favorite]').click()
    cy.get('[cy-name=set-favorite]').should('not.be.visible')
    cy.get('[cy-name=unset-favorite]').should('be.visible')
    cy.get('[cy-name=unset-favorite]').click()
    cy.get('[cy-name=set-favorite]').should('be.visible')

    // test to see if a contact appears on top of the contact list if favorited
    cy.get('[cy-name=set-favorite]').click()
    cy.visit('/dashboard')
    cy.createContact('Abc', 'Abc', 'Man')
    cy.visit('/people')

    cy.get('.people-list-item-name span').should('contain', 'John Doe')
    cy.get('.people-list-item-name svg').should('be.visible')

    cy.get('.list > li:nth-child(3) > span.people-list-item-name').should('contain', 'Abc Abc')
  })
})
