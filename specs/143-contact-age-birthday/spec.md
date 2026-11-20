# Feature Specification: Contact age and upcoming birthday

**Feature Branch**: `2026-11-20-name-template-copy` | **Created**: 2026-11-20 | **Status**: Draft

**Input**: User description: "Affiche l’âge d’un contact sur sa fiche, quand on le connaît. Ajoute la
notion de fenêtre. Base toi sur le design 1b." Design: `Contact Age and Birthday.dc.html`, option 1b,
"Une phrase sous le nom".

## Clarifications

### Session 2026-11-20

- Q: How many days is the window, and does the dashboard share it? The design says 30 days "like the
  dashboard", but the dashboard announces 7. → A: 7 days everywhere. The contact page uses the window
  the dashboard and the vault reminder dates already use, and the three read it from one place.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The contact page says how old somebody is (Priority: P1)

Michael opens Jim’s page. Jim’s birthday is recorded as 26 November 1978. Directly under Jim’s name, a
line reads "47 years old". The exact date stays where it already is, in the row of facts under that
line.

**Why this priority**: it is the request itself. An age is what a reader looks for first when they
introduce somebody, and today they have to work it out from the date.

**Independent Test**: record a birthday with a year on a contact, open the contact page, read the line
under the name.

**Acceptance Scenarios**:

1. **Given** a contact whose birthday is 26 November 1978, **When** the page is opened on 20 November
   2026, **Then** the line under the name reads "47 years old".
2. **Given** the same contact, **When** the page is opened on 26 November 2026, **Then** the age reads
   48.
3. **Given** a contact whose birthday is recorded without a year, **Then** no age is shown.
4. **Given** a contact with no birthday, or whose template has no birthday field, **Then** the line
   under the name is absent and the row of facts sits directly under the name, as it does today.

---

### User Story 2 - The contact page warns that a birthday is coming (Priority: P2)

On 20 November 2026, Jim’s page reads "47 years old · Turns 48 in 6 days, on Thursday 26 November".
Michael learns in one sentence both that the birthday is near and how old Jim will be, without
counting.

**Why this priority**: the age alone is useful every day; the warning only matters a few days a year,
but it is the moment the page is most likely to prompt an action.

**Independent Test**: set a birthday inside the window, open the page, read the sentence; move the
birthday outside the window and check the sentence goes.

**Acceptance Scenarios**:

1. **Given** a birthday with a year falling inside the window, **Then** the line reads "[age] years
   old · Turns [age + 1] in [n] days, on [weekday] [day] [month]".
2. **Given** the birthday is tomorrow, **Then** the second part reads "Turns [age + 1] tomorrow".
3. **Given** the birthday is today, **Then** the line reads "Turns [age] today" alone, without the
   "years old" part, since the two would repeat the same number.
4. **Given** a birthday outside the window, **Then** the line holds the age alone.
5. **Given** a birthday recorded without a year falling inside the window, **Then** the line reads
   "Birthday in [n] days, on [weekday] [day] [month]" (and "Birthday tomorrow", "Birthday today"),
   with no age.
6. **Given** a birthday recorded without a year outside the window, **Then** the line is absent.

---

### User Story 3 - The window is a known quantity (Priority: P3)

"Coming" means one fixed number of days, the window, stated once in the product and applied the same
way wherever a birthday is announced as near.

**Why this priority**: without a named window, every screen chooses its own idea of "soon", and the
contact page and the dashboard disagree about the same birthday.

**Independent Test**: place a birthday exactly on the last day of the window and one day after it;
the first is announced, the second is not.

**Acceptance Scenarios**:

1. **Given** the window of 7 days, **When** the birthday falls 7 days after today, **Then** it is
   announced, on the contact page and on the dashboard alike.
2. **Given** the same window, **When** the birthday falls 8 days after today, **Then** the contact
   page shows the age alone.

---

### Edge Cases

- A birthday on 29 February is celebrated on 28 February in a year that has no 29 February, the rule
  the vault reminder dates already apply. The countdown and the age change on that day.
