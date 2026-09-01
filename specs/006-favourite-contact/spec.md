# Feature Specification: Favourite a Contact From Their Page

**Feature Branch**: `006-favourite-contact`

**Created**: 2026-08-31

**Status**: Draft

**Input**: User description: "on the contact show page, add the ability to favorite a contact by clicking on the star next to the avatar."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Mark somebody as a favourite while looking at them (Priority: P1)

Somebody opens a contact's page, decides this is one of the handful of people they come back to
every week, and presses the star beside the avatar. The star fills in, and from then on that person
sits at the top of the people list and appears under the favourites filter.

Today the only moment a contact can be made a favourite is while they are being created. Anybody
who did not think of it then has no way to change their mind, short of deleting the contact and
writing them down again.

**Why this priority**: it is the whole of the request, and on its own it closes the gap. Without it
the favourite mark is a decision the product asks for exactly once, at the worst possible moment,
before the person has been used at all.

**Independent Test**: open a contact who is not a favourite, press the star, and confirm the person
now appears at the top of the people list and under the favourites filter. Delivers the entire value
on its own.

**Acceptance Scenarios**:

1. **Given** a contact who is not a favourite, **When** the user presses the star beside the avatar,
   **Then** the contact becomes a favourite and the star shows the new state without the user having
   to reload or navigate.
2. **Given** a contact who has just been made a favourite, **When** the user opens the people list,
   **Then** that contact appears among the favourites and is ordered above the contacts who are not.
3. **Given** a contact who has just been made a favourite, **When** the user reloads their page,
   **Then** the star still shows them as a favourite.
4. **Given** a contact who has just been made a favourite, **When** the user reads the account
   activity, **Then** an entry records that this contact was pinned, naming them.

---

### User Story 2 - Take somebody off the favourites (Priority: P2)

The same star takes the mark away. Somebody who stopped being one of the handful is unpinned from
the page they are on, with no separate screen and no confirmation step.

**Why this priority**: a mark that can only be applied is a trap, and the control is the same one, so
it costs almost nothing once P1 exists. It is P2 only because a user who cannot yet unfavourite still
has a working product, while a user who cannot favourite has nothing.

**Independent Test**: open a contact who is a favourite, press the star, and confirm they leave the
favourites filter and return to their ordinary place in the list.

**Acceptance Scenarios**:

1. **Given** a contact who is a favourite, **When** the user presses the star, **Then** the contact
   stops being a favourite and the star shows the new state immediately.
2. **Given** a contact who has just been unfavourited, **When** the user opens the favourites filter,
   **Then** that contact is not listed.
3. **Given** a contact who has just been unfavourited, **When** the user reads the account activity,
   **Then** an entry records that this contact was unpinned, naming them.
4. **Given** a contact who is a favourite, **When** the user presses the star, **Then** nothing is
   deleted and no warning is shown, because taking a mark off destroys nothing.

---

### User Story 3 - Know the state, and reach it, without a mouse or colour (Priority: P3)

The star is present on every contact's page whether or not they are a favourite, it says in words
what pressing it will do, it can be reached and operated from the keyboard, and its two states are
told apart by more than a colour.

**Why this priority**: it is a delivery condition of the two stories above rather than a separate
feature, and it is listed separately because it is independently testable and independently
demonstrable. It is P3 because it refines a control the first two stories create.

**Independent Test**: reach the star using the keyboard alone, operate it, and confirm a screen
reader announces both what the control does and which state it is now in. Confirm in a greyscale
rendering that the two states remain distinguishable.

**Acceptance Scenarios**:

1. **Given** a contact who is not a favourite, **When** the user looks at their page, **Then** the
   star is visible and legible as an unpressed control rather than absent.
2. **Given** the contact page, **When** the user moves through it with the keyboard alone, **Then**
   the star receives focus, the focus is visible, and pressing it toggles the mark.
3. **Given** the contact page read by a screen reader, **When** the star is reached, **Then** its
   purpose and its current state are both announced, and the state is announced again after it
   changes.
4. **Given** the contact page rendered without colour, **When** the user looks at the star, **Then**
   favourite and not favourite are still distinguishable.

---

### Edge Cases

- **A contact in another account or reached through the wrong vault.** The request is refused as not
  found. No favourite mark may be applied to a record the account does not reach.
- **An archived contact.** Archiving hides somebody from the main lists; it says nothing about
  whether they matter. The star stays available, and the mark set on an archived contact takes effect
  the moment they are unarchived.
- **The same star pressed several times in a row.** Each press is a real change and each is recorded
  in the activity, because the activity is append only and a corrected mistake is still something
  that happened. The contact ends in the state of the last press.
- **The contact is deleted, or its state is changed elsewhere, while the page is open.** Pressing the
  star on a stale page must not resurrect a deleted contact, and must not silently write over a newer
  state without the user knowing what they changed.
- **A contact who is both a favourite and partial.** A partial contact stays out of the lists until
  filled in, so a favourite mark on one has no visible effect until then. The mark is still accepted
  and still remembered.
