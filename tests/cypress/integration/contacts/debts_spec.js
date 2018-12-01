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

describe('Debts', function () {
  beforeEach(function () {
    cy.login()
    cy.createContact('John', 'Doe', 'Man')
  })

  it('lets you manage a debt', function () {
    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=debt-blank-state]').should('be.visible')

    // add a debt
    cy.get('[cy-name=add-debt-button]').should('be.visible')
    cy.get('[cy-name=add-debt-button]').click()
    cy.url().should('include', '/debts/create')

    cy.get('[name=amount]').type('123')
    cy.get('[cy-name=save-debt-button]').click()

    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=debt-blank-state]').should('not.be.visible')
    cy.get('[cy-name=debt-item-1]').should('exist')
    cy.get('[cy-name=debt-item-1]').should('contain', '123')

    // edit a debt
    cy.get('[cy-name=edit-debt-button-1]').click()
    cy.url().should('include', '/edit')

    cy.get('[name=amount]').clear()
    cy.get('[name=amount]').type('234')
    cy.get('[cy-name=save-debt-button]').click()

    cy.get('[cy-name=debt-item-1]').should('exist')
    cy.get('[cy-name=debt-item-1]').should('contain', '234')

    // delete a debt
    cy.get('[cy-name=delete-debt-button-1]').click()
    cy.get('[cy-name=debt-blank-state]').should('be.visible')
    cy.get('[cy-name=debt-item-1]').should('not.exist')
  })
})
