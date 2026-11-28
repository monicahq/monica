# Feature Specification: Vault card values in the API

**Feature Branch**: `2026-11-27-vault-card-api`

**Created**: 2026-11-27

**Status**: Draft

**Input**: User description: "Ajoute à la ressource coffre de l'API JSON (`/api/v1/vaults` et `/api/v1/vaults/{vault}`) ce qu'il faut à l'app Android pour afficher les mêmes cartes que la page web des coffres."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The Android app draws the same vault card as the web (Priority: P1)

The app lists the vaults. Each card says when the vault was last opened, shows up to four faces and
says how many dates fall before the month is out, exactly as the web vaults page does. The app reads
these values from the API and computes none of them.

**Independent Test**: read the list and one vault with a bearer token and compare each value with the
web vaults page for the same account.

**Acceptance Scenarios**:

1. **Given** a vault never opened, **When** the app reads it, **Then** `last_visited_at` is `null`.
2. **Given** six listed contacts, **When** the app reads the vault, **Then** `faces` holds the newest four.
3. **Given** a partial contact and an archived contact, **When** the app reads the vault, **Then**
   neither is in `faces`.
4. **Given** reminders awaiting an answer this month, overdue and next month, one of each, **When** the
   app reads the vault, **Then** `upcoming_reminder_count` is 2.
5. **Given** a vault of another account, **When** the app reads the list, **Then** it is absent, and
   reading it by identifier answers not found.
6. **Given** `sort=recent`, **When** the app reads the list, **Then** vaults come in the web order.

### User Story 2 - The app shows an avatar with only a token (Priority: P1)

A face carries the contact's photograph when there is one. The app has a token and no browser
session, so `avatar_url` must answer to the token and return the thumbnail.

**Independent Test**: request a face's `avatar_url` with the token, without it, and with another
account's token.

**Acceptance Scenarios**:

1. **Given** a contact with an avatar, **When** the app requests `avatar_url`, **Then** it receives the
   thumbnail image.
2. **Given** a contact without an avatar, **When** the app reads the face, **Then** `avatar_url` is
   `null`.
3. **Given** another account's token, a contact of another vault, or an avatar since removed, **When**
   the address is requested, **Then** the answer is not found.

### User Story 3 - Assistants and developers read the same thing (Priority: P2)

`list-vaults` answers with the same vault shape as the API, and the reference documents every new
attribute, parameter and address.

**Independent Test**: call `list-vaults`, then read the vaults and contacts pages of the reference and
the OpenAPI document.

**Acceptance Scenarios**:

1. **Given** an account with vaults, **When** an assistant calls `list-vaults`, **Then** each vault
   carries the three new attributes as the API returns them.
2. **Given** the reference and the OpenAPI document, **When** a developer reads them, **Then** they
   describe the three attributes, `sort` and the avatar address, and no longer say a token cannot fetch
   an avatar.

### Edge Cases

- No contacts gives `faces: []`. A switched off reminder or an answered occurrence is not counted.
- The month is the reader's: their last day counts while UTC is already in the next month.
- A missing avatar file answers not found; with the file upload flag off, stored avatars still serve.

## Clarifications

### Session 2026-11-27

- Q: Should the list accept `sort=recent`? → A: Yes; the default order stays by position.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every vault response MUST carry `last_visited_at`: the last opening in seconds since 1970,
  or `null`.
- **FR-002**: Every vault response MUST carry `faces`: at most four listed contacts (neither partial
  nor archived), most recently created first, each `{ id, name, avatar_url }`.
- **FR-003**: `avatar_url` MUST be an API address answering to the token with the thumbnail at the size
  the web card uses, or `null` when the contact has no avatar.
- **FR-004**: The avatar address MUST answer not found for another account, another vault, or a
  contact without an avatar, and refuse a request without a token.
- **FR-005**: Every vault response MUST carry `upcoming_reminder_count`: occurrences awaiting an answer
  up to the end of the reader's current month, overdue included.
- **FR-006**: The web card and the API MUST read each value, and the recent order, from one shared
  source.
- **FR-007**: A page of up to 100 vaults MUST load in a number of queries independent of its size.
- **FR-008**: The resource MUST NOT carry open tasks nor the "and N more" wording.
- **FR-009**: The list MUST keep its position order by default, accept `sort=recent` for the web order,
  and refuse any other `sort`.
- **FR-010**: Every MCP tool returning a vault MUST answer through the API controller, and `list-vaults`
  MUST accept `sort`.
- **FR-011**: The pull request MUST update the vaults and contacts reference pages, the OpenAPI
  document, the API changelog and the product changelog; the product guide and help bubbles stay
  unchanged because no screen changes, and the report says so.
- **FR-012**: Feature tests MUST cover, for the list and one vault, every acceptance scenario of User
  Story 1 and a query count that does not grow with the number of vaults.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: For every test scenario, the API values equal what the web vaults page shows.
- **SC-002**: A page of 100 vaults takes as many database queries as a page of 2.
- **SC-003**: Every face with an avatar displays for a client holding only a token.

## Assumptions

- The token holder's timezone defines "the current month", as the signed in person's does on the web.
- The avatar address serves the thumbnail size only, outside the file upload flag, and no MCP tool
  mirrors it because it returns an image.
- The contact resource keeps the value of `avatar.url`; only its description changes.
