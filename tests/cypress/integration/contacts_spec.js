describe('Contacts', function () {
  it('lets you add a contact', function () {

    cy.request('POST', '/login', {
      email: 'admin@admin.com',
      password: 'admin'
    })

    cy.visit('/dashboard')

    cy.url().should('include', '/dashboard')
  })
})