- "Today" is the reader’s day, in their own time zone, not the server’s.
- The birthday read is the contact’s main birthday value. A template that makes the birthday field
  repeatable has its first value read, the same one the contacts list reads for the age column.
- A birthday in the future (a date entered by mistake) shows no age.
- A birthday recorded with its year but not its month gives neither an age nor a countdown: the age
  is uncertain by one year and the day is unknown. The relationship and family blocks, which show
  ages today, stop showing one for such a birthday too.
- An age of zero (a baby under one year old) reads "0 years old"; the phrase still goes through the
  counted wording so each language can say it its own way.
- A contact set aside or archived shows the same line as any other contact; the page is unchanged
  otherwise.
- The weekday and the month follow the reader’s language and their date preferences.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The contact page MUST show, directly under the contact’s name, a line holding the
  contact’s age in whole years when their birthday is known with its year.
- **FR-002**: The age MUST be worked out on the reader’s current day in the reader’s time zone.
- **FR-003**: When the next birthday falls inside the window, the line MUST add a sentence giving the
  age the contact will reach, the number of days left and the weekday and date, with dedicated
  wordings for tomorrow and today, as set out in User Story 2.
- **FR-004**: When the birthday is known without its year, the line MUST show no age, and MUST show
  the countdown sentence without an age when the birthday is inside the window.
- **FR-005**: When neither an age nor a countdown applies, the line MUST be absent and take no space.
- **FR-006**: The exact birthday MUST stay in the row of facts where it is shown today; the new line
  adds to it and replaces nothing.
- **FR-007**: The window MUST be 7 days, defined once in the product and read from there by the
  contact page, the dashboard’s "Next seven days" group and the default period of the vault reminder
  dates. Today those last two each hold their own 7; they MUST read the shared one instead, with no
  change to what they show.
- **FR-008**: Every phrase holding a number MUST be written so each supported language can vary it by
  number, and every new phrase MUST be translated into every shipped language.
- **FR-009**: The countdown MUST NOT depend on a reminder existing for the birthday: a contact with no
  birthday reminder is announced the same way.
- **FR-010**: The API response describing a contact MUST give the same age and the date of the next
  birthday, so a client can show what the page shows.
- **FR-011**: The line MUST be readable by a screen reader as one sentence; the gift icon is
  decorative and MUST NOT be announced.

### Key Entities

- **Birthday**: the birthday value already recorded on a contact through their template, with a day, a
  month and possibly no year.
- **Age**: a whole number of years derived from the birthday and the reader’s current day. Never
  stored.
- **Window**: the 7 days ahead within which a birthday is announced as coming.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: For every contact whose birthday has a year, the age is visible on the contact page
  without opening any tab or panel.
- **SC-002**: For a birthday inside the window, a reader can tell the age the contact will reach and
  the date in one reading of one line, without arithmetic.
- **SC-003**: A contact with no birthday shows a contact page identical to today’s.
- **SC-004**: The page and the API give the same age and the same next birthday for the same contact
  on the same day, in 100% of cases.
- **SC-005**: Every new phrase exists in every shipped language at release.

## Assumptions

- Design 1b is the chosen design, with the window at 7 days instead of the 30 the design shows; the
  design’s "in 12 days" example falls outside it.
- Design 1b’s wording is kept, apart from the window. Designs 1a and 1c, including 1c’s banner with gift idea and reminder
  buttons, are out of scope.
- The age state "about 36" of design 1a does not apply: the product records no approximate age.
- The line appears on every page of the contact (each tab), since it sits in the header shared by all
  of them.
- Only the signed in owner of the account sees the contact page, who reaches sensitive fields, so a
  birthday stored in a sensitive field is shown there. A client reaching the API without access to
  sensitive fields gets no age and no next birthday when the birthday field is sensitive.
- No setting is added; the window is a product constant.
- The MCP server exposes the contact through the API and so gains the same two values with no rule of
  its own.
