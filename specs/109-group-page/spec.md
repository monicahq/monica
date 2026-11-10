# Feature Specification: A group page that holds the group's own history

**Feature Branch**: `2026-11-09-group-page`

**Created**: 2026-11-09

**Status**: Draft

**Input**: User description: "Implement this design to change how groups are displayed. It would let you record things about the group itself, beyond who belongs to it: notes, activities with the members who attended, reminders, files and custom fields. Notes and activities are the strongest additions: they give the group a shared history. Reminders and files would be useful depending on how people use groups. Design: `Group Page.dc.html` (claude.ai/design project `b78dc791`)."

## Context

Today a group page ([085 Groups](../085-groups/spec.md)) shows a name, a period and a list of members. Nothing can be recorded about the group itself, so "Had dinner with the Martin family" has to be written on each member's page, or on one of them chosen at random.

The design turns the page into three tabs:

- **Overview.** A writing box with two kinds, *Activity* and *Note*. An activity asks who was there, with every current member ticked by default. Below it, a **Shared history**: activities and notes about the group, newest first. Beside it, three panels: **About the group** (structured details such as the usual place or a website, each showing "Not recorded" when empty), **Reminders** (with a tick to complete one) and **Files**. This specification builds the last two; the first is out of scope (see Clarifications).
- **Members.** Every member with their role and how many of the group's activities they attended ("At 3 activities", "No activities yet").
- **Files.** Every file kept with the group, with a way to download it.

The header gains the members' avatars, "7 people belong to it" and "Since 22 September 1994" from the group's start date. "Edit the group" and "Add somebody" stay.

The design is a direction, not a pixel contract. It is rebuilt from the building blocks the application already has, and anything it shows that the product cannot yet do is a requirement below, not a decoration.

### What this collides with

Three earlier decisions stood in the way. The clarifications below settle each one.

1. **A group has no field of its own.** 085 FR-011 and the model file 032 say a group carries "a name, a description and its own dates, and no kind, template or field of its own". The **About the group** panel is custom fields, and constitution VII allows exactly one way to attach user defined data (Template, Page, Section, Field). The panel is left out; 085 FR-011 stands.
2. **A note is not a record of its own anywhere.** On a contact, notes are a repeater of Long text fields in the template ([049](../049-repeater-notes/spec.md)). A group note becomes a dated record of its own. This does not break constitution VII: the rule forbids a second way to attach *user defined* data, meaning fields somebody invents at runtime. A group note has a fixed shape (a text, a date, an author) decided by the product, like a reminder's name or a life event's narrative, and a group has no template to hold it otherwise.
3. **A file is attached through fields and to life events, "and to nothing else"** (File model). Groups become the third thing a file attaches to, and the File model's description changes with it.

## Clarifications

### Session 2026-11-09

- Q: Does this specification deliver all five capabilities, or notes and activities first? → A: All of them in this specification, except custom fields (next answer). Activities, notes, reminders and files ship together.
- Q: Do groups get a template so the About the group panel can exist? → A: No. The panel is left out, 085 FR-011 stands, and custom fields on a group are a later specification.
- Q: Is a group note a dated record of its own, or a repeater field in a group template? → A: A dated record of its own, shown in the shared history beside activities, as the design draws it.
- Q: Does this specification add the API and MCP coverage constitution X asks for? → A: First answered no (web only). Reversed in session 2026-11-10: every capability of the page is also offered through the API and the MCP server, and documented in the API reference.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Record an activity on the group (Priority: P1)

Somebody opens the "Sunday football" group, picks *Activity*, writes "Match against the Riverside team, we lost 3 to 2", unticks the two members who did not come, and saves. The activity appears at the top of the group's shared history with the five who were there, and on the page of each of those five people. Nobody copies it anywhere.

**Why this priority**: This is the distinction the request is about: information that belongs to the group, linked to the people it concerns, without writing it five times. It reuses life events, which already put something on the timeline of everybody who took part.

**Independent Test**: A group with seven current members. Record an activity with five of them ticked. Check it is listed in the group's history with five attendees, appears on the timeline of each of the five, and does not appear on the timeline of the two others.

