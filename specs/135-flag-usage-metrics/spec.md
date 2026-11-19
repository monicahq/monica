# Feature Specification: Flag usage metrics

**Created**: 2026-11-19

**Status**: Draft

**Input**: User description: "Admin flags page: Count how many users have a feature on, how many are
actually using it, and the number of hits."

## Clarifications

### Session 2026-11-19

- Q: What counts as one hit? → A: At most one per flag, per account, per request or job.
- Q: Over what period, and where, are usage and hits counted? → A: Not in Monica. Each request, job
  and command carries the flags it used as Nightwatch context, and usage is read in Nightwatch.
- Q: Which flags go in that context? → A: Only the flags asked for the account during the request,
  job or command that answered on.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - See how many accounts have each flag on (Priority: P1)

The instance administrator opens the Flags screen and reads, next to each flag, how many accounts it
answers on for, out of all the accounts on the instance.

**Why this priority**: today the screen only says whether a flag is on for every account. Once Michael
switches a flag on for Jim's account alone, nothing tells him how far the flag reaches.

**Independent Test**: switch a flag on for two accounts out of five while it is off for every account,
open the Flags screen, and read that it is on for 2 accounts out of 5.

**Acceptance Scenarios**:

1. **Given** five accounts and a flag off for every account but on for Jim's and Pam's accounts,
   **When** Michael opens the Flags screen, **Then** the flag reads on for 2 accounts out of 5.
2. **Given** the same flag then switched on for every account, **When** Michael opens the screen,
   **Then** it reads on for 5 accounts out of 5.

---

### User Story 2 - See in Nightwatch who actually uses a flag, and how often (Priority: P2)

The person operating the instance opens Nightwatch and reads, for any request, job or command, which
flags it used for the account it served. Counting those records gives the hits; counting the distinct
users on them gives the accounts actually using the flag.

**Why this priority**: a flag on for many accounts but used by nobody is not ready to be judged.
Usage is what tells Michael whether a capability is ready for release, and Nightwatch already records
every request, job and command with the user it served.

**Independent Test**: with a flag on for Jim and Pam, have Jim open a screen behind the flag three
times and Pam never, then find three requests for Jim carrying the flag and none for Pam.

**Acceptance Scenarios**:

1. **Given** a flag on for Jim, **When** Jim opens a screen behind the flag, **Then** that request
   carries the flag's name in its flag context.
2. **Given** one page that asks the same flag five times for Jim, **When** Jim opens it once, **Then**
   the request names the flag once.
3. **Given** a flag off for Dwight, **When** a screen asks it for Dwight and is refused, **Then** the
   request does not name the flag.

---

### Edge Cases

- No flag declared: the screen keeps its empty state and the command says no flag is declared.
- An account nobody has asked about yet is counted with the answer it would get if asked, so the
  permanent switch `mcp-server` counts it as on.
- A visitor who is not signed in reaches a public page behind a flag: there is no account, so the
  request carries no flag context.
- Nightwatch is switched off, as on most self hosted instances: nothing is sent anywhere, and the
  Flags screen still shows its figures.
- Nightwatch samples requests: the hits it shows are then a sample, not a full count.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The Flags screen MUST show, for each declared flag, the number of accounts it answers on
  for and the total number of accounts on the instance.
- **FR-002**: The command listing flags MUST print the same figure as the screen.
- **FR-003**: Every request (web, API, MCP, CardDAV), job and command MUST attach to its context,
  under one key, each flag asked for an account during it that answered on, named once.
- **FR-004**: A flag that answers off, or one asked for no account, MUST NOT be attached.
- **FR-005**: Monica MUST NOT store usage or hits itself. Retention, sampling and display belong to
  Nightwatch.
- **FR-006**: The context MUST hold flag names only. It MUST NOT carry anything held in a vault, nor a
  plan, a payment or a deployment fact about the account.
- **FR-007**: Attaching the context MUST NOT make a request fail or noticeably slower, and MUST work
  the same whether Nightwatch is on or off.
- **FR-008**: Only the instance administrator MUST be able to see the Flags screen figures.
- **FR-009**: Every count shown MUST follow the counted phrase rules, so it reads correctly at zero,
  one and many in every shipped locale.

### Key Entities

- **Flag**: a capability still being built, kept out of sight until released, with a name and an
  instance wide answer that one account may override.
- **Flag context**: the list of flag names a request, job or command used, attached to it and read in
  Nightwatch next to the user it served.

## Success Criteria *(mandatory)*

- **SC-001**: The administrator can tell how many accounts have each flag on without opening an
  account screen.
- **SC-002**: That count matches what those accounts see in 100% of cases, on screen and command.
- **SC-003**: In Nightwatch, the operator can find every recorded request that used a given flag, and
  the users it served.
- **SC-004**: The Flags screen opens in under two seconds on an instance with 100,000 accounts.
- **SC-005**: People using the instance notice no slowdown from the context being attached.

## Assumptions

- An account is one person (the constitution makes Monica mono-user), so "users" means accounts.
- Nightwatch offers no way to read its figures back, so usage and hits are not on the Flags screen.
- Nightwatch is operator tooling, enabled per deployment by its token; attaching context changes
  nothing a person using Monica sees, so it is not a feature that exists on one deployment only.
- The admin flag screens have no API or MCP counterpart today, and the figure adds no endpoint.
