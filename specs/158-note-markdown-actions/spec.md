# Feature Specification: Markdown actions on a note

**Feature Branch**: `158-note-markdown-actions`

**Created**: 2026-11-25

**Status**: Draft

**Input**: User description: "Three features on the page of a Knowledge note, following the design `Note Markdown Actions.dc.html`, placement 1a. Add the Markdown mark to the icon set, since other screens will need it. Add Markdown access to the API and MCP, unless it already exists or changes nothing."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Copy a note as Markdown (Priority: P1)

Somebody reading a note opens the Markdown menu beside Edit and chooses Copy. The note, title included,
is on their clipboard as Markdown, ready to paste into an assistant, a chat or another editor.

**Why this priority**: it is the most frequent need (notes in the design are prompts kept for reuse),
and it is one click from the reading view instead of opening the editor and selecting everything.

**Independent Test**: open a note with a list and inline code, choose Copy, paste into a plain text
field, and compare with the note's source.

**Acceptance Scenarios**:

1. **Given** a note with text, **When** the reader chooses Copy, **Then** the clipboard holds the title as a level one heading, a blank line, and the body exactly as written, and the menu closes.
2. **Given** the copy succeeded, **When** the reader looks at the menu button, **Then** it reads "Copied" for about two seconds, then "Markdown" again, and a screen reader announces the change.
3. **Given** the browser refuses the clipboard, **When** the reader chooses Copy, **Then** they are told the copy failed and nothing claims success.

---

### User Story 2 - View the note's Markdown source (Priority: P2)

From the same menu, View as MD replaces the drawn note with its Markdown source, in place, without
opening the editor. The same item, now labelled View formatted, brings the drawn note back.

**Why this priority**: it shows what Copy and Download will produce and lets somebody check the
syntax of a note without the risk of changing it.

**Independent Test**: choose View as MD on a note with headings and a task list, check the source
is shown verbatim, choose View formatted, check the drawn note is back.

**Acceptance Scenarios**:

1. **Given** the drawn note, **When** the reader chooses View as MD, **Then** the same Markdown Copy produces is shown in a fixed width face, wrapping long lines, and the menu closes.
2. **Given** the source is shown, **When** the reader opens the menu, **Then** the item reads View formatted, and choosing it restores the drawn note.
3. **Given** the source is shown, **When** the reader reloads the page, **Then** the drawn note is shown (the choice is not remembered).

---

### User Story 3 - Download the note as a Markdown file (Priority: P3)

Download as MD saves the same Markdown as a `.md` file named after the note.

**Why this priority**: useful for archiving or moving a note to another tool, but rarer than copying.

**Independent Test**: choose Download as MD on a note titled "Diagnose Wi-Fi and home network" and
open the file `diagnose-wi-fi-and-home-network.md`.

**Acceptance Scenarios**:

1. **Given** a note, **When** the reader chooses Download as MD, **Then** a file named after the title in lower case words joined by hyphens, ending in `.md`, is saved, holding exactly what Copy produces, encoded in UTF-8.
2. **Given** a title that yields no usable file name (only punctuation or a script that cannot be transliterated), **When** the reader downloads, **Then** the file is named `note.md`.

---

### User Story 4 - The Markdown mark in the icon set (Priority: P3)

The Markdown mark (the outlined rectangle holding an M and a down arrow) is available to every screen
the way the other glyphs are, at any size, in the current text colour.

**Independent Test**: render the mark on its own and inside the menu button, in light and dark themes.

**Acceptance Scenarios**:

1. **Given** the mark beside the visible word "Markdown", **When** a screen reader reaches the button, **Then** only "Markdown" is read; the mark is decorative there.
2. **Given** the mark used alone elsewhere, **When** it is given a label, **Then** it is exposed as an image with that label.

### Edge Cases

- An empty note: the Markdown menu is absent, as the screen already says the note is empty and points at Edit.
- The note open in its editor: the menu is absent; it belongs to the reading view.
- The note changes in another tab: the actions use what the page loaded; reloading picks up the change.
- Source view and task checkboxes: the source is plain text, so ticking a task is only possible in the drawn view.
- A title holding Markdown characters (`#`, `*`): copied as written, after the leading `# `.
- Keyboard use: the menu opens with Enter or Space, its items are reachable with the arrow keys, Escape closes it and returns focus to the button.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The reading view of a Knowledge note MUST show a secondary button made of the Markdown mark, the word "Markdown" and a chevron, placed before Edit and Delete.
- **FR-002**: The button MUST unfold a menu of three items, in this order: Copy, View as MD (or View formatted), Download as MD, each with its glyph from the design.
- **FR-003**: The Markdown a note produces MUST be its title as a level one heading, one blank line, then its body byte for byte as stored.
- **FR-004**: Copy MUST place that Markdown on the clipboard and report success or failure in words, announced to assistive technology.
- **FR-005**: View as MD MUST swap the drawn note for its source on the page, and View formatted MUST swap it back, with no request to the server and nothing stored.
- **FR-006**: Download as MD MUST save that Markdown as a UTF-8 `.md` file named from the title, falling back to `note.md`.
- **FR-007**: The menu MUST be absent when the note is empty and when the note is being edited.
- **FR-008**: The menu MUST follow the existing actions menu contract: announced as a menu, keyboard reachable, closed by Escape and by a click outside, focus returned to its button.
- **FR-009**: The Markdown mark MUST join the shared icon set, drawn in the current text colour, decorative by default and labelled when a label is given.
- **FR-010**: Every new wording MUST be translatable and translated into every shipped locale.
- **FR-011**: The API and the MCP server MUST NOT change. Both already return a note's body as the Markdown written, with its title alongside, so a client assembles the same document from what it already receives.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A reader gets a note's Markdown onto the clipboard in two clicks from the reading view, against opening the editor, selecting and copying before.
- **SC-002**: Pasting a copied note, or opening a downloaded one, gives a text identical to the stored body under the title heading, for every note in a test set including lists, tables, code and tasks.
- **SC-003**: All three actions can be completed with the keyboard alone and are announced correctly by a screen reader.

## Assumptions

- "La page d'une note" is the Knowledge note screen; notes on contacts and groups are out of scope.
- The Markdown document includes the title as a heading, as the design's sample does.
- The file name is derived the way the application already derives readable identifiers from a name.
- The source view is not remembered between visits, since the design treats it as a passing look.
- No journal entry is written: reading, copying and downloading change nothing.
