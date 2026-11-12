# Feature Specification: Possession Insurance

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-10-27 | **Status**: Draft

**Input**: "add the notion of insurance for a possession based on the given design. do not include the
document upload part for now we will add it later. put the insurance sidebar item under transactions."
Design: `Possession insurance.dc.html` (project 85e4275a).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Record an insurance record (Priority: P1)

Michael Scott records that his Dundie award is covered by Sabre Insurance, policy SAB-2026-0042,
from 1 January 2026 to 1 January 2027, for $500 with a $50 excess, listed individually on the policy.

1. **Given** the page, **When** Insurance is selected, **Then** it shows, marked current, at its own address.
2. **Given** "Add an insurance record", **When** selected, **Then** a form titled "New insurance record"
   opens above the list and the button hides.
3. **Given** the form, **Then** it asks for an insurer, a status (Active, Pending, Expired, Cancelled,
   Active chosen first), a policy number, a coverage, an insured value, a currency, an excess, a start
   date, an end or renewal date, whether the possession is listed individually on the policy, and,
   under "Who to call at the insurer", a name, an email and a phone, then a note.
4. **Given** the form, **Then** the insurer, the status, the insured value and its currency are
   required; everything else is optional, and the end date says to leave it empty for a policy with no
   end date.
5. **Given** a valid form, **When** submitted with "Add record", **Then** the record is listed and the
   form closes; a negative figure, a malformed email or an end date before the start returns the form
   with the error beside the field.

### User Story 2 - Read how the possession is covered (Priority: P1)

Dwight Schrute checks what his beet press is insured for, against what it is worth, and when it renews.

1. **Given** an active record, **Then** a summary gives "Insured for now" (its insured value, and the
   insurer with "listed individually" or "under general contents"), "Current estimated value" (the
   possession's declared value) and "Next renewal" (the end date, how far away it is and with whom, or
   "No end date" and "Renews on its own").
2. **Given** no active record, **Then** the summary reads "Not insured" and "No active record", and
   "Next renewal" reads nothing.
3. **Given** an active record insuring for less than the declared value, in the same currency,
   **Then** the summary says by how much, and a warning titled "Covered for less than it is worth"
   names the insurer, both values, and asks to update the insured value before the end date or the
   next renewal; otherwise it reads "Insured for at least what it is worth".
4. **Given** the list, **Then** it reads Active, then Pending, then Expired, then Cancelled, newest
   start first within each; each entry gives the insurer, its status, its coverage or "No coverage
   described", its insured value, the policy number or "Not given yet", the period ("1 January 2026 to
   1 January 2027", "From …", "Until …" or "No dates recorded") with "Renews in two months" for an
   active one, the excess ("None" at zero, "Not recorded" when empty), whether it is listed
   individually or under general contents, the contact with email and phone as links when any is
   given, the note or "No note on this record.", and when it was added and last edited.
5. **Given** the help mark, **When** opened, **Then** it says to add a new record when a policy renews
   with a new value or the insurer changes, the earlier ones staying as the history of the coverage.
6. **Given** the sections list, **Then** Insurance sits directly under Transactions and reads its count.

### User Story 3 - Correct or remove a record (Priority: P2)

Pam Beesly fixes the policy number on her watercolour, then deletes a record entered twice.

1. **Given** Edit, **When** selected, **Then** the form opens filled, titled "Edit insurance record",
   saying changes apply to this record only, and the entry leaves the list meanwhile.
2. **Given** a saved edit with "Save changes", **Then** the list, the summary and the last edited date follow.
3. **Given** Delete, **When** selected, **Then** the entry asks "Delete the Sabre Insurance record?",
   warns that the coverage history loses that period and that it is final; "Keep it" changes nothing.

### User Story 4 - See the coverage from the Overview (Priority: P3)

Jim Halpert glances at the Overview of his teapot. **Given** an active record, **Then** the facts read
"Insured" with the insurer and the insured value; without one, the fact is absent.

### Edge Cases

- Several active records: the one with the latest start is the current one.
- The status is what was entered and never follows the date; an active record whose end date has
  passed reads that date as passed in the summary.
- No declared value, or one in another currency: "Current estimated value" reads "Unknown" or the
  value as it is, and no comparison and no warning are shown.
- No record at all: the summary reads "Not insured", the list is empty and invites adding one.
- Another possession, vault or account answers not found.
- Deleting the possession deletes its insurance records.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A possession MUST hold any number of insurance records, each reached only through it.
- **FR-002**: A record MUST hold an insurer, one of four statuses, an insured value and its currency,
  and MAY hold a policy number, a coverage, an excess, a start date, an end date, whether it is listed
  individually, a contact name, email and phone, and a note.
- **FR-003**: Money MUST be stored exactly, never below zero, with its currency, never converted.
- **FR-004**: The current record, the gap against the declared value and the next renewal MUST be
  computed from the records, never copied onto the possession.
- **FR-005**: The currency MUST be chosen with the picker the transactions use.
- **FR-006**: Insurance MUST be an available section with its own address and count, moved to sit
  directly under Transactions in the sections list.
- **FR-007**: Each change MUST be journalled, doable through the API and the assistant, and translated.
- **FR-008**: Deleting MUST warn first that it is final.
- **FR-009**: Nothing about documents MUST appear: no attach control in the form, no documents block
  in the entries, no mention of documents in the delete warning.

### Key Entities

- **Insurance record**: one policy period covering one possession: insurer, status, policy number,
  coverage, insured value, currency, excess, start, end, listed individually, contact name, email,
  phone, note, when added and last edited.

## Success Criteria *(mandatory)*

- **SC-001**: A record is added in under a minute, by keyboard alone if wished.
- **SC-002**: The summary always matches the records; an under-insured possession is always flagged.
- **SC-003**: Another possession's, vault's or account's record is never shown or changed.
- **SC-004**: 100 records open without a visible wait.

## Assumptions

- Valuations do not exist yet, so "Current estimated value" is the possession's declared value, the
  one the Overview already shows under that label.
- The status is entered, as the design's chips do; the insurer and the contact are text, not contacts.
- Documents come in a later version, and so do insurance entries in the Timeline and "Coming up".
- The API and the assistant follow what 099 did for transactions.