**Acceptance Scenarios**:

1. **Given** a group with current members, **When** somebody picks *Activity*, **Then** every current member is listed as a choice of who was there, all ticked, with a count such as "7 of 7".
2. **Given** the description is empty or nobody is ticked, **When** somebody tries to save, **Then** the save is refused and the box says what is missing.
3. **Given** an activity saved with five of seven members, **Then** the history shows it with its title, its text, the five avatars and "5 of 7 were there: Alex, Sam, Jo, Kim and Lee", and says it is also on the history of each person who was there.
4. **Given** that activity, **When** somebody opens the page of one of the five, **Then** the activity is on their timeline and names the group it was recorded on.
5. **Given** that activity, **When** somebody opens the page of one of the two absent members, **Then** it is not on their timeline.
6. **Given** a member who left the group before the activity, **Then** they are not offered as an attendee by default, and can still be added the way any participant is added to a life event.
7. **Given** an activity recorded on a group, **When** somebody edits or deletes it from the group page or from a participant's page, **Then** it is the same activity in both places and the change shows in both.

---

### User Story 2 - Keep a note about the group (Priority: P1)

Somebody picks *Note* and writes "Ross has the spare key to Monica’s flat. The one under the mat is gone." The note is kept on the group only, dated, and shown in the shared history. It does not appear on anybody’s own page, and the box says so before it is saved.

**Why this priority**: The request names notes and activities together as the strongest additions. A note is the simplest thing to record about a group and the most common.

**Independent Test**: Write a note on a group, check it is listed in the history with its date, and check no member's timeline shows it.

**Acceptance Scenarios**:

1. **Given** *Note* is picked, **Then** there is no choice of attendees and the help line says the note is saved on the group only.
2. **Given** a saved note, **Then** it shows in the history as a note with its date, interleaved with activities by date, newest first.
3. **Given** a note, **When** somebody edits or deletes it, **Then** the history changes and nothing else does.

---

### User Story 3 - Read the group at a glance (Priority: P1)

Somebody opens a group and sees, before scrolling, who belongs to it, since when, and what the group last did together.

**Why this priority**: It replaces the current page. Without it the other stories have nowhere to live.

**Independent Test**: Open a group with members, a start date, and some history. Check the header, the tabs and the history. Open a group with none of it and check every block says what would go there.

**Acceptance Scenarios**:

1. **Given** a group with seven current members and a start date, **Then** the header shows its name, its description, the members' avatars, "7 people belong to it" and "Since" followed by the start date at its recorded precision.
2. **Given** a group with no start date, **Then** the "Since" line is absent rather than empty.
3. **Given** the page, **Then** it offers three tabs: Overview, Members with the count of members, and Files with the count of files.
4. **Given** a group with no history, **Then** the history says nothing is written down yet, and that an activity also appears on the page of each person who was there.
5. **Given** the Members tab, **Then** every member is listed with their role or their membership period, how many of the group's activities they attended, and the edit control that exists today. Members who left keep the "Left" mark they have today.
6. **Given** a group whose end date is past, **Then** the page still offers everything above; an ended group keeps its history.

---

### User Story 4 - Set a reminder about the group (Priority: P2)

Somebody adds "Organize the next game" on the group, or "Send the holiday card" every year on 1 December for a household. It is listed in the Reminders panel, reaches them like any other reminder, and can be ticked as done for this occurrence.

**Why this priority**: Useful depending on how a group is used. It reuses the reminder rules that already exist for contacts.

**Independent Test**: Add a one-off and a yearly reminder on a group; check both are listed on the group, in the vault's reminders list naming the group, and that the notification names the group.

**Acceptance Scenarios**:

