# Feature Specification: Support requests inside the application

**Feature Branch**: `2026-11-11-support-tickets`  
**Created**: 2026-11-11  
**Status**: Draft

**Input**: User description: "add a support tool for users in the app directly instead of using emails", following Claude Design project `69addc51-5064-42f2-92be-4e4004c1586a`: `Admin Support.dc.html` (the administrators' queue) and `Support.dc.html` (the user's side it refers to as "the help screen"). Then "all objects needed to support support tickets: model, factories, actions, mcp, api methods, proper docs".

## Clarifications

### Session 2026-11-11
- Q: Is a reply to the support email added to the thread? → A: No. Replies are written in the product only.
- Q: Are administrators emailed when a request enters To handle? → A: No. The navigation count only.
- Q: Does the administrator side go through the API and MCP? → A: Yes, both sides.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A user opens a request (Priority: P1)

The user picks one of five categories (feature request, billing, missing emails, question, something broken), reads the short guidance for it, writes a subject and a message, optionally attaches files, and sends. A confirmation shows the request number (`MON-24790` form), what was sent, and what happens next.

**Why this priority**: Without it nothing reaches the administrators.

**Independent Test**: Michael Scott sends a billing request with one PDF and sees its number on the confirmation.

**Acceptance Scenarios**:
1. **Given** a signed in user, **When** they pick "I have a billing question", **Then** the heading, quote, guidance and subject hint change to the billing ones.
2. **Given** an empty subject or a message under 10 characters, **When** they send, **Then** each field says what is missing and nothing is created.
3. **Given** a valid request, **When** they send, **Then** it is created as waiting for support, the confirmation shows its number, and a copy is emailed to the user.

### User Story 2 - An administrator answers from the queue (Priority: P1)

In the admin space, Support lists requests in three views (To handle, Waiting on the user, Solved) plus All, each with its count, oldest first, filterable by text, category, user and age. Opening one shows the thread, a status control, the user's account context and the status history. The administrator writes either a reply, emailed to the user and shown in their thread, or an internal note only administrators see, and chooses which status the request moves to after a reply.

**Why this priority**: It is the design the request names, and a request nobody answers is worse than an email.

**Independent Test**: Seed the five requests from the design and answer Jonas Weber's, moving it to Waiting on the user.

**Acceptance Scenarios**:
1. **Given** requests in several statuses, **When** an administrator opens Support, **Then** To handle is selected, its rows are oldest first, and the admin navigation shows the To handle count.
2. **Given** an open request, **When** the administrator sends a reply with "Then move it to: Solved", **Then** the reply joins the thread signed with their name, the user is emailed, and the history records the move.
3. **Given** an internal note, **When** it is added, **Then** it appears only in the admin thread and the request's status does not change.
4. **Given** filters that match nothing, **When** the list renders, **Then** it says nothing matches and offers to clear the filters.

### User Story 3 - The user follows up (Priority: P2)

"Your requests" lists the user's requests under Open and Solved, with status in words ("Waiting for support", "Reply from support", "Solved"). A request page shows the thread with attachments, a reply box with files, and "Mark as solved".

**Why this priority**: Answers are useless if the user cannot read them in the product.

**Independent Test**: Reply to a request that has a support answer, then mark it solved, then reply again.

**Acceptance Scenarios**:
1. **Given** a request with a support reply, **When** the user replies, **Then** it moves to To handle for the administrators and to "Waiting for support" for the user.
2. **Given** a solved request, **When** the user writes in it, **Then** the notice says sending reopens it, and sending reopens it with the same number.
3. **Given** any request, **When** the user reads it, **Then** no internal note is shown or counted.
4. **Given** the email copy of a reply, **When** the user reads it, **Then** it says to answer from the request page and links there.

### User Story 4 - The same capability through the API and MCP (Priority: P2)

Everything a user does in the Support area, and everything an administrator does in the queue, is possible through the API and the MCP server, and both are documented in the docs portal.

**Why this priority**: The constitution makes API coverage part of a capability being complete.

**Independent Test**: With a token, open a request, list requests, read one, reply, mark it solved.

