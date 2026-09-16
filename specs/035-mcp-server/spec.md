# Feature Specification: MCP Server

**Feature Branch**: `035-mcp-server` | **Created**: 2026-09-14 | **Status**: Draft

**Input**: User description: "create a mcp server using laravel basics"

## Clarifications

### Session 2026-09-14

- Q: Does this feature deliver the API key work it depends on (scopes, vault confinement), or inherit it later? → A: Ship now, inheriting whatever keys enforce; FR-004 and FR-005 become pass-through obligations and the confinement scenarios belong to the key feature.
- Q: The JSON API resolves no Me contact, yet FR-006 offers one and FR-003 forbids offering what the API lacks. Which gives way? → A: Neither. This feature adds the missing API capability first, then exposes it (FR-013).
- Q: Does a capability run the API's own logic, or send itself an API request? → A: The same logic, not an internal request, with each capability naming the API capability it mirrors so the correspondence is testable.
- Q: How is a key marked as an assistant's, and does the marking gate the MCP endpoint? → A: The user picks the kind when minting, and it is descriptive only; any live key may connect, and the changelog records the surface per request.
- Q: In which language does the server write a refusal? → A: Translated, through the locale resolution the JSON API already applies, as an ordinary translated string the assistant relays verbatim.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - An assistant reads the directory (Priority: P1)

Somebody pastes their instance address and an API key they already hold into their assistant's
connector settings. The assistant discovers what Erica offers and answers questions about the people
in their vaults, without anybody exporting a file.

**Why this priority**: this is the whole feature at its smallest. It proves the connection, the
authentication, the account boundary and the catalogue of capabilities, and it changes no data, so
it ships alone and is useful alone.

**Independent Test**: connect an assistant with a valid key, ask it who is in a named vault, and
confirm the answer matches that vault and no other.

**Acceptance Scenarios**:

1. **Given** a valid key for an account holding two vaults, **When** the assistant lists vaults, **Then** it receives exactly those two.
2. **Given** a capability the JSON API would refuse this key, **When** the assistant presents the same key, **Then** it is refused identically.
3. **Given** a revoked or expired key, **When** the assistant connects, **Then** it is refused and no capability is offered.
4. **Given** a contact belonging to another account, **When** the assistant asks for it by its public identifier, **Then** it is told the contact does not exist.

---

### User Story 2 - An assistant records a person (Priority: P2)

The user mentions somebody new during a conversation. The assistant creates the contact in the vault
they name, and can correct the name afterwards, without the user opening a browser.

**Why this priority**: this is the first time an assistant changes somebody's data, so it depends on
P1 being trustworthy. It is also where the account's changelog has to say the change arrived from an
assistant rather than from a browser.

**Independent Test**: ask a connected assistant to add a person to a named vault, then confirm the
contact exists in that vault and the changelog attributes it to MCP and to that key.

**Acceptance Scenarios**:

1. **Given** a key permitted to write, **When** the assistant creates a contact in a vault, **Then** the contact appears there and the changelog records the source as MCP and names the key.
2. **Given** a key permitted to read only, **When** the assistant tries to create a contact, **Then** it is refused and nothing is written.
3. **Given** a vault belonging to another account, **When** the assistant tries to write into it, **Then** it is told that vault does not exist.
4. **Given** a contact created by an assistant, **When** the user opens it in the interface, **Then** it is an ordinary contact with no marker separating it from one they typed themselves.

---

### User Story 3 - The user sees and cuts off what is connected (Priority: P3)

The user can tell an assistant's key apart from a personal script's key, read what it has done, and
stop it.

**Why this priority**: the two capabilities above are only safe if the way out is obvious. It is
last because the screens that list and revoke keys already exist, so this is a matter of telling the
reader which key belongs to an assistant.

**Independent Test**: mint a key for an assistant, confirm the key list says so, revoke it, and
confirm the assistant can no longer connect.

**Acceptance Scenarios**:

1. **Given** a key minted for an assistant, **When** the user reads their key list, **Then** that key is identified as an assistant's rather than as a personal script's.
2. **Given** a connected assistant, **When** the user revokes its key, **Then** the next request from that assistant is refused.
3. **Given** an assistant that created a contact, **When** the user reads the changelog, **Then** the entry says which key made the change and that it arrived over MCP.

### Edge Cases