1. **Given** a group, **When** somebody adds a reminder with a name, a date and a cadence, **Then** it is listed in the Reminders panel with its next date, and marked as repeating when it repeats.
2. **Given** a reminder occurrence that is due, **When** somebody ticks it, **Then** that occurrence is answered as done, the way it is answered from the vault's reminders list, and the panel shows the next one for a repeating rule.
3. **Given** a group reminder, **Then** it appears in the vault's reminders list and in the notification sent for it, naming the group.
4. **Given** a group with no reminder, **Then** the panel says what a reminder could be for and offers to add one.
5. **Given** a group that is deleted, **Then** its future occurrences are cancelled, and restoring the group brings them back (constitution VIII).

---

### User Story 5 - Keep files with the group (Priority: P3)

Somebody adds a team photo and a tournament registration form to the group. Both are listed in the Files panel and the Files tab, can be downloaded, and count against the account's storage like any other file.

**Why this priority**: Useful for some groups only.

**Independent Test**: Upload two files to a group; check they are listed with their type, size and the date added, download one, and check the account's used storage grew by their size.

**Acceptance Scenarios**:

1. **Given** a group, **When** somebody adds a file, **Then** it is listed with its name, its kind, its size and when it was added, in the panel and in the Files tab.
2. **Given** a file that would take the account over its storage limit, **Then** it is refused with the message every other upload gives.
3. **Given** a file, **When** somebody removes it from the group, **Then** it disappears from the group and is kept for the grace period every detached file gets.
4. **Given** the file upload capability is switched off for the account, **Then** the Files panel and tab are absent, and a group that already holds files says once that they are kept.

---

### Edge Cases

- A group with no current member: *Activity* offers nobody to tick. It explains that an activity needs at least one person who was there and links to adding somebody. *Note* still works.
- A member is deleted after attending an activity: the activity stays on the group with the remaining attendees; the deleted contact is hidden the way it is hidden everywhere else, and comes back if restored.
- A contact belongs to two groups and an activity is recorded on one: it shows on their timeline once, naming the group it was recorded on.
- An activity is recorded from a contact's page with people who all belong to a group: it is not moved onto the group. Only an activity recorded on the group belongs to it.
- The group is deleted: its notes, activities, reminders and files are hidden with it and come back when it is restored. Activities stay on the timelines of the people who were there, since participation is what puts them there.
- The vault is deleted: everything the group holds goes with it, as the group does today.
- A device syncing the vault over CardDAV: nothing about the group card changes; notes, activities, reminders and files are not part of it.
- Long text and long names: the history, the attendee line and file names wrap or truncate without pushing the panels off the screen at 390px.
- Two people write on the same group at once: both entries are kept; nothing overwrites anything.

## Requirements *(mandatory)*

### Functional Requirements

**Page**

- **FR-001**: The group page MUST show the group's name, description, the avatars and count of its current members, and its start date at its recorded precision when one exists.
- **FR-002**: The group page MUST offer three tabs, Overview, Members and Files, with the count of members and of files on their tab. The Files tab follows the capability rules of FR-022.
- **FR-003**: The Members tab MUST keep everything the current member list offers (role, period, "Left", "Outside the dates of the group", edit, pagination) and add how many of the group's activities each member attended.
- **FR-004**: The page MUST stay behind the groups flag and answer not found when the flag is off, as it does today.
- **FR-005**: Every block of the page MUST have an empty state saying what would go there.

**Activities**

- **FR-006**: Somebody MUST be able to record an activity on a group, with a description and at least one person who was there.
- **FR-007**: When recording an activity on a group, every current member MUST be offered and ticked by default; past members MUST NOT be ticked by default.
- **FR-008**: An activity recorded on a group MUST be a life event like any other, belonging to the group, with every ticked member as a participant, so it appears on each participant's timeline and nowhere else.
- **FR-009**: The activity MUST record a type and a date. The group form MUST offer both with defaults (today, and a default type) so somebody who only writes a sentence can save.
- **FR-010**: A participant's timeline MUST name the group an activity was recorded on and link to it.
- **FR-011**: Editing or deleting an activity MUST be possible from the group page and from any participant's page, acting on the same record.

**Notes**

- **FR-012**: Somebody MUST be able to write, edit and delete a note on a group.
- **FR-013**: A group note MUST appear on the group only, never on a member's page.
- **FR-014**: The shared history MUST list the group's activities and notes together, newest first, each marked with its kind and its date.

