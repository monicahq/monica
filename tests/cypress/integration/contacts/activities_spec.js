/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/

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
