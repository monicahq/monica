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

describe('Calls', function () {
  beforeEach(function () {
    cy.login()
    cy.createContact('John', 'Doe', 'Man')
  })

  it('lets you manage a call', function () {
    cy.url().should('include', '/people/h:')
    cy.get('[cy-name=calls-blank-state]').should('exist')
    cy.get('#logCallModal').should('not.be.visible')

    // add a call
    cy.get('[cy-name=add-call-button]').click()
    cy.get('#logCallModal').should('be.visible')
    cy.get('[cy-name=save-call-button]').click()
    cy.get('[cy-name=calls-blank-state]').should('not.exist')
    cy.get('[cy-name=call-body-1]').should('exist')
    cy.get('[cy-name=call-body-1]').should('contain', 'John')

    // delete a call
    cy.get('[cy-name=edit-call-button-1]').should('be.visible')
    cy.get('[cy-name=edit-call-button-1]').click()
    cy.get('[cy-name=calls-blank-state]').should('exist')
  })
})
