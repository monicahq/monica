# Feature Specification: MCP tools for relationship types

**Feature Branch**: `2026-09-25-mcp-relationship-types` | **Created**: 2026-09-25 | **Status**: Draft

**Input**: "i want to manage relationsip types in mcp now. add docs too."

## Context

The MCP server lets an assistant list relationship types (`list-relationship-types`) and relationship
categories (`list-relationship-categories`) and change nothing about them. The JSON API already reads
one, creates, updates, deletes, switches on or off and moves both a relationship type and a
relationship category. The constitution (principle X) says the MCP server exposes the API and never
bypasses it, so every tool below mirrors one of those endpoints, and no API change is needed.

A relationship type always belongs to a category (Family, Work, Love), and the web settings screen for
relationship types manages both on one page. So "managing relationship types" covers the categories
they are filed under too; without them an assistant cannot file a new type anywhere new.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Create and shape a relationship type with an assistant (Priority: P1)

Somebody asks their assistant "add a godparent relationship". The assistant finds the Family category
with the tool that already lists them, creates a type named Godparent with the roles godparent and
godchild, and the new type is offered the next time somebody links two contacts.

**Why this priority**: a relationship type is what somebody picks when they link two contacts; being
able to add the one they are missing is the core of the request.

**Independent Test**: through MCP alone, create a relationship type in an existing category, read it
back, rename it, and see it in `list-relationship-types` and on the web settings screen.

**Acceptance Scenarios**:

1. **Given** a category of the account, **When** the assistant creates a type with a name and a first
   role, and optionally a second role, whether it reads the same both ways, and whether it is active,
   **Then** it exists in that category, and what it left out takes the same defaults as the API (same
   both ways, active, the second role equal to the first).
2. **Given** a type that does not read the same both ways, **When** the assistant leaves out the second
   role, **Then** it is refused with the API's wording and nothing is written.
3. **Given** a name already used by another type in the account, **When** the assistant creates or
   renames a type to it, **Then** it is refused with the API's wording and nothing is written.
4. **Given** a relationship type, **When** the assistant reads it, **Then** it receives the same shape
   the API returns, including its category's identifier and whether it came with the account.
5. **Given** a type, **When** the assistant updates its name, roles or category, **Then** the type
   reflects it; **When** relationships are already written with it and the assistant tries to change
   whether it reads the same both ways, **Then** it is refused with the API's wording.

### User Story 2 - Organise and retire relationship types (Priority: P2)

Somebody asks their assistant to stop offering Ex-partner, to move Sibling to the top of Family, or to
delete a type they added by mistake.

**Why this priority**: useful once the defaults have been adjusted, less frequent than adding a type.

**Independent Test**: through MCP alone, deactivate a type, move it first in its category, reactivate
it, and delete one the person created and never used.

**Acceptance Scenarios**:

1. **Given** an active type, **When** the assistant switches it off, **Then** it is no longer offered
   when linking contacts while existing relationships stay, and switching it to the state it is
   already in changes nothing.
2. **Given** a type, **When** the assistant moves it to a position, **Then** the order within its
   category reflects it exactly as the API would.
3. **Given** a type the person created and no relationship uses, **When** the assistant deletes it,
   **Then** it is gone, and the tool is marked to clients as destructive so they ask the person first.
4. **Given** a type that came with the account, or one relationships are written with, **When** the
   assistant deletes it, **Then** it is refused with the API's wording ("The type came with your
   account and stays." or "Relationships are written with this type, so it stays.") and nothing
   changes.

### User Story 3 - Manage the categories types are filed under (Priority: P2)

Somebody asks their assistant for a new category, Sports, to hold Teammate and Coach, and later to move
it after Work.

**Why this priority**: needed as soon as a new type fits none of the supplied categories; otherwise
rarer than changing types.

**Independent Test**: through MCP alone, create a category, read it, rename it, create a type in it,
move it, switch it off and on, and delete an empty one.

**Acceptance Scenarios**:

1. **Given** the account, **When** the assistant creates a category with a name, **Then** it exists at
   the end of the list with no type in it.
2. **Given** a category the person created, **When** the assistant renames it, **Then** it reflects the
   new name; **Given** a category that came with the account, **Then** renaming is refused with the
   API's wording.
3. **Given** a category, **When** the assistant switches it off or on or moves it to a position,
   **Then** the result is exactly what the API would produce, and repeating the current state changes
   nothing.
4. **Given** a category the person created and holding no type, **When** the assistant deletes it,
   **Then** it is gone, and the tool is marked destructive; **Given** one that came with the account or
   still holds types, **Then** it is refused with the API's wording.
