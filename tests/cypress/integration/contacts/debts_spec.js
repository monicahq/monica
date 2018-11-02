describe('Debts', function () {
  beforeEach(function () {
    cy.login()
    cy.createContact('John', 'Doe', 'Man')
  })

  it('lets you manage a debt', function () {
    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=debt-blank-state]').should('be.visible')

    // add a debt
    cy.get('[cy-name=add-debt-button]').should('be.visible')
    cy.get('[cy-name=add-debt-button]').click()
    cy.url().should('include', '/debts/create')

    cy.get('[name=amount]').type('123')
    cy.get('[cy-name=save-debt-button]').click()

    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=debt-blank-state]').should('not.be.visible')
    cy.get('[cy-name=debt-item-1]').should('exist')
    cy.get('[cy-name=debt-item-1]').should('contain', '123')

    // edit a debt
    cy.get('[cy-name=edit-debt-button-1]').click()
    cy.url().should('include', '/edit')

    cy.get('[name=amount]').clear()
    cy.get('[name=amount]').type('234')
    cy.get('[cy-name=save-debt-button]').click()

    cy.get('[cy-name=debt-item-1]').should('exist')
    cy.get('[cy-name=debt-item-1]').should('contain', '234')

    // delete a debt
    cy.get('[cy-name=delete-debt-button-1]').click()
    cy.get('[cy-name=debt-blank-state]').should('be.visible')
    cy.get('[cy-name=debt-item-1]').should('not.exist')
  })
})
