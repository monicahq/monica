describe('Notes', function () {
  beforeEach(function () {
    cy.login()
    cy.createContact('John', 'Doe', 'Man')
  })

  it('lets you manage a note', function () {
    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=add-note-button]').should('not.be.visible')

    // add a note
    cy.get('[cy-name=add-note-textarea]').click()
    cy.get('[cy-name=add-note-button]').should('be.visible')

    cy.get('[cy-name=add-note-textarea]').type('This is a note')
    cy.get('[cy-name=add-note-button]').click()

    cy.get('[cy-name=note-body-1]').should('contain', 'This is a note')

    cy.get('[cy-name=edit-note-body-1]').should('not.be.visible')

    // edit a note
    cy.get('[cy-name=edit-note-button-1]').click()
    cy.get('[cy-name=edit-note-body-1]').should('be.visible')

    cy.get('[cy-name=edit-note-body-1]').clear()
    cy.get('[cy-name=edit-note-body-1]').type('This is another note')
    cy.get('[cy-name=edit-mode-note-button-1]').click()

    cy.get('[cy-name=edit-note-body-1]').should('not.be.visible')
    cy.get('[cy-name=note-body-1]').should('contain', 'This is another note')

    // delete a note
    cy.get('#modal-delete-note').should('not.be.visible')
    cy.get('[cy-name=delete-note-button-1]').click()
    cy.get('#modal-delete-note').should('be.visible')
    cy.get('[cy-name=delete-mode-note-button-1]').click()

    cy.get('[cy-name=note-body-1]').should('not.exist')
  })
})
