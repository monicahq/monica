/// <reference types="cypress" />

context('Example', () => {
  it('get a page', () => {
    cy.visit('/');
    cy.window().should('have.property', 'top')
  })

  it('get the document object', () => {
    cy.visit('/');
    cy.document().should('have.property', 'charset').and('eq', 'UTF-8')
  })

  it('get the title', () => {
    cy.visit('/');
    cy.title().should('include', 'Laravel')
  })
})
