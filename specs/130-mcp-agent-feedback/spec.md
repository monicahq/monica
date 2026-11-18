# Feature Specification: Agent feedback through MCP

**Feature Branch**: `2026-11-18-mcp-agent-feedback`

**Created**: 2026-11-18

**Status**: Draft

**Input**: User description: "Add a submit_feedback tool to the MCP. An agent using the MCP writes a detailed issue including a proposed fix, recorded in the instance and shown on a new screen of the admin panel. Document the tool in the MCP docs." Reference: Sentry's `submit_feedback_to_sentry_team` tool. Design: `Admin Agent Feedback.dc.html`, design project `670b2827-68ce-4595-8d08-43fbe6fcd282`.

**Scope**: the MCP tool and the API endpoint it mirrors, the admin screens that list and handle reports, and the MCP documentation. Reports go to the administrators of the instance the agent is connected to, never to the Monica maintainers.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - An agent reports friction with a tool (Priority: P1)

An agent working in somebody's account hits a problem with Monica's MCP: a tool fails, a tool it needs is missing, or a description misled it. It calls `submit_feedback` once, with a title, the kind of problem, how much it blocked the task, the tool concerned, what it tried step by step, what it observed, what it expected, and the fix it proposes. The instance records the report and answers with its identifier.

**Why this priority**: without the tool there is nothing to show. It is the whole input of the feature.

**Independent Test**: call the tool with a complete report and find it stored, attributed to the account, with every field intact.

**Acceptance Scenarios**:

1. **Given** a connected agent, **When** it submits a complete report, **Then** the report is stored with status New and the answer carries its identifier.
2. **Given** a report whose description is a placeholder ("test", "n/a", "todo", empty), **When** it is submitted, **Then** it is refused with a sentence telling the agent to describe the problem or not call the tool.
3. **Given** a report missing a required field or exceeding a length limit, **When** it is submitted, **Then** it is refused with the validation message, exactly as the API endpoint refuses it.
4. **Given** the MCP server is switched off for the account, **When** the agent lists tools, **Then** `submit_feedback` is absent with the rest.

---

### User Story 2 - An administrator triages reports (Priority: P1)

An administrator opens Agent feedback from the admin sidebar, which shows how many reports are New. The list shows each report with its status, title, severity, kind, short identifier, age, model, person and vault, and the tool concerned. Status tabs (New, Accepted, Fixed, Dismissed, All) carry counts; kind and severity filters and a search over title, tool, person and email narrow the list. Opening a report shows what the person asked, the steps the agent tried with their outcome, observed and expected behaviour, the proposed fix with its code, the person and account it came from, the session (connection, model, submission time, report identifier) and the other reports about the same tool.

**Why this priority**: the reports exist to be read and acted on.

**Independent Test**: seed reports in every status, filter, search and open one.

**Acceptance Scenarios**:

1. **Given** reports in several statuses, **When** the administrator picks a tab, **Then** only reports in that status show, and each tab shows its count.
2. **Given** filters or a search matching nothing, **When** the list renders, **Then** it says no report matches and suggests clearing the filters; with no filter, each tab has its own empty message.
3. **Given** a report about tool X, **When** it is opened, **Then** "Same tool" lists the other reports about X and links to them.

---

### User Story 3 - An administrator acts on a report (Priority: P2)

From a report, the administrator accepts it, marks it fixed, dismisses it or reopens it, adds notes for other administrators, and copies the report as Markdown to paste into an issue tracker.

**Why this priority**: triage without a state turns the list into a pile. Copying gives the report a way out of the instance.

**Independent Test**: move one report through every transition and add a note.

**Acceptance Scenarios**:

1. **Given** a New report, **Then** Accept and Dismiss are offered; **given** an Accepted one, Mark as fixed and Dismiss; **given** a Fixed or Dismissed one, Reopen, which returns it to New.
2. **Given** a non-empty note, **When** it is added, **Then** it shows with its author and time, and the list row shows the note count.
3. **When** Copy as Markdown is pressed, **Then** the clipboard holds the title, tool, observed, expected, proposed fix and code, and the button says it was copied.

---

### User Story 4 - Agents and people learn what the tool is for (Priority: P2)

The MCP documentation describes `submit_feedback`: what it is for, when to call it (once per task, only when a tool failed, was missing or got in the way), when not to (progress logs, tests, tasks that went fine), what it sends and who reads it. The tool's own description and the server instructions say the same in one line.

**Independent Test**: open the MCP documentation and find the tool with its purpose.

### Edge Cases

- An agent submits the same report several times in one task: each submission is stored; the rate limit of the MCP route applies, and the description asks for one call per task.
- The tool named in the report does not exist (the "missing tool" kind): the name is stored as written.
- The account is deleted: its reports and their notes are deleted with it.
- A report's text contains Markdown or HTML: it is shown as plain text, never rendered.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The MCP server MUST offer `submit_feedback` to every account for which the MCP server is on, mirroring an authenticated API endpoint that does the same.
- **FR-002**: A report MUST carry a title, a kind (bug, missing tool, unclear description, improvement), a severity (blocks the task, needs a workaround, minor), observed and expected behaviour and a proposed fix; it MAY carry the tool concerned, the person's request, the steps tried (tool, arguments, outcome, result), proposed code, and the model name.
- **FR-003**: The system MUST record, without asking the agent, the account, the user, the name of the connection (assistant or API key) it came through, and the submission time.
- **FR-004**: The system MUST refuse placeholder descriptions and enforce a length limit on every text field.
- **FR-005**: The tool description MUST tell the agent to leave out vault content and personal data: it paraphrases the person's request and replaces names, dates and other values in arguments with placeholders, so the administrator capacity never reaches the contents of a vault.
- **FR-006**: Only administrators MUST reach the Agent feedback screens; anybody else gets not found.
- **FR-007**: The list MUST filter by status, kind and severity, search title, tool, person name and email, and page through results.
- **FR-008**: Status transitions MUST follow User Story 3 and be written to the administrator log with the administrator who made them.
- **FR-009**: Notes MUST be visible to administrators only and never to the person.
- **FR-010**: The sidebar entry MUST show the count of New reports, announced to screen readers.
- **FR-011**: The MCP documentation and the tool catalogue MUST list `submit_feedback` with its purpose and calling rules.
- **FR-012**: The call MUST appear in the person’s MCP history like any other call, and the report as one line of their account log, so the person can see what was sent on their behalf.

### Key Entities

- **Agent feedback report**: one submission, with the fields of FR-002 and FR-003, a status, a public identifier and a short sequential number for display.
- **Agent feedback note**: a note by an administrator on one report, with its author and time.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An agent files a report in one call, and it is visible to administrators immediately after.
- **SC-002**: An administrator finds any report by tool, person or title in under 30 seconds with 500 reports stored.
- **SC-003**: Every screen of the feature passes WCAG 2.2 AA with the keyboard alone and a screen reader.
- **SC-004**: A copied report pastes into an issue tracker with no editing needed to read it.

## Assumptions

- The tool is not behind a new flag: it rides the existing `mcp-server` switch and ships whole in one change.
- Every admin action has an API endpoint and an MCP tool for administrators, as the support queue does (constitution principle X).
- Status and notes are admin-side only; the person is not notified when their report changes status.
- "Same tool" lists reports with the same tool name, newest first.
- Severity and kind labels are the design's; the stored values are stable identifiers.
