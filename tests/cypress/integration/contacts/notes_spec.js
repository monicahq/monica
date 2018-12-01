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

describe('Notes', function () {
  beforeEach(function () {
    cy.login()
    cy.createContact('John', 'Doe', 'Man')
  })

  it('lets you manage a note', function () {
    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=add-note-button]').should('not.be.visible')

    // add a note
    cy.get('[cy-name=add-note-textarea]').click()
    cy.get('[cy-name=add-note-button]').should('be.visible')

    cy.get('[cy-name=add-note-textarea]').type('This is a note')
    cy.get('[cy-name=add-note-button]').click()

    cy.get('[cy-name=note-body-1]').should('contain', 'This is a note')

    cy.get('[cy-name=edit-note-body-1]').should('not.be.visible')

    // edit a note
    cy.get('[cy-name=edit-note-button-1]').click()
    cy.get('[cy-name=edit-note-body-1]').should('be.visible')

    cy.get('[cy-name=edit-note-body-1]').clear()
    cy.get('[cy-name=edit-note-body-1]').type('This is another note')
    cy.get('[cy-name=edit-mode-note-button-1]').click()

    cy.get('[cy-name=edit-note-body-1]').should('not.be.visible')
    cy.get('[cy-name=note-body-1]').should('contain', 'This is another note')

    // delete a note
    cy.get('#modal-delete-note').should('not.be.visible')
    cy.get('[cy-name=delete-note-button-1]').click()
    cy.get('#modal-delete-note').should('be.visible')
    cy.get('[cy-name=delete-mode-note-button-1]').click()

    cy.get('[cy-name=note-body-1]').should('not.exist')
  })
})
