# Feature Specification: Possessions Empty State

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-10-26 | **Status**: Draft

**Input**: "same branch. implement the blank state when the possessions listing screen is empty."
Design: `All possessions - empty state.dc.html` (claude.ai/design project 85e4275a).

All possessions is a placeholder today ("Possessions are coming"). For a vault holding no possession,
this feature replaces it with a screen saying what a possession is and offering three ways in: add
one, start from a type, set up locations. The list itself is a later feature. The design draws an
icon per type, and types have none, so possession types gain an icon everywhere they are read or
written.

## Clarifications

### Session 2026-10-26
- Q: Types have a colour but no icon; the design draws one per type. → A: Add an icon to possession
  types: stored, chosen in settings, set on the defaults, carried by the API, the assistant, import
  and export. Migrations created on this branch are edited in place; older ones are not touched.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Understand the screen and add the first possession (Priority: P1)

Michael Scott opens All possessions in an empty vault, reads "Nothing here yet" and what a possession
keeps, selects "Add your first possession" and reaches the Add a possession form.

**Independent Test**: An empty vault shows the explanation and the button; the button opens the form.

1. **Given** an empty vault, **When** All possessions opens, **Then** it reads "All possessions", its
   description, "Nothing here yet", the explanation, and offers "Add your first possession".
2. **Given** a vault of another account, **When** its address is opened, **Then** the answer is not found.

### User Story 2 - Start from a type (Priority: P2)

Dwight Schrute sees "Or start from a type", a grid of his account's types with their icon, colour and
first fields, selects Cars, and the form opens with Cars chosen and its fields shown.

**Independent Test**: Selecting a tile opens the form with that type chosen.

1. **Given** an account with types, **When** the screen shows, **Then** up to eight are listed, each
   with its name, its icon in its colour, and the names of its first two fields.
2. **Given** a type without fields, **When** listed, **Then** its line says it has no field.
3. **Given** an account without types, **When** the screen shows, **Then** one sentence links to the
   possession types settings in place of the grid.

### User Story 3 - Learn how it works and reach locations (Priority: P3)

Pam Beesly reads three "How it works" cards, then a line naming the account's locations, and
selects "Set up locations".

**Independent Test**: The cards render; the locations line names and counts the account's own.

1. **Given** the screen, **When** shown, **Then** three steps appear and the third links to the
   possession tags settings.
2. **Given** locations, **When** shown, **Then** a line names up to three top level ones, counts all
   of them, and links to "Manage locations"; without locations the line is absent.

### User Story 4 - Give a type an icon (Priority: P2)

Angela Martin edits Cats in the type settings and picks the pet glyph; the tile shows it.

**Independent Test**: An icon set in settings is drawn, returned by API and assistant, and survives export.

1. **Given** a new account, **When** its default types are created, **Then** each carries an icon of
   its kind (a car for Cars, a gift for Jewellery).
2. **Given** a type without icon, **When** drawn, **Then** the catalogue's default glyph stands in.
3. **Given** a glyph outside the catalogue, **When** sent by settings, API, assistant or a file,
   **Then** it is refused.
4. **Given** a file exported before types had icons, **When** imported, **Then** it succeeds with none.

### Edge Cases

- Possessions that are sold or given away still count: the empty state shows only with none at all.
- More than three top level locations: three are named, then how many more.
- More than eight types: the first eight by name; the main button reaches the rest.
- An account without types reaches the screen by its address, though the sidebar hides it.
- Every count reads correctly at one and in every shipped language.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The empty state MUST show when the vault holds no possession; else the placeholder stays.
- **FR-002**: The page MUST be titled "All possessions", as the sidebar, with the line "Everything you
  own in this vault, where it is kept and what it is worth."
- **FR-003**: It MUST offer "Add your first possession" (the form) and "Set up locations" (the
  locations settings).
- **FR-004**: It MUST list up to eight types, each with its name, its icon in its colour, and its
  first two field names.
- **FR-005**: A type tile MUST open the Add a possession form with that type chosen.
- **FR-006**: Without types, a sentence and a link to the types settings MUST replace the grid.
- **FR-007**: It MUST show the three "How it works" steps; the third links to the tags settings.
- **FR-008**: With locations, it MUST name up to three top level ones, count all, and link to the
  locations settings.
- **FR-009**: Every link MUST work by keyboard; illustrations MUST be hidden from assistive technology.
- **FR-010**: Every string MUST be translated in every configured locale.
- **FR-011**: A possession type MUST carry an optional icon from the glyph catalogue sections and
  conditions use; without one, the catalogue's default glyph is drawn.
- **FR-012**: Every default type, including those the existing backfill creates, MUST carry an icon.
- **FR-013**: The type edit screen MUST let the icon be chosen and cleared.
- **FR-014**: The API and the assistant MUST return a type's icon and accept it on create and update;
  the API reference MUST say so.
- **FR-015**: Export MUST write the icon; import MUST read it when present and accept its absence.
  The export format version stays the same.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: The add form is one selection away, with or without a type chosen.
- **SC-002**: An empty vault never shows "Possessions are coming"; every link on it answers.
- **SC-003**: Types, locations and counts shown are the account's own, never fixed copy.
- **SC-004**: Every default type of a new account shows its own icon, never the stand-in glyph.

## Assumptions

- The "How it works" illustrations are fixed, translated examples from the design, not account data.
- Locations belong to the account, so every vault shows the same line.
- Several default types may share a glyph; adding glyphs to the catalogue is out of scope.
