# Feature Specification: Contact relationships over the API and MCP

**Feature Branch**: `2026-10-23-mcp-contact-relationships` | **Created**: 2026-10-23 | **Status**: Draft

**Input**: "[...] la connexion actuelle ne permet pas de créer le lien entre deux contacts. [...] je
veux que la gestion des relations fonctionne via MCP."

## Context

An assistant found the Partner type and both contacts, then stopped: no tool links two contacts. The
web interface records, edits and removes relationships (specs 029, 031, 045), but the JSON API has no
endpoint for them, and principle X says the MCP server exposes the API and never bypasses it. So this
feature adds the API capability first and the MCP tools mirroring it second. It changes no rule a
relationship obeys: the web form's behaviour is the reference for every case below.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Link two existing contacts (Priority: P1)

"Alexis Saettler and Aurore are partners." The assistant finds both contacts and the type, links them,
and both contacts' Relationships tabs show it. This is the request that failed.

**Independent Test**: through MCP alone, link two contacts of a vault and see the link on both tabs.

1. **Given** two contacts and an active type reading the same both ways, **When** the assistant links
   them, **Then** one current relationship exists and shows on both contacts.
2. **Given** a directed type (Parent and Child), **When** the assistant says which person holds the
   first role, **Then** the roles land on the people it named.
3. **Given** the same contact on both sides, a contact of another vault, a switched off type, or a
   link making somebody their own ancestor, **Then** it is refused as the web form refuses it, and
   nothing is written.

### User Story 2 - Read a contact's relationships (Priority: P1)

"Who is Jim Halpert related to?" The assistant lists Jim's relationships, and checks what exists
before adding a duplicate.

**Independent Test**: through MCP alone, list the relationships of a contact and read one.

1. **Given** a contact, **When** listed, **Then** each relationship names its type, both people with
   their identifiers and roles, its status and its dates; a contact with none gives an empty list.
2. **Given** a relationship read through a contact that is neither of its sides, **Then** it is
   reported as not existing.

### User Story 3 - Correct or end a relationship (Priority: P2)

"Jim and Karen broke up in 2008", or "that should be Cousin, not Sibling".

**Independent Test**: through MCP alone, change a relationship's type, end it with a date, read it.

1. **Given** a relationship, **When** the assistant changes its type, status, dates or which side holds
   the first role, **Then** it reflects the change; leaving a value out keeps it.
2. **Given** an end date before the start date, or a switched off type it does not already use,
   **Then** it is refused as the web form refuses it.

### User Story 4 - Link somebody not written down yet (Priority: P2)

"Pam has a son called Cece." The assistant names the other side, as a full contact or as a
placeholder, or leaves the name unknown, exactly as the web form offers.

**Independent Test**: through MCP alone, create relationships with a named and an unnamed other side.

1. **Given** a name, **Then** the other side is created with it, as a placeholder unless a full
   contact is asked for; **given** no contact and no name, **Then** it is an unnamed placeholder.
2. **Given** both an existing contact and a name, **Then** it is refused.
3. **Given** an update naming the other side, **Then** a placeholder takes the name, and somebody with
   a record of their own is refused.

### User Story 5 - Remove a relationship, and document it all (Priority: P3)

**Independent Test**: through MCP alone, delete a relationship; it leaves both contacts, both people
stay (a placeholder included), and the tool is marked destructive. The API reference and `/docs/mcp`
name every new endpoint and tool, the deleting one saying it deletes.

### Edge Cases

- Anything outside the key's account, or reached through the wrong vault, answers not found, in a
  translated sentence, never forbidden.
- The same two people may be linked more than once, even with the same type, as on the web form.
- Every change is written to the account's journal with the origin MCP and API changes carry today.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The API MUST list, read, create, update and delete the relationships of a contact.
- **FR-002**: Each endpoint MUST apply the business rules the web interface applies and refuse with
  the same translated wording.
- **FR-003**: Creation MUST accept the other side as an existing contact, a name (full contact or
  placeholder) or nobody named, and which side holds the first role of a directed type.
- **FR-004**: Update MUST accept a type, a status, a start and an end date, which side holds the first
  role, and a name for a placeholder other side, keeping whatever is left out.
- **FR-005**: A relationship MUST be returned in one shape: its type, both people with their
  identifiers, names and roles, status and dates.
- **FR-006**: The MCP server MUST offer one tool per endpoint, with the endpoint's inputs, refusals and
  shape, naming the endpoint it mirrors; reading tools read only, the deleting tool destructive.
- **FR-007**: The create and update tools MUST explain the two roles and which side holds which.
- **FR-008**: The API reference and the MCP documentation MUST describe the new endpoints and tools.

### Key Entities

- **Relationship**: a link between two contacts of one vault with one type; holds both sides, a status
  (current or ended) and optional dates.
- **Relationship type**: a role per side, reading the same both ways or not; already on API and MCP.
- **Contact**: either side; the other side may be a placeholder existing only through the link.

## Success Criteria *(mandatory)*

- **SC-001**: An assistant completes "Alexis Saettler and Aurore are partners" with no manual step.
- **SC-002**: Every action of a contact's Relationships tab can be done over the API and MCP with the
  same result.
- **SC-003**: Every refusal of the web form comes back over both surfaces with the same wording, in the
  caller's language.
- **SC-004**: No relationship of another account or vault is reachable through either surface.

## Assumptions

- Contacts and types are found with existing tools; a relationship is addressed under either of its
  contacts, as reminders are. Webhooks and relationships inside `get-contact` are out of scope.
