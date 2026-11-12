# Feature Specification: Location API and MCP Tools

**Feature Branch**: `2026-10-25-location-management`

**Created**: 2026-10-25

**Status**: Draft

**Input**: User description: "ajoute mcp et api support pour les locations"

## Context

Specification 086 added the places of an account (a town, a house, a room, a shelf, a box), each
optionally inside another, managed from the account settings. It deferred the API and the MCP server
(its research R10) and said so in its pull request. Principle X of the constitution says a capability
is not complete until the API covers it and its coverage is tested, and that the MCP server exposes the
API and never bypasses it. This closes that gap: everything the locations screen does can be done with
an API key, and an assistant can do it through the MCP server.

Nothing about what a location is changes. The rules stay where 086 put them: the parent is in the same
account, a location never sits inside itself or one it holds, and a location holding others cannot be
deleted.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read the locations of the account (Priority: P1)

A script or an assistant lists every location of the account, or reads one, and receives for each its
name, its parent, its emoji, its address, its full path and how many locations it holds.

**Why this priority**: Every other capability needs to find a location first, and an assistant
answering "where is the garage?" needs nothing more.

**Independent Test**: Seed "Dunder Mifflin Scranton" with an address, "Warehouse" inside it and
"Shelf 3" inside that, plus one location in another account; list and read them with a key.

**Acceptance Scenarios**:

1. **Given** locations in two accounts, **When** the list is asked for, **Then** only those of the key's
   account are returned, each parent before the locations inside it, sorted by name at every level, as
   the screen shows them.
2. **Given** "Shelf 3", **When** it is read, **Then** the answer carries its identifier, its name, its
   parent's identifier, its emoji, its own address, its path "Dunder Mifflin Scranton › Warehouse ›
   Shelf 3", the address it falls back on from its nearest ancestor, how many locations it holds, and a
   link to the locations screen.
3. **Given** a location of another account, **When** it is read, **Then** the answer is not found.
4. **Given** an account with no location, **When** the list is asked for, **Then** it is empty, not an
   error.

### User Story 2 - Add and change a location (Priority: P1)

A script or an assistant adds a location with a name and optionally a parent, an emoji and an address,
and later renames it, moves it or clears any of the optional values.

**Why this priority**: Recording places is the purpose of the feature; reading alone cannot fill an
account.

**Independent Test**: With a key alone, add "Annex" inside "Dunder Mifflin Scranton", move "Shelf 3"
into it, give it 📦, then clear its parent.

**Acceptance Scenarios**:

1. **Given** only a name, **When** a location is added, **Then** it exists at the top level with no
   emoji and no address, and the answer is the new location in the same shape as a read.
2. **Given** a missing name, a name of nothing but markup, an emoji that is not exactly one emoji, or a
   parent that does not exist in the account, **When** a location is added or changed, **Then** it is
   refused, each problem named against its field, and nothing is written.
3. **Given** a parent that is the location itself or one inside it, at any depth, **When** the location
   is changed, **Then** it is refused with the wording the screen uses, on the parent, and nothing
   changes.
4. **Given** a change that clears the parent, **When** it is saved, **Then** the location moves to the
   top level with everything inside it.
5. **Given** a change through the API that leaves out the parent, the emoji or the address, **When**
   it is saved, **Then** that value is cleared, as the API's other update endpoints do. Through the
   assistant, a value left out keeps what it was and only an explicit empty value clears it, as the
   other assistant tools do.

### User Story 3 - Delete a location (Priority: P2)

A script or an assistant deletes a location that holds nothing.

**Why this priority**: Tidying matters less than recording, and the refusal protects what is inside.

**Independent Test**: With a key, delete a location holding one other, then the empty one.

**Acceptance Scenarios**:

1. **Given** an empty location, **When** it is deleted, **Then** it is gone and the answer says so with
   no content.
2. **Given** a location holding others, **When** it is deleted, **Then** it is refused with the wording
   the screen gives ("1 location is inside it. Move it first."), and nothing changes.
3. **Given** the MCP tool that deletes, **When** a client lists the tools, **Then** that tool is marked
   destructive so the client asks the person first.

### User Story 4 - Work with locations through an assistant (Priority: P2)

Somebody asks their assistant "put a Garage inside my house, and a Workbench in it". The assistant
finds the house by listing, adds both, and the screen shows them.

**Why this priority**: The MCP server is how most people will reach the API; it is a transport over
stories 1 to 3, not new behaviour.

**Independent Test**: Through MCP alone, perform stories 1 to 3 and compare each answer and each
refusal with the API's.

**Acceptance Scenarios**:

