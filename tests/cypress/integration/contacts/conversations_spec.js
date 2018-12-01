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

describe('Conversations', function () {
  beforeEach(function () {
    cy.login()
    cy.createContact('John', 'Doe', 'Man')
  })

  it('lets you manage a conversation', function () {
    cy.get('[cy-name=conversation-blank-state]').should('be.visible')

    // add a conversation
    cy.visit('/people')

    // this gets the first content of the list
    cy.get('li.people-list-item.bg-white.pointer').click()

    cy.get('[cy-name=add-conversation-button]').should('be.visible')
    cy.get('[cy-name=add-conversation-button]').click()
    cy.url().should('include', '/conversations/create')

    cy.get('[name=contactFieldTypeId]').select('3')
    cy.get('[name=content_1]').type('This is a message')
    cy.get('[cy-name=save-conversation-button]').click()

    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=conversation-blank-state]').should('not.be.visible')
  })
})
