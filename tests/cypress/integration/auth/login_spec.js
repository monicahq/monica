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

describe('Login', function () {
  beforeEach(function () {
    cy.exec('php artisan setup:frontendtesting')
  })

  it('should not let user sign in with a non-existing account', function () {
    cy.visit('/')

    cy.get('input[name=email]').type('impossibru@test.com')
    cy.get('input[name=password]').type('testtest')
    cy.get('button[type=submit]').click()

    cy.get('.alert').should('exist')
  })

  it('should sign in into the application', function () {
    cy.visit('/')

    cy.get('input[name=email]').type('admin@admin.com')
    cy.get('input[name=password]').type('admin')
    cy.get('button[type=submit]').click()

    cy.url().should('include', '/dashboard')
  })
})
