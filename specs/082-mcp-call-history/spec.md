# Feature Specification: MCP Call History

**Feature Branch**: `082-mcp-call-history`
**Created**: 2026-10-23
**Status**: Draft
**Input**: "Implémente une page « Historique MCP », accessible depuis Paramètres → Sécurité, pour consulter les appels MCP reçus par Erica." Full brief in the conversation; design: claude.ai design project `4efc2d07`, `MCP Activity.dc.html`.

The changelog records changes, not reads, refusals or failures, so an account holder cannot see what
a connected assistant asked for. This adds a separate, read only history of every MCP tool call.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Every tool call is recorded with its true outcome (Priority: P1)

An assistant calls tools on the account. Each call leaves one entry, whether it read, changed,
was refused, sent invalid parameters, failed, or never finished.

**Why this priority**: Without a faithful record the screen has nothing true to show.
**Independent Test**: Call `get-contact`, `update-contact-name`, a refused call, invalid parameters and an MCP error; read the entries.

**Acceptance Scenarios**:

1. **Given** a valid `get-contact` call, **When** it returns, **Then** one entry records operation read, result succeeded, its duration, vault and contact.
2. **Given** a change made through a tool, **When** it returns, **Then** the entry records the operation (create, update or delete) and succeeded.
3. **Given** a call to a record of another account, **When** it is refused, **Then** the entry says refused and names no vault or object.
4. **Given** parameters the tool rejects, **When** it answers, **Then** the entry says invalid parameters, with the code and reason.
5. **Given** an MCP error inside an HTTP 200 response, **When** it is recorded, **Then** the entry says error, never succeeded.
6. **Given** a call that started and produced no final result, **When** the history is read, **Then** it shows as interrupted, never as succeeded.
7. **Given** a request without valid authentication, **When** it arrives, **Then** no account history gains an entry.

### User Story 2 - The account holder reads the history (Priority: P1)

From Settings, Security and access, the account holder opens the MCP history and sees their calls,
newest first, grouped by day, one line each, with a detail they can unfold.

**Why this priority**: The screen is the reason the record exists.
**Independent Test**: Seed calls of two accounts, open the screen as one, read the lines and unfold one.

**Acceptance Scenarios**:

1. **Given** calls exist, **When** the screen opens, **Then** each line shows the time, a short description, the connection name, the tool name, the operation and vault, the duration and the result.
2. **Given** a line, **When** unfolded, **Then** it shows the full date in the user's time zone, connection, tool, operation, vault, duration, IP address, call identifier, the refusal or error reason, and "Parameters received" and "Response returned" as indented JSON.
3. **Given** more calls than a page holds, **When** the screen opens, **Then** it paginates.
4. **Given** another account's calls, **When** the screen opens, **Then** none of them appear.
5. **Given** no call yet, **When** the screen opens, **Then** an empty state explains what will land here.
6. **Given** a revoked API key or a disconnected OAuth client, **When** its past calls are shown, **Then** they keep the name it had.

### User Story 3 - The account holder narrows the history (Priority: P2)

They filter by period, connection, result and tool.

**Why this priority**: Useful once the history is long; the screen works without it.
**Independent Test**: Apply each filter alone and together, then a combination that matches nothing.

**Acceptance Scenarios**:

1. **Given** filters, **When** applied, **Then** only matching calls are listed and the filters survive pagination.
2. **Given** filters matching nothing, **When** applied, **Then** a "nothing matches" state appears, distinct from the empty state, with a way to clear the filters.

### User Story 4 - Secrets stay out and old calls leave (Priority: P2)

Stored parameters and responses never hold a secret, and calls older than the retention are purged.

**Why this priority**: A history that leaks secrets is worse than none.
**Independent Test**: Call a tool with a token, a password and a sensitive field value, read the stored row; age entries past the retention and run the purge.

**Acceptance Scenarios**:

1. **Given** parameters or a response holding a token, a password or a sensitive field value, **When** stored, **Then** the value is already masked in storage.
2. **Given** a payload larger than the limit, **When** stored, **Then** it is cut and the detail says it was truncated.
3. **Given** entries older than the retention, **When** the daily purge runs, **Then** they are deleted and newer ones stay.

### Edge Cases

- A vault or object named in the parameters that does not belong to the account is not recorded as the call's vault or object.
- A vault or contact deleted after the call: the entry stays. The same JSON-RPC identifier twice: two internal identifiers.
- A notification or a method other than a tool call (`initialize`, `tools/list`) is not recorded.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST record every MCP tool call in a history separate from the changelog, captured in one place around tool execution.
- **FR-002**: Each entry MUST hold the account, the user, the connection (API key or OAuth client) and its name at the time, the UTC start with milliseconds, and the duration.
- **FR-003**: Each entry MUST hold the tool's real name, its operation (read, create, update, delete), and the vault and object when identified and inside the account.
- **FR-004**: Each entry MUST hold one result among succeeded, refused, invalid parameters, error and interrupted, plus the error code and reason when there is one.
- **FR-005**: The result MUST be read from the MCP response, not the HTTP status.
- **FR-006**: An entry MUST be written before the tool runs and is interrupted until a final result replaces it.
- **FR-007**: Each entry MUST hold a unique internal identifier, the JSON-RPC identifier and the IP address.
- **FR-008**: Parameters and response MUST be stored filtered: tokens, passwords, authentication headers and sensitive field values masked before storage, each capped at 16 KB and flagged when cut.
- **FR-009**: An unauthenticated request MUST NOT be attributed to any account.
- **FR-010**: The screen MUST list the signed in account's entries only, newest first, grouped by day, paginated with the size the other long lists use.
- **FR-011**: The short description MUST come from a fixed, translated wording chosen by operation and by whether the call succeeded, naming the object when known.
- **FR-012**: The screen MUST offer the filters period, connection, result and tool, and an empty and a no match state.
- **FR-013**: The screen MUST say "Sensitive information is masked." and state the retention in days.
- **FR-014**: Nothing on the screen or the API MAY edit or delete an entry.
- **FR-015**: Entries older than the retention (90 days by default, set by the server) MUST be purged daily.
- **FR-016**: The history MUST be readable through the JSON API with the same scoping and filters.
- **FR-017**: The screen MUST be linked from Security and access, and absent when the MCP server is switched off for the account.

### Key Entities

- **MCP call**: one tool call. Belongs to an account and a user; holds connection kind and name, tool, operation, vault and object when known, result, error, timings, identifiers, IP, filtered parameters and response, truncation flags.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of tool calls in the five test cases (read, change, refusal, invalid parameters, MCP error in HTTP 200) produce exactly one entry with the correct result.
- **SC-002**: 0 stored entries contain a planted token, password or sensitive value.
- **SC-003**: 0 entries of one account are visible to another, on the screen or the API.
- **SC-004**: An account holder finds a given call from the last week in under 30 seconds with the filters.
- **SC-005**: 0 entries older than the retention remain the day after the purge runs.

## Assumptions

- Where the brief and the design differ, the brief wins: title "MCP history" (design: "Assistant activity"); its four filters replace the design's chips and free search; the design's "Download as CSV" and client user agent are not built; its tool names (`create-note`, `delete-contact`) are illustrative, the real ones are used.
- Out of scope: geolocation, a language model, conversational sessions, new read, write or delete permissions. A connection name is what the account holder typed, not a verified provider identity.
- "Sensitive field" means a field marked sensitive in a template, plus keys named like a secret.
- Period choices: last 24 hours, 7 days, 30 days, whole retention. The operation comes from each tool's declared nature, not its name.
