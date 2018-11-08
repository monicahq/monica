describe('Gifts', function () {
  beforeEach(function () {
    cy.login()
    cy.createContact('John', 'Doe', 'Man')
  })

  it('lets you manage a gift', function () {
    cy.url().should('include', '/people/h:')

    // add a gift
    cy.get('[cy-name=add-gift-button]').should('be.visible')
    cy.get('[cy-name=add-gift-button]').click()
    cy.url().should('include', '/gifts/create')

    cy.get('[name=name]').type('This is a gift')
    cy.get('[cy-name=save-gift-button]').click()

    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=gift-idea-item-1]').should('exist')
    cy.get('[cy-name=gift-idea-item-1]').should('contain', 'This is a gift')

    // edit a gift
    cy.get('[cy-name=edit-gift-button-1]').click()
    cy.url().should('include', '/gifts/1/edit')

    cy.get('[name=name]').clear()
    cy.get('[name=name]').type('This is another gift')
    cy.get('[cy-name=save-gift-button]').click()

    cy.get('[cy-name=gift-idea-item-1]').should('exist')
    cy.get('[cy-name=gift-idea-item-1]').should('contain', 'This is another gift')

    // delete an gift
    cy.get('[cy-name=delete-gift-button-1]').click()
    cy.get('[cy-name=modal-delete-gift-button-1]').click()
    cy.get('[cy-name=activities-blank-state]').should('be.visible')
    cy.get('[cy-name=gift-idea-item-1]').should('not.exist')
  })
})
