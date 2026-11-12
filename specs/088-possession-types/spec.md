# Feature Specification: Possession Types

**Feature Branch**: `2026-10-25-location-management`
**Created**: 2026-10-26 · **Status**: Draft
**Input**: "je veux gerer les collection types. on doit supporter les modeles suivants: Possession, PossessionType, PossessionField, PossessionFieldValue […] dans les settings du account de monica." Then: "ajoute aussi le support mcp et api + docs".

An account will one day record what it owns: a Bosch dishwasher, a car, a guitar. Each kind of thing
carries its own details: a serial number and a power rating for an appliance, a plate for a car. This
adds the kinds, called possession types, and the fields each one carries, managed from the account
settings under the Possessions group. Possessions themselves,
and the values their fields hold, are a later feature.

## Clarifications

- Q: Fields through the existing field engine, as life event types do, or in dedicated tables? → A: Dedicated tables. This departs from constitution principle VII, which the plan must amend.
- Q: Possessions and their values? → A: Another feature. Q: Field groups and JSON export and import? → A: Included. Q: Branch? → A: The current one.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Create, list, name, colour and delete types (Priority: P1)
The settings screen lists the account's types with colour, name, group and field counts. "New type" creates one and opens its screen, where the name is edited in place and the colour picked from a fixed palette.

**Why this priority**: Nothing else exists until a type does. **Independent Test**: Create "Office equipment" and "Vehicle"; rename, recolour, open the list.

**Acceptance Scenarios**:
1. **Given** types in two accounts, **When** the list opens, **Then** only this account's appear, sorted by name.
2. **Given** "New type" pressed, **Then** a type named "Untitled type" in the user's language exists with the palette's first colour and its screen opens; new groups and fields are named the same way.
3. **Given** an empty name, or a colour outside the palette, **When** saved, **Then** it is refused, saying why.
4. **Given** no type, **When** the list opens, **Then** an empty state says types define what a possession carries and offers to create or import one.
5. **Given** delete chosen in the type's menu, **Then** the confirmation says its groups and fields go with it; nothing happens until confirmed.

### User Story 2 - Define the fields of a type (Priority: P1)
Each field has a name and a kind, edited in place and saved as changed; fields are added, moved up or down, and removed.

**Why this priority**: The fields are the reason a type exists. **Independent Test**: On "Office equipment", add "Serial number" (text), "Power" (number), "Warranty end" (date), "Condition" (select: New, Used, Broken), "Rating"; reorder; remove one.

**Acceptance Scenarios**:
1. **Given** "Add field", **When** pressed, **Then** an unnamed text field appears last.
2. **Given** a name or kind changed, **When** the control is left, **Then** it is saved without a save button, and the change is announced.
3. **Given** the kind set to select, **Then** its options are listed, an option can be added, and removing one asks first; with no option, the screen says it needs one.
4. **Given** move up on the second field, **Then** it becomes first; the first cannot move up, the last cannot move down.
5. **Given** remove on a field, **When** confirmed, **Then** the field is gone.

### User Story 3 - Group fields (Priority: P2)
Fields can sit in named groups ("Purchase", "Technical details") or stand alone. Groups are added, renamed, reordered and removed; each holds its own ordered fields.

**Why this priority**: A type with many fields stays readable. **Independent Test**: Add "Purchase" holding "Price" and "Shop", keep "Serial number" standalone, move the group, remove it.

**Acceptance Scenarios**:
1. **Given** "Add group", **Then** an unnamed group appears last, with "+ Field" adding a field inside it.
2. **Given** move on a field, **Then** it moves only among the fields of its own group, or among the standalone fields.
3. **Given** a group removed and confirmed, **Then** its fields are kept and become standalone fields, as the confirmation says.

### User Story 4 - Export and import a type as JSON (Priority: P2)
The type's menu opens an export screen showing its structure as JSON, with a copy button. The list offers "Import JSON", which creates a new type from a pasted document.

**Why this priority**: Sharing and backing up a proven structure. **Independent Test**: Export "Office equipment", import the copy, compare.

