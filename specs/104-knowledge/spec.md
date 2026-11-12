# Feature Specification: Knowledge

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-10-27 | **Status**: Draft

**Input**: "create the notion of knowledge, a way to record any kind of information, as folders, notes
or lists. For now a note can only be text. Contacts cannot be linked in a note yet. Add API, MCP and
docs support." Design: `Knowledge.dc.html` and `Knowledge - empty state.dc.html` (project 85e4275a).

## Clarifications

### Session 2026-10-27
- Q: Links between notes and lists in double brackets? → A: Deferred with contact links (FR-010).
- Q: User invented list columns against principle VII? → A: Amend it; columns are a second named exception.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Start Knowledge and write a first note (Priority: P1)
Pam Beesly opens Knowledge in her Family vault for the first time and notes where the water shutoff is.

1. **Given** nothing in Knowledge, **When** opened from the vault's sidebar under "Your knowledge",
   **Then** the screen says what Knowledge is for, names folders, notes and lists, and offers "Write a
   first note", "Start a list" and "Create a folder"; the folder column reads "No folders yet".
2. **Given** that screen, **When** a note or a list is started, **Then** a first folder "General" holds it.
3. **Given** a note, **When** edited, **Then** a form asks for a title, a folder and Markdown text;
   "Add a link" (also Ctrl K) inserts a web link from a text and an address.
4. **Given** a saved note, **Then** it reads headings, paragraphs, bullets, tasks, quotes, bold text and
   web links, a bare web address becoming a link opening in a new tab.
5. **Given** a note being read, **When** a task's box is ticked, **Then** it saves; a clicked title
   becomes a field, Enter saving and Escape cancelling.

### User Story 2 - Organise in folders (Priority: P1)
Dwight Schrute files everything under Home, Travel and Entertainment.

1. **Given** the plus menu, **Then** it offers "New note", "New list" and "New folder", and names the
   folder a new note or list goes to: the one being read, else the first.
2. **Given** the folder column, **Then** each folder reads its name and count, folds and unfolds, and
   lists its notes and lists by title, an icon telling one from the other.
3. **Given** a folder's menu, **When** "Rename" is chosen or the name double clicked, **Then** the name
   becomes a field saved on Enter or on leaving it.
4. **Given** a folder holding files, **When** "Delete folder" is chosen, **Then** a warning asks whether
   they move to another folder or are deleted too, and says which; "Keep it" changes nothing.
5. **Given** a single folder left, **Then** its menu offers no deletion and says Knowledge needs one;
   a note or list whose folder is changed moves there.

### User Story 3 - Keep a list (Priority: P1)
Kevin Malone keeps his favourite restaurants with a city, a cuisine and a note for each.

1. **Given** a new list, **Then** it opens as "Untitled list", columns "Name" and "Note", a row ready.
2. **Given** a list, **Then** it reads an optional description, a table of its columns and items in
   order, and its item count; an empty cell reads a dash.
3. **Given** "Add an item", **When** used, **Then** one field per column appears; Enter adds the item,
   Escape cancels, a row with every cell blank is not added.
4. **Given** a list's columns, **When** edited, **Then** a column can be added, renamed, reordered or
   removed, removal warning that its cells go with it.
5. **Given** an item, a note or a list, **When** edited or deleted, **Then** the screen follows;
   deletion warns that it is final, and a deleted note or list lands on Contents.
6. **Given** a cell or the description, **Then** it reads bold text and web links like a note.

### User Story 4 - Find and browse (Priority: P2)
Angela Martin looks for the paint reference of the study.

1. **Given** the search field, **When** typed in, **Then** only notes and lists whose title, text,
   description or cells hold the words remain, their folders unfolded; else "Nothing matches" and the words.
2. **Given** "Contents", **When** opened, **Then** it lists every folder, its notes and lists with kind,
   item count and last edit, and each note's headings, each opening the note there; it sums them all.
3. **Given** the vault's sidebar, **Then** Knowledge reads how many notes and lists the vault holds.

### User Story 5 - Reach Knowledge from outside (Priority: P2)
Oscar Martinez's assistant updates his travel notes; his script adds books to a list.

1. **Given** an API key, **Then** every action of this feature works through the documented API.
2. **Given** a connected assistant, **Then** it reads, searches, creates, changes, moves and deletes
   folders, notes, lists, columns and items under the same rules.
3. **Given** the product documentation, **Then** a page explains Knowledge, folders, notes and lists.

### Edge Cases

- An empty note reads "This note is empty. Use Edit to write in it."; an empty folder says how to fill it.
- A blank title or folder name keeps the previous one; a new note is "Untitled note".
- Deleting the vault deletes its Knowledge. A list keeps at least one column.
- Markdown is displayed, never executed: raw markup shows as text, and double brackets are ordinary text.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A vault MUST hold any number of Knowledge folders, flat, each with a name and a position.
- **FR-002**: Every note and list MUST sit in exactly one folder of its vault.
- **FR-003**: A note MUST have a title and MAY have Markdown text; nothing else attaches to it.
- **FR-004**: A list MUST have a title and at least one ordered named column, and MAY have a
  description and any number of ordered items holding one text per column.
- **FR-005**: The last folder of a vault MUST NOT be deletable; another MUST move or delete its files as chosen.
- **FR-006**: Every deletion MUST warn first and say what goes with it.
- **FR-007**: Notes and lists MUST record when they were created and last edited, shown in words.
- **FR-008**: Rendering MUST support headings, paragraphs, bullets, tasks, quotes, bold and web links, nothing executable.
- **FR-009**: Search MUST cover titles, note text, list descriptions and every cell.
- **FR-010**: Knowledge MUST NOT link notes, lists, items, contacts or possessions in this release,
  and its copy MUST NOT promise it.
- **FR-011**: Each change MUST be journalled, doable through the API and the assistant, documented and translated.
- **FR-012**: Knowledge MUST stay outside shares and exports until decided otherwise.

### Key Entities

Knowledge folder (vault; name, position). Note (folder; title, Markdown text). List (folder; title,
description). List column (list; name, position). List item (list; position, one text per column).

## Success Criteria *(mandatory)*

- **SC-001**: From an empty vault, a first note is saved in under a minute.
- **SC-002**: A list of 500 items and a note of 20,000 words open without a visible wait.
- **SC-003**: Search finds a word in any title, note, description or cell of 1,000 files at once.
- **SC-004**: Another account's or vault's Knowledge is never shown or changed, by any surface.

## Assumptions

- Principle VII is amended to name Knowledge folder, note, list, list column and list item as fixed
  concepts, and list columns as a second exception: text only, stored as data, never as schema.
- Folders are not nested; the design's "Sommaire" is "Contents" in English, translated like the rest.
- Every cell is Markdown text, typed columns being a later change. No note version is kept beyond the journal.
- Shipped without a flag, as possessions were. Vault wide search beyond Knowledge is out of scope.
