# Feature Specification: Possession Loans

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-11-12 | **Status**: Draft

**Input**: "manage loans for a possession … choose a contact from our contacts." Design: `Possession loans.dc.html`.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Lend a possession to a contact (Priority: P1)

Michael Scott lends his World's Best Boss mug to Dwight Schrute, who is already in his contacts.

1. **Given** the possession page, **When** Loans is selected, **Then** it shows at its own address with
   its loan count, a help mark, a line saying an active loan means the possession is not in your
   hands, and "Add a loan".
2. **Given** "Add a loan", **When** selected, **Then** a form titled "New loan" opens above the list
   and the button hides. It asks for "Direction" ("Lent out", "Borrowed in"), "Status" ("Planned",
   "Active", "Returned", Active by default), "Contact", "Started on" (today by
   default), "Due back", and, all optional, "Condition when it left", a
   deposit with its currency, "Purpose", and "Add to the provenance".
3. **Given** the contact field, **When** focused, **Then** it lists the contacts of the possession's
   vault; typing filters them by name. It is the vault's usual record search, used by keyboard alone,
   and a picked contact replaces the field with its name and "Choose another".
4. **Given** a form, **Then** a line says what will be recorded, without dates: "Dwight Schrute has
   the possession now.", "You will have the possession from Dwight Schrute." when planned, whether a
   return day is agreed, and "Choose who has the possession to see a summary of this loan." until a
   contact is chosen.
5. **Given** no contact, no start day, or a due day before the start, **When** submitted, **Then** the
   form returns with "Choose a contact, or create a new one.", "Say when the loan started." or "The due
   date is before the loan started." beside the field.
6. **Given** a valid form, **When** "Add loan" is pressed, **Then** the loan is saved, the form closes
   and the list and count follow. "Cancel" changes nothing. No transaction is recorded.

### User Story 2 - See who has it and mark it returned (Priority: P1)

Pam Beesly checks whether the office camera is back, then marks it returned.

1. **Given** an active loan, **Then** a panel above the list shows the contact's initials, "Dwight
   Schrute has had the mug since 12 June 2024." (or "You have had it from … since …"), its due line
   ("Due back 15 June 2024, in 3 days", "Due back today", "Was due back 15 June 2024, 4 days ago", "No
   return date agreed") and "Mark as returned". Without one, it reads "Not lent to anyone right now.".
2. **Given** loans, **Then** "Every loan" lists them newest first under "2 loans, 1 lent out". Each row
   gives "Lent to" or "Borrowed from", the contact's name linking to their page, a status ("Planned",
   "With them now", "Overdue" once an active loan is past its due day, "Returned"), "In the provenance"
   when linked, its period and length or due line, its purpose, and condition out, condition back or
   due back, and deposit, each "Not recorded" when empty.
3. **Given** "Mark as returned", **When** selected, **Then** the edit form opens with status Returned,
   "Returned on" set to today and "Condition when it came back"; saving closes the active loan.
4. **Given** a returned loan without a return day, or one before the start, **When** saved, **Then** the
   form returns with "Say when it came back." or "It came back before it left.".
5. **Given** no loan, **Then** an empty state says to record it here each time the possession leaves
   your hands without changing owner.

### User Story 3 - Edit or delete a loan (Priority: P2)

Andy Bernard fixes the day he lent the banjo; Kevin Malone deletes a loan he never made.

1. **Given** "Edit", **Then** the form opens titled "Edit loan", filled, with "Save changes". **Given** "Delete", **Then** the row asks "Delete the loan to Dwight Schrute of 12 June 2024?", says
   it is final, that the contact stays in your contacts, that an active loan's possession shows as at
   home, and that its two provenance events go with it when linked. "Keep it" changes nothing.

### User Story 4 - Create the contact from the loan (Priority: P3)
Oscar Martinez lends his tax guide to a man from the warehouse who is not in his contacts yet.

1. **Given** a search matching nobody, **Then** the list says "Nobody called “Nate” in your contacts."
   and offers "Create “Nate” as a new contact".
2. **Given** that offer, **Then** the field becomes "First name" (required, from the search), "Last
   name", a "New contact" badge, "Search your contacts instead", and a line
   saying the contact is added to the vault's people when the loan is saved.
3. **Given** no first name, **When** submitted, **Then** "Give the new contact at least a first name.";
   otherwise the contact and the loan are created together, or neither.

### User Story 5 - The loan on the contact's page and in the provenance (Priority: P3)
1. **Given** a contact holding or having held a possession, **Then** their page lists those loans, each
   linking to the possession's Loans section.
2. **Given** "Add to the provenance", **Then** a loan records a change of custody event on its start
   and another on its return, kept in step with the loan and deleted with it.

### Edge Cases

- A second active loan, or a planned one turned active, while one is active is refused: "Another
  loan of this possession is active. Mark it as returned first."
- Deleting a contact keeps their loans, which then read "A deleted contact". Deleting the possession
  deletes its loans and their provenance events.
- A contact of another vault, or a possession, loan or contact of another account, answers not found.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A possession MUST hold any number of loans, reached only through it.
- **FR-002**: A loan MUST hold a direction, a status, a contact of the possession's vault and a start
  day; MAY hold a due day, a return day, conditions out and back, a deposit with a currency, a purpose.
- **FR-003**: A possession MUST hold at most one active loan.
- **FR-004**: The contact MUST be chosen from the vault's contacts by search, or created with the loan.
- **FR-005**: Overdue, lengths, due lines and counts MUST be computed, never stored.
- **FR-006**: A loan MUST NOT record or change a transaction or the owner.
- **FR-007**: A loan MAY be edited, and deleted after a warning that it is final.
- **FR-008**: A loan linked to the provenance MUST keep its two events in step with it.
- **FR-009**: The contact's page MUST list the loans they are party to.
- **FR-010**: Loans MUST be an available section with its own address and count. **FR-011**: Each creation, edit, return and deletion MUST be journalled, doable through the API and
  the assistant, and translated.

### Key Entities

- **Loan**: a possession with one contact, out of or into your hands, for a period, with no change of owner.

## Success Criteria *(mandatory)*

- **SC-001**: A loan to an existing contact is recorded in under 30 seconds, by keyboard alone if wished.
- **SC-002**: The active loan on the possession, on the contact's page and in the API always agree;
  another vault's contact or another account's loan is never shown or used.

## Assumptions

- Conditions and currencies reuse the existing lists, not the design's own. Loans carry no documents.
- The copy names "this possession" where the design names the watch.
- The Overview's "On loan" line, "Lend to someone" in the actions menu, a reminder for the due day and
  the Timeline section stay out of scope.
