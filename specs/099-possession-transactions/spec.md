# Feature Specification: Possession Transactions

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-10-26 | **Status**: Draft

**Input**: "add the transactions tab based on the design. add proper colors to sidebar icons. design
the model to represent the needed data. for the currency selector, use the attached screenshot."
Design: `Possession transactions.dc.html` (project 85e4275a); a screenshot of flags and codes, the chosen one ticked.

## Clarifications

### Session 2026-10-26
- Q: Transactions and the possession's own acquisition? → A: Transactions replace it (US5, FR-012).
- Q: A deleted transaction's documents? → A: Deleted with it, and the warning says so.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Record a transaction (Priority: P1)

Michael Scott records that he bought "Dundie award" from Dundies Inc. on 14 March 2005: $40, $3.20
tax, $6 shipping, invoice 4021.

1. **Given** the page, **When** Transactions is selected, **Then** it shows, marked current, at its own address.
2. **Given** "Add a transaction", **When** selected, **Then** a form opens above the list and the button hides.
3. **Given** the form, **Then** it asks for a type, grouped "Change of ownership" (purchase, sale,
   exchange, gift received, gift given, inheritance) and "Money only" (refund, fee, tax, shipping,
   other), a date, a counterparty, an amount, a tax, fees, shipping, a total, a currency, a
   reference, a source link and a note; only the type is required, and a currency once a figure is.
4. **Given** a type, **Then** the counterparty reads "Seller", "Buyer", "Exchanged with", "Given by",
   "Given to", "Inherited from", or "Counterparty" for money only types.
5. **Given** figures and no total, **Then** a line reads their sum and names them; a total stands "as entered".
6. **Given** the currency, **When** opened, **Then** each reads as flag and code, the chosen one ticked.
7. **Given** a valid form, **When** submitted, **Then** the transaction is listed and the form closes;
   a negative figure or a malformed link returns the form with the error beside the field.

### User Story 2 - Read what changed hands (Priority: P1)

Dwight Schrute reads when his beet press came to him, at what price, and what it has cost since.

1. **Given** transactions, **Then** a summary gives "Acquired" (date, how, counterparty), "Acquisition
   price" (its total, in its currency) and "Costs since" (money only transactions on or after it).
2. **Given** several ownership transactions, **Then** the acquisition is the earliest purchase,
   exchange, gift received or inheritance.
3. **Given** the list, **Then** it reads newest first; each entry gives its type and icon, an
   "Acquisition" mark, date, counterparty, total and how it was reached, the four figures, note,
   reference, source link, documents, and when it was added and last edited.
4. **Given** the list, **Then** filters "Ownership changes" (chosen first) and "All transactions" give counts.
5. **Given** the help mark, **When** opened, **Then** it says the first acquisition transaction gives
   the acquisition date and price, and editing it changes both.
6. **Given** the sections list, **Then** Transactions reads its count.

### User Story 3 - Correct or remove a transaction (Priority: P2)

Pam Beesly fixes the shipping on a frame, then deletes a duplicate fee.

1. **Given** Edit, **When** selected, **Then** the form opens filled, titled "Edit transaction", and the entry leaves the list meanwhile.
2. **Given** a saved edit, **Then** the list, the summary and the last edited date follow.
3. **Given** Delete, **When** selected, **Then** the entry warns that it is final, which transaction
   becomes the acquisition or that none remains, and that its documents go; "Keep it" changes nothing.

### User Story 4 - Documents (Priority: P3)

Angela Martin attaches a receipt in the add form or afterwards, and opens it later. Each reads its
name, kind and size; without any, the entry reads "No invoice or receipt attached yet."

### User Story 5 - Existing acquisitions become transactions (Priority: P2)

Kevin Malone recorded before this change that he bought his chili pot on 2 May 2019.

1. **Given** a possession recorded as bought, a gift, inherited or made, **When** deployed, **Then** it
   holds one purchase, gift received, inheritance or "other" transaction, on the recorded date or
   undated, the contact's name as counterparty; without a recorded acquisition it holds none.
2. **Given** the add form, **Then** it no longer asks how, when or from whom; the replacement value stays.
3. **Given** the Overview, **Then** its facts read the acquisition from the acquisition transaction.

### Edge Cases

- No acquisition transaction: the summary reads "Unknown" and invites adding one; "Costs since" reads nothing.
- No figure and no total: the total reads "Unknown". Undated: "Date unknown", sorted before every dated one.
- "Costs since" subtracts refunds, and leaves out other currencies saying how many.
- A sale or a gift given leaves the status alone. A money only one recorded under "Ownership changes" shows "All transactions".
- Another possession, vault or account answers not found. Uploads off: attaching is absent.
- Deleting the possession deletes its transactions and their documents.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A possession MUST hold any number of transactions, each reached only through it.
- **FR-002**: A transaction MUST record a type, a currency with any figure, and MAY record a date, a
  counterparty, four figures, a total, a reference, a source link and a note.
- **FR-003**: The eleven types MUST each be a change of ownership or money only; purchase, exchange,
  gift received and inheritance are acquisitions.
- **FR-004**: Money MUST be stored exactly, never below zero, with its currency, never converted.
- **FR-005**: A total MUST be the one entered, else the sum of the figures, else unknown, saying which.
- **FR-006**: Acquisition date, price and costs since MUST be computed from transactions, never copied.
- **FR-007**: The currency picker MUST list the product's currencies by flag and code, tick the
  chosen one, work by keyboard and announce each currency's full name.
- **FR-008**: Transactions MUST be an available section with its own address and count.
- **FR-009**: Section icons MUST take the design's colours in both themes: ink for Overview and
  Timeline, one identity colour each for the eight others, unbuilt ones still unavailable.
- **FR-010**: A transaction MUST hold documents, counted against storage, deleted with it.
- **FR-011**: Each change MUST be journalled, doable through the API and the assistant, and translated.
- **FR-012**: Each recorded acquisition MUST become one transaction without loss, and the possession
  MUST stop holding it on every screen, form, API answer and assistant tool.
- **FR-013**: The replacement value and its currency MUST stay on the possession, unchanged.

## Success Criteria *(mandatory)*

- **SC-001**: A purchase with its figures is recorded in under a minute, by keyboard alone if wished.
- **SC-002**: The acquisition shown always matches the transactions, and every acquisition recorded
  before the change reads the same after it.
- **SC-003**: Another possession's, vault's or account's transaction is never shown or changed.
- **SC-004**: 200 transactions open without a visible wait.

## Assumptions

- The ten currencies of the screenshot; historical ones (the design's francs) are out of scope.
- The counterparty is text; a converted acquisition keeps the contact's name, not the link.
- The acquisition is the earliest acquisition transaction; costs are summed in its currency.
