# Feature Specification: Life events through the API and the MCP server

**Feature Branch**: `2026-10-11-life-events-api`

**Created**: 2026-10-11

**Status**: Draft

**Input**: User description: "Add API and MCP support to manage life events. Add proper docs to both of them as well."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read life events from outside (Priority: P1)

Dwight Schrute's script, holding an API key, lists the life events of the "Dunder Mifflin" vault, narrows them to Jim Halpert's, and opens one to read its type, date, title, narrative, location, duration, participants and custom field values.

**Why this priority**: reading is what every client needs first, and it carries no risk.

**Independent Test**: create three life events, one without Jim, then list the vault, list it narrowed to Jim, and read one.

**Acceptance Scenarios**:

1. **Given** life events in a vault, **When** the vault's life events are listed, **Then** they come most recent first, paginated, deleted ones left out.
2. **Given** a contact of the vault, **When** the list is narrowed to that contact, **Then** only life events that contact takes part in are returned.
3. **Given** one life event, **When** it is read, **Then** the answer holds its type, dates, text, participants in order and the values of its custom fields.
4. **Given** a life event of another vault or another account, **When** it is read through this vault, **Then** the answer is not found.

---

### User Story 2 - Write down, correct and delete a life event from outside (Priority: P1)

Through an assistant connected over MCP, Pam Beesly says "Jim and I had lunch with Michael today". The assistant records a "Meal" life event with Pam, Jim and Michael as participants. Later it corrects the date, then deletes it, then restores it.

**Why this priority**: principle X; the contact page can do all four, so the API must.

**Independent Test**: create, update, delete and restore one life event through the API, then through the matching MCP tools, and compare with the same steps done on the contact page.

**Acceptance Scenarios**:

1. **Given** an active type and contacts of the vault, **When** a life event is created with a date and participants, **Then** it is recorded exactly as the contact page records it, on the timeline of every participant, and marked as written from the API or from MCP.
2. **Given** a life event, **When** it is updated, **Then** every value sent replaces the previous one and the timelines and counts follow.
3. **Given** a life event, **When** it is deleted, **Then** it leaves every timeline and can be restored, as on the contact page.
4. **Given** a refused write (a missing date, a deactivated type, a required custom field left empty, a participant from another vault), **When** it is sent, **Then** the API answers with a validation error naming the input and the MCP tool with an error message, and nothing is written.

---

### User Story 3 - Find how to do it in the documentation (Priority: P2)

A developer opens the API reference and finds a "Life events" page next to "Life event types" describing every endpoint, its parameters, its answer and its errors, with a request and an answer for each. The MCP page lists the new tools with a sentence each and explains how a life event relates to its type.

**Why this priority**: the request names the docs, and an undocumented endpoint is unused.

**Independent Test**: follow only the docs to create a life event with a custom field value, through the API and through an assistant.

**Acceptance Scenarios**:

1. **Given** the API reference, **When** the developer opens "Life events", **Then** every endpoint of this feature is described with a sample request and answer.
2. **Given** the MCP page, **When** the developer reads the tools list, **Then** every tool of this feature is there with its description.

### Edge Cases

- A life event whose type was deleted since is still read and listed, under its type's name.
- A life event recorded against an older version of its type's fields is read with the fields of that version, and an update keeps it there (principle VI).
- The same contact sent twice among the participants is recorded once.
- A file field cannot be written through this feature: a value sent for it is refused, and its value is read as the file's name only (clarified 2026-10-11).
- An update that leaves out the custom field values keeps every one of them; one that sends them replaces the whole set, so a field missing from the set is cleared, or refused when required.
- An update keeps the first participant, the person the life event is recorded for, as the contact page does; recording it for somebody else means deleting it and creating it again.
- A life event left with no contact among its participants (all of them deleted) cannot be deleted or restored through this feature, since every write names the person it is recorded for.
- Restoring a life event that is not deleted answers not found, as the action does.
- Every life event write is listed in the account journal, as from the contact page.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The API MUST list the life events of a vault, most recent first, paginated, optionally narrowed to one contact of the vault.
- **FR-002**: The API MUST return one life event with its type, dates, title, narrative, location, duration, participants in order and custom field values.
- **FR-003**: The API MUST create a life event with a type, a date, at least one participant and optional text, through the same action the contact page uses.
- **FR-004**: The API MUST update, delete and restore a life event through the same actions the contact page uses.
- **FR-004a**: Create and update MUST accept the values of the type's custom fields, every field type except file, validated as the contact page validates them.
- **FR-005**: A life event written through the API MUST be marked as written from the API, and one written through MCP as written from MCP.
- **FR-006**: Every record named in a request MUST belong to the account and the vault in the address, or the answer is not found.
- **FR-007**: The API MUST refuse a write with a validation error naming the input, in the shape the other endpoints use.
- **FR-008**: The MCP server MUST offer one tool per endpoint (list, get, create, update, delete, restore), each calling the API's action and holding no rule of its own.
- **FR-009**: The value of a sensitive custom field MUST be returned only to a key holding the `sensitive-fields` scope, as search and the vCard export do.
- **FR-010**: The API reference MUST gain a "Life events" page covering every endpoint with a sample request and answer.
- **FR-011**: The MCP documentation MUST list every new tool and explain in its summary how a life event relates to its type and its participants.
- **FR-012**: Every new string shown to a reader MUST be translated in every configured locale.

### Key Entities

- **Life event**: unchanged. Its existing "written from" values `api` and `mcp` start being used.
- **Participant**: a contact of the vault who took part, in order. The first one is the person the life event is recorded for.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every life event operation of the contact page (create, update, delete, restore) has an endpoint and an MCP tool, proven by a test each.
- **SC-002**: A life event created through the API and one created on the contact page with the same input differ only in where they were written from, proven by a test.
- **SC-003**: A request naming a record of another vault or account is answered not found on every endpoint, proven by a test.
- **SC-004**: A developer can create a life event with a custom field value by following the docs alone, with no other source.

## Assumptions

- Participants are contacts only, as on the contact page; entities are left out until the contact page offers them.
- Dates are day precise, as the contact page records them; approximate dates arrive with the product's imprecise date work, not here.
- Life event types and categories already have their API and MCP tools (specification 060); this feature reuses them to pick a type and to read the fields it asks for.
- No webhook is added: there is no webhook system yet.
- Life events are not behind a feature flag on the contact page, so the new surfaces are not either.
