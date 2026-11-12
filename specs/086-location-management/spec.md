# Feature Specification: Location Management

**Feature Branch**: `2026-10-25-location-management`
**Created**: 2026-10-25 · **Status**: Draft
**Input**: "Implémente la gestion des lieux (Location) dans Monica pour pouvoir situer les biens (Possession). Un lieu peut représenter une ville, une maison, une pièce ou un rangement. […] Interdire les cycles et les parents appartenant à un autre vault. Bloquer la suppression d’un lieu contenant des sous-lieux ou des possessions […]. Je veux qu’on supporte un emoji possible par location." Full brief in the conversation; design: claude.ai design project `85e4275a`, `Emplacements.dc.html`.

Nothing in the account says where anything is. This adds the places of the account: a town, a house, a room, a
shelf, a box. A place can sit inside another, as deep as somebody wants, so that one day a thing can
be put in "Maison › Garage › Établi" or simply in "Maison". This first version records the places
only; putting things in them comes with possessions, later. The screen is "Emplacements" in French,
as in the design, and lives in the account settings, since one house holds the things of every vault.

## Clarifications

- Q: Which records can be put in a location in this version? → A: None. Locations are recorded on their own; linking things to them comes later.
- Q: Deleting a location that holds others: refused (brief) or contents moved up (design)? → A: Refused until the sub-locations are moved.
- Q: Are locations kept per vault or per account? → A: Per account, managed from the account settings with an entry of their own in its sidebar (asked after the first build, which kept them per vault).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Add a location (Priority: P1)
From the locations screen, somebody adds a name, and optionally a parent, an emoji and an address.

**Why this priority**: Nothing else exists until a location does. **Independent Test**: Add "Dunder Mifflin Scranton" with an address, "Warehouse" inside it, "Shelf 3" inside that; open the list.

**Acceptance Scenarios**:
1. **Given** only a name, **When** submitted, **Then** the location exists at the top level, with no emoji and no address.
2. **Given** an empty name, **When** submitted, **Then** the form refuses it and says a name is needed.
3. **Given** a parent chosen in the list, **When** submitted, **Then** the location appears under that parent.
4. **Given** the parent list, **When** opened, **Then** every location of this account is offered by its full path, and none of another account.
5. **Given** an emoji picked among the suggestions or typed in the free field, **When** submitted, **Then** the location shows it instead of the default icon.
6. **Given** the "add inside" button on a row, **When** pressed, **Then** the form opens with that row as parent and says so in its title.
7. **Given** a parent with an address and a new location without one, **When** the form is shown, **Then** the address field says which address applies if left empty.

### User Story 2 - See the locations of the account as a tree (Priority: P1)
The list shows every location of the account, each followed by the locations inside it, indented,
sorted by name at every level.

**Why this priority**: The entry point to every other action. **Independent Test**: Seed three levels in one account and a location in another; open the list.

**Acceptance Scenarios**:
1. **Given** locations in two accounts, **When** the list opens, **Then** only this account's appear.
2. **Given** a location with an address, **When** listed, **Then** its line shows the address.
3. **Given** a location without an address inside one that has one, at any depth, **When** listed, **Then** its line says it is at the address of the nearest one that has it.
4. **Given** a location holding others, **When** listed, **Then** its line says how many it holds.
5. **Given** two "Garage" under different parents, **When** offered in a parent list, **Then** they read "Maison › Garage" and "Chalet › Garage".
6. **Given** no location, **When** the list opens, **Then** an empty state suggests starting with a home or a building.

### User Story 3 - Edit a location (Priority: P2)
The same form, prefilled, changes the name, the parent, the emoji or the address.

**Why this priority**: Correcting matters, after entering and reading. **Independent Test**: Rename "Warehouse" to "Annex", move "Shelf 3" under it, remove its emoji.

**Acceptance Scenarios**:
1. **Given** changed fields, **When** saved, **Then** the list shows them, and the location sits under its new parent with everything inside it.
2. **Given** the parent list while editing, **When** opened, **Then** the location itself and every location inside it are left out.
3. **Given** a parent that is the location or one inside it, submitted anyway, **When** saved, **Then** it is refused, saying it would put the location inside itself.
4. **Given** a parent of another account, submitted anyway, **When** saved, **Then** it is refused.
5. **Given** the parent cleared, **When** saved, **Then** the location moves to the top level.

