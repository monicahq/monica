# Feature Specification: MCP tools for reading the account logs

**Feature Branch**: `2026-09-25-mcp-account-logs` (based on `main`) |
**Created**: 2026-09-25 | **Status**: Draft

**Input**: "i want to add logs (from http://monica.test/settings/account/logs) reading to mcp. add to
docs too."

## Context

The Logs screen in the account settings shows two things: the activity of the account (every action
that touched it, newest first, with who did it, where it came from and the address it came from) and
the emails the account was sent (their subject, the address, and whether the mail service delivered
them). The MCP server can read neither.

The JSON API already reads both: it lists the activity a page at a time and reads one entry, and it
lists the emails sent a page at a time and reads one email with its body. The constitution (principle
X) says the MCP server exposes the API and never bypasses it, so every tool below mirrors one of those
endpoints, and no API change is needed.

The tools change no data. Reading the logs is not itself written to the logs, as over the API.

One change is needed before an email body is handed to an assistant. The body is stored with its link
tags removed, but the text inside them stays, and every email with a button also prints the button's
address as plain text under it ("copy and paste the address below into your browser"). The stored body
of a sign-in link email therefore still holds the working link. This feature removes web addresses from
the body when it is stored, which closes the same hole in the API.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ask an assistant what happened in the account (Priority: P1)

Somebody asks their assistant "what changed in my account this week?" or "did my assistant really
delete the Got a tattoo type yesterday?". The assistant reads the activity, newest first, and answers
with what was done, by whom, from where (the web, the API, an assistant) and when.

**Why this priority**: the activity is the main content of the screen named in the request, and it is
the only way to check afterwards what an assistant did with a key.

**Independent Test**: through MCP alone, list the activity, page through it, read one entry by the
identifier the list handed out, and compare with the web screen.

**Acceptance Scenarios**:

1. **Given** the account, **When** the assistant lists the activity, **Then** it receives the entries
   newest first, in the API's shape, with the page, the page size and the total, so it knows whether to
   ask for the next page.
2. **Given** a page number and a page size, **When** the assistant lists the activity, **Then** it
   receives that page, and a page size above the API's maximum is brought down to it, as over the API.
3. **Given** an entry of the account, **When** the assistant reads it, **Then** it receives the same
   shape the API returns: the author, the action, its parameters, the sentence describing it, the
   source, the address it came from and when it happened.
4. **Given** a change made earlier through MCP, **When** the assistant reads the activity, **Then**
   that entry names the assistant (`mcp`) as its source. The name of the key is not part of an entry,
   over the API or here.

### User Story 2 - Ask an assistant whether an email went out (Priority: P2)

Somebody asks their assistant "did Monica send me the reminder about Pam's birthday?" or "why did I
never get the sign-in email?". The assistant lists the emails sent, finds the one in question, and says
whether it was delivered, bounced or is still on its way.