- **Pressing the star with scripting unavailable.** The mark must still be applied; only the redraw
  in place is a refinement.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The contact page MUST show a star beside the avatar for every contact, whether or not
  that contact is currently a favourite.
- **FR-002**: The star MUST be an operable control rather than decoration, and pressing it MUST
  toggle whether the contact is a favourite.
- **FR-003**: The star MUST show the contact's current state before it is pressed, and the new state
  after, without the user reloading the page or navigating away.
- **FR-004**: The two states MUST be distinguishable by shape or wording and not by colour alone.
- **FR-005**: The star MUST be reachable and operable using the keyboard alone, with a visible focus
  indicator.
- **FR-006**: Assistive technology MUST be able to announce what the control does, which state it is
  in, and that the state has changed.
- **FR-007**: Every user-facing word this feature introduces MUST be translatable and MUST be
  translated into every configured locale.
- **FR-008**: Making a contact a favourite MUST put them among the favourites of the people list and
  above the contacts who are not favourites, using the same ordering the list already applies.
- **FR-009**: Removing the mark MUST take the contact out of the favourites and return them to their
  ordinary place, and MUST NOT delete anything or ask for confirmation.
- **FR-010**: Each change MUST be recorded in the account activity, naming the contact and saying
  which way the mark went.
- **FR-011**: A request to change the mark on a contact the account does not reach, whether it
  belongs to another account or is reached through the wrong vault, MUST be refused as not found.
- **FR-012**: The state the star shows MUST be the state that is stored, so that reloading the page,
  opening it elsewhere, or reading the people list all agree.
- **FR-013**: The feature MUST be usable at the small screen sizes the contact page already supports,
  and the star MUST remain a large enough target to press on a touch screen.
- **FR-014**: The capability MUST state where its API coverage is. No endpoint ships with this
  feature, because the product has no contacts API surface to hang one off. The operations that
  change the mark MUST therefore stay in reusable operations that a later endpoint calls without
  rework, and the endpoint's shape MUST be written down now so the later work inherits it rather
  than deciding it. Resolved in `research.md` decision 1, contract in
  `contracts/favourite-a-contact.md`.

### Key Entities

- **Contact**: the person whose page is being looked at. Already carries a favourite mark, already
  carries whether it is archived and partial, and belongs to exactly one vault of one account.
- **Favourite mark**: a yes or no on a contact, set by the person who owns the account. It has no
  history of its own beyond the activity entries, and it is not shared with anybody, because an
  account is one person.
- **Activity entry**: the append-only record that this contact was pinned or unpinned, carrying the
  contact's name so the entry still reads if the contact is later renamed.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A user looking at a contact can make them a favourite in one press, without leaving the
  page, and sees the result within one second.
- **SC-002**: The favourite mark can be changed at any point in a contact's life, not only while the
  contact is being written down. Measured as: the number of screens on which the mark can be set goes
  from one to two, and the number of contacts whose mark can never be changed goes to zero.
- **SC-003**: 100% of the control's function is available to a keyboard-only user and to a screen
  reader user, verified against WCAG 2.2 AA for the contact page.
- **SC-004**: Both states of the star are distinguishable in a greyscale rendering by 100% of
  reviewers asked.
- **SC-005**: Every string the feature introduces exists in all nine configured locales, verified by
  the project's translation check reporting nothing missing.
- **SC-006**: A change made on the page is reflected in the people list and its favourites filter on
  the next visit, in 100% of cases.
- **SC-007**: No contact belonging to another account, or reached through the wrong vault, can have
  its mark changed, in 100% of attempts.

## Assumptions

- **The mark itself already exists.** Contacts already carry a favourite mark, the people list
  already orders favourites first and already offers a favourites filter, and the account activity
  already has entries for pinning and unpinning a contact. This feature is about reaching that mark
  from the contact's page, not about inventing it.
- **The star is already drawn there.** The contact page already shows a star beside the avatar, but
  only for a contact who is already a favourite, and it is decoration rather than a control. Making
  it appear in both states and making it operable is part of this feature.
- **Scope is the contact page only.** The user asked for the star on the contact page. Adding the
  same control to the rows of the people list, to search results, or anywhere else is out of scope,
  and so is any bulk operation over several contacts.
- **No new way to record the mark.** The existing operations for pinning and unpinning a contact are
  reused rather than reimplemented, so the activity entries and the account checks stay identical to
  what the creation form already produces.
- **No confirmation step.** Removing a favourite mark destroys nothing and is undone by pressing the
  star again, so the product's rule about warning before a destructive action does not apply.
- **No limit on favourites.** Nothing caps how many contacts an account may mark, and marking many
  simply means the top of the list is longer.
- **One person per account.** The product is mono-user, so a favourite is not "somebody's favourite"
  and there is no question of one person's marks being visible to another.
- **The mark is not part of a record's frozen structure.** It is a property of the contact rather
  than of the template version they render from, so changing it does not create a version, does not
  require a snapshot, and cannot alter what anybody else already sees.
