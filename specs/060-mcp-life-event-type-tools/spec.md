# Feature Specification: MCP tools for life event types

**Feature Branch**: `2026-09-25-mcp-life-event-types` (based on `2026-09-25-mcp-relationship-types`) |
**Created**: 2026-09-25 | **Status**: Draft

**Input**: "manage life event categories and types in mcp now. add docs too. base yourself on this
branch so we can move forward easily without merge issues"

## Context

The MCP server lets an assistant list life event categories (`list-life-event-categories`) and nothing
else about life events: it cannot even list the types. The JSON API already reads, creates, updates,
deletes, switches on or off and moves both a life event category and a life event type, lists the types
of a category, and reads and replaces the fields a type asks for. The constitution (principle X) says
the MCP server exposes the API and never bypasses it, so every tool below mirrors one of those
endpoints, and no API change is needed.

A life event type (Moved house, Started a job) is what somebody picks when they record something that
happened to a contact. It is filed under a category (Work, Home, Health), and it can ask for fields of
its own (the new address, the employer). The web settings screen for life event types manages all
three, so this feature covers all three.

The branch is based on the relationship types branch (PR #246), not on `main`, so the two features
touch the same MCP files without conflicting.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Create and shape a life event type with an assistant (Priority: P1)

Somebody asks their assistant "I want to record when someone runs a marathon". The assistant finds the
Health category, creates a type named Ran a marathon with an icon and a colour it picked from the
allowed ones, and the new type is offered the next time somebody records a life event.

**Why this priority**: adding the kind of event somebody is missing is the core of the request, and
nothing else about life events can be done over MCP today.

**Independent Test**: through MCP alone, list the types, create one in an existing category, read it
back, change only its colour, and see it on the web settings screen.

**Acceptance Scenarios**:

1. **Given** the account, **When** the assistant lists the life event types, **Then** it receives every
   type of the account, grouped by category order then type order, in the API's shape.
2. **Given** a category of the account, **When** the assistant creates a type with a name, and
   optionally a description, an icon and a colour, **Then** it exists at the end of that category.
3. **Given** an icon or colour outside the allowed ones, **When** the assistant creates or updates a
   type, **Then** it is refused, and the tool's description lists the allowed values so the assistant
   can pick one without guessing.
4. **Given** a type, **When** the assistant reads it, **Then** it receives the same shape the API
   returns, including its category's identifier and whether it came with the account.
5. **Given** a type, **When** the assistant updates its name, description, icon, colour or category,
   **Then** the type reflects it and anything left out keeps its value.

### User Story 2 - Organise and retire life event types (Priority: P2)

Somebody asks their assistant to stop offering Got a tattoo, to move Graduated to the top of its
category, or to delete a type they added by mistake.

**Why this priority**: useful once the defaults have been adjusted, less frequent than adding a type.

**Independent Test**: through MCP alone, deactivate a type, move it first in its category, reactivate
it, and delete one the person created and never used.

**Acceptance Scenarios**:

1. **Given** an active type, **When** the assistant switches it off, **Then** it is no longer offered
   when recording a life event while life events already recorded with it stay, and switching it to
   the state it is already in changes nothing.
2. **Given** a type, **When** the assistant moves it to a position, **Then** the order within its
   category reflects it exactly as the API would.
3. **Given** a type the person created and no life event uses, **When** the assistant deletes it,
   **Then** it is gone, and the tool is marked to clients as destructive so they ask the person first.
4. **Given** a type that came with the account, or one life events were recorded with, **When** the
   assistant deletes it, **Then** it is refused with the sentence the settings screen shows ("The type
   came with your account and stays." or "Life events were recorded with this type, so it stays.") and
   nothing changes.

### User Story 3 - Manage the categories types are filed under (Priority: P2)

Somebody asks their assistant for a new category, Sport, to hold Ran a marathon and Climbed a
mountain, and later to move it after Health.

**Why this priority**: needed as soon as a new type fits none of the supplied categories.

**Independent Test**: through MCP alone, create a category, read it, rename it, list its types, move
it, switch it off and on, and delete an empty one.

**Acceptance Scenarios**:

1. **Given** the account, **When** the assistant creates a category with a name, **Then** it exists at
   the end of the list with no type in it.
2. **Given** a category, **When** the assistant renames it, switches it off or on, or moves it,
   **Then** the result is exactly what the API would produce, and repeating the current state changes
   nothing.
3. **Given** a category, **When** the assistant asks for its types, **Then** it receives them in the
   order they are offered.
4. **Given** a category the person created and holding no type, **When** the assistant deletes it,
   **Then** it is gone, and the tool is marked destructive; **Given** one that came with the account or
   still holds types, **Then** it is refused with the settings screen's sentence.

### User Story 4 - Choose what a life event type asks for (Priority: P3)

Somebody asks their assistant to make Ran a marathon ask for the race name, the time it took and the
city, grouped under Race.

**Why this priority**: a type is usable without fields of its own; fields make it richer but are the
least frequent change.

**Independent Test**: through MCP alone, read a type's fields, replace them with a group holding three
fields, read them back, and see that a life event recorded before the change still shows what it was
written with.

**Acceptance Scenarios**:

1. **Given** a type, **When** the assistant reads its fields, **Then** it receives its groups and their
   fields in the API's shape, including the key that identifies each field.
2. **Given** a type, **When** the assistant replaces its fields with a set of groups and fields, **Then**
   the type asks for exactly that set, a field sent with an existing key keeps its identity, and a field
   left out is no longer asked for.
3. **Given** a life event recorded before the change, **When** the fields are replaced, **Then** it
   keeps what it was written with.
4. **Given** a field kind that does not exist, a counted setting that is not a whole number, or a
   reference the account cannot reach, **When** the assistant replaces the fields, **Then** it is
   refused as the API refuses it and nothing changes.
5. **Given** the replace tool, **Then** it is marked to clients as destructive, since a field left out
   stops being asked for.

### User Story 5 - Know what the assistant can now change (Priority: P3)

A visitor to `/docs/mcp` reads which life event tools exist, which of them change data, and which ones
delete.

**Why this priority**: a key hands an assistant the whole account; the page is where somebody decides
whether to allow that.

**Independent Test**: compare the page's tools table with the server's registered tools.

**Acceptance Scenarios**:

1. **Given** "Available tools", **Then** every new tool is listed by its exact name with one sentence,
   the reading ones next to `list-life-event-categories`, the changing ones in a group badged as
   changing data, and each deleting or replacing tool says what it removes.
2. **Given** `/docs/mcp.md`, **Then** it carries the same tools.
3. **Given** the tool count in the prose, **Then** it matches the table and the server.
4. **Given** the page, **Then** it says in a few sentences what a life event type and a category are,
   that a type can ask for fields of its own and that replacing them never changes a life event already
   recorded, and that types and categories supplied with the account, or in use, cannot be deleted.

### Edge Cases

- A type or category of another account, or an identifier that is not one, answers not found in a
  translated sentence, never forbidden, including when named as the category of a type.
- Updating a type keeps whether it is active; only the activation tool changes that, as over the API.
- Updating with the same values changes nothing visible and still answers with the record.
- A type or category switched off stays readable and editable, and existing life events stay.
- Replacing the fields of a type with an empty set leaves a type that asks for nothing of its own.
- Every change is written to the account's journal as arriving from an assistant.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The server MUST offer one tool for each of these API capabilities on life event types:
  list, read one, create, update, delete, set activation, set position, read fields, replace fields.
- **FR-002**: The server MUST offer one tool for each of these API capabilities on life event
  categories: read one, list the types of one, create, update, delete, set activation, set position.
- **FR-003**: Each tool MUST validate, refuse and answer in the same cases as its API endpoint, return
  the same resource shape, and name the endpoint it mirrors. Refusals MUST be in the caller's language.
- **FR-004**: Every tool MUST be confined to the key's account; a record outside it MUST be reported as
  not existing, in a translated sentence.
- **FR-005**: The reading tools MUST be marked read only; the two delete tools and the replace fields
  tool MUST be marked destructive to the client.
- **FR-006**: The create and update tools for a type MUST state the allowed icons and colours in their
  input description, taken from the same lists the API validates against, so the two cannot drift.
- **FR-007**: The replace fields tool MUST describe in its input the shape of a group and of a field,
  the allowed field kinds, and that the set sent replaces the whole set.
- **FR-008**: Every change MUST be recorded in the account journal with the assistant as its source, as
  the existing changing tools are.
- **FR-009**: `/docs/mcp` and `/docs/mcp.md` MUST list every registered tool, and the suite MUST fail
  when the page and the server disagree (the existing check, extended to the new tools).
- **FR-010**: `/docs/mcp` MUST explain in a few sentences what a life event type, its category and its
  fields are, in both the page and its Markdown.

### Key Entities

- **Life event type**: a kind of thing that happens to a contact (Moved house). Has a name, a
  description, an icon, a colour, a category, a position within it, whether it is offered, whether it
  came with the account, and the fields it asks for.
- **Life event category**: a group of life event types (Work, Home). Has a name, a position, whether it
  is offered, and whether it came with the account.
- **Field of a type**: one thing a life event of that type asks for (the new address). Has a key, a
  label, a kind, whether it is required, a help text, settings and choices, and may sit in a named
  group.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Everything the web settings screen for life event types can do, for types, categories
  and fields, can be done through MCP.
- **SC-002**: An assistant can turn "record when someone runs a marathon" into a new type with its own
  fields, in the right category, using only identifiers the tools themselves handed it.
- **SC-003**: Every tool name on `/docs/mcp` matches the server; the suite fails otherwise.
- **SC-004**: For each new tool, the same input sent to the tool and to its API endpoint produces the
  same record and a refusal in the same case.

## Assumptions

- Fields are in scope, because the web screen edits them with the type and the API exposes them.
  Cutting them would leave SC-001 unmet and FR-001 at seven tools instead of nine.
- Life events themselves (recording that Jim moved house) are out of scope: the request names
  categories and types.
- Replacing the fields is exposed as one whole set, as the API and the screen do; there is no tool to
  add or remove a single field.
- Deleting is exposed because "manage" includes it and the API allows it; clients are told those tools
  are destructive so they confirm with the person.
- The API reference needs no change, since no endpoint is added or changed.
- Tool copy is English, like the existing tools; refusals reuse the translated sentences the settings
  screen shows, as in spec 059.
