# Feature Specification: Add a reminder from the contact page's Reminders widget

**Feature Branch**: `2026-09-26-reminder-answers` | **Created**: 2026-09-27 | **Status**: Draft

**Input**: Add an Add button to the Reminders widget which shows, within the widget, the form drawn
in `Reminders Widget Add.dc.html` in the Claude Design project.

## Context

[064](../064-contact-page-reminders-widget/spec.md) put a Reminders widget on the contact's overview
and left out its Add button. [063](../063-contact-reminders-widget/spec.md) already lets a reminder be
written about a contact on its own screen, with its rules and refusals. This feature adds the button
and a shorter form inside the widget, so a reminder is written without leaving the contact's page.
What a reminder may be, and what is refused, stays 063's.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Write a reminder without leaving the page (Priority: P1)

On Chandler Bing's page, Monica Geller presses **Add** in the Reminders widget. The widget turns into
a form headed "Add a reminder", "About Chandler Bing". She writes "Ask how the new job is going",
picks 14 October, leaves Once, and presses **Add reminder**. She is still on Chandler's page, and the
widget lists the new reminder under Upcoming.

**Independent Test**: open the form from the widget, submit a wording and a date, check the reminder
exists about that contact and the reader is back on the overview with it in the widget.

**Acceptance Scenarios**:

1. **Given** the widget, with or without reminders, **Then** its header carries an Add button.
2. **Given** Add pressed, **Then** the widget's content is replaced by the form, without leaving the
   page, and focus moves to the first field.
3. **Given** the form, **Then** it asks "What should we remind you about?" (required), the Date
   (required) and Repeat (Once, Weekly, Monthly, Yearly), Once selected.
4. **Given** a repeat other than Once, **Then** a line "Every {n} {unit}" appears with 1 filled in, its
   unit following the repeat and the number ("week", "weeks").
5. **Given** a valid form submitted, **Then** the reminder is written about the contact under 063's
   rules, the reader is on the contact's overview, and the widget shows the list with the new row.
6. **Given** Cancel, **Then** nothing is written, what was typed is dropped, the widget shows its list
   or blank state again, and focus returns to Add.

---

### User Story 2 - A form the server refuses (Priority: P1)

Monica submits without a date. The form stays open in the widget with what she typed and says
"Pick a date." under the field.

**Independent Test**: submit an empty wording, an empty date, and a one-off date already passed, and
check each error in the open form.

**Acceptance Scenarios**:

1. **Given** no wording, **Then** the form says "Say what the reminder is about." under it.
2. **Given** no date, **Then** the form says "Pick a date." under it.
3. **Given** a refusal of 063 (a one-off date whose time has passed, for example), **Then** its message
   shows under the field it concerns.
4. **Given** any refusal, **Then** the reader is on the overview with the form open in the widget and
   every value kept, and nothing is written.

---

### User Story 3 - The notification time (Priority: P2)

The form reads "Notification time: 09:00 · America/Toronto". Monica presses **Change**, sets 18:30,
and presses **Done**; the line now reads 18:30.

**Acceptance Scenarios**:

1. **Given** the form, **Then** it states the notification time and time zone it will use, the user's
   own by default, with Change.
2. **Given** Change, **Then** a Time field and a Time zone field appear, and the button reads Done and
   reports that it is expanded; Done folds them away and keeps the values.
3. **Given** the form, **Then** it says "We’ll notify you on this date. No advance notifications."

---

### Edge Cases

- A contact with no reminder: Add opens the same form from the blank widget, and Cancel returns to it.
- An archived contact: the widget offers no Add, since 063 gives an archived contact no deadlines.
- An interval above the maximum 063 allows is refused under the Every field.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The widget's header MUST carry an Add button in both of its states, except for an
  archived contact.
- **FR-002**: Add MUST replace the widget's content with the form in place, focus its first field, and
  leave the page where it is.
- **FR-003**: The form MUST ask the wording and the date, both required, and the repeat, Once by
  default; a repeat other than Once MUST reveal its interval, 1 by default, with a unit following the
  repeat and the number.
- **FR-004**: The form MUST state the notification time and time zone, the user's own by default,
  and let them be changed behind a Change control that reports whether it is expanded.
- **FR-005**: A valid form MUST write the reminder through the same rules as the contact's Reminders
  screen and return the reader to the overview with the widget listing it.
- **FR-006**: A refused form MUST return the reader to the overview with the form open, every value
  kept, and each error under its field.
- **FR-007**: Cancel MUST write nothing, drop what was typed, restore the widget and return focus to
  Add.
- **FR-008**: Every field MUST be labelled, required fields MUST say so to assistive technology, and
  the whole form MUST work with the keyboard alone.
- **FR-009**: Every new wording MUST be translated in every configured locale.

## Success Criteria *(mandatory)*

- **SC-001**: A reminder about a contact is written from their page in one form, without opening
  another screen.
- **SC-002**: A reminder written from the widget and one written from the Reminders screen with the
  same values are identical.
- **SC-003**: Every control of the form works with the keyboard alone and is announced correctly.

## Assumptions

- The widget form's copy is the design's ("What should we remind you about?", "Add reminder"). The
  Reminders screen keeps its own form and wording.
- The time zones offered are those the Reminders screen's form offers.
- The success message after adding is the one the Reminders screen shows.
