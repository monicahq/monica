# Feature Specification: Reminder answers and the contact's reminders

**Feature Branch**: `2026-09-26-reminder-answers` | **Created**: 2026-09-26 | **Status**: Draft

**Input**: Rework reminders around two answers per deadline, "I remembered" and "I forgot", kept
per date, and add a screen listing every reminder about one contact, from `Contact Reminders.dc.html`
in the Claude Design project. The application is not in production: no backward compatibility, no
data kept, no transition. The target schema is written directly.

## Context

A deadline today can be postponed, dismissed, or notified ahead of time through the rule's moments
([041](../../spec/models/041-reminder-occurrence-done.md)). None of that says whether the
person actually thought of it. This feature removes those mechanisms and replaces them with one
question asked per deadline, answered once.

Words used below. A **reminder** is the rule. A **deadline** is one date the rule produces
(`ReminderOccurrence`). An **answer** is `remembered` or `forgotten`, stored on the deadline.
**Today** is the calendar day in the user's time zone.

This feature supersedes the conflicting parts of [012](../012-reminder-foundation/spec.md),
[013](../013-reminder-delivery-engine/spec.md), [015](../015-vault-reminders-list/spec.md),
[017](../017-reminders-api/spec.md), [020](../020-reminder-detail-page/spec.md) and
[043](../043-reminder-row-actions/spec.md). Aligning the living documents is part of this feature:
`spec/spec.md` sections 10.1 to 10.3 and 14.2, models 040 to 043, and the constitution's reminder
constraint, which still names postponing and skipping as actions.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Answer a deadline (Priority: P1)

Monica Geller gets told on 26 September that it is the day to call Chandler about the flat. She
opens the reminder and presses **I remembered**. The deadline leaves the active list and appears in
the history as "You remembered". The monthly rule's October deadline is untouched.

**Independent Test**: create a deadline due today, answer it, check it left the active list, sits in
the history with its date and answer, and the next deadline is unchanged.

**Acceptance Scenarios**:

1. **Given** a deadline due today, **Then** only I remembered is offered, before and after the
   notification went out.
2. **Given** a deadline due yesterday with no answer, **Then** it is Overdue and both I remembered and
   I forgot are offered.
3. **Given** a deadline due tomorrow, **Then** it is Upcoming and no answer is offered.
4. **Given** an answerable deadline, **When** one answer is pressed, **Then** it is recorded at once,
   without a confirmation step, with the time it was given.
5. **Given** an answered deadline, **Then** no screen, endpoint or tool can undo it, change it or
   answer it again, including a second tab sending the other answer.
6. **Given** a deadline due today with its email not yet sent, **When** it is answered, **Then** the
   email is never sent; a notification already sent stays as history.
7. **Given** this year's birthday answered I forgot, **Then** next year's birthday is Upcoming with
   no answer.

---

### User Story 2 - A deadline nobody answered stays open (Priority: P1)

Monica ignores the reminder. It is never marked forgotten on her behalf, and reading the
notification does not count as remembering.

**Acceptance Scenarios**:

1. **Given** a deadline due today whose notification was sent and read, **Then** it is still Today
   with no answer.
2. **Given** a deadline three weeks past with no answer, **Then** it is still Overdue and still
   answerable.
3. **Given** a monthly rule whose last three deadlines are unanswered, **Then** all three are
   Overdue, and the next one is still generated and notified on its day.
4. **Given** an unanswered deadline, **Then** no second notification is ever sent for it.

---

### User Story 3 - The contact's reminders (Priority: P1)

From Chandler Bing's page, Monica opens **Reminders**: every reminder about him, grouped into
Overdue, Today and Upcoming, with the history of her answers and a summary of the last twelve
months. This is `Contact Reminders.dc.html`.

**Independent Test**: seed a contact with a deadline in each category and two answers, open the
screen, check each group, each filter and the summary.

**Acceptance Scenarios**:

1. **Given** the contact's page, **Then** it offers a way to the Reminders screen, and that screen
   offers the way back to the overview.
2. **Given** the screen, **Then** it shows the filters All, Overdue, Today, Upcoming and History,
   each with its count, All selected by default.
3. **Given** All, **Then** the groups read Overdue ("The day has passed without an answer."), Today,
   Upcoming ("Soonest first."), each omitted when empty.
4. **Given** a row, **Then** it shows the wording, the date with its relative day ("in 2 weeks"), the
   cadence ("Once", "Every month", "Every 3 months", "Every year"), and for a reminder read from a
   date on the contact, "Follows" and that date's label, linking to it. An overdue row carries an
   Overdue badge.
5. **Given** an Overdue or Today row, **Then** it carries the answers the day allows; an Upcoming row
   carries none.