1. **Given** the MCP server, **When** its tools are listed, **Then** there is one tool per API capability
   above (list, read, add, change, delete), each reading tool marked read only.
2. **Given** any tool, **When** it is called with the same input as the matching endpoint, **Then** it
   returns the same data and the same refusal wording.
3. **Given** the server's instructions, **Then** they say in two or three sentences what a location is,
   that it can sit inside another, that a location holding others cannot be deleted, and that a location
   is chosen by its path when two share a name.

### User Story 5 - Know what the API and the assistant can now do (Priority: P3)

A developer reads the API reference, and a visitor reads the MCP page, before handing a key to a
script or an assistant.

**Why this priority**: A key gives access to the whole account; the documentation is where somebody
decides whether to allow that.

**Independent Test**: Compare the API reference and the MCP page with the registered endpoints and
tools.

**Acceptance Scenarios**:

1. **Given** the API reference, **Then** every location endpoint is documented with its fields, its
   answer, an example, and each refusal with its wording.
2. **Given** the MCP page and its plain text version, **Then** every new tool is listed by its exact
   name with one sentence, the changing ones badged as changing data and the deleting one saying that
   it deletes, and the tool count in the prose matches the server.

### Edge Cases

- Two locations share a name under different parents: both are returned, told apart by their path.
- A location is moved: its path and the paths of everything inside it change in the next answer.
- A tree many levels deep: the list still returns every location in one answer for a household of
  tens of locations; a larger account is paged the way the other list endpoints are.
- An emoji sent as an empty string, or an address of only spaces: stored as none, as on the screen.
- A key of a deactivated account, or no key: refused the way every other endpoint refuses.
- Two requests move two locations into each other at the same time: out of scope here, as on the
  screen (086 audit, point 5).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The API MUST let a key list the locations of its account, read one, add one, change one and
  delete one, and nothing of another account.
- **FR-002**: A location MUST be identified by the same identifier the screen uses in its addresses.
- **FR-003**: A location's answer MUST carry its identifier, name, parent identifier, emoji, own address,
  inherited address and the name of the location it comes from, path, number of locations held, and the
  link to the screen for the person.
- **FR-004**: The list MUST be ordered as the screen orders it: each parent followed by what it holds,
  sorted by name at every level.
- **FR-005**: Adding and changing MUST apply exactly the rules of the screen: required name, one emoji or
  none, free text address, parent in the same account, never the location itself or one it holds.
- **FR-006**: Deleting a location holding others MUST be refused with the screen's counted wording.
- **FR-007**: Every refusal MUST use the wording the screen gives, in the language of the key's owner.
- **FR-008**: Adding, changing and deleting through the API or the MCP server MUST be written to the
  account's changelog. A change through the assistant is recorded as arriving from it; a change through
  the API is recorded as every other API change is today (plan, research R7).
- **FR-009**: The MCP server MUST offer one tool per API capability, MUST return what the endpoint
  returns, and MUST hold no rule of its own.
- **FR-010**: The deleting MCP tool MUST be marked destructive and the reading tools read only.
- **FR-011**: The API reference and the MCP documentation MUST describe every new endpoint and tool.
- **FR-012**: The locations screen MUST behave exactly as before.

### Key Entities

- **Location**: unchanged from 086. A place of the account with a name, an optional parent, an optional
  emoji and an optional address. This feature adds ways to reach it, not data.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Everything the locations screen can do, a key can do: five capabilities, five endpoints,
  five tools.
- **SC-002**: Every attempt to reach a location of another account, to create a cycle or to delete a
  location holding others is refused, whatever the request sent, through both the API and the MCP
  server.
- **SC-003**: For the same input, the API, the MCP server and the screen give the same result and the
  same refusal wording.
- **SC-004**: An assistant records a four level chain (town, house, room, shelf) from one sentence of
  the person, with no identifier typed by them.
- **SC-005**: Every endpoint and every tool has an automated test, and the documentation lists exactly
  the endpoints and tools that exist.

## Assumptions

- The API and the MCP tools reuse the actions of 086 unchanged; the rules are not written a second time.
- The endpoints sit with the other account level resources (templates, relationship types), not under
  a vault, since locations belong to the account.
- No new key scope: like templates and relationship types, locations are reachable by any key of the
  account. A location holds nothing sensitive.
- Pagination, error shape, version prefix and rate limit follow the existing API endpoints.
- The MCP tool names follow the existing pattern: `list-locations`, `get-location`, `create-location`,
  `update-location`, `delete-location`.
- No webhook: the application sends none for any record yet, so principle X's outbound counterpart is
  not in scope.
- Linking a location to a possession, a contact or a vehicle is not in scope, as in 086.
