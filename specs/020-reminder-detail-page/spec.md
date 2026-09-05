# Feature Specification: Reminder Detail Page

**Feature Branch**: `2026-09-04-reminder-detail`

**Created**: 2026-09-04

**Status**: Draft

**Input**: User description: "i want to implement the page to have the details of a reminder", plus the design at `Reminder detail.dc.html`, which settles the layout and the wording.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read one reminder end to end (Priority: P1)

One screen holding the whole account of a reminder: who it is about, which date it reads, how often it fires, when it fires next, where the notification goes, what is still coming, and what has happened.

**Why this priority**: a reminder has no screen today, only a checkbox under a date on a contact.

**Independent Test**: read a birthday reminder's cadence, moments, zone, next date and history here.

**Acceptance Scenarios**:

1. **Given** an active reminder, **When** the page is opened, **Then** it names the contact, the field it reads, the cadence, the moments, the hour and the zone the rule was frozen in.
2. **Given** a reminder that has gone quiet, **When** the page is opened, **Then** the state says so, the reason is given in words, no date is claimed as next, and Edit still leads to the field on the contact.
3. **Given** a reminder of another account, or of a vault other than the one in the address, **When** the page is opened, **Then** it is not found.

---

### User Story 2 - Act on what is coming, from here (Priority: P2)

The next notification is offered with the two things somebody does about it, putting it off and dismissing it, and so is each occurrence still ahead.

**Why this priority**: sending somebody back to the list to act makes this page a poster.

**Independent Test**: postpone the next occurrence and see the banner and the list move with it.

**Acceptance Scenarios**:

1. **Given** an occurrence still ahead, **When** it is postponed, **Then** only that occurrence moves, its original instant is kept, and the page redraws with the new instant.
2. **Given** an occurrence still ahead, **When** it is dismissed, **Then** it stops being deliverable, the page says so, and the next one becomes the next notification.
3. **Given** a reminder that has gone quiet, **When** the page is drawn, **Then** neither offer is made.

---

### User Story 3 - Pause a reminder, and bring it back (Priority: P3)

Somebody silences a reminder they still want the rule for. The schedule is kept, nothing is delivered while it holds, and resuming puts the next notification back where it was.

**Why this priority**: stopping a reminder today means deleting it, losing the rule and its history.

**Independent Test**: pause, confirm nothing is delivered, resume, and see the same next date return.

**Acceptance Scenarios**:

1. **Given** an active reminder, **When** it is paused, **Then** it becomes inactive, gives being paused by hand as the reason, and its occurrences still ahead are cancelled.
2. **Given** a paused reminder, **When** it is resumed, **Then** it becomes active and its cancelled occurrences still ahead come back at the instants they held.
3. **Given** a reminder silenced by the system, **When** the page is drawn, **Then** it offers no resume, because the cause is not somebody's to lift.

---

### User Story 4 - Delete the reminder from here (Priority: P4)

The page ends with the one destructive thing it offers, behind a warning saying what goes and stays.

**Why this priority**: it completes the page, the action exists, and nothing depends on it.

**Independent Test**: delete a reminder and find the date still on the contact.

**Acceptance Scenarios**:

1. **Given** the delete panel, **When** it is used, **Then** it warns first, naming the reminder.
2. **Given** the warning is accepted, **Then** the reminder goes, its occurrences still ahead are cancelled, the date on the contact is untouched, and the browser lands on the reminders list.

---

### Edge Cases

- A yearly reminder whose next date lies beyond the generation window. Nothing is listed, and the page says when the rule falls next rather than drawing an empty panel as if it were broken.
- A reminder whose value was cleared has no source date, and one that has never fired has no last delivery. The page says so in both cases rather than leaving a blank.
- The design's contact card carries a group, a location and a last conversation. None exists here, and the card MUST carry only what does.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A reminder MUST have an address of its own inside its vault, reachable from every row of the reminders list, refusing as not found a reminder of another account or of another vault.
- **FR-002**: The page MUST name the contact, link to their profile, and draw the date the reminder reads with the field it comes from.
- **FR-003**: The page MUST state the cadence, its interval, every moment, the hour and the frozen zone, and say when the rule falls next in that zone.
- **FR-004**: The page MUST say which channels a notification uses, what it will say, when one last went out, and MUST list the occurrences still ahead with their instants, statuses and whether each was moved.
- **FR-005**: The page MUST show what has happened to the reminder, newest first, built from the account's existing record of actions and from the occurrences themselves. No new store of events is created.
- **FR-006**: The page MUST offer postponing and dismissing on any occurrence that is still actionable, and MUST offer neither on a reminder that is not active.
- **FR-007**: Users MUST be able to pause an active reminder from this page, and to resume one they paused.
- **FR-008**: Pausing MUST keep the rule and its history whole, cancel what is still ahead, and deliver nothing while it holds. Resuming MUST restore only what pausing cancelled.
- **FR-009**: A reminder silenced by the system MUST NOT offer resuming, and the page MUST give the cause in words.
- **FR-010**: Users MUST be able to delete the reminder from this page, warned first, and MUST land back on the reminders list.
- **FR-011**: Editing MUST NOT be duplicated here: the page links to the field on the contact where the rule is set.
- **FR-012**: Every instant on the page MUST be read in the rule's own frozen zone, and MUST name that zone.
- **FR-013**: Every string MUST be translatable, every count MUST choose its wording by the count, the page MUST be usable by keyboard alone, and no state may be carried by colour alone.

### Key Entities

- **Reminder**: the rule. Gains one inactive reason, paused by hand, the only one a person may set and lift.
- **Reminder occurrence**: one concrete deadline, acted on here exactly as the list acts on it.
- **Log entry**: the account's append-only record, already written for every reminder action, read here as history.

## Success Criteria

### Measurable Outcomes

- **SC-001**: Somebody can answer "when does this next reach me, and why then" from the page alone, without opening the contact.
- **SC-002**: Pausing takes one action, delivers nothing while it holds, and resuming returns the same next instant.
- **SC-003**: Deleting a reminder never removes the date it was built from.
- **SC-004**: The page works in all nine configured locales with no untranslated string.

## Assumptions

- The address is `vaults/{vault}/reminders/{reminder}`, which the routes file already reserves below its literal segments. Postponing and dismissing reuse the routes the list already uses.
- History is derived from existing log rows and occurrence columns. Nothing is backfilled, so an old reminder shows a shorter history.
- The contact card carries the name, avatar, source date and the contact's reminder count. Groups, tags and last conversation are left out because they do not exist.
- Design tokens map onto the existing Tailwind theme; no new CSS layer is introduced.
