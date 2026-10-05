# Feature Specification: Feature flags

**Feature Branch**: `2026-10-04-laravel-pennant`

**Created**: 2026-10-04

**Status**: Draft

**Input**: User description: "add laravel pennant feature flag package"

## Clarifications

### Session 2026-10-04

- Q: Where should the instance administrator switch a flag on or off? → A: Both an Artisan command and a screen in the admin area.
- Q: Should this change put a real capability behind a flag, or deliver only the flag mechanism? → A: Only the mechanism, proven by a flag that exists only in the tests. Superseded 2026-10-05: the marketplace is the first flag.
- Q: When a flag is switched on for every account, do accounts created afterwards get it too? → A: Yes, it is a standing default and the latest switch wins (recommended answer, adopted 2026-10-05).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ship an unfinished capability switched off (Priority: P1)

A developer merges an unfinished capability behind a flag that is off for every account, so nobody sees it.

**Why this priority**: it lets work land on `main` in small pull requests without exposing it.

**Independent Test**: prove an account with the flag off cannot reach the gated route or control, and one with it on can.

**Acceptance Scenarios**:

1. **Given** a flag nobody has switched on, **When** any account opens the screen, the API or the MCP
   server, **Then** the capability is absent and a direct request to it answers as not found.
2. **Given** the same flag switched on for Jim's account, **When** Jim and Dwight use the product,
   **Then** Jim reaches the capability and Dwight does not.
3. **Given** a test, **When** it forces the flag for an account, **Then** no other test's decisions affect it.

---

### User Story 2 - The administrator switches a flag (Priority: P2)

The instance administrator switches a flag on or off for one named account or for every account, from
the command line or from a screen in the admin area. Every change is recorded in the administrator log.

**Why this priority**: without it, switching a flag means editing the database by hand.

**Independent Test**: switch a flag on for one account from the screen and off from the command line,
and check that the account follows each change and the log holds both.

**Acceptance Scenarios**:

1. **Given** a declared flag, **When** the administrator switches it on for Pam's account, **Then**
   Pam's next request sees the capability and no other account changes.
2. **Given** a flag switched on for everybody, **When** the administrator switches it off, **Then** it
   disappears for every account and the data created under it is kept.

---

### User Story 3 - Retire a flag once released (Priority: P3)

Once a capability is released to everybody, its flag and the decisions stored for it are deleted.

**Why this priority**: a flag left in place forever turns into a permanent second version of the product.

**Independent Test**: remove a flag from the code, run the cleanup, and prove no stored decision names it.

**Acceptance Scenarios**:

1. **Given** decisions stored for a flag no longer declared, **When** the cleanup runs, **Then** none remain.

### Edge Cases

- A flag switched off after Jim created data under it: the data is kept and shown again once the flag is back on (principle VIII).
- Work running with nobody signed in (a queued job, a scheduled command, CardDAV, an API token): the
  flag resolves for the account the work runs for, and gives the same answer the web would.
- A control behind a flag that is off is absent, not shown disabled. A flag is not an ability, so the
  rule that a refused control stays on the screen (`.claude/rules/policies.md`) does not apply.
- A fresh install with no decision stored: every flag answers off and the application works normally.
- No flag declared: the admin screen says so, and the command lists nothing.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST let a capability be declared behind a named flag that is off for every account until switched on.
- **FR-002**: A flag MUST resolve per account, giving one answer for that account across the web, the API, the MCP server, queued work, scheduled commands and CardDAV.
- **FR-003**: A capability whose flag is off MUST be absent from every surface, and a direct request to it MUST answer as not found.
- **FR-004**: Switching a flag off MUST NOT delete or alter any data created while it was on.
- **FR-005**: A decision stored for an account MUST persist across requests, deployments and server restarts until it is changed.
- **FR-006**: Tests MUST be able to force a flag on or off for an account.
- **FR-007**: A flag MUST NOT distinguish accounts by plan, payment status or deployment type (hosted or self hosted).
- **FR-008**: Releasing a capability to everybody MUST remove its flag and its stored decisions in the same change.
- **FR-009**: The administrator MUST be able to switch a flag on or off for one account or for every account, both through a command and through a screen in the admin area, and both MUST give the same result.
- **FR-010**: Every switch MUST be recorded in the administrator log with who changed which flag, for which account, and when.
- **FR-011**: The system MUST be able to delete the stored decisions of a flag that is no longer declared.
- **FR-012**: This change MUST put the marketplace behind a flag, off for every account when it ships; a visitor sees it exactly when it is on for every account.

### Key Entities

- **Flag**: a named switch declared in the code, with a default of off.
- **Flag decision**: the stored answer of one flag for one account, or for every account.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: With a flag off, zero screens, API endpoints or MCP tools expose its capability to that account.
- **SC-002**: A switch takes effect on the affected account's next request, and no other account sees any change.
- **SC-003**: Switching a flag off and on again restores 100% of the data created under it.
- **SC-004**: After a flag is retired, zero stored decisions name it.
- **SC-005**: The suite passes on SQLite, MySQL and PostgreSQL with no decision stored.

## Assumptions

- The mechanism is Laravel Pennant, as the request names, storing decisions in the database so web servers and workers share them.
- A flag is scoped to the account. Erica is mono user, so an account is one person.
- Rollout by percentage, A/B experiments and per plan entitlements are out of scope; the last breaks the invariant that paying unlocks no feature.
- The migration the package publishes is kept as it ships (principle IV).
- Flags live in `app/Features`, admitted by constitution 2.5.0.
- The admin area has no API today, so a management screen there owes no endpoint (principle X).
- This change gates web and API routes; gating an MCP tool or a CardDAV resource lands with the first flag that reaches one.
