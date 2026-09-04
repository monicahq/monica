# Feature Specification: Vault reminders list

**Feature Branch**: `2026-09-04-vault-reminders-list` | **Created**: 2026-09-04 | **Status**: Draft

**Input**: Implement `Vault reminders.dc.html` from the Claude Design project "Reminder icon for contact dates". An Active tab lists upcoming reminders, a Sent tab lists ones already sent, and each Active row carries four buttons. The reminder detail screen is a separate pull request.

## Context

The reminder engine is complete. [012](../012-reminder-foundation/spec.md) built the rule; [013](../013-reminder-delivery-engine/spec.md) built generation, delivery, postponement, dismissal and reconciliation. Both excluded screens by instruction, so none of it is reachable from the interface except the panel under one date on one contact. `resources/views/components/vault/sidebar.blade.php:52` already carries a Reminders nav entry with `'url' => null`.

This feature is the screen. Decisions and rejected alternatives are in [research.md](research.md).

## User Scenarios & Testing *(mandatory)*

Requirements below are the testable statements. Each story lists only the scenarios its requirements do not already fix.

### User Story 1 - See upcoming reminders (Priority: P1)

Open Reminders from the vault sidebar and see every reminder due in the next 90 days, grouped so urgent ones come first, with late ones marked. The only story that stands alone; every other acts on a row this one draws.

**Independent Test**: seed reminders across all five buckets plus one beyond the horizon, open the page, read back the buckets in order with the overdue one marked and the far one absent.

1. **Given** a reminder due exactly now, **When** the list draws, **Then** it appears in one bucket only.
2. **Given** a vault whose only live reminders fall beyond 90 days, **When** the tab opens, **Then** the empty state distinguishes that from having no reminders.
3. **Given** a rule with no generated occurrence yet, **When** the tab opens, **Then** it does not appear.

### User Story 2 - See what was sent (Priority: P2)

The Sent tab lists every reminder actually delivered, newest first. The only way to confirm delivery worked, and the first place to look when a reminder seems missing.

**Independent Test**: seed notified occurrences across several days, open the tab, read back one row each, newest first.

1. **Given** a notified occurrence whose rule was since deleted, **When** the tab opens, **Then** the row still appears.
2. **Given** an occurrence dismissed before it was ever notified, **When** the tab opens, **Then** it does not appear.

### User Story 3 - Postpone one occurrence (Priority: P3)

A dialog names the reminder and its current instant, offers four presets, states what the chosen one means, and accepts an explicit date and time. The most common action, and the engine supports it exactly.

**Independent Test**: postpone one occurrence, assert the sibling occurrence, the next cycle and the contact's date are unchanged, and the original instant is kept.

1. **Given** an explicit instant in the past, **When** confirmed, **Then** the save is refused with a readable message and the dialog stays open with its input intact.
2. **Given** an occurrence already dismissed in another tab, **When** postponement is confirmed, **Then** it is refused with a readable message.
3. **Given** a rule whose frozen zone differs from the reader's, **When** a preset is confirmed, **Then** the instant is computed in the rule's zone.
4. **Given** an occurrence in another account, **When** postponement is attempted, **Then** the response is 404.

### User Story 4 - Dismiss one occurrence (Priority: P4)

One button, no dialog. The cheapest way to clear a stale list, and a single call to an existing action.

**Independent Test**: dismiss one occurrence, assert it leaves the Active tab, its rule stays active, and the next cycle remains scheduled.

1. **Given** a rule with two moments in one cycle, **When** one occurrence is dismissed, **Then** the other still appears.

### User Story 5 - Send the notification now (Priority: P5)

A button delivers the reminder's notification to the reader immediately. Answers "will this reach me" before the date arrives; nothing breaks without it.

**Independent Test**: press the button, assert a notification reaches the current user, and assert the occurrence's delivery state is unchanged.

1. **Given** quiet hours in force, **When** the button is pressed, **Then** the notification is still delivered.
2. **Given** the button pressed twice, **When** the Sent tab opens, **Then** neither test appears on it.

### User Story 6 - Reach the reminder's own screen (Priority: P6)

The fourth button links to the reminder detail screen, which is a separate pull request. This feature owes the link only.

**Independent Test**: assert the row carries a link addressed to the reminder, named for assistive technology.

### Edge Cases