**Acceptance Scenarios**:
1. **Given** an export, **Then** it holds the name, colour, groups and fields in order with kinds and options, and a format version; never anything else.
2. **Given** an exported document imported, **Then** a new type identical in structure exists; nothing existing is changed.
3. **Given** invalid JSON, a missing format version, an unknown kind, or a document over the size limit, **When** imported, **Then** nothing is created and every problem is listed, naming the group or field at fault; the obvious ones show as soon as it is pasted.
4. **Given** a select field without options in the document, **When** imported, **Then** it arrives without options, as the screen allows one to be (US2.3), so that every export imports back.

### User Story 5 - Through the API and an assistant (Priority: P2)
Everything the screens do, a key does through the API and an assistant through the MCP server, and both are documented.

**Why this priority**: Principle X: a capability is not complete until the API covers it. **Independent Test**: With a key alone, build, reorder, export, import and delete a type; repeat through MCP.

**Acceptance Scenarios**:
1. **Given** a key, **When** types are listed or read, **Then** only its account's are returned, with groups and fields in order.
2. **Given** a type, group or field of another account, **When** reached, **Then** the answer is not found.
3. **Given** invalid input, **Then** it is refused with the screen's wording, in the language of the key's owner.
4. **Given** the MCP tool list, **Then** there is one tool per API capability, reading tools read only, deleting tools destructive.

### Edge Cases

- Shared names are allowed; blank options are ignored, duplicate options refused. A field leaving select loses its options.
- An import beyond a limit is refused with the limit named. A deleted account takes its types.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A possession type MUST belong to one account, shared by its vaults; another account's returns not found.
- **FR-002**: A type MUST have a name and one colour from a fixed palette.
- **FR-003**: A field MUST belong to one type, at most one group of that type, and have a name, a kind and a position.
- **FR-004**: The kinds MUST be text, number, date, yes or no, select and rating from one to five.
- **FR-005**: A select field MUST carry an ordered list of distinct, non-empty options.
- **FR-006**: A group MUST belong to one type and have a name and a position; groups are ordered among themselves.
- **FR-007**: Users MUST be able to create, rename, recolour and delete types; add, edit, reorder and remove groups and fields.
- **FR-008**: Edits MUST be saved as changed, and each save MUST be announced to screen readers.
- **FR-009**: Deleting a type, a group, a field or an option MUST ask first; deleting a type deletes its groups and fields; removing a group keeps its fields as standalone.
- **FR-010**: Export MUST produce a versioned JSON document of the structure only; import MUST create a new type from it or create nothing.
- **FR-011**: Import MUST refuse a document over a size limit or beyond limits on groups, fields, options and name length.
- **FR-012**: Creating, changing, deleting and importing MUST be written to the changelog; the settings sidebar's Possessions group MUST gain a "Possession types" entry.
- **FR-013**: The API MUST cover every capability of FR-007 and FR-010 with the screens' rules and wording; the MCP server MUST offer one tool per API capability.
- **FR-014**: The API reference, the MCP documentation and the product guide MUST describe possession types.
- **FR-015**: Every control MUST meet WCAG 2.2 AA; reordering and colour choice MUST work by keyboard, and a colour MUST be named.
- **FR-016**: Every sentence MUST be translatable in every configured locale, and counted phrases MUST vary with the number.

### Key Entities

- **PossessionType**: a kind of thing the account owns. Name, colour. Holds groups and standalone fields.
- **PossessionFieldGroup**: a named, ordered set of fields within a type.
- **PossessionField**: one detail a type asks for. Name, kind, position, optional group, options when select.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody creates a type with two groups and five fields, one a select with three options, in under three minutes.
- **SC-002**: An exported type imported back is identical in structure, every time.
- **SC-003**: Every attempt to reach another account's type, group or field, by screen, API or MCP, returns not found.
- **SC-004**: For the same input, the screen, the API and the MCP server give the same result; the docs list exactly what exists.
- **SC-005**: Every business rule above has an automated test that fails when the rule is removed.

## Assumptions

- The palette is the vaults' named colours; the export format and import limits are those of research R9; an import with no colour takes Slate. Specification 089 seeds 25 types into every account.
- When possessions arrive, deleting a field or type that holds values is decided then, against principle VIII.
