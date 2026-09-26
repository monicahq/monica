# Feature Specification: MCP tools for entity types

**Feature Branch**: `2026-09-25-mcp-stuff-2` | **Created**: 2026-09-25 | **Status**: Draft

**Input**: "i want to manage Entity types in mcp now. add docs too."

## Context

The MCP server lets an assistant list entity types (`list-entity-types`) and change nothing about
them. The JSON API already reads one, creates, updates, deletes, switches on or off and moves an
entity type. The constitution (principle X) says the MCP server exposes the API and never bypasses
it, so every tool below mirrors one of those endpoints, and no API change is needed.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Create and shape an entity type with an assistant (Priority: P1)

Somebody asks their assistant "I want to keep track of my wine bottles". The assistant creates an
entity type named Wine bottle / Wine bottles, with an icon and a colour it picked from the allowed
ones, and then, with the template tools that already exist, creates a template for it.

**Why this priority**: creating the type is the first step before any template or record of that kind
can exist; without it the template tools cannot be pointed at a new kind of record.

**Independent Test**: through MCP alone, create an entity type, read it back, rename it, and see it in
`list-entity-types` and on the web settings screen.

**Acceptance Scenarios**:

1. **Given** the account, **When** the assistant creates an entity type with a singular and a plural
   name (and optionally a description, an icon, a colour, a slug and whether it is active), **Then**
   it exists at the end of the list, and what it left out takes the same defaults as the API.
2. **Given** a slug already used by another type in the account, or a slug with capitals, spaces or
   accents, **When** the assistant creates the type, **Then** it is refused with the API's wording and
   nothing is written.
3. **Given** an icon or colour outside the allowed ones, **When** the assistant creates or updates a
   type, **Then** it is refused, and the tool's description lists the allowed values so the assistant
   can pick one without guessing.
4. **Given** an entity type, **When** the assistant reads it, **Then** it receives the same shape the
   API returns, including the identifier the other tools take.
5. **Given** an entity type, **When** the assistant updates its names, description, icon or colour,
   **Then** the type reflects it and its slug is unchanged.

### User Story 2 - Organise and retire entity types (Priority: P2)

Somebody asks their assistant to move Wine bottles to the top of the list, to stop new Recipes being
added, or to delete a type they never used.

**Why this priority**: useful once several types exist, less frequent than creating them.

**Independent Test**: through MCP alone, deactivate a type, move it first, reactivate it, and delete an
empty one.

**Acceptance Scenarios**:

1. **Given** an active type, **When** the assistant switches it off, **Then** no new record of it can
   be added while existing ones stay, and switching it to the state it is already in changes nothing.
2. **Given** a type, **When** the assistant moves it to a position, **Then** the list order reflects it
   exactly as the API would.
3. **Given** a type holding no record, **When** the assistant deletes it, **Then** it is gone, and the
   tool is marked to clients as destructive so they ask the person first.
4. **Given** a type still holding records, **When** the assistant deletes it, **Then** it is refused
   with the API's wording ("The type still holds records and stays.") and nothing changes.

### User Story 3 - Know what the assistant can now change (Priority: P3)

A visitor to `/docs/mcp` reads which entity type tools exist, which of them change data, and which
one deletes.

**Why this priority**: a key hands an assistant the whole account; the page is where somebody decides
whether to allow that.

**Independent Test**: compare the page's tools table with the server's registered tools.

**Acceptance Scenarios**:

1. **Given** "Available tools", **Then** every new tool is listed by its exact name with one sentence,
   in an "Entity types, changing" group badged as changing data, next to the reading group that holds
   `list-entity-types` and the new reading tool, and the deleting tool says it deletes.
2. **Given** `/docs/mcp.md`, **Then** it carries the same tools.
3. **Given** the tool count in the prose, **Then** it matches the table and the server.
4. **Given** the page, **Then** it says in one or two sentences that an entity type is a kind of record
   besides contacts, that records of it follow a template made with the template tools, and that a
   type holding records cannot be deleted.

### Edge Cases

- An entity type of another account, or an identifier that is not one, answers not found in a
  translated sentence, never forbidden.
- Creating a type with no slug derives one from the singular name, as the API does, including when
  the derived one is taken.
- Updating with the same values changes nothing visible and still answers with the type.
- A type switched off stays readable and editable, and its existing records stay.
- Every change is written to the account's journal as arriving from an assistant.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The server MUST offer one tool for each of these API capabilities on entity types: read
  one, create, update, delete, set activation, set position.
- **FR-002**: Each tool MUST validate, refuse and answer exactly as its API endpoint does, return the
  same resource shape, and name the endpoint it mirrors.
- **FR-003**: Every tool MUST be confined to the key's account; a type outside it MUST be reported as
  not existing, in a translated sentence.
- **FR-004**: The read tool MUST be marked read only; the delete tool MUST be marked destructive to the
  client.
- **FR-005**: The create and update tools MUST state the allowed icons and colours in their input
  description, taken from the same list the API validates against, so the two cannot drift.
- **FR-006**: Every change MUST be recorded in the account journal with the assistant as its source,
  as the existing changing tools are.
- **FR-007**: `/docs/mcp` and `/docs/mcp.md` MUST list every registered tool, and the suite MUST fail
  when the page and the server disagree (the existing check, extended to the new tools).
- **FR-008**: `/docs/mcp` MUST explain in a sentence or two what an entity type is and how it relates
  to templates, in both the page and its Markdown.

### Key Entities

- **Entity type**: a kind of record the account keeps besides contacts (Animal, Wine bottle). Has a
  singular and plural name, a slug fixed at creation, a description, an icon, a colour, a position,
  whether new records can be added, and a count of records.
- **Template**: already managed over MCP; one may describe an entity type, and records of that type
  follow it.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Everything the web settings screen for entity types can do can be done through MCP.
- **SC-002**: An assistant can turn "keep track of my wine bottles" into a new entity type and a
  template for it using only identifiers the tools themselves handed it.
- **SC-003**: Every tool name on `/docs/mcp` matches the server; the suite fails otherwise.
- **SC-004**: For each new tool, the same input sent to the tool and to its API endpoint produces the
  same record and the same refusal.

## Assumptions

- Entity records (listing and creating the entities of a type in a vault) are out of scope: the request
  names entity types. The API already has both endpoints, so a follow up can mirror them.
- The slug cannot be changed after creation, over MCP as over the API and the web, because links rely
  on it.
- Deleting is exposed because "manage" includes it and the API allows it; clients are told the tool is
  destructive so they confirm with the person.
- The API reference needs no change, since no endpoint is added or changed.
- The branch stays `2026-09-25-mcp-stuff-2`, the one checked out for this work.
- Tool copy is English, like the existing tools; refusals reuse the API's translated sentences.
