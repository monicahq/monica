# Feature Specification: MCP documentation page

**Feature Branch**: `2026-09-25-docs-mcp` | **Created**: 2026-09-25 | **Status**: Draft

**Input**: "add the `docs/mcp` page in the docs" from `Docs MCP.dc.html` (design project
`eaa519cf-…`), "make sure text is accurate". Where design and code disagree, "Copy corrections" wins.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Connect an assistant to Monica (Priority: P1)

A visitor opens `/docs/mcp`, reads how to create an API key for an assistant, picks their client from
a set of tabs, and copies a configuration that already carries this instance's server address.

**Why this priority**: it is the page; without it nobody connects an assistant without reading code.

**Independent Test**: open `/docs/mcp` signed out and copy the Claude Code configuration.

**Acceptance Scenarios**:

1. **Given** a visitor not signed in, **When** they open `/docs/mcp`, **Then** it answers with the
   title "Model Context Protocol (MCP)" and this instance's address ending in `/api/mcp`.
2. **Given** the server address or a configuration, **When** the visitor copies it, **Then** the
   clipboard holds exactly what is shown and the button says it was copied.
3. **Given** the setup section, **Then** it offers the tabs Claude Code, Cursor, Codex, VS Code,
   Claude Desktop, ChatGPT and Other, Claude Code first.
4. **Given** the "Your Monica address" field, **When** the visitor edits it, **Then** every address on
   the page follows, with a trailing slash removed; emptied, it falls back to the instance's own.
5. **Given** the portal, **When** the visitor follows the MCP card, **Then** they reach `/docs/mcp`.

### User Story 2 - Know what the assistant can do and how to stop it (Priority: P2)

**Why this priority**: a key hands an assistant the whole account; its reach decides whether to connect.

**Independent Test**: compare the tools table with the tools the server registers.

**Acceptance Scenarios**:

1. **Given** "Available tools", **Then** it lists the 13 tools the server exposes, in seven groups,
   each with its exact name and one sentence, and only the group that changes data is badged so.
2. **Given** "Manage access", **Then** it names Settings → Logs as where changes are recorded and
   Settings → Security and access → API keys as where a key is revoked.

### User Story 3 - Fix a failing connection (Priority: P3)

**Independent Test**: "Troubleshooting" holds four entries (refused, unreachable, empty key, too
many requests), each matching a real failure of the server, the last giving the real limit.

### User Story 4 - Read the page with an assistant (Priority: P4)

**Independent Test**: follow "View as Markdown", then "Open in Claude".

**Acceptance Scenarios**:

1. **Given** the page, **When** the visitor presses "Copy for LLM" or follows "View as Markdown",
   **Then** they get the page as Markdown, the latter at `/docs/mcp.md`.
2. **Given** "Open in Claude" or "Open in ChatGPT", **Then** a new conversation opens with a prompt
   asking to read `/docs/mcp.md`, which `/llms.txt` also lists.

### Edge Cases

- A refused clipboard says so; a narrow window hides "On this page"; a sub path or port is kept.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: `/docs/mcp` and `/docs/mcp.md` MUST be public.
- **FR-002**: The page MUST follow the sections of `Docs MCP.dc.html`: Overview, Set up Monica MCP,
  Check your connection, Manage access, Available tools, Troubleshooting, See also, with an "On this
  page" list marking the section in view.
- **FR-003**: Every address on the page MUST default to the instance's own address plus `/api/mcp`.
- **FR-004**: Each client tab MUST show the configuration fixed in
  [contracts/page-content.md](contracts/page-content.md).
- **FR-005**: The tools table MUST match the tools the server registers, by name; a mismatch MUST fail
  the suite, and the count in the prose MUST match the table.
- **FR-006**: The copy MUST be the design's, except where "Copy corrections" replaces it.
- **FR-007**: The portal's MCP card and `/llms.txt` MUST point at the page; "See also" MUST link to
  the API reference, the API keys settings and Settings → Logs.
- **FR-008**: The page MUST meet WCAG 2.2 AA: a keyboard operable tab list, announced copy results,
  `aria-current` on the section in view.

### Copy corrections

| Where | Design | Page | Why |
| --- | --- | --- | --- |
| Set up | Select **Assistant** | Under **What will use this key**, choose **AI Assistant** | The form's label (`ApiKeyClientEnum`) |
| Manage access | your account’s activity log | **Settings → Logs** | The screen's name. It shows no source or key name, so the page promises neither |
| Access notice | every vault you can open | every vault in your account | Access is by account |
| `list-vaults` | the vaults you can open, with their names | Lists the vaults of your account. | Same |
| `list-contacts` | Can search by name. | Can search, narrow to a grouping, and sort. | Takes `query`, `filter`, `sort`, `direction` |
| `get-contact` | names, entity type and metadata | Returns one contact: their names, their template, and whether they are a favourite or archived. | The response carries `template_name` |
| `update-contact-name` | first name, last name or nickname | first, middle or last name, or nickname | Takes `middle_name` |
| `list-templates` | available in a vault | Lists the templates of your account. | Account level |
| `list-entity-types` | a vault holds, such as people, pets or organisations | Lists the kinds of record your account keeps besides contacts. | Account level; examples not guaranteed |
| `list-relationship-types` | such as mother or colleague | Lists the kinds of relationship your account uses, such as parent or colleague. | The tool's wording |
| Other two category tools | (no owner) | "your account" | Account level |
| Refused | wrong, expired or revoked | wrong or revoked | Keys do not expire |
| Claude Desktop notice | not yet verified, use another client | Request headers are a beta some organisations have: choose No sign in, add `Authorization: Bearer YOUR_API_KEY`; otherwise use another client | Claude's connector documentation ([research](research.md#r2)) |
| VS Code notice | Configurations that ask for input … are not passed to Agent Host | VS Code does not forward servers that ask for input to Agent Host | VS Code's wording |
| Too many requests | the request limit for your key | 60 requests a minute for each key; a self hosted instance sets none | `throttle:mcp` |

Everything else was checked and is kept ([research](research.md#r4)).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: From opening the page to a configuration on the clipboard takes under one minute,
  without editing the address.
- **SC-002**: Every tool name on the page matches the server; the suite fails otherwise.
- **SC-003**: No sentence on the page or its Markdown states a fact the code or a client's
  documentation contradicts, and both carry the same sections.

## Assumptions

- The page copy is English in the markup, like the API reference.
- The client configurations and third party notices are checked in [research.md](research.md).
- Out of scope, worth separate changes: the API keys notice "There is nothing to point a key at yet",
  and the server announcing itself as "Erica" (`#[Name('Erica')]`).
