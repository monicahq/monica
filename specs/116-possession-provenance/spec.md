# Feature Specification: Possession Provenance

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-11-12 | **Status**: Draft

**Input**: "i want to manage provenance of possessions now based on this screen." Design: `Possession provenance.dc.html` (project 85e4275a), the Provenance section only.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Record a provenance event (Priority: P1)

Dwight Schrute records that his grandfather's beet press was made in Honesdale around 1921.

1. **Given** the possession page, **When** Provenance is selected, **Then** it shows, marked current,
   at its own address, with its count of events in the sections list.
2. **Given** "Add an event", **When** selected, **Then** a form titled "New provenance event" opens
   above the chain, says it is added in date order, and the button hides.
3. **Given** the form, **Then** it asks for a type, grouped as Ownership (Origin, Acquisition,
   Inheritance, Gift, Sale), Custody (Change of custody, Lost or stolen, Recovered) and Authenticity
   and record (Authentication, Exhibition, Publication, Other), Acquisition first; a title, whose
   placeholder follows the type; a date precision (Exact date, Month and year, Year, Around a year,
   Unknown; Exact date first) with a line saying what it means; a date shaped by that precision (a
   day, a month, a year, an approximate year, none when unknown); from, to and location; a
   description; a reference number; a source link; a linked transaction; and "This event has been
   verified", with a line saying that family memory alone stays unverified.
4. **Given** the form, **Then** the type and the precision are required; the rest is optional. A title
   left empty takes the type's name. A date that does not match its precision, a malformed link or a
   transaction of another possession returns the form with the error beside the field. Changing the
   precision clears the date.
5. **Given** the linked transaction list, **Then** it offers "Not linked to a transaction" first, then
   the possession's transactions oldest first, each with its type, date, counterparty and total, and a
   line saying that what the moment cost lives on the transaction.
6. **Given** a valid form, **When** submitted with "Add event", **Then** the event takes its place in
   the chain, the filter returns to "All events" and the form closes.

### User Story 2 - Read where the possession comes from (Priority: P1)

Jim Halpert checks how the teapot reached him.

1. **Given** events, **Then** a summary gives "Origin" (the origin event's location or "Place
   unknown", with its date and whether it is verified; "Unknown" and "No origin event yet" without
   one), "Chain of ownership" (the number of distinct owners known, named in order joined by ", then
   ", taken from the "to" of the ownership events; "No owner recorded" and "Add an acquisition, a
   gift or an inheritance" without one) and "Verified" (how many of the events are, and how many rest
   on memory alone, or "Every event is backed by a record").
2. **Given** the chain, **Then** it reads oldest first. An event with an unknown date goes to the end;
   events on the same date keep the order they were recorded in. A date reads by its precision: "14
   March 1962", "March 1962", "1962", "About 1975" or "Date unknown".
3. **Given** an entry, **Then** it gives its date, its type, "Verified" or "Not verified" in words,
   its title, a line naming who and where ("From Horlogerie Dubois to Odette Moreau in Lyon.", or "No
   people or place recorded."), its description when there is one, its reference number and source
   link when there are, and, when linked, "Linked transaction: Purchase, 14 March 1962" with that
   transaction's total, leading to the Transactions section.
4. **Given** the filter, **Then** it offers "All events", "Ownership", "Custody" and "Authenticity",
   each with its count, and the chain shows only that group.
5. **Given** no event, **Then** an empty state titled "No provenance recorded yet" invites starting
   with what is known for certain. Given events but none in the chosen group, it reads "Nothing of
   this kind yet".
6. **Given** the help mark, **Then** it says what belongs in provenance and that prices stay on transactions.

### User Story 3 - Correct or remove an event (Priority: P2)

Pam Beesly marks the purchase as verified; Kevin Malone deletes an exhibition he invented.

1. **Given** Edit on an entry, **When** selected, **Then** the form opens titled "Edit provenance
   event", filled with the event, the entry leaves the chain while it is edited, and the button reads
   "Save changes".
2. **Given** a valid change, **When** saved, **Then** the entry, its place in the chain and the summary
   follow. "Cancel" changes nothing.
3. **Given** Delete, **When** selected, **Then** the entry asks "Delete “Shown at a watch fair”?",
   says it is removed from the provenance of this possession, that this is final, and, when linked,
   that the linked transaction stays in Transactions. "Keep it" changes nothing.
4. **Given** a confirmed delete, **Then** the chain, the summary, the filter counts and the section
   count follow.

### Edge Cases

- Several origin events: the oldest is the one the summary reads.
- The same person in several ownership events counts once in the chain of ownership.
- Deleting a linked transaction keeps the event and removes only its link.
- Deleting the possession deletes its events.
- Another possession, vault or account answers not found.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A possession MUST hold any number of provenance events, each reached only through it.
- **FR-002**: An event MUST hold a type and a date precision, and MAY hold a title, a date matching the
  precision, from, to, a location, a description, a reference number, a source link, one linked
  transaction of the same possession, and whether it is verified (not verified by default).
- **FR-003**: An event MUST NOT hold an amount; the cost of a linked event MUST be read from its
  transaction, never copied.
- **FR-004**: An event MAY be edited and deleted. Deleting it MUST warn first that it is final and
  MUST leave its linked transaction untouched.
- **FR-005**: The origin, the chain of ownership, the verified count and the filter counts MUST be
  computed from the events, never stored on the possession.
- **FR-006**: Provenance MUST be an available section with its own address and count.
- **FR-007**: Each change MUST be journalled, doable through the API and the assistant, and translated.

### Key Entities

- **Provenance event**: one moment in the origin, ownership, custody or authenticity of one
  possession, holding what FR-002 lists and when it was added.

## Success Criteria *(mandatory)*

- **SC-001**: An event is recorded in under a minute, by keyboard alone if wished.
- **SC-002**: The summary always matches the events shown under "All events".
- **SC-003**: Another possession's, vault's or account's event or transaction is never shown, linked
  or changed.
- **SC-004**: 100 events open without a visible wait.

## Assumptions

- From, to and location are text, not contacts or places, as the valuer is in 115.
- Events carry no documents; the design's form has none.
- The date of an event is kept apart from the linked transaction's date, since the two may differ.
- The help mark, the empty state and the copy name "this possession" where the design names the watch.
- The Timeline, Loans, Locations and Documents sections the design shows stay out of scope.
- The API and the assistant follow what 099, 102 and 115 did.
