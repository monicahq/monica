# Feature Specification: Details of a life event on the vault timeline

**Feature Branch**: `2026-11-24-life-event-details`

**Created**: 2026-11-24

**Status**: Draft

**Input**: User description: "sur la timeline d'un vault, on liste tous les life events, mais on affiche pas assez d'informations. à coté de chaque life event, on a le bouton Edit. on a besoin d'un bouton "View details" ou autre, qui affiche en ajax, une section avec toutes les infos du life event que l'on connait. pour des raisons de perf, uniquement charger ces informations quand on clique sur More details." Design: `Vault Timeline.dc.html`.

**Design**: `Vault Timeline.dc.html` (claude.ai design project `c1c29144`). Each entry of the timeline gains a **Details** button with a chevron, beside **Edit**. Pressed, it shows a short loading placeholder under the entry, then a panel. The panel's header holds the type as a badge and its category. Its body holds the description, a list of facts (one line each, with an icon, a label, a value and an optional second line), **Who was there** (each person with their initials, a link to them, how they relate to you and their role in the event) and **Files**. Its footer says who added the event and when it was last edited, and holds **Close**. The button then reads **Hide details** and its chevron turns. The design's sample facts (cost, reminder, distance, how it felt) are illustrative: a fact is shown only when Monica records it, as the requirements below say. The design's **Copy link** is not built (see Clarifications).

## Clarifications

### Session 2026-11-24

- Q: Where should **Copy link** lead, since no page shows one life event? → A: Nowhere: the button is dropped from this feature.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read everything known about one life event (Priority: P1)

Somebody scrolls the timeline of their Personal vault and sees "Moved into a new apartment, 22/12, with Fletcher Russell". They want to know what else they wrote about it without leaving the timeline. They press **Details** next to the entry. A placeholder appears for a moment, then a panel opens under the entry with the type and category, the full description, the date with its time and duration, the place, every value filled in for the type's own fields, who was there with their role, the files attached, and who added it and when. They read it and press **Close**, or **Hide details** on the entry, and the panel goes away.

**Why this priority**: This is the whole feature. The timeline today shows a title, a date and names, and nothing else a person wrote about the event.

**Independent Test**: Open the dashboard of a vault holding a life event with a description, a place, two participants and filled fields. Press **Details** on it and check every one of those appears; press **Hide details** and check the panel is gone.

**Acceptance Scenarios**:

1. **Given** a timeline entry, **When** the person presses **Details**, **Then** a loading placeholder appears under the entry, then the panel replaces it, and the button reads **Hide details**.
2. **Given** an open panel, **When** the person presses **Close** or **Hide details**, **Then** the panel closes and the button reads **Details** again.
3. **Given** a life event with a description, **When** its details open, **Then** the description is shown in full, with its paragraphs.
4. **Given** a life event whose type has fields, **When** its details open, **Then** every field holding a value is listed with its label and the value as it was written, and no line is shown for a field left empty.
5. **Given** a life event with participants, **When** its details open, **Then** **Who was there** lists each of them, contacts and entities alike, with their role and their note when one was written, and each person who has a page links to it.
6. **Given** a participant who is related to the vault's own person, **When** the details open, **Then** that relationship is shown under the participant's name (for example "Your partner").
7. **Given** a life event with files or images in its fields, **When** its details open, **Then** **Files** lists each with its name and size, and each opens the file.
8. **Given** any life event, **When** its details open, **Then** the footer says who added it, on which date, and when it was last changed.

---

### User Story 2 - The timeline stays fast (Priority: P1)

Somebody's vault holds hundreds of life events, and the timeline shows up to a hundred of them a page. The dashboard opens as fast as it does today, because nothing beyond what the entry already shows is read until somebody asks for it.

**Why this priority**: The user named it as a constraint, and the dashboard timeline was built around a query budget (`specs/149-dashboard-timeline`). Loading every detail with the list would break that budget.

**Independent Test**: Count the database queries the dashboard runs before and after the change, with a page of a hundred entries: the count is the same. Then open one entry's details and check the work done is for that event only.

**Acceptance Scenarios**:

1. **Given** a dashboard showing a page of entries, **When** it loads, **Then** it reads nothing more than it reads today, whatever the number of entries.
2. **Given** an entry, **When** its details are opened, **Then** only that event's details are fetched, without reloading the page or the timeline.
3. **Given** an entry whose details were opened and closed, **When** they are opened again in the same visit, **Then** they show again without a second wait.
4. **Given** two entries, **When** the person opens the details of both, **Then** both panels stay open, each under its own entry.

---

### Edge Cases

