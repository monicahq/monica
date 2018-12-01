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

describe('Gifts', function () {
  beforeEach(function () {
    cy.login()
    cy.createContact('John', 'Doe', 'Man')
  })

  it('lets you manage a gift', function () {
    cy.url().should('include', '/people/h:')

    // add a gift
    cy.get('[cy-name=add-gift-button]').should('be.visible')
    cy.get('[cy-name=add-gift-button]').click()
    cy.url().should('include', '/gifts/create')

    cy.get('[name=name]').type('This is a gift')
    cy.get('[cy-name=save-gift-button]').click()

    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=gift-idea-item-1]').should('exist')
    cy.get('[cy-name=gift-idea-item-1]').should('contain', 'This is a gift')

    // edit a gift
    cy.get('[cy-name=edit-gift-button-1]').click()
    cy.url().should('include', '/gifts/1/edit')

    cy.get('[name=name]').clear()
    cy.get('[name=name]').type('This is another gift')
    cy.get('[cy-name=save-gift-button]').click()

    cy.get('[cy-name=gift-idea-item-1]').should('exist')
    cy.get('[cy-name=gift-idea-item-1]').should('contain', 'This is another gift')

    // delete an gift
    cy.get('[cy-name=delete-gift-button-1]').click()
    cy.get('[cy-name=modal-delete-gift-button-1]').click()
    cy.get('[cy-name=activities-blank-state]').should('be.visible')
    cy.get('[cy-name=gift-idea-item-1]').should('not.exist')
  })
})
