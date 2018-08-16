describe('Calls', function () {
  beforeEach(function () {
    cy.login()
    cy.createContact('John', 'Doe', 'Man')
  })

  it('lets you manage a call', function () {
    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=calls-blank-state]').should('exist')
    cy.get('#logCallModal').should('not.be.visible')

    // add a call
    cy.get('[cy-name=add-call-button]').click()
    cy.get('#logCallModal').should('be.visible')
    cy.get('[cy-name=save-call-button]').click()
    cy.get('[cy-name=calls-blank-state]').should('not.exist')
    cy.get('[cy-name=call-body-1]').should('exist')
    cy.get('[cy-name=call-body-1]').should('contain', 'John')

    // delete a call
    cy.get('[cy-name=edit-call-button-1]').should('be.visible')
    cy.get('[cy-name=edit-call-button-1]').click()
    cy.get('[cy-name=calls-blank-state]').should('exist')
  })
})