- An occurrence whose contact is archived after generation is cancelled by reconciliation and leaves the Active tab; unarchiving restores it.
- An occurrence postponed several times is one row showing its current instant, not its original; a contact with no relation value shows no relation label.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The sidebar Reminders entry MUST link to this screen, render as the current section while on it, and carry the count of upcoming reminders in that vault or no count when zero.
- **FR-002**: A vault in another account MUST return 404, and each tab MUST have its own address.
- **FR-003**: The Active tab MUST list actionable occurrences in the vault inside the generation window, excluding dismissed, cancelled, and those whose rule is deactivated or deleted.
- **FR-004**: The screen MUST state the horizon the Active tab covers, and its empty state MUST distinguish no reminders from none inside the horizon.
- **FR-005**: The list MUST be groupable by when it fires and by contact, switchable on the page, with every bucket showing its count worded for that number and empty buckets omitted.
- **FR-006**: Grouped by when, buckets MUST be Overdue, This week, Later this month, Next month, Later, in that order; grouped by contact, contact names alphabetically with no overdue marking.
- **FR-007**: The Overdue bucket MUST be distinguished by tint, by a mark on its heading, and by the colour of its relative dates, so lateness is never signalled by colour alone.
- **FR-008**: Every Active row MUST show the firing date as day, month and year; the cadence; the contact's avatar and name linked to the contact; the contact's relation when present; and the reminder text beside a mark for the kind of date.
- **FR-009**: Every Active row MUST show the distance in words and MUST reveal the exact instant with its zone and offset on hover and on focus; a row past its instant MUST word the distance as elapsed and the instant as already sent.
- **FR-010**: Every Active row MUST carry four controls in order: postpone, dismiss, send now, detail. Each MUST be named and described for assistive technology, with the description shown on hover and on focus.
- **FR-011**: Postponing MUST open a dialog naming the contact, the reminder text and the current instant, offering in an hour, tomorrow, in a week and in a month, showing which is selected, and stating the exact resulting date and time.
- **FR-012**: The dialog MUST accept an explicit date and time, and MUST name the zone every instant it produces is computed in, which is the rule's frozen zone.
- **FR-013**: Postponing MUST move one occurrence, leaving the sibling occurrence, later cycles and the contact's date untouched, and keeping the original instant.
- **FR-014**: A postponement into the past, or of a finished occurrence, MUST be refused with a readable message, leaving the dialog open and its input intact.
- **FR-015**: Dismissing MUST remove one occurrence from the Active tab without a dialog, leaving its rule active and its other occurrences untouched.
- **FR-016**: Sending now MUST deliver to the requesting user on their enabled channels, MUST ignore quiet hours, MUST leave the occurrence pending, scheduled and unnotified, and MUST NOT produce a Sent row.
- **FR-017**: The detail control MUST be a link addressed to the reminder.
- **FR-018**: The Sent tab MUST list notified occurrences in the vault, newest sent first, excluding those never sent, remaining populated after a rule is deleted or deactivated, each row showing the sent day and time, the channel's mark, the contact's avatar and linked name, the reminder text, and the outcome.
- **FR-019**: The outcome MUST name only states the product holds: dismissed, postponed, or still open. It MUST NOT claim completion.
- **FR-020**: The Sent tab MUST NOT load the vault's whole history at once.
- **FR-021**: The heading MUST name the vault and describe delivery truthfully (channels, time, horizon), and each tab MUST show its row count, readable by assistive technology as a count.
- **FR-022**: Dates, times and numbers MUST follow the reader's own preferences, every phrase MUST be translatable, and every counted phrase MUST vary its wording by number.
- **FR-023**: The screen MUST meet WCAG 2.2 AA, MUST NOT scroll sideways on a narrow window, and MUST NOT carry an add action; reminders continue to be created from a contact's date.

### Key Entities

`Reminder`, `ReminderOccurrence`, `Notification`, `Contact`, `Field`, `FieldValue` and `Vault` all exist unmodified; see [013 data-model](../013-reminder-delivery-engine/data-model.md). A row is a `ReminderOccurrence`.

## Success Criteria *(mandatory)*

- **SC-001**: Reminders due in the next 90 days are reachable in one click from anywhere in the vault, where today none are.
- **SC-002**: Late reminders are identifiable without reading a date and without relying on colour.
- **SC-003**: Postponing takes one click to open, one to choose, one to confirm, and never requires typing a date.
- **SC-004**: Whether a reminder was sent, and when, is answerable without leaving the vault.
- **SC-005**: A reminder's notification can be previewed before its date.
- **SC-006**: The page loads in the time other vault list screens take with 100 upcoming and 1000 sent reminders, and its query count does not grow with row count.
- **SC-007**: Every action touches one occurrence, verifiable from the screen.
- **SC-008**: All phrases exist in the nine configured locales and `php artisan monica:localize --check` passes.

## Assumptions

Recorded with their rationale in [research.md](research.md). The load-bearing one: a row is an occurrence, not a rule, so a rule with nothing generated has no row.
