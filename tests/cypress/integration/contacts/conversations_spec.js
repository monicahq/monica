describe('Conversations', function () {
  beforeEach(function () {
    cy.login()
    cy.createContact('John', 'Doe', 'Man')
  })

  it('lets you manage a conversation', function () {
    cy.get('[cy-name=conversation-blank-state]').should('be.visible')

    // add a conversation
    cy.visit('/people')

    // this gets the first content of the list
    cy.get('li.people-list-item.bg-white.pointer').click()

    cy.get('[cy-name=add-conversation-button]').should('be.visible')
    cy.get('[cy-name=add-conversation-button]').click()
    cy.url().should('include', '/conversations/create')

    cy.get('[name=contactFieldTypeId]').select('3')
    cy.get('[name=content_1]').type('This is a message')
    cy.get('[cy-name=save-conversation-button]').click()

    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=conversation-blank-state]').should('not.be.visible')
  })
})
