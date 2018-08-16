describe('Tasks', function () {
  beforeEach(function () {
    cy.login()
    cy.createContact('John', 'Doe', 'Man')
  })

  it('lets you manage a task', function () {
    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=task-blank-state]').should('be.visible')

    // add a task
    cy.get('[cy-name=add-task-button]').click()
    cy.get('[cy-name=task-add-view]').should('be.visible')

    cy.get('[cy-name=task-add-title]').type('This is a task')
    cy.get('[cy-name=save-task-button]').click()
    cy.get('[cy-name=task-add-view]').should('not.be.visible')

    cy.get('[cy-name=task-item-1]').should('exist')
    cy.get('[cy-name=task-item-1]').should('contain', 'This is a task')

    // edit a task
    cy.get('[cy-name=task-toggle-edit-mode]').click()
    cy.get('[cy-name=task-delete-button-1]').click()

    cy.get('[cy-name=task-blank-state]').should('be.visible')
  })
})
