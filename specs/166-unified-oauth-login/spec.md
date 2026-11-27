# Feature Specification: One OAuth sign-in for every client

**Feature Branch**: `2026-11-26-unified-oauth-login`

**Status**: Draft

**Input**: Monica runs two OAuth sign-ins, one for assistants reaching the MCP server and one for the mobile apps (spec `165-mobile-app-sign-in`). Merge them so there is one OAuth sign-in whatever the client is used for, with no "mobile" way and no "MCP" way.

## What differs today, and what it becomes

| Area | Mobile apps today | Assistants today | Every client after this change |
| --- | --- | --- | --- |
| Consent | Asked at every sign-in | Asked once, then approved silently | Asked at every sign-in |
| Record of the connection | One session per phone, ended when a code or a replaced refresh token comes back | None, the list is worked out from the tokens | One connection per sign-in, with the same replay rule |
| Ending it from the client | Revocation address, or API logout | Not possible | Revocation address, or API logout where the token reaches the API |
| Ending it from Monica | Per phone, in "Mobile apps" and through the API | Per assistant, every token at once, in "Connected assistants" only | Per connection, in one list and through the API |

## Clarifications

### Session 2026-11-26

- Q: What happens when a client is authorized on an account where the MCP server is off? → A: A request asking for `mcp:use` answers not found, whoever the client is; any other request shows the consent page (FR-004).
- Q: How is `prompt=none` answered now that consent is asked every time? → A: `none` is ignored, the consent page is shown, and `none` is no longer advertised (FR-003, FR-015).
- Q: How does an assistant connected before this change show in the list? → A: As one connection per assistant and person, ended as a whole; sign-ins after the change get one connection each (FR-013).
- Q: What are the section and one entry called? → A: "Connected apps" and "connection"; a phone is disconnected, not signed out (FR-009).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Any client signs in the same way (Priority: P1)

Somebody connects Claude to the MCP server, and somebody else taps "Log in" in the Android app. Both meet the same sign-in, the same consent page, and get a connection that behaves the same afterwards.

**Why this priority**: It is the request. Two flows means every rule about consent, replay and revocation is written twice and drifts.

**Independent Test**: Run the authorization flow with PKCE once as the mobile client and once as a freshly registered assistant, and compare what each step does.

**Acceptance Scenarios**:

1. **Given** a signed-in person who already allowed a client, **When** that client asks for access again, **Then** the consent page is shown again, whichever client it is.
2. **Given** an account with the MCP server switched off, **When** a client asks for `mcp:use`, **Then** the answer is not found, as today, and a request for another scope is not affected.
3. **Given** the person allows access, **When** the client exchanges the code, **Then** one connection is recorded for that sign-in, whichever client it is.
4. **Given** a connection, **When** its exchanged code or a replaced refresh token is presented again, **Then** the request is refused and that connection ends, whichever client it is.
5. **Given** the mobile client asks for `mcp:use`, or any other client asks for `api`, **When** the request arrives, **Then** it is refused with `invalid_scope`, as today.

### User Story 2 - One list of what is connected (Priority: P1)

In Settings → Account → Security, one section lists everything connected through OAuth: each phone and each assistant, with its name, what it may reach, when it connected and when it was last used. Any one of them can be ended after a confirmation.

**Why this priority**: Two lists with two different ways of ending something is the visible half of the duplication.

**Independent Test**: Sign in from two phones and connect one assistant twice, open the security settings, end one entry, and check the three others still work.

**Acceptance Scenarios**:

1. **Given** two phones and one assistant connected, **When** the security settings open, **Then** one section lists the three, and no second section lists any of them.
2. **Given** the list, **When** one connection is ended and confirmed, **Then** its access and its renewal stop at once and every other connection, API key and browser session is untouched.
3. **Given** a connection that reaches the MCP server while the MCP server is off for the account, **When** the list shows, **Then** one sentence says so and that the connection is kept.
4. **Given** the API, **When** connections are listed or one is ended, **Then** it answers with the same list and the same rules as the settings.

### User Story 3 - A client signs itself out (Priority: P2)

A client that is done hands its refresh token to the revocation address, and its connection ends. An assistant can do what only a phone could do before.

**Why this priority**: It closes a gap for assistants, but nobody is blocked without it.

**Independent Test**: Revoke with an assistant's refresh token, then try to renew and to call the MCP server.