**Why this priority**: the second half of the same screen, and the question it exists to answer ("if
one you expected never arrived, look here first"), but less frequent than reading the activity.

**Independent Test**: through MCP alone, list the emails sent, page through them, and read one by the
identifier the list handed out.

**Acceptance Scenarios**:

1. **Given** the account, **When** the assistant lists the emails sent, **Then** it receives them
   newest first, in the API's shape, with the page, the page size and the total.
2. **Given** an email of the account, **When** the assistant reads it, **Then** it receives its kind,
   subject, address, body, delivery status and the moments it was sent, delivered, bounced or
   complained about, exactly as the API returns it.
3. **Given** a sign-in link email or an address verification email sent after this change, **When**
   the assistant reads it, **Then** its body holds no web address, neither as a link nor as the "copy
   and paste the address below" text, so nobody reading the logs can use the link.

### User Story 3 - Know what the assistant can now read (Priority: P3)

A visitor to `/docs/mcp` reads that an assistant can read the account's activity and emails, and what
that hands it.

**Why this priority**: a key hands an assistant the whole account; the page is where somebody decides
whether to allow that.

**Independent Test**: compare the page's tools table with the server's registered tools.

**Acceptance Scenarios**:

1. **Given** "Available tools", **Then** every new tool is listed by its exact name with one sentence,
   in a group marked as reading only.
2. **Given** `/docs/mcp.md`, **Then** it carries the same tools.
3. **Given** the tool count in the prose, **Then** it matches the table and the server.
4. **Given** the page, **Then** it says in a few sentences what the activity and the emails sent are,
   that the activity records the address each action came from, and that every change an assistant
   makes appears there with the assistant as its source, so it can be checked afterwards.

### Edge Cases

- An entry or an email of another account, or an identifier that is not a positive whole number,
  answers not found in a translated sentence, never forbidden.
- A page past the last one answers an empty list with the total, as over the API.
- A page size below one is brought up to one, as over the API.
- An account with no activity or no email answers an empty list, not a refusal.
- An entry whose author has since been removed still reads, with the author name it was written with.
- An email still on its way has no delivery moment, and answers that as empty rather than omitting it.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The server MUST offer one tool for each of these API capabilities: list the activity,
  read one activity entry, list the emails sent, read one email sent.
- **FR-002**: Each tool MUST accept, refuse and answer in the same cases as its API endpoint, return the
  same resource shape, and name the endpoint it mirrors. Refusals MUST be in the caller's language.
- **FR-003**: The two list tools MUST accept a page and a page size, apply the API's default and
  maximum, and return the page, the page size and the total with the entries.
- **FR-004**: Every tool MUST be confined to the key's account; a record outside it MUST be reported as
  not existing, in a translated sentence.
- **FR-005**: Every tool MUST be marked read only to the client.
- **FR-006**: Using these tools MUST NOT write anything to the account's activity.
- **FR-007**: The reading tool for one email MUST return its body, as the API does.
- **FR-008**: When an email is recorded, its stored body MUST hold no web address: not as a link, and
  not as text, including the address printed under a button. The rest of the body MUST stay readable.
  This applies to every email recorded from then on, whichever way it is read.
- **FR-009**: `/docs/mcp` and `/docs/mcp.md` MUST list every registered tool, and the suite MUST fail
  when the page and the server disagree (the existing check, extended to the new tools).
- **FR-010**: `/docs/mcp` MUST explain in a few sentences what the activity and the emails sent are and
  what reading them hands an assistant, in both the page and its Markdown.

### Key Entities

- **Activity entry**: one thing done in the account. Has an author, an action, its parameters, a
  sentence describing it, a source (web, API, assistant), the address it came from and a moment.
- **Email sent**: one email the account was sent. Has a kind, a subject, an address, a body, a delivery
  status, and the moments it was sent, delivered, bounced or complained about.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Everything the Logs screen and the Emails sent screen show can be read through MCP.
- **SC-002**: An assistant can answer "what did you change in my account yesterday?" using only the
  tools and the identifiers they hand out.
- **SC-003**: Every tool name on `/docs/mcp` matches the server; the suite fails otherwise.
- **SC-004**: No email recorded after this change, read over MCP or the API, holds a web address a
  reader could open.
- **SC-005**: For each new tool, the same input sent to the tool and to its API endpoint produces the
  same records and a refusal in the same case.

## Assumptions

- "Logs" covers both sections of the screen at `/settings/account/logs`: the activity and the emails
  sent, since the screen shows both and links to the full list of emails.
- Pagination follows the API (a page number and a page size), not the web screen's "load more", since
  the tools mirror the API.
- The instance administrator's logs (`/admin/logs`) are out of scope: they govern the instance, not an
  account, and the key belongs to an account.
- There is no filter by date, source or action, because the API offers none. An assistant pages until
  it has gone far enough back.
- The email body is returned over MCP (decided 2026-09-25), on the condition that web addresses are
  removed from it when it is stored.
- Emails recorded before this change are not rewritten. The links they hold last at most an hour (a
  verification link 60 minutes, a sign-in link 5 minutes and once), so any row older than that holds
  only dead links.
- The channel proof code email carries its code in its subject and body, and this feature leaves it
  there: the code only confirms a notification destination the person is setting up, and the subject
  is what the screen needs to recognise the email.
- No endpoint is added or changed. The API reference's description of the email body gains one
  sentence saying links and web addresses are removed from the kept copy (plan, R7).
- The branch is based on `main`. The life event types branch (spec 060) is not merged yet and touches
  the same server registration and docs page, so the second of the two to merge resolves a small
  conflict in those lists.
- Tool copy is English, like the existing tools; refusals reuse translated sentences.
