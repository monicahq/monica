# Feature Specification: Possession Detail

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-10-26 | **Status**: Draft

**Input**: "same branch. add the detail page of a possession. design as follow. for all the tabs
except overview, implement the sidebar, but not the pages."
Design: `Possession detail.dc.html` (claude.ai/design project 85e4275a).

A possession has no page today, so the rows of the 096 listing are not links. This feature gives each
possession a page: a header, a list of ten sections, and the Overview section. The other nine
sections (Timeline, Transactions, Valuations, Provenance, Insurance, Maintenance, Loans, Locations,
Documents) appear in the list only; their content is a later feature.

## Clarifications

### Session 2026-10-26
- Q: What does an unbuilt section do when selected? → A: Shown, not activable. No address yet.
- Q: Does this feature include an edit screen? → A: No. The Edit menu offers featuring and deleting.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Open a possession (Priority: P1)

Michael Scott selects "Dundie award" in All possessions and reads its page: its type, where it is
kept, its name, its tags, its description, the values of its type's fields, its declared value and
the facts about it.

**Independent Test**: Selecting a possession in the listing opens its page with the Overview shown.

1. **Given** the listing, **When** a possession is selected, **Then** its page opens, and its address
   can be reloaded or shared.
2. **Given** the page, **When** shown, **Then** a trail reads vault, All possessions, the possession.
3. **Given** the header, **When** shown, **Then** it gives the type with its icon and colour, "kept
   in" the location and "at" its top level location, the name, a star when featured, and the tags.
4. **Given** a description, **When** the Overview shows, **Then** it reads under "Description".
5. **Given** filled fields, **When** shown, **Then** "Details" lists them under their field groups, in
   the type's order, each value written for its kind (a yes or no, a date, a rating, an option).
6. **Given** a declared value, **When** shown, **Then** a panel gives "Current estimated value".
7. **Given** the facts panel, **When** shown, **Then** it gives type, kept in, status, how and when it
   was acquired and from whom, and quantity when above one.
8. **Given** the page, **When** shown, **Then** it reads when the possession was added and last edited.

### User Story 2 - The sections list (Priority: P2)

Dwight Schrute sees the ten sections of the possession beside its content, Overview marked current.

1. **Given** the page, **When** shown, **Then** the list gives the ten sections with their icons, in
   the design's order, Overview marked current.
2. **Given** a section other than Overview, **When** selected, **Then**
   nothing happens: it stays in the list, marked unavailable, with a reason read to assistive
   technology saying it comes in a later version.

### User Story 3 - Act on the possession (Priority: P3)

Pam Beesly opens the Edit menu to feature the possession or delete it.

1. **Given** the header, **When** shown, **Then** an Edit split button is offered.
2. **Given** Edit, **When** selected, **Then** its main part is marked unavailable, with the
   reason that editing comes in a later version, and its menu part unfolds the menu.
3. **Given** the menu, **When** "Add to featured" or "Remove from featured" is chosen, **Then** the
   possession is featured or no longer featured, and the page says so.
4. **Given** the menu, **When** "Delete this possession" is chosen, **Then** a card warns that it is
   final, and confirming deletes it and returns to All possessions with a message.

### Edge Cases

- A possession without a location reads "No location" in the header and the facts.
- A possession at a top level location reads "kept in" it, without "at".
- Without a description, the Description block is absent; without filled fields, Details says so.
- Without a declared value, the value panel says none is recorded.
- A contact the possession came from, once deleted, is no longer named.
- A possession of another vault of the account, or of another account, answers not found.
- Values in a currency are written in that currency, never converted.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Each possession MUST have its own page, reached from its row in the 096 listing.
- **FR-002**: The page MUST read only the possession of the vault in its address, else not found.
- **FR-003**: The header MUST give type, location, name, featured mark and tags.
- **FR-004**: The Overview MUST give description, field values by group, declared value, facts, and
  the added and edited dates.
- **FR-005**: A field value MUST be written for its kind, and empty fields left out.
- **FR-006**: The sections list MUST give all ten sections, mark Overview as current, and mark the
  nine others unavailable with their reason.
- **FR-011**: The main part of Edit MUST be marked unavailable with its reason until editing exists.
- **FR-007**: Photos MUST show as coming in a later version, as the add form does.
- **FR-008**: Featuring and deleting MUST go through the existing actions, deleting only after a
  warning that it is final.
- **FR-009**: The sections list MUST be a navigation landmark, usable by keyboard, its current item
  announced, and the Edit menu MUST be operable and dismissable by keyboard.
- **FR-010**: Every string MUST be translated in every configured locale.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every possession in the listing opens its page in one selection.
- **SC-002**: Every value recorded for a possession is readable on its Overview.
- **SC-003**: A possession of another vault or account is never shown.
- **SC-004**: A possession with 50 field values opens without a visible wait.

## Assumptions

- Reading one possession is already in the API and the assistant; nothing is added there.
- Photos, valuations, transactions, insurance, maintenance, loans, moves and documents are not
  recorded, so "Coming up", the photo gallery, "Up from the previous estimate" and "Record a
  valuation" are left out, and the sections list shows no counts.
- The design's "Owner" fact is not recorded and is left out.
- "Add a tag" in the header is left out: tags are chosen where the possession is edited.
- The Edit menu holds only what works today: featuring and deleting. Move, lend, record a valuation,
  duplicate, move to another vault, export and archive come with their own features.
- The declared value is the "current estimated value" until valuations exist.
