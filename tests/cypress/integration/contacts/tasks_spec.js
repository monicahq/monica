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
