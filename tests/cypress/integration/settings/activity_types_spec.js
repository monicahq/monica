describe('Settings: activity types', function () {
    it('doesn\'t let you manage activity types if user is not premium', function () {
        cy.login()
        cy.visit('/settings/personalization')
        cy.get('[cy-name=activity-type-premium-message]').should('be.visible')
        cy.get('[cy-name=activity-type-edit-button]').should('not.exist')
    })

    it('lets you manage an activity type category and activity type', function () {
        cy.login()

        // set account as premium
        cy.get('body').invoke('attr', 'data-account-id').then(function ($accountId) {
            cy.setPremium($accountId)
        })

        // make sure that going premium removes restrictions
        cy.visit('/settings/personalization')
        cy.get('[cy-name=activity-type-premium-message]').should('not.be.visible')
        cy.get('[cy-name=activity-types]').should('contain', 'did sport together')

        // add an activity type category
        cy.get('[cy-name=add-activity-category-type-button]').click()
        cy.get('.sweet-modal-overlay').should('be.visible')
        cy.get('[name=add-category-name]').type('This is an activity type category')
        cy.get('[cy-name=add-activity-type-category-button]').click()

        cy.get('[cy-name=activity-types]').should('contain', 'This is an activity type category')

        // edit an activity type category
        cy.get('[cy-name=activity-type-category-edit-button-5]').click()
        cy.get('.sweet-modal-overlay').should('be.visible')
        cy.get('[name=update-category-name]').clear()
        cy.get('[name=update-category-name]').type('This is still an activity type category')
        cy.get('[cy-name=update-activity-type-category-button]').click()
        cy.get('[cy-name=activity-types]').should('contain', 'This is still an activity type category')

        // add an activity type
        cy.get('[cy-name=add-activity-type-button-for-category-5]').click()
        cy.get('.sweet-modal-overlay').should('be.visible')
        cy.get('[name=add-type-name]').type('This is activity type 1')
        cy.get('[cy-name=add-type-button]').click()
        cy.get('[cy-name=activity-types]').should('contain', 'This is activity type 1')

        // mae sure the activity type exists on the Add activity page
        cy.createContact('John', 'Doe', 'Man')
        cy.get('[cy-name=add-activity-button]').click();
        cy.get('#activity_type_id').should('contain', 'This is activity type 1')

        // edit an activity type
        cy.visit('/settings/personalization')
        cy.get('[cy-name=activity-type-edit-button-14]').click()
        cy.get('.sweet-modal-overlay').should('be.visible')
        cy.get('[name=update-type-name]').clear()
        cy.get('[name=update-type-name]').type('This is modified activity type 1')
        cy.get('[cy-name=update-type-button]').click()
        cy.get('[cy-name=activity-types]').should('contain', 'This is modified activity type 1')

        // mae sure the modified activity type exists on the Add activity page
        cy.visit('/people')

        // this gets the first content of the list
        cy.get('li.people-list-item.bg-white.pointer').click()
        cy.get('[cy-name=add-activity-button]').click();
        cy.get('#activity_type_id').should('contain', 'This is modified activity type 1')

        // create another activity type to delete it right after
        cy.visit('/settings/personalization')
        cy.get('[cy-name=add-activity-type-button-for-category-5]').click()
        cy.get('.sweet-modal-overlay').should('be.visible')
        cy.get('[name=add-type-name]').type('This is activity type 2')
        cy.get('[cy-name=add-type-button]').click()
        cy.get('[cy-name=activity-type-delete-button-15]').click()
        cy.get('[cy-name=delete-type-button]').click()
        cy.get('[cy-name=activity-types]').should('not.contain', 'This is activity type 2')

        // now delete the activity type category and make sure it also deletes
        // the activity type that belonged to it
        cy.get('[cy-name=activity-type-category-delete-button-5]').click()
        cy.get('[cy-name=delete-category-button]').click()
        cy.get('[cy-name=activity-types]').should('not.contain', 'This is still an activity type category')
        cy.get('[cy-name=activity-types]').should('not.contain', 'This is modified activity type 1')
    })
})
