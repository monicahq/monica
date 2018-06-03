Cypress.Commands.add('createContact', (firstname, lastname, gender, action = 'save') => {
    cy.visit('/people')
    cy.get('#button-add-contact').click()

    cy.get('input[name=first_name]').type(firstname)
    cy.get('input[name=last_name]').type(lastname)
    cy.get('select[name=gender]').select(gender)

    cy.get('button[name=' + action + ']').click()
})