- A key lacks the permission for a capability: it is refused, and it is absent from the catalogue too, so the assistant never attempts it. A key reaching the whole account sees every vault of that account and no other account's.
- An assistant asks for a record by an identifier that belongs to another account or another vault: the answer is that it does not exist, never that it is forbidden.
- An assistant exceeds the hosted rate limit: it is told to retry later rather than silently given partial results. A self hosted instance limits nothing and behaves identically otherwise.
- The account's Me contact is asked for: it resolves stably, and it cannot be deleted through this surface.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The instance MUST serve an MCP endpoint that an external assistant connects to over the network, reachable identically on a self hosted and on a hosted instance.
- **FR-002**: The endpoint MUST authenticate with the API keys the account already holds, MUST refuse a request carrying no key, a revoked key or an expired key, and MUST NOT refuse a live key over the kind of caller it was issued for.
- **FR-003**: The endpoint MUST NOT hold an authorisation rule of its own. Every capability it offers MUST be served by the same logic as the JSON API's matching capability, and MUST name the capability it mirrors.
- **FR-004**: The catalogue offered to an assistant MUST reflect the scopes the presented key carries, whatever the keys enforce at the time, so this surface is never more permissive than the JSON API and needs no change when scopes start being enforced.
- **FR-005**: A key MUST reach exactly what the JSON API lets it reach and no more, and a record outside the key's account or outside the vault named in the request MUST fail as though it did not exist. Confining a key to one vault is the keys' work, not this surface's.
- **FR-006**: The server MUST offer these reading capabilities: list vaults, list and look up contacts, resolve the Me contact, list templates and their versions, list entity types, list relationship types and categories, and list activity categories.
- **FR-007**: The server MUST offer these writing capabilities: create a contact in a named vault, change a contact's name, and mark or unmark a contact as a favourite.
- **FR-008**: Every change made through the server MUST be recorded in the changelog with its source given as MCP and with the key that made it, so the user can tell an assistant's change from their own.
- **FR-009**: A key MUST record the kind of caller it was issued for, chosen by the user when the key is minted, so an assistant's key is distinguishable from a personal script's on the key list and in the changelog.
- **FR-010**: The hosted version MUST rate limit per key, and a self hosted instance MUST apply no limit, with neither varying the capabilities offered.
- **FR-011**: A capability that refuses MUST say why in a translated string, resolved the way the JSON API already resolves a request's locale, so the assistant relays it verbatim.
- **FR-012**: The public reference MUST document how to connect an assistant and what each capability does, alongside the existing API reference.
- **FR-013**: The JSON API MUST gain a capability resolving the account's Me contact, because FR-003 lets nothing be offered here that the API does not already carry.

### Key Entities

- **API key**: the existing key an external caller authenticates with. It gains the kind of caller it was issued for, chosen at mint time and describing the key rather than restricting it.
- **Changelog entry**: the existing record of who changed what. Its source records that a change arrived over MCP, and its detail names the key.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A user connects an assistant to their instance in under five minutes, with nothing installed on their machine beyond the assistant itself.
- **SC-002**: An assistant answers a question about who is in a vault without the user exporting, pasting or uploading any data.
- **SC-003**: 100% of capabilities offered name an API capability that exists and are served by its logic, verified by a test that fails if one does not.
- **SC-004**: 100% of attempts to reach another account's or another vault's records are answered as not found, across every capability, verified by a test per capability.
- **SC-005**: 100% of changes made through the server appear in the changelog naming the key and the MCP source.
- **SC-006**: The same capabilities behave identically on a self hosted and on a hosted instance, differing only in whether a rate limit applies.

## Assumptions

- Connection is over the network to the instance rather than through a program the user installs locally, so the hosted and self hosted deployments share one path and nothing needs installing.
- The permission scopes and vault confinement the keys are meant to carry are the permission model here. This surface invents no second set and holds no rule of its own, so it inherits them the day the keys enforce them and is blocked on nothing.
- An existing first party protocol implementation is adopted rather than writing the protocol by hand. This adds one dependency, which the user approved on 2026-09-14.
- Notes, activities and the values held in a contact's fields are out of scope. No note or activity exists in the product yet, and field values are reachable only from a browser screen, so offering them here would mean bypassing the API, which is forbidden. They arrive once the API carries them.
- The rest of the API surface (billing, notifications, duplicates, merges, marketplace, template editing) is out of scope for this feature. It is added capability by capability afterwards.
