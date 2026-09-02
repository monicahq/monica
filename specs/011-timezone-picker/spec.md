# Feature Specification: Time zone picker

**Feature Branch**: `2026-09-02-timezone`

**Created**: 2026-09-02

**Status**: Draft

**Input**: Let somebody choose their time zone on the preferences screen, from the design `Preferences time zone.dc.html`: a search field, a world map that is clicked, and a save over ajax with a confirmation.

`users.timezone` exists, is filled from the browser at sign up, and cannot be changed afterwards
([user model](../../spec/models/002-user-done.md), line 63). This feature makes it changeable, and
leaves line 64, that nothing else reads it yet, standing.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Find a time zone by name (Priority: P1)

Somebody who has moved opens preferences, types the name of their city or their country, and picks
the zone from the matches. The list shows each candidate's zone, its offset from UTC, and the time
it is there now, so the right one is recognisable without knowing IANA naming.

**Why this priority**: it is the whole capability, and being keyboard only it is also the accessible path the map decorates.

**Independent Test**: type `Lisbon`, press Enter, reload, and the zone reads `Europe/Lisbon`.

**Acceptance Scenarios**:

1. **Given** the zone is `UTC`, **When** somebody types `Paris` and picks the first match, **Then** the zone saves as `Europe/Paris`, a confirmation appears, and the screen shows the Paris time.
2. **Given** a query matching nothing, **When** it is typed, **Then** the screen says so and offers the country instead, and nothing saves.
3. **Given** the results are open, **When** the arrow keys and Enter are used, **Then** the highlighted match is the one that saves.

---

### User Story 2 - Point at the map (Priority: P2)

Somebody who cannot name their zone clicks roughly where they live. The nearest zone is chosen and
saved at once, the map marks it and shades its hour band, and the screen says it took the nearest
one so the guess is visible rather than silent.

**Why this priority**: the design's centrepiece, and how somebody unfamiliar with zone names gets there. It adds to story 1 rather than replacing it, because a click is not a keystroke.

**Independent Test**: click over western Europe and the zone changes to a western European one, with a confirmation.

**Acceptance Scenarios**:

1. **Given** the map is drawn, **When** a point over Japan is clicked, **Then** `Asia/Tokyo` saves, a confirmation appears, and the marker sits there after a reload.
2. **Given** a click resolved to the nearest zone, **When** the screen redraws, **Then** it says the nearest one was taken.
3. **Given** a click over open ocean far from any zone, **When** it resolves, **Then** the nearest zone still saves and is still announced as nearest.

---

### User Story 3 - Ask the device (Priority: P3)

Somebody presses one button and the zone the browser reports is offered, which is the same source
sign up used.

**Why this priority**: one click for the common case, and worthless without stories 1 and 2.

**Independent Test**: press the button and the zone becomes the browser's own.

**Acceptance Scenarios**:

1. **Given** the browser reports `America/Chicago`, **When** the button is pressed, **Then** that zone saves with a confirmation.
2. **Given** the browser reports a zone the product does not know, **When** the button is pressed, **Then** nothing saves and the screen says so.

### Edge Cases

- A zone submitted that is not a known IANA identifier is refused, and the stored zone is untouched.
- Two clicks in quick succession leave the zone at the second one, never at the first.
- A reader who cannot click still reaches every zone, because the search field and its results are operated from the keyboard alone.
- A country whose name the reader's language spells differently still matches, because the name is offered in that language.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The preferences screen MUST show the stored zone, its offset from UTC, and the time it is there now.
- **FR-002**: Somebody MUST be able to search every zone the product knows by city name, by country name, and by identifier, and pick one from the matches.
- **FR-003**: Each match MUST show its identifier and its current offset from UTC.
- **FR-004**: The search MUST be operable with the keyboard alone: type, arrow through the matches, Enter to choose, Escape to close.
- **FR-005**: Somebody MUST be able to click the map and have the nearest known zone chosen.
- **FR-006**: A zone reached by clicking MUST be labelled as the nearest one, in words and not by colour alone.
- **FR-007**: The map MUST mark the chosen zone's position and shade its hour band.
- **FR-008**: Somebody MUST be able to offer the zone their browser reports, and MUST be told when the product does not know it.
- **FR-009**: Choosing a zone by any of the three means MUST save it immediately, without a Save button, over ajax, and MUST confirm with the existing notification.
- **FR-010**: The product MUST refuse a zone that is not a known IANA identifier and MUST leave the stored one alone.
- **FR-011**: Saving a zone MUST NOT alter the language, clock, date order or number separators.
- **FR-012**: Saving a zone MUST be recorded in the account's log.
- **FR-013**: The zone MUST be readable and writable through the JSON API, over the same operation the screen uses.
- **FR-014**: Every string on the screen MUST be translatable, country names included, in all nine locales.
- **FR-015**: The screen MUST satisfy WCAG 2.2 AA: keyboard operable, announced, focus visible, readable at 200%, legible in both themes, and still without animation when reduced motion is asked for.
- **FR-016**: Saving a zone MUST NOT shift anything already computed, in keeping with Principle VI.

### Key Entities

- **User time zone**: the IANA identifier on the user, one per account, defaulting to `UTC`.
- **Known zone**: an identifier the product will accept, with the country it sits in and a position on the globe, used for searching and for resolving a click.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody who knows their city sets their zone in under fifteen seconds, from opening the screen to seeing the confirmation.
- **SC-002**: Somebody who does not know their zone's name sets it with one click on the map, and one confirmation.
- **SC-003**: Every one of the 419 zones the product knows is reachable by typing its city or its country.
- **SC-004**: The whole screen, the map included, is completed with a keyboard alone and with a screen reader.
- **SC-005**: The chosen zone survives a reload and a sign in from another device.
- **SC-006**: No new front-end dependency and no request to a third party host is added.

## Assumptions

- The zones the product knows are the 419 canonical IANA identifiers PHP already carries, each with a country and a position. No city list, no geographic data file and no external service is introduced. A zone's searchable city name is the last part of its identifier, and its country name comes from the reader's language.
- The nearest zone to a click is the nearest of those positions by great circle distance. It is a guess, which is why FR-006 says so on the screen.
- The map's land outline is committed as an SVG path already projected, so pin placement, the hour band and the inversion from a click to a position are arithmetic. The d3, topojson and world atlas the design fetched are not needed.
- The time zone gets its own section and its own save, so it stays out of the set the four format rows carry between one another.
- The map is drawn in the palette the application already defines, in both themes, not in the design's literal hexadecimal values, which belong to a warmer palette the application has not adopted.
- The design's shell, rows, panel, dropdown, form, icons and confirmation exist as components already.
- Rendering the rest of the product's dates in the chosen zone is out of scope and stays with [Reminder](../../spec/models/040-reminder-not-yet.md).
