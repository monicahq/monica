# Feature Specification: Possession Valuations

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-11-12 | **Status**: Draft

**Input**: "add the concept of valuations based on this design for a possession."
Design: `Possession valuations.dc.html` (project 85e4275a), the Valuations section only.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Record a valuation (Priority: P1)

Michael Scott records that a jeweller appraised his Dundie award at $650 on 14 March 2026.

1. **Given** the possession page, **When** Valuations is selected, **Then** it shows, marked current,
   at its own address, with its count in the sections list.
2. **Given** "Record a valuation", **When** selected, **Then** a form titled "New valuation" opens above
   the history, says that once added it is not edited, and the button hides.
3. **Given** the form, **Then** it asks for a type (Own estimate, Appraisal, Insurance declaration,
   Auction result, Comparable listing, Offer received; Own estimate first), a date, a confidence
   (Unknown, Certain, Probable, Uncertain; Unknown first), an amount, a currency, a valuer, a method, a
   reference number, a source link, a note and documents (a certificate, a listing or any other file).
4. **Given** the form, **Then** the type, the date, the amount and its currency are required; the rest
   is optional. A negative amount, a malformed link or a missing date returns the form with the error
   beside the field.
5. **Given** an amount typed in, **Then** a line under it says what happens: it becomes the current
   value (up, down or unchanged by how much from the current one), or, dated before the current one, it
   goes into the history and the current value stays; in another currency, it is not compared.
6. **Given** a valid form, **When** submitted with "Add valuation", **Then** it is listed and the form closes.

### User Story 2 - Read what the possession is worth (Priority: P1)

Dwight Schrute checks how the value of his beet press moved over the years.

1. **Given** valuations, **Then** a summary gives "Current value" (the latest by date, with its type
   and date), "Change from the previous" (with the previous amount and date, "Different currencies"
   or "Only one valuation so far") and "Insured for" (the current insurance record's insured value).
2. **Given** an insured value below the current value, in the same currency, **Then** "Insured for"
   says by how much, without relying on colour alone; otherwise it names the insurer.
3. **Given** the history, **Then** it reads newest first; each entry gives its type, "Current value"
   on the latest, its date, the valuer or "No valuer recorded", the amount, the change from the one
   before it ("First valuation", "In GBP" across currencies), the confidence, method, reference and
   source link or "Not recorded", the note or "No note on this valuation.", its documents (name, kind
   and size, each opening the file), and when it was added.
4. **Given** no valuation, **Then** an empty state titled "No valuation yet" invites recording one.
5. **Given** the help mark, **When** opened, **Then** it explains that a valuation is never edited, a
   correction is a new valuation, and the latest by date is the current value.

### User Story 3 - Remove a valuation entered by mistake (Priority: P2)

Pam Beesly deletes a valuation she recorded twice.

1. **Given** Delete, **When** selected, **Then** the entry asks "Delete the appraisal of 14 March
   2026?", says what the current value becomes (unchanged, the one before, or none), that it is
   final, and either that its documents are detached or that a figure is corrected by recording a new
   valuation; "Keep it" changes nothing.
2. **Given** a confirmed delete, **Then** the history, the summary and the count follow.

### User Story 4 - See the value from the rest of the page (Priority: P3)

Jim Halpert glances at the Overview of his teapot.

1. **Given** valuations, **Then** the Overview's value panel reads "Current estimated value", the
   amount, its type and valuer with its date, the change from the previous one, and a link "Record a
   valuation" to the Valuations section. Without one, the panel reads the declared value, as today.
2. **Given** the Edit menu of the header, **Then** it offers "Record a valuation".
3. **Given** the Insurance section, **Then** its "Current estimated value" and its under insured
   warning read the current valuation, and the declared value when there is none.

### Edge Cases

- Two valuations on the same date: the one recorded last is the current one.
- A current valuation in another currency than the insurance or the previous one: no comparison, no
  warning, the amounts are shown as they are and never converted.
- Deleting the only valuation leaves the possession with no current value.
- Another possession, vault or account answers not found.
- Deleting the possession deletes its valuations.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A possession MUST hold any number of valuations, each reached only through it.
- **FR-002**: A valuation MUST hold a type, a date, an amount and its currency, and MAY hold a
  confidence, a valuer, a method, a reference number, a source link and a note.
- **FR-003**: A valuation MUST NOT be edited once recorded; it MAY be deleted.
- **FR-004**: Money MUST be stored exactly, never below zero, with its currency, never converted.
- **FR-005**: The current value, the changes and the insurance gap MUST be computed from the
  valuations, never copied onto the possession.
- **FR-006**: The currency MUST be chosen with the picker the transactions use.
- **FR-007**: Valuations MUST be an available section with its own address and count.
- **FR-008**: Each change MUST be journalled, doable through the API and the assistant, and translated.
- **FR-009**: Deleting MUST warn first that it is final.
- **FR-010**: A valuation MAY carry documents, attached while it is recorded, counted against the
  account's storage like a transaction's; deleting it detaches them under the usual grace period.
- **FR-011**: Wherever the page reads a current value, it MUST read the latest valuation, and the
  possession's declared value only when it has none; the declared value stays on the possession form.

### Key Entities

- **Valuation**: one estimate of what one possession is worth on one date: type, date, amount,
  currency, confidence, valuer, method, reference number, source link, note, when added.

## Success Criteria *(mandatory)*

- **SC-001**: A valuation is recorded in under a minute, by keyboard alone if wished.
- **SC-002**: The current value shown anywhere on the page always matches the latest valuation by date.
- **SC-003**: Another possession's, vault's or account's valuation is never shown or changed.
- **SC-004**: 100 valuations open without a visible wait.

## Assumptions

- The declared value stays as a replacement cost entered on the form; it is not turned into a valuation.
- Documents are attached only in the form, as the design shows; a recorded valuation is not edited.
- The valuer is text, not a contact, as the insurer is in 102.
- Confidence "Unknown" reads "Not recorded" in an entry, as the design does.
- The Timeline, "Coming up" and the other sections the design shows stay out of scope.
- The API and the assistant follow what 099 and 102 did.
