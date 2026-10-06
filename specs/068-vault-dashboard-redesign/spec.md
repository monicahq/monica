# Feature Specification: Vault dashboard redesign

**Feature Branch**: `2026-10-02-dashboard` · **Created**: 2026-10-02 · **Status**: Draft

**Input**: Redesign the vault dashboard (`vaults/{vault}`) after the design `Vault Dashboard.dc.html` in
claude.ai/design project `4bc65e33-5e86-4d20-893e-40a25867499f` (Monica design system). The shell,
the breadcrumb and the vault sidebar are unchanged. The five newest contacts, the three placeholder
boxes and the vault settings box give way to what needs doing and the people who matter.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - See and answer the dates that need attention (Priority: P1)

The main column, "Needs your attention", lists the reminder dates that are overdue, due today, or due in
the next seven days, in three bands (Overdue, Today, Next seven days). Each row shows the person's name
linked to their page, what the reminder says, when it is due, and the answers the date may take. A link
under the list leads to the vault's reminders.

**Why this priority**: it is the one block on the screen somebody acts on, and it replaces a placeholder.

**Independent Test**: give a vault one overdue date, one today and one in five days, open the dashboard,
see the three bands, answer the overdue one, and see it leave the list.

**Acceptance Scenarios**:

1. **Given** dates overdue, today and in five days, **When** the dashboard opens, **Then** each is in
   its band, bands are in that order, and a band with nothing in it is not shown.
2. **Given** a date due in eight days, **When** the dashboard opens, **Then** it is not listed.
3. **Given** a listed date, **When** the reader answers it, **Then** the answer is recorded exactly as
   on the reminders screen, and the row leaves the list without the reader losing their place.
4. **Given** nothing due within seven days, **When** the dashboard opens, **Then** the block says so in
   one sentence and still links to the vault's reminders.

### User Story 2 - Reach favourite and recently viewed people in one step (Priority: P2)

The side column shows "Favourite people" (contacts marked as favourite) and "Recently viewed" (the
contacts the reader last opened, newest first, each with when). Every row opens the contact.

**Why this priority**: it replaces "the five newest contacts", which is rarely who somebody wants.

**Independent Test**: mark two contacts favourite, open three contacts, return to the dashboard, and
find the two favourites and the three opened contacts in the order they were opened.

**Acceptance Scenarios**:

1. **Given** favourites in the vault, **When** the dashboard opens, **Then** they are listed in the
   account's name order, up to ten, with a link to the full list when there are more.
2. **Given** the reader opened Nora's page and then Helena's, **When** they return, **Then** Helena is
   first, with "Today", and Nora second.
3. **Given** no favourite, or no contact ever opened, **When** the dashboard opens, **Then** that block
   says in one sentence how it fills.

### User Story 3 - See what happened recently and how big the vault is (Priority: P3)

Under the vault's name, one line of figures: people, reminders in the attention list, favourites, and
life events in the last three months. The side column ends with "Recent life events": the latest five,
each with its type's icon and colour, its title, its date and the people in it, opening the life event.

**Independent Test**: record five life events, open the dashboard, and check figures and list.

**Acceptance Scenarios**:

1. **Given** 25 people and 4 dates in the attention list, **When** the dashboard opens, **Then** the line
   reads "25 people", "4 reminders this week", and so on, each phrase correct at zero and at one.
2. **Given** a life event with three people, **When** it is listed, **Then** all three names show.

### Edge Cases

- A vault has no empty state: every vault holds its Me contact from the start (spec 069), so the
  dashboard always shows its blocks, each with its own one sentence empty state.
- An account at its contact ceiling keeps the refused "Add somebody" control and its reason on screen.
- An archived or deleted contact appears in no block.
- A life event dated approximately shows the precision it was recorded with, never an invented day.
- A date answered in another tab, then answered here, is refused with the reminders screen's message.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The dashboard MUST show the vault's name, colour tile and description, one line of figures,
  and the "Add somebody" control.
- **FR-002**: The attention list MUST hold every date awaiting an answer that is overdue, due today, or due
  within the next seven days of the reader's own day, grouped and ordered as in Story 1.
- **FR-003**: Each row MUST offer exactly the answers the reminders screen offers for that date, with the
  same wording, and a refused answer stays on screen marked as unavailable with its reason.
- **FR-004**: The favourites block MUST list the vault's favourite contacts, up to ten.
- **FR-005**: The product MUST record when each contact was last consulted (see Assumptions), and the recently
  viewed block MUST list the five most recent, with a relative day.
- **FR-006**: The life events block MUST list the vault's five most recent life events.
- **FR-007**: The Tasks, Reminders and Files placeholders and the vault settings box MUST be removed;
  vault settings stay reachable from the sidebar.
- **FR-008**: Each figure and each date MUST be translated, use counted phrases, and follow the reader's
  date format and time zone.
- **FR-009**: Every block MUST be a landmark section with a heading, operable by keyboard, and MUST NOT
  carry meaning through colour alone: each band says its name in text.
- **FR-010**: The time a contact was last opened MUST be readable through the API on the contact.
- **FR-011**: Block headings, band labels and row tiles MUST use the product's icons, never emoji.

### Key Entities

- **Contact**: gains when the reader last opened it; already carries whether it is a favourite.
- **Reminder date** and **Life event**: unchanged; a life event's type gives its icon and colour.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A date due within seven days can be answered from the dashboard in one press.
- **SC-002**: A favourite or recently opened contact is reached from the dashboard in one press.
- **SC-003**: The screen passes WCAG 2.2 AA (keyboard, screen reader, 200% zoom).

## Assumptions

- The answer wording stays "I remembered" and "I forgot", as on the reminders screen; the design's
  "Remembered" and "Forgot" are not a second wording for one action.
- Answers are final but not destructive, and take no confirmation, as on the reminders screen.
- "This quarter" in the design means the last three months: the sample lists August events in October.
- Consulting one contact counts as viewing it, whether its page is opened or it is read alone through
  the API or MCP; a listing does not.
- The reminder row's tile uses the reminder's icon where one exists, else the reminder icon.
