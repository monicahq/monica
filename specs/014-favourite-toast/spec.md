# Feature Specification: Confirmation when a contact is marked a favourite

**Feature Directory**: `specs/014-favourite-toast` | **Created**: 2026-09-02 | **Status**: Draft

**Input**: "Quand on met en favori, je veux trigger une notification dans l'interface utilisateur (le toaster)"

## Context

Marking a contact a favourite is silent today. The star changes shape and colour, the ring around the
avatar changes colour, nothing else happens. That was deliberate: the note where the mark is handled
says the star is the answer, so a message about a mark somebody just watched change under their
finger says nothing new. This feature reverses that, so the act reads as a save that landed rather
than a control that merely looks different. The reversal is argued once, in [research.md](research.md).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Marking a contact a favourite is confirmed (Priority: P1)

Someone opens a person they look up often, presses the star beside their photo, and the product says
back, in the corner it always speaks from, that this person is now a favourite.

**Why this priority**: This is the request, and on its own it delivers the whole value.

**Independent Test**: Press the star on a non-favourite; a confirmation appears while the star shows
the new state.

**Acceptance Scenarios**:

1. **Given** a contact who is not a favourite, **When** the star is pressed, **Then** a confirmation
   says they are now a favourite, and the star and ring show that state.
2. **Given** the confirmation is showing, **When** the user waits, **Then** it dismisses itself.
3. **Given** the confirmation is showing, **When** the user dismisses it, **Then** it goes and the
   favourite state is untouched.
4. **Given** the user moves to another page, **When** it loads, **Then** the confirmation is not
   shown again.

### User Story 2 - Removing the mark is confirmed too (Priority: P2)

The same star pressed again takes the mark off, and the product says they are no longer a favourite.

**Why this priority**: One control, two directions. A message one way and silence the other reads as a
save that failed.

**Independent Test**: Press the star on a favourite; a confirmation naming the removal appears.

**Acceptance Scenarios**:

1. **Given** a contact who is a favourite, **When** the star is pressed, **Then** a confirmation says
   they are no longer a favourite, and the star and ring show the plain state.
2. **Given** the two directions, **When** their wording is compared, **Then** neither can be mistaken
   for the other.

### User Story 3 - The confirmation behaves like every other one (Priority: P3)

Same place, lifetime, dismissal and screen reader announcement as the confirmation after saving a
preference or creating a list.

**Why this priority**: No value alone, but a message behaving differently costs more than it gives.

**Independent Test**: Compare it against any other confirmation in the product.

**Acceptance Scenarios**:

1. **Given** a screen reader, **When** it appears, **Then** it is announced politely without stealing
   focus from the star.
2. **Given** scripting is unavailable, **When** the star is pressed, **Then** the page reloads with
   the new state and the same confirmation.
3. **Given** the star pressed twice quickly, **When** the second answer arrives, **Then** one
   confirmation is showing and it matches the stored state.

### Edge Cases

- **Repeated presses.** Confirmations must not stack into a column; the latest replaces what showed.
- **A stale page.** The request sends the state it wants rather than a toggle, so a press can confirm
  a state already stored. Naming the resulting state stays true either way.
- **An unreachable contact** (another account, or another vault) fails as today, with nothing said.
- **A long name** must not push the page sideways.

## Requirements *(mandatory)*

- **FR-001**: Marking a contact a favourite MUST show a confirmation where the product shows every
  confirmation.
- **FR-002**: Removing the mark MUST show one there too.
- **FR-003**: Each MUST name the state the contact is now in, so the two directions cannot be confused.
- **FR-004**: It MUST appear whether scripting is available (part of the page redraws) or not (the
  whole page reloads).
- **FR-005**: It MUST dismiss itself after the standard delay, be dismissible by hand before then, and
  be announced politely to assistive technology.
- **FR-006**: At most one favourite confirmation MUST be visible at a time.
- **FR-007**: The star and the ring MUST keep showing the state; the confirmation is said in addition
  to them, never instead.
- **FR-008**: A failed request MUST show no confirmation and keep the response it produces today.
- **FR-009**: It MUST NOT reappear on any later page.
- **FR-010**: Every word MUST be translatable and translated in all nine configured locales.

## Success Criteria *(mandatory)*

- **SC-001**: Every successful press produces a confirmation, both directions, every attempt.
- **SC-002**: It is readable within one second of the star changing state.
- **SC-003**: Shown the message alone, with the star out of view, a user can say which direction
  happened, every time.
- **SC-004**: Behaviour is identical with scripting available and unavailable.
- **SC-005**: Five presses in a row leave exactly one confirmation, matching the stored state.
- **SC-006**: No untranslated string is reported in any configured locale.

## Assumptions

- **Both directions are covered.** The request named only marking. Silence on removal would read as a
  failed save. Narrowing this is still the user's call.
- **The contact page is the only origin.** Lists show the mark but do not change it.
- **The existing notification area is reused.** No new style, position, lifetime, sound, browser
  notification or email.
- **A recorded decision is overridden**, and the note saying nothing is flashed has to be replaced
  rather than left standing.

## Out of Scope

Marking a favourite from anywhere but the contact page; favourite vaults or any other object; a
history of past notifications; what the mark does or how favourites are sorted; the notification area
itself.