6. **Given** an active row, **Then** it carries a menu to edit or delete the reminder.
7. **Given** History, **Then** each answered deadline shows "You remembered" or "You forgot", "Due
   {date} · answered {date}", its cadence and its source, newest answer first, with no action.
8. **Given** the summary, **Then** it states the share remembered and the share forgotten out of the
   deadlines due in the last twelve months, the rest waiting for an answer, as text and as a chart
   whose label says the same.
9. **Given** a filter with nothing in it, **Then** the screen says so in that filter's own words
   (for example "Nothing overdue").
10. **Given** a contact with no reminder, **Then** All says "No reminders about {name} yet" and how to
    add one.
11. **Given** **Add a reminder**, **Then** a reminder about this contact can be created with a
    wording, a first date, a cadence and its interval, and the local time.

---

### User Story 4 - Same rules on the vault's Reminders page and the reminder's page (Priority: P2)

The vault's Reminders page and a reminder's own page use the same categories and the same two
answers. Dismiss, Remind me later and their modals are gone.

**Acceptance Scenarios**:

1. **Given** the vault's Reminders page, **Then** it groups active deadlines into Overdue, Today and
   Upcoming, and offers the same answers as the contact's screen.
2. **Given** a reminder's page, **Then** its next deadline card offers the answers the day allows,
   and its history lists every past deadline with its date and its answer or "No answer".
3. **Given** any screen, **Then** nothing offers to postpone, dismiss, reopen, undo, pause, or notify ahead
   of the day.

---

### User Story 5 - The API and MCP (Priority: P3)

A client lists a contact's deadlines by category, reads the history, records an answer, and creates,
edits and deletes a manual reminder, with the same refusals as the interface.

**Acceptance Scenarios**:

1. **Given** a deadline due tomorrow, **When** forgotten is sent for it, **Then** it is refused with
   422 and nothing is written.
2. **Given** an answered deadline, **When** any answer is sent again, **Then** it is refused with 422.
3. **Given** the history endpoint, **Then** each past deadline returns its date, its answer or null,
   and the time of the answer.
4. **Given** an MCP client, **Then** the same operations and refusals are available as tools.

---

### Edge Cases

- A deadline cancelled (rule changed, reminder deleted, contact archived or deleted) is
  neither Overdue nor answerable, and is never counted as forgotten.
- A reminder created today after its send time produces no deadline for today; it starts at its next
  date. A one-off reminder whose only date is that one is refused.
- A notification held into the next day (quiet hours, digest, outage) is still sent, unless the
  deadline was answered or cancelled in the meantime.
- Editing a reminder's date, cadence or time replaces its future unanswered deadlines and leaves every
  answer untouched.
- A reminder whose date field is cleared stops producing deadlines; its answers stay in the history.
- A deadline whose day comes round while the user changes time zone is classified by the zone in force
  when the screen is read; its notification time stays in the zone frozen on the rule.
- Two answers sent at once from two tabs: exactly one is recorded, the other is refused.
- Keyboard only: every filter, answer and menu is reachable, and the filters report which is selected.

## Requirements *(mandatory)*

### Functional Requirements

**Scheduling and notification**

- **FR-001**: A reminder MUST define a first date and a cadence (once, weekly, monthly, yearly, with an
  interval). Every date it produces is an independent deadline.
- **FR-002**: A reminder MUST have one of two sources: manual, about a contact, or a date on the
  contact (birthday included). Reminders tied to life events are out of scope.
- **FR-003**: Each deadline MUST produce exactly one notification per enabled channel, on its day, at
  the reminder's local time. Nothing is sent before the day and nothing is sent again after it.
- **FR-004**: Moments ("7 days before"), postponing, dismissing, reopening, undoing and pausing a
  reminder MUST be removed,
  with their settings, fields, statuses, routes, endpoints and wording. Nothing is kept for
  compatibility.
- **FR-005**: Saving a date on a contact MUST NOT create a reminder silently; the date panel offers
  one, as today, without moments.

**Answers**

- **FR-006**: I remembered MUST be available from the deadline's day; I forgot MUST be available from
  the day after.
- **FR-007**: An answer MUST be recorded in one action with no confirmation, and MUST store
  `remembered` or `forgotten` with its timestamp on the deadline.
- **FR-008**: An answer MUST be final. Every attempt to change or repeat it MUST be refused, whatever
  the surface.
- **FR-009**: Answering MUST take the deadline off the active lists and abandon its unsent
  notifications, and MUST NOT change the reminder or any other deadline.
- **FR-010**: An unavailable answer MUST NOT be offered and MUST be refused by the server.

**Categories**