### User Story 4 - Delete a location (Priority: P2)
Each row has a delete control. It asks first, and refuses while the location holds others.

**Why this priority**: Tidying up matters less than protecting what is inside. **Independent Test**: Try deleting a location holding a sub-location, then an empty one.

**Acceptance Scenarios**:
1. **Given** an empty location, **When** delete is pressed, **Then** a confirmation asks first, and nothing happens until confirmed.
2. **Given** confirmed deletion, **When** done, **Then** the location is gone from the list.
3. **Given** a location holding others, **When** deletion is asked, **Then** it is refused, saying how many it holds and that they must be moved first.

### Edge Cases

- A location is moved or renamed: everything inside moves with it, and every path containing it changes.
- Two locations at the same level share a name: allowed; the path and the tree tell them apart. An emoji field holding text, or more than one emoji: refused.
- A tree ten levels deep stays readable through indentation; a deleted account takes its locations with it; deleting a vault leaves them.
- No address on the location or any ancestor: the line says it has no address.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A location MUST belong to exactly one account, shared by all its vaults; another account's returns not found.
- **FR-002**: A location MUST have a name, and MAY have one parent, one emoji and one address. No type and no level is required.
- **FR-003**: A location's parent MUST belong to the same account.
- **FR-004**: A location's parent MUST NOT be the location itself or any location inside it, at any depth.
- **FR-005**: Users MUST be able to create, edit and delete locations, and list them as a tree sorted by name at every level.
- **FR-006**: Wherever a location is chosen outside the tree, it MUST be named by its full path, its parts separated by "›".
- **FR-007**: The emoji MUST be one emoji; the form offers twelve suggestions, a free field for any other, and none.
- **FR-008**: A location with no emoji MUST show a default icon.
- **FR-009**: The address MUST be free text over several lines. Nothing geocodes it and no map is shown.
- **FR-010**: A location without an address MUST be described by its nearest ancestor's address, without copying it into its own record.
- **FR-011**: Deleting a location MUST ask for confirmation first.
- **FR-012**: Deleting a location that holds others MUST be refused, saying how many and that they must be moved first. Nothing is moved on the person's behalf.
- **FR-013**: A location MUST NOT be linked to any other record (contact, vehicle, owned item) in this version.
- **FR-014**: A location MUST stay a record of its own: recording an owned house creates no location, and the reverse.
- **FR-015**: Creating, editing and deleting a location MUST be recorded in the changelog.
- **FR-016**: Every control MUST meet WCAG 2.2 AA: the nesting level of each location is announced, the emoji is a single choice, and a refused delete says why.
- **FR-017**: Every sentence MUST be translatable, and counted phrases MUST vary with the number in every configured locale.

### Key Entities

- **Location**: a place of the account. Name, optional parent location, optional emoji, optional address. Holds other locations, and later the things kept there.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody records a four-level chain (town, house, room, shelf) in under two minutes with the "add inside" button.
- **SC-002**: Every attempt to create a cycle or to borrow a parent from another account is refused, whatever the request sent.
- **SC-003**: No location holding another can be deleted, and the refusal says how many have to be moved.
- **SC-004**: Two locations with the same name are always told apart where one is chosen.
- **SC-005**: Every business rule above has an automated test that fails when the rule is removed.

## Assumptions

- The screen lives in the account settings, with its own sidebar entry; the form opens in a panel above the list.
- The path separator is "›", as in the design and the breadcrumb, rather than the brief's "→".
- The twelve suggestions are the design's: 🏠 🏡 🏢 🛋️ 🛏️ 🍳 🖥️ 🚗 🗄️ 📚 📦 🧰.
- No depth limit and no manual ordering; no location is created automatically for an owned house. An account starts with a house and five rooms (specification 097).
- When possessions arrive, the delete rule extends to refuse a location that holds one, as the brief asks.
