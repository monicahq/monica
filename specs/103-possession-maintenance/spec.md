# Feature Specification: Possession Maintenance

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-10-27 | **Status**: Draft

**Input**: "same branch. add the notion of maintenance records on the possession object, based on this
design. add also the mcp + api + docs part." Design: one card per job with a type chip, a title, an
"Overdue" chip, a cost, "Performed by", "Performed", "Condition" ("Worn → Like New"), "Next due", a
description and a Documents block. Documents are left out for now, as in 102 (asked on 2026-10-27).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Record a maintenance job (Priority: P1)

Michael Scott has his Dundie award re-plated by Scranton Trophy & Engraving on 12 March 2026 for $40.
It went in Worn, came back Like new, and should be looked at again in a year.

1. **Given** the page, **When** Maintenance is selected, **Then** it opens at its own address, marked
   current, in the place it already holds in the sections list, which reads its count.
2. **Given** the section, **Then** it is headed "Maintenance", says "The work done on this copy over
   time, with the condition before and after each job.", and offers "Maintenance record".
3. **Given** "Maintenance record", **When** selected, **Then** a form titled "New maintenance record"
   opens above the list and the button hides.
4. **Given** the form, **Then** it asks for a type, a title, who performed it, the date performed, a
   cost and its currency, the condition before, the condition after, the next due date and a description.
5. **Given** the form, **Then** the type, the title and the date performed are required; everything
   else is optional; both conditions list the account's conditions in their order, or "Not recorded".
6. **Given** a valid form, **When** submitted with "Add record", **Then** the record is listed, the
   count follows and the form closes. A negative cost, a cost without a currency, a date performed in
   the future, or a next due date before the date performed returns the form with the error beside it.

### User Story 2 - Read the maintenance history (Priority: P1)

Dwight Schrute checks when his beet press was last serviced, by whom, and whether it is due again.

1. **Given** the list, **Then** it reads newest performed date first, the latest added first on the same day.
2. **Given** a record, **Then** its card gives the type as a coloured chip, the title, the cost with
   "cost" under it ("No cost" when empty), Edit, "Performed by" ("Not recorded" when empty),
   "Performed", "Condition" ("Worn → Like new", a missing side reading "Not recorded", both missing
   reading "Not recorded" once), "Next due" ("Not scheduled" when empty), and the description if any.
3. **Given** the most recent record whose next due date has passed, **Then** it carries an "Overdue"
   chip; an older record never does, since a later job supersedes its schedule.
4. **Given** a long title or "Performed by", **Then** it is shortened on screen and given in full on
   hover and to a screen reader.
5. **Given** the help mark, **When** opened, **Then** it says each job is its own record, that the
   conditions show what the job changed, and that only the latest job's next due date counts.
6. **Given** no record, **Then** the list says no maintenance is recorded yet and invites adding one.

### User Story 3 - Correct or remove a record (Priority: P2)

Pam Beesly fixes the cost of her watercolour's reframing, then deletes a record entered twice.

1. **Given** Edit, **When** selected, **Then** the form opens filled, titled "Edit maintenance record",
   and the card leaves the list meanwhile.
2. **Given** "Save changes", **Then** the card, the order and the overdue marking follow.
3. **Given** Delete, **When** selected, **Then** it asks "Delete the “:title” record?" and warns that it
   is final; "Keep it" changes nothing.

### User Story 4 - Maintenance through the API, the assistant and the docs (Priority: P2)

Oscar Martinez's script records each service of the office copier, and he asks his assistant whether
anything he owns is overdue.

1. **Given** an API key, **Then** the API lists a possession's records, reads, creates, updates and
   deletes one, with the form's rules and refusals, and says whether each is overdue.
2. **Given** a connected assistant, **Then** it can do the same five operations within the account only.
3. **Given** the API reference, **Then** every endpoint is documented with its fields, an example and
   its errors; **Given** the user guide, **Then** a page explains maintenance records.

### Edge Cases

- A condition renamed: records show the new name. Deleted: records read "Not recorded" on that side,
  and the condition's delete warning says how many maintenance records name it.
- A next due date of today is not overdue until tomorrow, in the user's timezone.
- A cost of zero reads as zero in its currency, not "No cost".
- The cost is never converted, and never added to the transactions or to the declared value.
- Another possession, vault or account answers not found, on screen, through the API and the assistant.
- Deleting the possession deletes its maintenance records.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A possession MUST hold any number of maintenance records, each reached only through it.
- **FR-002**: A record MUST hold a type, a title and a date performed, and MAY hold who performed it, a
  cost with its currency, a condition before, a condition after, a next due date and a description.
- **FR-003**: The type MUST be one of a fixed list (Inspection, Cleaning, Servicing, Repair,
  Restoration, Part replacement, Conservation, Other), each with a colour of its own.
- **FR-004**: The conditions MUST be chosen from the account's own conditions.
- **FR-005**: A cost MUST be stored exactly, never below zero, never converted, its currency chosen
  with the picker the transactions use.
- **FR-006**: Whether a record is overdue MUST be computed when read, never stored.
- **FR-007**: Maintenance MUST become an available section with its own address and count, in place.
- **FR-008**: Nothing about documents MUST appear: no Documents block, no attach control, no mention
  of documents in the warning, the API or the assistant.
- **FR-009**: Each change MUST be journalled, translated in every configured locale, and doable through
  the API and the assistant; deleting a record MUST warn first that it is final.
- **FR-010**: The API reference and the user guide MUST describe maintenance records.

### Key Entities

- **Maintenance record**: one job done on one possession: type, title, performed by, date performed,
  cost, currency, condition before, condition after, next due date, description, added, last edited.
- **Possession condition** (existing): the account's list of states, named before and after a job.

## Success Criteria *(mandatory)*

- **SC-001**: A record is added in under a minute, by keyboard alone if wished.
- **SC-002**: The overdue marking always matches the latest record's next due date and today.
- **SC-003**: Another possession's, vault's or account's record is never shown or changed.
- **SC-004**: A possession with 100 records opens without a visible wait.

## Assumptions

- The type is a fixed list, as transaction types are; the chip colour comes from the type.
- "Performed by" is text, not a contact, as the insurer is.
- Overdue applies only to the latest record; there is no "due soon" state, the design shows none.
- The cost stays apart from the transactions; whoever wants it there records a transaction too.
- Maintenance does not yet appear in the Timeline, in "Coming up" or on the Overview.
- Documents come later, as one feature attaching files to insurance and maintenance records together.
- The API and the assistant follow what 099 did for transactions and 102 for insurance.
