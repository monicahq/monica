# Feature Specification: Possession Locations

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-11-12 | **Status**: Draft

**Input**: "implement the locations in the possession object." Design: `Possession locations.dc.html`.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Move a possession elsewhere (Priority: P1)

Dwight Schrute moves his grandfather's beet press from the barn to the cellar.

1. **Given** the possession page, **When** Locations is selected, **Then** it shows at its own address
   with its stay count, "Manage locations" leading to Settings, and a button "Move elsewhere".
2. **Given** "Move elsewhere", **When** selected, **Then** a form titled "Move the possession" opens
   above the list, says where it moves from ("From the barn", or "It has no current location"), and
   the button hides.
3. **Given** the form, **Then** it asks for "Move to" (required, every location of the account written
   as its full path, "Schrute Farms › Barn › Loft"), "Moved on" (required, today by default), and an
   optional reason and note.
4. **Given** a location and a day, **Then** a line says what the move records: "The stay in the barn
   closes on 4 June 2024, after 3 years and 2 months. A new stay in Cellar opens the same day.", or "A
   new stay in Cellar opens on 4 June 2024." when nothing is open, or "Choose a location and a day to
   see what this move records." until both are given.
5. **Given** no location, no day, the location it is already in, or a day before the open stay began,
   **When** submitted, **Then** the form returns with "Choose where it went.", "Enter the day it
   moved.", "It is already in the barn." or "The current stay began on 8 November 2019. Date the move
   after that, or correct the current stay instead." beside the field.
6. **Given** a valid form, **When** submitted with "Move it", **Then** the open stay closes on that
   day, a new one opens there the same day, the possession's current location becomes that location
   everywhere it is shown, and the form closes. "Cancel" changes nothing.

### User Story 2 - Read where it is and where it has been (Priority: P1)

Jim Halpert checks where the teapot is, and where it spent the last five years.

1. **Given** an open stay, **Then** "Kept now in" shows the location's path (ancestors quieter), the
   address of the nearest location carrying one, a "Correct" button, and "Since" (the day, or "Date
   unknown"), "Time there" and "Why it is there" (the reason, or "No reason recorded").
2. **Given** no open stay, **Then** an empty state titled "Not kept anywhere right now" invites moving
   the possession somewhere.
3. **Given** stays, **Then** "Every stay" lists them newest first under a count line ("5 stays in 4
   locations"). Each gives the path, "Here now" on the open one, its period ("14 March 2025 to 21
   March 2025", "Date unknown to 22 October 2019", "21 March 2025 to now"), its length, its reason or
   "No reason recorded", its note when there is one, and "Correct" and "Delete".
4. **Given** a length, **Then** it reads "less than a day", in days under a month ("7 days"), in years
   and months beyond ("1 year and 7 months"), or "length unknown" without an arrival.
5. **Given** two consecutive stays where the older closed before the newer began, **Then** a line
   between them reads "No location recorded from 22 October 2019 to 8 November 2019, 17 days."
6. **Given** the help mark, **Then** it says each stay is one location and the days spent there, that
   moving closes the current stay and opens the next, and that locations are set up once, in Settings.

### User Story 3 - Correct or delete a stay (Priority: P2)

Pam Beesly fixes the day the painting arrived in the hallway; Kevin Malone deletes a stay he invented.

1. **Given** "Correct", **When** selected, **Then** the form opens titled "Correct this stay", under it
   "A past stay" or "The current stay. Move the possession to close it.", filled with "Location",
   "Arrived" (optional, "Leave it empty when the day is unknown."), "Left" for a past stay only,
   reason and note, a line saying the stays before and after are not moved, and "Save changes".
2. **Given** a past stay without a day it left, or one before its arrival, **When** saved, **Then** the
   form returns with "Enter the day it left." or "This is before the day it arrived.".
3. **Given** "Delete", **When** selected, **Then** the row asks "Delete the stay in Cellar?", says it is
   final, and for the open stay that the possession will have no current location until moved, or for
   a past stay that its period will show as unrecorded and the location itself stays. "Keep it"
   changes nothing.
4. **Given** a confirmed delete, **Then** the list, its lines and the section count follow.

### User Story 4 - The location chosen on the possession form (Priority: P2)

Angela Martin adds a cat figurine on the mantelpiece, then changes its location on the edit form.

1. **Given** a possession added with a location, **Then** it has one open stay there, arriving that day.
2. **Given** the edit form, **When** the location changes, **Then** a move dated that day is recorded;
   cleared, the open stay closes that day; unchanged, nothing changes.

### Edge Cases

- A possession recorded before this feature with a location gets one open stay there, arrival unknown.
- A stay closing and the next opening on the same day shows no gap line; overlaps from corrections
  are shown as recorded. Unknown arrivals sort first, in the order recorded.
- A location visited twice counts once; two locations named alike are told apart by their paths.
- Deleting the possession deletes its stays. Another possession, vault or account answers not found.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A possession MUST hold any number of stays, reached only through it.
- **FR-002**: A stay MUST hold a location of the account; MAY hold an arrival, a departure, a reason, a note.
- **FR-003**: A possession MUST hold at most one open stay, whose location MUST be its current
  location wherever that is read: the page, the possessions list and its tree, the API, the assistant.
- **FR-004**: Moving MUST close the open stay and open the next on the same day, in one step, refusing
  the open stay's location and a day before the open stay began.
- **FR-005**: Correcting a stay MUST change only that stay; a past stay keeps a day it left, not before
  its arrival; the open stay is never given one.
- **FR-006**: A stay MAY be deleted, after a warning that it is final; no other stay changes.
- **FR-007**: Lengths, gaps and counts MUST be computed, never stored.
- **FR-008**: The add and edit forms MUST follow FR-004 when they set or change the location.
- **FR-009**: Locations MUST be an available section with its own address and count.
- **FR-010**: Deleting a location any stay refers to, open or past, MUST be refused, saying why, as a
  location holding others already is.
- **FR-011**: Each move, correction and deletion MUST be journalled, doable through the API and the
  assistant, and translated.

### Key Entities

- **Stay**: one possession at one location of the account for a period, open while it is there now.

## Success Criteria *(mandatory)*

- **SC-001**: A move is recorded in under 30 seconds, by keyboard alone if wished.
- **SC-002**: The current location in the section, the list and the API always agree; another
  account's stay or location is never shown or used; 100 stays open at once.

## Assumptions

- The design says "place"; the glossary's noun is "location", so the copy says "location".
- The copy names "this possession" where the design names the watch. Stays carry no documents.
- The Overview's "kept in" line and the Timeline section stay out of scope.