**Acceptance Scenarios**:
1. **Given** a token for Michael, **When** he lists requests, **Then** only his requests are returned, without internal notes.
2. **Given** a request of another account, **When** it is requested by number, **Then** it is not found.
3. **Given** an administrator's token, **When** they list the queue and reply, **Then** the same rules as the admin screen apply; a non administrator's token gets not found.

### Edge Cases
- More than 5 files, or one over 10 MB: the file is refused with a sentence naming why, the rest are kept.
- The instance has no administrator: the Support area is not offered, since nobody would read it.
- An administrator's own account opens a request: it is handled like any other; the administrator still cannot read the user's vaults (context shows counts only).
- A user account is deleted: its requests and files are deleted with it.

## Requirements *(mandatory)*

### Functional Requirements
- **FR-001**: Users MUST be able to open a request with a category, a subject, a message of at least 10 characters, and up to 5 files of at most 10 MB each.
- **FR-002**: Every request MUST get a unique, human readable number that never changes, including on reopening.
- **FR-003**: A request MUST hold one of three statuses, shown to administrators as To handle, Waiting on the user, Solved, and to the user as Waiting for support, Reply from support, Solved.
- **FR-004**: A new request and every user reply MUST put the request in To handle; a user MUST be able to mark their request solved.
- **FR-005**: Administrators MUST be able to reply, add internal notes, and move a request to any status, and the reply form MUST let them pick the status that follows the reply (default: Waiting on the user).
- **FR-006**: Internal notes MUST never reach the user by any surface: screens, email, API, MCP, export.
- **FR-007**: Every status change MUST be recorded with who made it and when, and shown as the request's status history.
- **FR-008**: The user MUST receive an email copy of a new request and of every administrator reply, linking to the request page.
- **FR-009**: Administrators MUST NOT be emailed about requests; the admin navigation MUST show the To handle count.
- **FR-010**: The admin list MUST filter by text (subject, message, name, email, number), category, user and age (under 24 hours, 1 to 7 days, older), and show counts per view.
- **FR-011**: The admin context panel MUST show account number, subscription, language, application version, vault and contact counts, sign up date, and a link to the account's admin page, and nothing from inside vaults.
- **FR-012**: A user MUST reach only their own requests; a request of another account MUST answer not found on every surface.
- **FR-013**: Every user capability (open, list, read, reply with files, mark solved) MUST exist in the API and the MCP server, documented in the docs portal.
- **FR-014**: Replies MUST be written in the product only; every support email MUST say so and link to the request page.
- **FR-015**: Every administrator capability (queue with filters, read with notes and context, reply, note, move) MUST also exist in the API and MCP, refused as not found to anybody who is not an administrator.
- **FR-016**: Every screen MUST meet WCAG 2.2 AA: category choice as a radio group, list rows reachable by keyboard, status carried by words as well as tone.
- **FR-017**: All shipped copy, including category guidance, MUST be translated in every configured locale.

### Key Entities
- **Support request**: belongs to one account; number, category, subject, status, opened and last activity dates.
- **Support message**: one entry of a request's thread; author (the user or an administrator), kind (user message, reply, internal note), body, time.
- **Support attachment**: a file on a message; name, size, type.
- **Status change**: from, to, by whom, when.

## Success Criteria *(mandatory)*

### Measurable Outcomes
- **SC-001**: A user opens a request in under 2 minutes from entering the Support area.
- **SC-002**: An administrator answers the oldest request to handle in under 3 actions from the admin overview.
- **SC-003**: Zero internal notes are readable by a user through any surface, proven by a test per surface.
- **SC-004**: Every user and administrator capability is reachable through the API and MCP on release day.

## Assumptions

- The intro card names the instance's administrators rather than "Regis and Alexis", so the same copy is true on hosted and self hosted instances.
- One noun, "request", is used on every user screen; the design's "conversation" in the navigation and admin copy becomes "request".
- Support files do not count against the account's storage limit.
- The guide suggestions sketched in `Support.dc.html` but not drawn are out of scope.
- Requests are kept until the account is deleted; there is no automatic purge.