**Reminders**

- **FR-015**: Somebody MUST be able to add, edit and delete a reminder about a group, with the same name, date and cadence choices a reminder about a contact offers.
- **FR-016**: A group reminder MUST be delivered, listed in the vault's reminders list and answered exactly as a contact reminder is, naming the group where a contact reminder names the contact.
- **FR-017**: The Reminders panel MUST let somebody mark the due occurrence as done, and MUST show a repeating rule as repeating.
- **FR-018**: Deleting a group MUST cancel its future reminder occurrences, and restoring it MUST bring them back.

**Files**

- **FR-019**: Somebody MUST be able to add a file to a group, download it, and remove it.
- **FR-020**: A group file MUST count against the account's storage and follow the same size, type and quota rules as every other upload.
- **FR-021**: Removing a file from a group MUST detach it, and the file MUST survive the grace period every detached file gets.

**Everywhere**

- **FR-022**: Files on a group MUST be absent when the file upload capability is off for the account, following the flag rules (absent, said once to an account that holds data).
- **FR-023**: Every new string MUST be translated in every configured locale, and every phrase showing a number MUST take the wording each language uses for that number ("7 people belong to it", "5 of 7 were there", "At 3 activities").
- **FR-024**: The page MUST meet WCAG 2.2 AA: the Activity and Note choice is a labelled radio group, the attendee choices are a labelled fieldset of checkboxes, the reminder tick names the reminder it completes, and the tabs are keyboard reachable with their counts announced.
- **FR-025**: Every write MUST be recorded in the account's journal, as the corresponding contact write is.
- **FR-026**: Everything the page lets somebody do MUST be doable through the API and, except uploading and downloading a file, through the MCP server, and every endpoint MUST be documented in the API reference (constitution X).

### Key Entities *(include if feature involves data)*

- **Group**: unchanged in nature, and still without a template or a field of its own. Gains a history (its activities and notes), reminders and files.
- **Life event (activity)**: gains an optional link to the group it was recorded on. Its participants remain the people who were there.
- **Group note**: a record of its own. Free text about one group, its date, and who wrote it. Shown on the group only, hidden and restored with the group.
- **Reminder**: may be about a group, in addition to a contact or an entity.
- **File**: may be attached to a group, in addition to a field value or a life event.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Recording an activity for a group of seven takes one save, against seven separate entries today.
- **SC-002**: Somebody can record an activity or a note on a group in under 30 seconds from opening the page.
- **SC-003**: 100% of activities recorded on a group appear on the timeline of every person ticked, and on no other contact's timeline.
- **SC-004**: The page passes the accessibility review with no WCAG 2.2 AA finding, at desktop width and at 390px.
- **SC-005**: Every new string is translated in every configured locale, and the translation check reports nothing missing.
- **SC-006**: Every new API endpoint has a test and a documented section whose returned fields match what it answers.

## Assumptions

- Activities are life events. No second "activity" concept is introduced; the design's word *Activity* is the glossary's "life event" as it already reaches the reader.
- The design's writing box has no type or date. The specification adds both with defaults (FR-009), since a life event cannot exist without them. Which type is the default is a planning decision.
- Attendees are contacts who are or were members. Entities (the household dog) are not offered, because groups hold contacts only (032, open question).
- An activity recorded on a group belongs to that group alone; it cannot be moved onto another group.
- The design's "Since" line uses the group's start date, not the earliest membership.
- The design's dark theme is the application's existing dark theme; no new tokens.
- The design's tab for files and the panel for files show the same list; the panel shows the most recent few and links to the tab.
- The vCard export and the CardDAV card are unchanged.
- MCP gets no upload or download of a file, as it has none for a contact's photo; the API does both.
- Structured details about a group (the About the group panel, User Story 6 in the first draft) are out of scope and need their own specification, which would have to reverse 085 FR-011 by giving groups a template.
- The `groups` flag still ships off, so the page can change without a transition for any account that has not been given it.
