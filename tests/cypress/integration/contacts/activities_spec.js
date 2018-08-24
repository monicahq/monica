describe('Activities', function () {
  beforeEach(function () {
    cy.login()
    cy.createContact('John', 'Doe', 'Man')
  })

  it('lets you manage an activity', function () {
    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=activities-blank-state]').should('be.visible')

    // add an activity
    cy.createActivity()

    // edit an activity
    cy.get('[cy-name=edit-activity-button-1]').click()
    cy.url().should('include', '/activities/h:')

    cy.get('[name=summary]').clear()
    cy.get('[name=summary]').type('This is another summary')
    cy.get('[cy-name=save-activity-button]').click()

    cy.get('[cy-name=activity-body-1]').should('exist')
    cy.get('[cy-name=activity-body-1]').should('contain', 'This is another summary')

    // delete an activity
    cy.get('[cy-name=delete-activity-button-1]').click()
    cy.get('[cy-name=activities-blank-state]').should('be.visible')
    cy.get('[cy-name=activity-body-1]').should('not.exist')
  })
})