- **Nothing more to show.** A life event with only a type and a date still opens, and its panel shows the type, the date and the footer, without empty headings for description, people or files.
- **The event was deleted meanwhile.** Somebody opens the details of an event deleted in another tab. The panel says the life event no longer exists, and nothing else breaks.
- **The details fail to load.** The panel says they could not be loaded and offers to try again, instead of spinning forever.
- **A date known only to the month or the year.** The date line says so ("Sometime in 2019"), as the entry does, and carries no relative date or time.
- **A partial contact.** A participant who is a partial contact is named without a link, as on the entry.
- **No Edit button.** An entry with no editable participant has no **Edit** button today. It still has **Details**.
- **A long description or many participants.** The panel grows with its content; nothing is cut off.
- **Keyboard and screen readers.** The button says whether its panel is open and which panel it controls, the loading state is announced, focus stays on the button when the panel opens, and **Close** returns focus to the button.
- **Reduced motion.** The placeholder does not pulse and the chevron does not animate for somebody who asked for less motion.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every entry of the vault dashboard timeline MUST carry a **Details** button, whether or not it carries **Edit**.
- **FR-002**: Pressing **Details** MUST fetch and show that life event's details under its entry without reloading the page, show a loading placeholder until they arrive, and change the button to **Hide details**.
- **FR-003**: Pressing **Hide details** or the panel's **Close** MUST close the panel. Reopening it in the same visit MUST NOT fetch the details again.
- **FR-004**: The dashboard MUST NOT read any detail beyond what the entry shows today until somebody opens it. The number of queries the dashboard runs MUST NOT change.
- **FR-005**: The panel MUST show, when Monica records them: the type and its category; the title; the description in full; the date with its precision, the end date, the time, the time zone when it differs from the reader's, and the duration; the place; the value of every field of the event's type that holds one, in the type's order; the group it was recorded in; each participant (contact or entity) with their role, their note, and their relationship to the vault's own person; the files and images attached through its fields; who added it, from where (a contact page, a group page or the dashboard), when, and when it was last changed.
- **FR-006**: The panel MUST leave out every line, heading and block that has nothing to show.
- **FR-007**: A participant or a file that has a page MUST link to it. A partial contact MUST be named without a link.
- **FR-008**: Values MUST be written the way the rest of the product writes them for the reader: dates in their date format, numbers in their number format, a duration in words.
- **FR-009**: Only somebody of the vault's account MUST be able to load a life event's details. A life event of another account, or reached through another vault of the same account, MUST answer as not existing.
- **FR-010**: A life event deleted after the timeline loaded MUST answer as not existing, and the panel MUST say so in a sentence.
- **FR-011**: A failure to load MUST be told in the panel with a way to try again.
- **FR-012**: The button MUST announce whether its panel is open and which panel it controls; the loading state MUST be announced; the panel MUST be reachable and closable with the keyboard alone, with focus returned to the button on **Close**.
- **FR-013**: Every new piece of copy MUST be translatable and translated into every shipped language.
- **FR-014**: The API MUST already give, or MUST be extended to give, every detail the panel shows, so that nothing the panel shows exists only on the screen.

### Key Entities

- **Life event**: Something that happened, with a type, a date and its precision, optionally an end date, a time, a time zone, a duration, a title, a description, a place, a group, values for its type's fields, participants, who created it and from where.
- **Participant**: A contact or an entity who was there, with an optional role and note.
- **Life event type and category**: What kind of event it is, with its icon, colour and fields.
- **Field value**: What was written in one field of the type, including files and images.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: The dashboard timeline loads with exactly as many database queries as before the change, for a page of 1 and of 100 entries.
- **SC-002**: The details of one life event appear within one second of pressing **Details** on a local instance holding a vault of several hundred events.
- **SC-003**: Loading one life event's details runs a fixed number of queries, whatever the number of participants, fields or files it has.
- **SC-004**: Everything a person entered for a life event through its form can be read from the timeline without opening the edit form.
- **SC-005**: The panel can be opened, read and closed with the keyboard and a screen reader alone.

## Assumptions

- The feature covers the dashboard timeline of a vault. The contact page and group page timelines are unchanged; the contact timeline already shows the description.
- The button reads **Details** and **Hide details**, as the design draws it, rather than "View details" or "More details".
- Several panels may be open at once; opening one does not close another.
- The design's **Reminder** line is not built: no reminder is linked to a life event in Monica today. Cost, distance and how it felt are fields a type may hold, shown through FR-005 like any other field.
- The design's **Files** block comes from the event's file and image fields. Monica has no other way to attach a file to a life event.
- "How they relate to you" uses the relationship between the participant and the vault's own person (the contact marked as being the account holder in that vault). With no such contact, or no relationship, the line is left out.
- Sensitive field values are shown, as they are to the account on every other screen of its own vault.
- The details are read-only. Changing anything still goes through **Edit**.
- No flag: the change only adds a way to read what is already recorded.
