# Feature Specification: Reminders widget on the contact page

**Feature Branch**: `2026-09-26-reminder-answers` | **Created**: 2026-09-27 | **Status**: Draft

**Input**: Add the reminders widget to the contact page from `Reminders Widget.dc.html` in the
Claude Design project, with its blank state, without the Add button.

## Context

[063](../063-contact-reminders-widget/spec.md) gave each contact a Reminders screen and two answers
per deadline. Its words (reminder, deadline, answer, today) and its rules (categories, which answer a
day allows, finality) apply here unchanged. This feature puts the deadlines that matter now on the
contact's overview, so one can be answered without leaving the page.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - See and answer what is due (Priority: P1)

On Chandler Bing's page, Monica Geller sees "Call about the flat on Bedford Street" marked Overdue,
"Ask how the new job is going" due today and "Chandler’s birthday" next April. She presses
**I remembered** on the first and stays on his page, the row gone.

**Independent Test**: seed a deadline in each category, open the overview, check each row, answer
the overdue one, and check the reader is back on the overview without it.

**Acceptance Scenarios**:

1. **Given** a contact with active reminders, **Then** the overview shows a Reminders panel listing
   what the Reminders screen lists under All, earliest first.
2. **Given** a row, **Then** it shows the wording, the date with its relative day
   ("27 September 2026 · today") and the cadence ("Every 3 months").
3. **Given** an overdue row under All, **Then** it carries an Overdue badge; under Overdue it does not.
4. **Given** a Today row, **Then** it offers I remembered; an Overdue row adds I forgot; an Upcoming
   row offers nothing.
5. **Given** an answer pressed, **Then** it is recorded with no confirmation and the reader is back
   on the overview; a refusal is shown there in the Reminders screen's wording.
6. **Given** the panel, **Then** its foot links to the Reminders screen: "All of {name}’s reminders".

---

### User Story 2 - A contact with nothing to remember (Priority: P1)

Monica opens the page of somebody she met last week. The panel reads "Nothing to remember about
{name} yet" and says how a date on the page can be followed.

**Acceptance Scenarios**:

1. **Given** nothing under All, **Then** the panel shows its title, that sentence and one line, and no
   filter, no row and no foot link.

---

### User Story 3 - Narrow to one category (Priority: P2)

Chandler has eight deadlines. Monica selects **Overdue** to see only the three she missed.

**Acceptance Scenarios**:

1. **Given** the panel, **Then** it offers All, Overdue, Today and Upcoming with their counts, All
   selected on arrival, and no History.
2. **Given** a filter selected, **Then** only its rows show, without leaving the overview.
3. **Given** an empty filter, **Then** it says so in the Reminders screen's wording for that filter.

---

### User Story 4 - Edit or delete from the row (Priority: P2)

The flat is sorted. Monica opens the row's menu, chooses Delete, is warned, confirms, and is back on
Chandler's page without the reminder.

**Acceptance Scenarios**:

1. **Given** a row, **Then** its menu offers Edit, opening the reminder's form, and Delete, which warns
   that it is final before deleting.
2. **Given** a deletion confirmed from the panel, **Then** the reader is back on the overview.

---

### Edge Cases

- A reminder read from a date on the page shows no "Follows {label}"; the Reminders screen does.
- An archived contact has no unanswered deadline under 063, so the panel is blank.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The overview MUST show a Reminders panel in its side column, under Immediate family, and
  no other page of the contact MUST show it.
- **FR-002**: The panel MUST list every deadline the Reminders screen lists under All, earliest first,
  with no cap; the counts and the foot link cover a long list.
- **FR-003**: A row MUST show the wording, the date with its relative day, the cadence, and under All
  an Overdue badge when it is overdue.
- **FR-004**: A row MUST offer exactly the answers its day allows under 063, recorded in one action,
  returning the reader to the overview.
- **FR-005**: The panel MUST offer All, Overdue, Today and Upcoming with their counts, and switching
  MUST NOT leave the overview.
- **FR-006**: Each empty filter, and the blank panel, MUST say so in its own words.
- **FR-007**: A row's menu MUST offer Edit and Delete, Delete warning first, and a deletion from the
  panel MUST return the reader to the overview.
- **FR-008**: The panel MUST end with a link to the Reminders screen, except when blank.
- **FR-009**: The panel MUST NOT show an Add button in this feature.
- **FR-010**: The copy MUST NOT assume the contact's gender ("their page", "a birthday").
- **FR-011**: Every filter, answer, menu and link MUST work with the keyboard; the filters MUST report
  which is selected, the menu whether it is open.
- **FR-012**: Every new wording MUST be translated in every configured locale.

## Success Criteria *(mandatory)*

- **SC-001**: A deadline due today or overdue is answered from the overview in one action.
- **SC-002**: For any contact, the panel under All and the screen under All list the same deadlines
  with the same counts.
- **SC-003**: Every control in the panel works with the keyboard alone and is announced correctly.

## Assumptions

- Wordings the panel shares with the Reminders screen are the screen's; the design's variants are not
  added. The blank title follows the design, and its line drops "Add a reminder here".
- The "Reminders" entry in the contact's facts stays.
- The row cap was asked on 2026-09-27 and left unanswered; FR-002 takes the design's reading.
