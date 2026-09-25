# Feature Specification: Contacts and more

**Feature Branch**: `2026-09-25-entity-types-screen` | **Created**: 2026-09-25 | **Status**: Draft

**Input**: "A new Entity type screen based on `Contacts and more.dc.html` (claude.ai/design project
`eaa519cf`). This will require wording change to make it more clear to the user what it does. Touch
only this screen, we'll do the rest after."

## Context

The settings screen listing entity types is called **Kinds of record** and speaks of "types" and
"records". Readers do not connect it with the contacts they already keep. The design renames it
**Contacts and more**, calls each record a **profile** and each entity type a **profile type**,
shows three example profiles side by side (a contact, a pet, a company) to explain the idea, and
lists Contacts first as the built in type the reader already uses.

Only the list screen (`settings.entityTypes.index`) changes. The settings sidebar entry, the
settings home card, the create and edit form, and every other string keep their wording until the
follow up.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Understand what the screen is for (Priority: P1)

Dwight opens the screen without knowing what an entity type is. The heading reads "Contacts and
more", the line under it explains that he can create other kinds of profile for pets, companies or
things, and three example cards show a contact (Judy Geller), a pet (Garfield) and a company
(Central Perk), each with example field names and a caption.

**Independent Test**: open the screen for an account with no custom type and check the heading, the
explanation, the three example cards and the note under them.

**Acceptance Scenarios**:

1. **Given** any account, **Then** the page title and heading read "Contacts and more", with the
   design's explanation under the heading.
2. **Given** any account, **Then** three example cards show: Contacts marked "Built in", Pets and
   Companies marked "Example", each with a name, three field names and a caption.
3. **Given** the example cards, **Then** the empty field bars are hidden from assistive technology
   and the field names are read as a description list.
4. **Given** the example cards, **Then** a note under them says the fields are examples, that a type
   says what is kept track of and its templates define the fields, and that pets and companies do
   not use the relationships people have.

---

### User Story 2 - Create a profile type (Priority: P1)

Under the examples, a primary button "Create a profile type" opens the existing create form, next
to a line explaining that one creates the kind ("Pets") first and the individual profiles
("Garfield") afterward.

**Independent Test**: the button links to `settings.entityTypes.new`.

**Acceptance Scenarios**:

1. **Given** any account, **When** Dwight activates "Create a profile type", **Then** the existing
   create form opens, unchanged.

---

### User Story 3 - See and order the profile types (Priority: P2)

Under "Your profile types", the list starts with **Contacts**, marked "Built in", with the number of
contacts across every vault of the account and "Can’t be deleted" next to a lock. The account's own
types follow, each with its icon, plural name and number of profiles, and can be reordered by
dragging as today.

**Independent Test**: an account with two custom types and some contacts; open the screen and check
the order, the counts and the Contacts row.

**Acceptance Scenarios**:

1. **Given** an account with no custom type, **Then** the list shows only the Contacts row and no
   drag instruction.
2. **Given** at least one custom type, **Then** the line "Drag a row to change the order in the
   vault navigation." shows beside the heading, and each custom row can be dragged and reordered
   with the keyboard as today.
3. **Given** the Contacts row, **Then** it stays first, cannot be dragged, has no Edit control, and
   says "Built in" and "Can’t be deleted" in text, not by the lock icon alone.
4. **Given** a custom type, **Then** its row shows the plural name, "N profiles" through a counted
   phrase ("No profiles yet" at zero), an Edit link to the existing edit form, and "Not active"
   when it is turned off.
5. **Given** another account's types and contacts, **Then** none of them are listed or counted.

### Edge Cases

- A type with a very long plural name wraps inside its row without pushing the Edit link off
  screen.
- At 390px the example cards stack one per line and the create line wraps under the button.
- Right to left locales mirror the rows and cards.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The screen MUST be titled "Contacts and more" and show the design's explanation.
- **FR-002**: The screen MUST show the three example profiles from the design; they are fixed
  illustrations, not data, and every string in them MUST be translatable.
- **FR-003**: The screen MUST offer "Create a profile type", leading to the existing create form.
- **FR-004**: The list MUST start with a Contacts row counting the account's contacts across all its
  vaults, not reorderable, not editable, and stating in text that it is built in and permanent.
- **FR-005**: Custom type rows MUST keep today's reordering, Edit link and inactive marker, and show
  the plural name and a counted number of profiles.
- **FR-006**: The drag instruction MUST show only when there is at least one custom type.
- **FR-007**: The slug line, the singular name next to the plural, the "types · records" summary and
  the "No types yet" empty state MUST be removed from this screen.
- **FR-008**: The footnote about turning a type off MUST stay, reworded to speak of profiles.
- **FR-009**: No string outside this screen may change.
- **FR-010**: The screen MUST meet WCAG 2.2 AA (keyboard, screen reader, contrast, 200% zoom).

### Key Entities

- **Entity type** (shown as "profile type"): unchanged. The Contacts row is not an entity type; it
  is drawn by the screen from the account's contact count.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A reader who has never seen the screen can say, after reading it, that a profile type
  lets them keep profiles for things other than people.
- **SC-002**: Every string on the screen is translated in every configured locale, and the
  localization check passes.
- **SC-003**: The screen is operable end to end with the keyboard alone, including reordering.
- **SC-004**: No other screen's wording changes.

## Assumptions

- The design omits Edit, the inactive marker and the footnote; they are kept because removing them
  would take away what the screen does today. The design is followed for layout and wording.
- The design shows a drag handle on the Contacts row; contacts have no position among entity types,
  so the row is pinned first instead.
- "Profile" and "profile type" are the new words on this screen only; the glossary entry in
  `.claude/rules/translations.md` is revisited in the follow up that renames the rest.
- The sidebar keeps "Kinds of record" until the follow up, so the heading and the sidebar entry
  differ for a while.