**Acceptance Scenarios**:

1. **Given** any client's refresh token, **When** it is sent to the revocation address with its client identifier, **Then** that connection ends and nothing else does.
2. **Given** an unknown or already revoked token, **When** it is sent, **Then** the answer is a success and nothing changes.

### Edge Cases

- The same assistant connected from two computers shows as two connections, and ending one leaves the other.
- A client sends no label, or one that is empty, too long or holds markup: the connection shows the client's name, or the label cleaned.
- An account log entry written before this change (phone signed in, phone session ended, assistant disconnected) still reads as it did.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every OAuth client MUST go through the same authorization, consent, token, renewal and revocation rules, and no rule other than FR-002 MAY depend on which client is asking.
- **FR-002**: The `api` scope MUST stay reserved to the first-party mobile client, and that client MUST NOT obtain `mcp:use`, as FR-002 and FR-003 of spec 165 say.
- **FR-003**: The consent page MUST be shown at every authorization, for every client, and `none` in `prompt` MUST be dropped rather than honoured.
- **FR-004**: An authorization asking for `mcp:use` on an account where the MCP server is off MUST answer not found, and that rule MUST read the scope asked, never the client.
- **FR-005**: The consent page MUST use one wording for every client, naming the client, the instance, what is asked for and where the person is sent back, in the reader's language.
- **FR-006**: Each code exchanged MUST create one connection (user, client, scopes, label, connected, last used, ended) holding only fingerprints of its code and tokens.
- **FR-007**: Presenting an exchanged code or a replaced refresh token again MUST end the connection concerned, for every client.
- **FR-008**: The revocation address MUST end the connection of any client's refresh token, and `DELETE /api/v1/logout` MUST end the connection of the OAuth token presented.
- **FR-009**: Settings → Account → Security MUST show one list of active connections, in a section called "Connected apps", replacing "Mobile apps" and "Connected assistants", and let the person end one after a confirmation that says it is final.
- **FR-010**: The API MUST list the person's active connections, show one and end one, with the same rules as the settings, under one new resource; the `mobile-sessions` addresses are removed and the API changelog says so (decided 2026-11-26).
- **FR-011**: Starting and ending a connection MUST each write one kind of entry to the account log, naming the client and the label, and entries of the three earlier kinds MUST stay readable.
- **FR-012**: A connection that reaches the MCP server on an account where it is switched off MUST be said once, in the list, with the fact that it is kept.
- **FR-013**: Every connection active before this change MUST be listed, MUST keep working, renewal included, and MUST be endable, with no step from the person; everything one assistant held for a person before this change MUST become one connection, and each phone session one connection; FR-007 and the revocation address of FR-008 do not reach such an assistant connection until that assistant signs in again.
- **FR-014**: Connections ended or unused for 30 days MUST be pruned, for every client.
- **FR-015**: What clients outside this repository rely on MUST keep its value: the mobile client identifier and return address, the scope names, the optional `device_name`, the `mobile` member of `GET /api/v1/status`, the revocation address, dynamic registration, and every member of the authorization server description except `none`, which leaves `prompt_values_supported`.
- **FR-016**: Web sign-in, API keys, the password API login, token lifetimes and rate limits MUST behave exactly as before.

This specification replaces FR-004, FR-006, FR-007, FR-008, FR-012, FR-013 and FR-015 of spec 165, and the rule that an assistant already allowed is approved silently.

### Key Entities

- **Connection**: one sign-in of one client for one person. It replaces the mobile session and the list worked out from an assistant's tokens. It knows its client, its scopes, an optional label, when it started, when it was last used and when it ended.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Apart from the scope rule of FR-002, no behaviour of the sign-in differs between the mobile client and an assistant in any tested case.
- **SC-002**: The security settings hold one section for OAuth connections, and ending one of several leaves every other one working in 100% of the tested cases.
- **SC-003**: The Android app and an MCP client that worked before this change sign in, renew and call their server with no change on their side.
- **SC-004**: No token, code or verifier can be read from the database or the logs.

## Assumptions

- The glossary of `.claude/rules/translations.md` has no row for "connection" yet.
- `device_name` stays the name of the label parameter, since the Android app sends it, and any client may send it.
- Asking consent every time means an assistant reconnecting after its connection ended asks the person again, which is accepted.
- No MCP tool for connections: none exists for API keys, as spec 165 already decided.
