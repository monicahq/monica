# Feature Specification: Relatives without a record and possessions through a connected assistant

**Feature Branch**: `2026-11-13-mcp-relatives-possessions` | **Created**: 2026-11-13 | **Status**: Draft

**Input**: A ChatGPT conversation over MCP was asked to add Maxime's wife Sarah without a record of her
own, and his two children, Milo (a boy) and one whose name is unknown. It answered that the connector
cannot add relatives without a record. Adding a possession over MCP also failed. Make both possible.

## Context

Both operations already exist on the MCP server (`create-contact-relationship` with `creates_record`
false, and `create-possession`). The assistant did not find or recognise them. Why is in
[research.md](research.md).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Add relatives without a record through an assistant (Priority: P1)

A person asks their assistant to add Sarah as Maxime's wife without a record, Milo as his son, and a
second child whose name they do not know. The assistant does it in one conversation.

**Why this priority**: this is the conversation that failed, and relatives known only by name are the
most common thing somebody tells Monica about a contact.

**Independent Test**: give a connected assistant the request above; Maxime's page shows three
relationships, and the vault's contact list shows none of the three people.

**Acceptance Scenarios**:

1. **Given** a vault holding Maxime, **When** the assistant adds his wife Sarah without a record,
   **Then** a partner relationship links Maxime to Sarah, who has no record of her own.
2. **Given** the same vault, **When** it adds a child whose name is unknown, **Then** Maxime is the
   parent of a person without a name and without a record.
3. **Given** the same vault, **When** it adds Milo as his son, **Then** Maxime is the parent and Milo
   the child, never the reverse.
4. **Given** an assistant that has read only the server's guidance and catalogue, **When** it looks
   for a way to add a relative without a record, **Then** the guidance names the capability and the
   parameter that keeps the person without a record.

### User Story 2 - Record a possession through an assistant (Priority: P1)

A person asks their assistant to add their bicycle to a vault. The assistant finds a fitting
possession type and records the possession.

**Why this priority**: the second reported failure, on the newest part of the product.

**Independent Test**: give a connected assistant the request; the vault's possessions list the bicycle.

**Acceptance Scenarios**:

1. **Given** a vault and an account with possession types, **When** the assistant is asked to add an
   object, **Then** a possession is recorded in that vault with the name given and an existing type.
2. **Given** an assistant that has read only the server's guidance, **When** it looks for a way to
   record a thing somebody owns, **Then** the guidance names the capability that lists types, the one
   that records the possession and the one that writes its field values.
3. **Given** a request where no type obviously fits, **Then** the guidance tells the assistant to ask
   the person rather than invent a type.

### User Story 3 - Every capability reachable in one listing (Priority: P2)

An assistant client that asks for the capability catalogue once, without following further pages,
still receives every capability the server offers.

**Why this priority**: it removes the most likely cause of both failures for every capability at once.
P2 because stories 1 and 2 can be proven without it on a client that pages.

**Independent Test**: request the catalogue without a cursor; every registered capability is in it
and no further page is announced.

**Acceptance Scenarios**:

1. **Given** a connected assistant, **When** it requests the catalogue without a cursor, **Then** the
   answer holds every capability registered for its key.
2. **Given** a client that follows cursors, **When** it pages, **Then** no capability is missing or
   repeated.

### Edge Cases

- The contact named is not in the vault: answered as not found, nothing written.
- A last name without a first name: refused by validation, as today.
- The partner or child type is switched off in the account: the refusal says the type is unavailable.
- A relative shares a name with an existing contact: a new person without a record is created, the
  existing contact is untouched.
- The account has no possession type: the assistant is told so and may create one first.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A connected assistant MUST be able to link a contact to a new named person without giving that person a record.
- **FR-002**: A connected assistant MUST be able to link a contact to a new person of unknown name without giving that person a record.
- **FR-003**: The server's guidance MUST say, in the words a person uses, that a relative can be added without a record and with or without a name, naming the capability and the parameter.
- **FR-004**: The first sentence of the relationship capability's description MUST say it adds a family member, a partner or another relative, including one without a record.
- **FR-005**: A connected assistant MUST be able to record a possession in a vault with a name and an existing possession type.
- **FR-006**: The server's guidance MUST describe recording a possession: list the types, record it, write its field values, and ask the person when no type fits.
- **FR-007**: The capability catalogue requested without a cursor MUST contain every capability registered for the key presented.
- **FR-008**: A client following cursors MUST receive every capability exactly once.
- **FR-009**: Every change made this way MUST reach the account journal and the MCP history, as other assistant changes do.
- **FR-010**: This feature MUST NOT change what the JSON API or the web screens accept.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Replaying the reported request produces the three relationships in one conversation, with no statement that the capability is unavailable.
- **SC-002**: Asking a connected assistant to add an object produces a recorded possession in one conversation.
- **SC-003**: 100% of registered capabilities are present in one catalogue request made without a cursor.
- **SC-004**: The MCP history of both replays shows the relationship and possession capabilities called and succeeding.

## Assumptions

- The failures came from the assistant not finding the capabilities. The account's MCP history was not
  checked; if it shows `create-contact-relationship` called and refused, this specification is revisited.
- The possession attempt may predate the 2026-11-12 deployment. Deploying main is a precondition of the
  acceptance tests, not a deliverable.
- Milo being a boy is carried by the relationship type the account offers (son, or child). A gender on
  a person without a record is out of scope: gender is a template field, not part of a contact.
- A catalogue of every capability in one answer is an acceptable size for the clients in use. A cap a
  client applies to the number of capabilities is outside this feature.
- No new capability, permission or flag. The `mcp-server` switch still governs whether anything is listed.