5. **Given** a category, **When** the assistant asks for its types, **Then** it receives them in the
   API's shape.

### User Story 4 - Know what the assistant can now change (Priority: P3)

A visitor to `/docs/mcp` reads which relationship tools exist, which of them change data, and which
ones delete.

**Why this priority**: a key hands an assistant the whole account; the page is where somebody decides
whether to allow that.

**Independent Test**: compare the page's tools table with the server's registered tools.

**Acceptance Scenarios**:

1. **Given** "Available tools", **Then** every new tool is listed by its exact name with one sentence,
   the reading ones next to `list-relationship-types` and `list-relationship-categories`, the changing
   ones in a group badged as changing data, and each deleting tool says it deletes.
2. **Given** `/docs/mcp.md`, **Then** it carries the same tools.
3. **Given** the tool count in the prose, **Then** it matches the table and the server.
4. **Given** the page, **Then** it says in one or two sentences that a relationship type names how two
   contacts are linked, with a role for each side, that it is filed under a category, and that a type
   or category supplied with the account, or still in use, cannot be deleted.

### Edge Cases

- A type or category of another account, or an identifier that is not one, answers not found in a
  translated sentence, never forbidden. Creating or moving a type into another account's category is
  refused the same way the API refuses it.
- A type that reads the same both ways ends up with its second role equal to the first, whatever the
  assistant sent, as the API does.
- Updating a type keeps whether it is active; only the activation tool changes that, as over the API.
- Updating with the same values changes nothing visible and still answers with the record.
- A type or category switched off stays readable and editable, and existing relationships stay.
- Types with a behaviour of their own (the ones the application relies on, such as a partner) keep it;
  no tool can set or clear it, because the API cannot.
- Every change is written to the account's journal as arriving from an assistant.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The server MUST offer one tool for each of these API capabilities on relationship types:
  read one, create, update, delete, set activation, set position.
- **FR-002**: The server MUST offer one tool for each of these API capabilities on relationship
  categories: read one, list the types of one, create, update, delete, set activation, set position.
- **FR-003**: Each tool MUST validate, refuse and answer in the same cases as its API endpoint, return
  the same resource shape, and name the endpoint it mirrors. Refusals MUST be in the caller's language.
- **FR-004**: Every tool MUST be confined to the key's account; a record outside it MUST be reported as
  not existing, in a translated sentence.
- **FR-005**: The reading tools MUST be marked read only; the two delete tools MUST be marked
  destructive to the client.
- **FR-006**: The create and update tools for a type MUST say in their input description what the two
  roles mean and when the second one is required, so an assistant does not have to guess.
- **FR-007**: Every change MUST be recorded in the account journal with the assistant as its source, as
  the existing changing tools are.
- **FR-008**: `/docs/mcp` and `/docs/mcp.md` MUST list every registered tool, and the suite MUST fail
  when the page and the server disagree (the existing check, extended to the new tools).
- **FR-009**: `/docs/mcp` MUST explain in a sentence or two what a relationship type and a category
  are, in both the page and its Markdown.

### Key Entities

- **Relationship type**: how two contacts are linked (Parent, Colleague). Has a name, a role for each
  side (parent and child), whether it reads the same both ways, a category, a position within it,
  whether it is offered, whether it came with the account, and a count of relationships using it.
- **Relationship category**: a group of relationship types (Family, Work). Has a name, a position,
  whether it is offered, whether it came with the account, and a count of types.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Everything the web settings screen for relationship types can do, for types and for
  categories, can be done through MCP.
- **SC-002**: An assistant can turn "add a godparent relationship" into a new type in the right
  category using only identifiers the tools themselves handed it.
- **SC-003**: Every tool name on `/docs/mcp` matches the server; the suite fails otherwise.
- **SC-004**: For each new tool, the same input sent to the tool and to its API endpoint produces the
  same record and the same refusal.

## Assumptions

- Categories are in scope, because a type cannot exist without one and the web screen manages both.
  This adds seven tools to the six for types; cutting categories would leave SC-001 unmet.
- Relationships between two contacts (linking Michael and Jan as partners) are out of scope: the
  request names relationship types.
- Deleting is exposed because "manage" includes it and the API allows it; clients are told those tools
  are destructive so they confirm with the person.
- The API reference needs no change, since no endpoint is added or changed.
- Tool copy is English, like the existing tools. A tool refuses in exactly the cases its endpoint
  refuses, but with the translated sentence the web settings screen shows, because the actions' own
  messages are untranslated English and MCP answers in the caller's language. "The API's wording" in
  the scenarios above means the same refusal, not the same sentence.