- **FR-011**: An active deadline with no answer MUST be Upcoming before its day, Today on its day
  (also after the notification time), and Overdue from the next day until it is answered.
- **FR-012**: No absence of answer MUST ever be recorded or shown as forgotten. No notification sent
  or read MUST ever be recorded or shown as remembered.
- **FR-013**: Overdue and Today MUST list every unanswered deadline. Upcoming MUST list the next date
  of each active reminder, whether or not that deadline has been generated yet.

**History**

- **FR-014**: Every deadline MUST keep its date, its answer if any, and the time of the answer, so that
  for any date the product can say remembered, forgotten or no answer.
- **FR-015**: The answer MUST be stored apart from the delivery state and the cancellation state.
- **FR-016**: Editing or deleting a reminder MUST NOT alter a recorded answer.
- **FR-017**: Answers MUST be presented under **History**. No screen uses "Done" or "Completed".

**Surfaces**

- **FR-018**: The contact's page, the contact's Reminders screen, the vault's Reminders page, the
  reminder's page, the API and MCP MUST apply FR-006 to FR-017 identically.
- **FR-019**: The contact's Reminders screen MUST follow `Contact Reminders.dc.html`: summary, filters
  with counts, the three groups, History, empty states, Add a reminder, and the row menu.
- **FR-020**: The summary chart MUST NOT carry its meaning by colour alone: the percentages are
  written next to it and the chart has a text label.
- **FR-021**: Deleting a reminder MUST warn first that it is final. Deleting is the only way to stop a
  reminder; there is no pause.
- **FR-022**: Every new wording MUST be translated in every configured locale.

**Decided on 2026-09-26**

- **FR-023**: A date whose send instant has already passed when the reminder is created MUST NOT
  produce a deadline. Creating a one-off reminder whose only date is in that case MUST be refused.
- **FR-024**: A notification held past the deadline's day MUST still be sent when it is released,
  unless the deadline has been answered or cancelled by then. It is still the one notification per
  channel of FR-003.
- **FR-025**: Deleting a reminder, and archiving or deleting its contact, MUST cancel every
  unanswered deadline of that reminder, Today and Overdue included, and abandon their unsent
  notifications. Answered deadlines MUST stay in the history unchanged.
- **FR-026**: The vault's Reminders page MUST keep its Sent tab and gain a History tab.

### Key Entities

- **Reminder**: the rule. Source (manual or a date on the contact), wording, first date for a manual
  reminder, cadence and interval, local time, frozen time zone, whether it is active and why not. No
  moments.
- **ReminderOccurrence**: one deadline. Its local day, its notification instant, its answer
  (`remembered`, `forgotten` or none) and answer time, when delivery was processed, and whether and
  why it was cancelled. No status column, no postponement and no dismissal fields.
- **Notification**: one per deadline and channel. Its delivery state never counts as an answer.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: For any deadline of the last twelve months, the product returns remembered, forgotten or
  no answer, and the same value on every surface.
- **SC-002**: Answering takes one action and zero confirmations.
- **SC-003**: No deadline receives more than one notification per channel.
- **SC-004**: No screen, endpoint or tool offers postpone, dismiss, reopen, undo, pause or an early
  notification.
- **SC-005**: Every control on the contact's Reminders screen works with the keyboard alone and is
  announced correctly by a screen reader.

## Assumptions

- Answers are declarations: "I remembered" does not prove the call was made.
- A manual reminder cannot start before today.
- Editing a reminder's schedule replaces its unanswered deadlines after today; Today and Overdue ones
  stay as they are.
- Restoring a contact does not bring back the Today and Overdue deadlines
  FR-025 cancelled; only what is still ahead is generated again.
- The "Send me a test" action is unaffected.
- The sidebar count becomes the number of deadlines awaiting an answer (Today plus Overdue).
- The quiet hours, the digest and the channel preferences stay as they are.
- The summary's twelve months end today and count deadlines due in that period that were not
  cancelled.
- The way into the contact's Reminders screen is a link on the contact's overview carrying the number
  awaiting an answer; the design draws only the screen itself.

## Clarifications

### Session 2026-09-26

- Q: A reminder created at 15:00 for today with a 09:00 send time? → A: No deadline today; it starts
  at its next date (FR-023).
- Q: A notification held into the next day? → A: Sent anyway, unless answered or cancelled
  (FR-024).
- Q: Today and Overdue deadlines when the reminder is deleted, or its contact archived or
  deleted? → A: Always cancelled (FR-025).
- Q: The vault page's Sent tab? → A: Kept, with History added (FR-026).
- Pausing a reminder is removed as well; deleting is the only way to stop one (FR-004, FR-021).
