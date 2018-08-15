describe('Settings: activity types', function () {
    // it('doesn\'t let you manage activity types if user is not premium', function () {
    //     cy.login()
    //     cy.visit('/settings/personalization')
    //     cy.get('[cy-name=activity-type-premium-message]').should('be.visible')
    //     cy.get('[cy-name=activity-type-edit-button]').should('not.exist')
    // })

    it('lets you manage an activity type category and activity type', function () {
        cy.login()

        // set account as premium
        cy.get('body').invoke('attr', 'data-account-id').then(function ($accountId) {
            cy.setPremium($accountId)
        })

        cy.visit('/settings/personalization')
        cy.get('[cy-name=activity-type-premium-message]').should('not.be.visible')
        cy.get('[cy-name=activity-types]').should('contain', 'did sport together')
    })

    // // add an activity
    // cy.createActivity()

    // // edit an activity
    // cy.get('[cy-name=edit-activity-button-1]').click()
    // cy.url().should('include', '/activities/h:')

    // cy.get('[name=summary]').clear()
    // cy.get('[name=summary]').type('This is another summary')
    // cy.get('[cy-name=save-activity-button]').click()

    // cy.get('[cy-name=activity-body-1]').should('exist')
    // cy.get('[cy-name=activity-body-1]').should('contain', 'This is another summary')

    // // delete an activity
    // cy.get('[cy-name=delete-activity-button-1]').click()
    // cy.get('[cy-name=activities-blank-state]').should('be.visible')
    // cy.get('[cy-name=activity-body-1]').should('not.exist')
})
