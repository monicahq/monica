# Feature Specification: Reminder detail page redesign

**Feature Branch**: `2026-09-27-reminder-detail-page`

**Created**: 2026-09-27

**Status**: Draft

**Input**: Rebuild the reminder detail page from the design `Reminder Detail.dc.html` (claude.ai/design project 9dbb31f7). Two cases: a reminder written by hand, and a reminder built from a date on the record (a birthday).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read a reminder and answer its dates (Priority: P1)

Somebody opens a reminder about a contact. They see its name, who it is about, when it fires, the dates that wait for an answer, and what they answered before. They answer an overdue or current date in one press.

**Why this priority**: this is the whole purpose of the page. It replaces the current page, which spreads the same facts over six panels.

**Independent Test**: open a manual monthly reminder with one overdue, one current and one upcoming date and four past answers; answer the overdue date.

**Acceptance Scenarios**:

1. **Given** a reminder about Dwight Schrute, **When** the page opens, **Then** the heading is the reminder name, under it the contact's avatar and name linked to the contact, then one schedule line such as "Every month on the 28th at 09:00 · America/Toronto."
2. **Given** dates waiting, **When** the page opens, **Then** "Dates awaiting your response" lists them oldest first, each with its date, a relative wording ("3 weeks ago", "today", "in 4 weeks") and a badge Overdue, Today or Upcoming.
3. **Given** an overdue date, **Then** it offers "I remembered" and "I forgot"; a date of today offers "I remembered" only; an upcoming date offers nothing.
4. **Given** the reader presses an answer, **Then** the date leaves the waiting list and appears in History with that answer.
5. **Given** answered dates, **Then** History lists the most recent first as "date · You remembered" or "date · You forgot", with "Answered 28 July at 10:14" on the right, four at a time, and "Load more" shows the next four.
6. **Given** no answered date, **Then** History reads "Each date you answer is kept here, with what you said."

---

### User Story 2 - A reminder built from a date (Priority: P1)

A reminder derived from a field such as a birthday says where its date comes from and links to the place where that date is changed.

**Independent Test**: open the reminder built from Michael Scott's birthday.

**Acceptance Scenarios**:

1. **Given** a derived reminder, **Then** a third line reads "Based on Michael Scott’s birthday · Edit the date", the field label being the one the record uses, and "Edit the date" opens the contact page on the page holding that field.
2. **Given** a manual reminder, **Then** that line is absent.
3. **Given** a derived reminder, **Then** no Edit button is shown; "Edit the date" is the only way to change it. (Decided 2026-09-27, against the design, which draws Edit on both kinds.)

---

### User Story 3 - A reminder that cannot fire (Priority: P2)

When a reminder is stopped, the page says why and how to undo it, instead of a status badge.

**Acceptance Scenarios**:

1. **Given** a reminder stopped because its date was cleared, **Then** a warning notice titled "This reminder has no date to follow" explains that adding the date again brings the next one back.
2. **Given** a reminder stopped because the contact is archived, **Then** the notice is titled "Michael Scott is archived" and says restoring the contact starts notifications again.
3. **Given** any stopped reminder, **Then** no date waits for an answer, since stopping cancels every unanswered date (spec 063 FR-025, research R6), and the list reads "No upcoming date until this is resolved."

---

### User Story 4 - Delete, and the delivery record (Priority: P3)

Delete moves into a menu beside Edit. The delivery record is folded away by default.

**Acceptance Scenarios**:

1. **Given** the reader opens the "More actions" menu and picks "Delete reminder", **Then** a dialog "Delete this reminder?" says it removes the reminder and its upcoming dates, and that the answers given and the record stay.
2. **Given** the reader confirms, **Then** the reminder is deleted and they land on the contact's reminders page with a confirmation message.
3. **Given** the page opens, **Then** "Delivery details" is collapsed; expanding it lists each delivery ("Email sent to … · delivered") and the creation entry, newest first, with a timestamp in the reminder's time zone.

### Edge Cases

- A reminder about something other than a contact: no contact line, and the breadcrumb falls back to the vault's reminders.
- A reminder stopped because its field left the structure or the record was deleted gets a notice of its own, with the existing explanations.
- A one-off reminder whose date has passed: the waiting list is empty and says nothing is left to answer.
- A past date closed without an answer (cancelled) appears in History with its existing outcome wording.
- More waiting dates than fit a screen are all listed; only History is paged.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The page MUST sit under the contact: the breadcrumb reads People › contact › Reminders › reminder name, and the sidebar marks People as current.
- **FR-002**: The header MUST show the name, the contact line, the schedule line, and for a derived reminder the source line of FR-006.
- **FR-003**: The schedule line MUST state cadence, day, time and time zone in one sentence, in the reader's locale.
- **FR-004**: The waiting list MUST show every open date with its category badge and only the answers FR-005 allows.
- **FR-005**: Overdue dates MUST offer both answers, today's date only "I remembered", upcoming dates none, as the answer rules already decide.
- **FR-006**: A derived reminder MUST name the field and the contact it reads and link to where that date is edited.
- **FR-007**: A stopped reminder MUST show one warning notice per inactive reason with its title, its explanation and the way back.
- **FR-008**: History MUST list closed dates newest first, four at a time, each with its outcome and when it was answered.
- **FR-009**: Delete MUST live in a "More actions" menu and ask for confirmation, saying how many answers are kept (deletion keeps them, unlike the design's copy).
- **FR-010**: Delivery details MUST be collapsed by default and operable with the keyboard, announcing expanded or collapsed.
- **FR-011**: The Schedule panel, the Next card, the status badge, the contact card, the Activity panel and the danger panel MUST be removed from this page.
- **FR-012**: Every new wording MUST be translated in every configured locale, counted phrases through the counted-phrase rules.

### Key Entities

- **Reminder**: name, cadence, time, time zone, source (manual or a field), active flag and inactive reason.
- **Reminder date**: one occurrence; open (overdue, today, upcoming) or closed (answered, cancelled).
- **Delivery entry**: a notification sent for a date, or a journal entry about the reminder.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A reader answers an overdue date within one press of opening the page, without scrolling at 1280×800.
- **SC-002**: The page shows the same facts as today in four blocks instead of seven.
- **SC-003**: Every control on the page is reachable and operable with the keyboard alone, and the menu and the disclosure report their state to a screen reader.
- **SC-004**: A stopped reminder states its reason and its remedy on the first screen in every case.

## Assumptions

- Only the web screen changes. The API already exposes reminders, dates and answers; no capability is added.
- The design's in-page "Reminder deleted" state is replaced by the existing redirect with a flash, because the page no longer exists after deletion.
- "Load more" pages History on the server; four per page follows the design.
- The Edit button of a manual reminder keeps opening the existing edit form.
- The pronoun "his" in the design's copy is replaced by wording that names the contact.
