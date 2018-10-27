Cypress.Commands.add('createContact', (firstname, lastname, gender, action = 'save') => {
    cy.visit('/people')
    cy.get('#button-add-contact').click()

    cy.get('input[name=first_name]').type(firstname)
    cy.get('input[name=last_name]').type(lastname)
    cy.get('select[name=gender]').select(gender)

    cy.get('button[name=' + action + ']').click()
})

Cypress.Commands.add('createActivity', () => {
    cy.visit('/people')

    // this gets the first content of the list
    cy.get('li.people-list-item.bg-white.pointer').click()

    cy.get('[cy-name=add-activity-button]').should('be.visible')
    cy.get('[cy-name=add-activity-button]').click()
    cy.url().should('include', '/activities/add/h:')

    cy.get('[name=summary]').type('This is a summary')
    cy.get('[cy-name=save-activity-button]').click()

    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=activities-blank-state]').should('not.be.visible')
    cy.get('[cy-name=activity-body-1]').should('exist')
    cy.get('[cy-name=activity-body-1]').should('contain', 'This is a summary')
})
