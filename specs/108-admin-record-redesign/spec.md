# Feature Specification: Admin record screen redesign

**Feature Branch**: `2026-11-09-admin-redesign`

**Created**: 2026-11-09

**Status**: Draft

**Input**: User description: "implement this design for https://monica.test/admin/logs" (Claude Design project `b78dc791-8645-424e-b24c-635ab7aea4f3`, file `Admin Record.dc.html`). The shell around it is specification [106](../106-admin-redesign/spec.md).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - An administrator reads what was done, day by day (Priority: P1)

An administrator opens the record. Under the title and its line, a "Write once" badge says nothing here can change. Entries are grouped under one heading per day ("Today", "Yesterday", then the date), each heading followed by the date and how many actions it holds on this page. Each entry is one sentence saying what was done, with the thing it was done to set apart (a flag name in a fixed width face, a person as a link to their account), a line saying who did it, and on the right how long ago it happened with the exact time beneath.

**Why this priority**: the record answers "who changed this and when". Today it is one undivided list of plain sentences, and the time is only relative.

**Independent Test**: seed actions on three different days by two authors, open the record, and check the day headings, the order, the sentences, the links and both times.

**Acceptance Scenarios**:

1. **Given** Michael Scott switched on the `groups` flag for Pam Beesly today at 13:54, **When** the administrator opens the record, **Then** under "Today" an entry reads "Switched on `groups` for Pam Beesly", "Pam Beesly" leads to her account, the line under it names Michael Scott, and the right side shows how long ago it was and "13:54".
2. **Given** actions yesterday and on 2 November, **When** the record renders, **Then** they sit under "Yesterday" and under "2 November 2026", newest first, each heading saying how many actions it holds on this page.
3. **Given** an action run from the command line, **When** the record renders, **Then** its author reads "The console".
4. **Given** an administrator in New York who prefers a 12 hour clock, **When** the record renders, **Then** the days are cut and the times written in that zone and that clock.
5. **Given** an account that has since been deleted, **When** its entries render, **Then** the person's name shows as plain text, not as a link.

---

### User Story 2 - An administrator narrows and pages the record (Priority: P2)

From an account's screen, "See the record for this account" opens the record narrowed to that account; the screen says so and offers a way back to everything. When more than one administrator has acted, the record can be narrowed to one of them. At the bottom, the record says which entries are shown out of how many, lets the administrator choose how many rows a page holds, and moves to newer or older entries.

**Why this priority**: the filters and paging exist and work today; the redesign must keep them while it changes the look.

**Independent Test**: open the record from an account screen, narrow to one administrator, change the page size, and move between pages.

**Acceptance Scenarios**:

1. **Given** the record opened for Dwight Schrute's account, **When** it renders, **Then** only entries about that account show, and a way to show everything is offered.
2. **Given** 45 entries and 20 rows a page, **When** the administrator moves to older entries, **Then** entries 21 to 40 show and the footer says so.

---

### Edge Cases

- **Nothing recorded yet**: the existing empty state shows ("Nothing has been done yet").
- **A day split across two pages**: its heading repeats at the top of the next page; each count covers only what that page shows.
- **An action whose kind is unknown to this version**: its stored name shows as the sentence, as today, with a neutral icon.
- **An author who was deleted**: the stored address names them, as today.
- **A long flag name or address at 320 pixels**: wraps inside the entry rather than widening the page.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The header MUST show the title, its line, and a "Write once" badge marked with a lock.
- **FR-002**: Entries MUST be grouped by day in the viewer's time zone, newest day first, each group headed by "Today", "Yesterday" or the date, followed by the date and the number of actions it holds on the page.
- **FR-003**: Each entry MUST show an icon for its kind, its sentence, its author, how long ago it happened, and its exact time in the viewer's time format, machine readable and spelled out in full on hover.
- **FR-004**: In the sentence, a flag name MUST be set in a fixed width face, and a person MUST link to their account's admin screen when the account still exists.
- **FR-005**: The flag sentences MUST read "Switched on … for …", "Switched off … for …", "Switched on … for every account" and "Switched off … for every account", matching the "Switch on" and "Switch off" buttons of the account screen.
- **FR-006**: Every other sentence MUST keep its current wording.
- **FR-007**: The administrator filter, the account filter, "Show everything", the page size and the paging MUST keep working as today.
- **FR-008**: The days MUST be a list of sections, each with its heading, and each day's entries an ordered list.
- **FR-009**: Every new wording MUST be translated in every configured language, and the screen MUST meet WCAG 2.2 AA in both themes at 320 pixels and 200% zoom.

### Key Entities

- **Record entry**: one action by one author (a person or the console), with its kind, the values it names, the account it concerns if any, and its moment. It is written once and never changed.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An administrator can tell on which day and at what exact time any entry happened without hovering.
- **SC-002**: Reaching the account an entry concerns takes one action from the entry.
- **SC-003**: Every entry the current screen shows still shows, in the same order, with the same filters and paging.
- **SC-004**: The screen passes a WCAG 2.2 AA check in both themes with no violation.

## Assumptions

- **Reading column**: the screen uses the same 960 pixel column as the account screen (specification 107).
- **Counts per page**: a day's count covers the entries on the page, so the screen never queries beyond what it shows.
- **The account in the meta line**: the design adds "on behalf of account 1"; the account is already linked in the sentence, so the line names the author alone.
- **No new data**: entries already store their kind and values; nothing is added to what is recorded.
