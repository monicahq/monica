# Feature Specification: Notes in a repeater

**Feature Branch**: `2026-09-25-contact-notes-design` | **Created**: 2026-09-25 | **Status**: Draft

**Input**: "In a template, a note field can be added in a repeater. A new design for this component.
Design: `Contact notes.dc.html` (claude.ai/design project `eaa519cf`)."

## Context

A repeater whose only field is a Long text (the default template's **Notes** repeater holds one
**Note**) reads today like every other repeater on the contact page: a label on the left, and each
row boxed in a small card on the right. The design gives it the whole width of its section, as
prose, with the date the note was added under it. Other field types keep their label and value row.
Editing is unchanged and stays in the section editor, opened from **Edit**.

Design frames: 1a desktop, 1b long note, 1c empty, 1d mixed fields, 1e two note repeaters, 1f
mobile at 390px.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read a note (Priority: P1)

Pam opens Michael’s contact page. His Notes section shows what she wrote, across the full width of
the panel, in paragraphs as she typed them, with "Added September 25, 2026" under it. There is no
label column and no card around the text.

**Independent Test**: a contact with one row in a Notes repeater; open the page, check the note text
and its added date render and the repeater label is not repeated as a row label.

**Acceptance Scenarios**:

1. **Given** a note repeater with one row, **Then** the note shows as prose with its line breaks, and
   "Added" followed by the date the row was created, in the reader’s date format.
2. **Given** a note repeater with several rows, **Then** each row is its own block with its own date,
   in the order the repeater already uses, separated by a line.
3. **Given** the repeater label equals the section name, **Then** no heading is shown above the note.
4. **Given** the repeater label differs from the section name (1d "Medical history", 1e "Gift
   ideas"), **Then** the label shows as a small heading above the note.
5. **Given** a section mixing a note repeater with other fields, **Then** the other fields keep their
   label and value rows, in template order around the note block.

### User Story 2 - Read a long note (Priority: P2)

Dwight kept a long note on Angela. The page shows its opening and a **Read more** control; pressing
it shows the rest, and the control becomes **Show less**.

**Independent Test**: a row holding a note past the preview length; check the control exists, is
announced as collapsed, and reveals the full text.

**Acceptance Scenarios**:

1. **Given** a note longer than the preview, **Then** only its opening shows, with **Read more**.
2. **When** Read more is pressed, **Then** the full note shows, the control reads **Show less**, and
   it reports its expanded state to assistive technology and names the text it controls.
3. **Given** a note within the preview length, **Then** no control shows.
4. **Given** the page without scripts, **Then** the full note is readable.

### User Story 3 - An empty note repeater (Priority: P3)

Jim has written nothing about Karen yet. The Notes section says **No note yet** and offers **Add a
note**, which opens the section editor, the same place **Edit** leads to.

**Independent Test**: a contact with no note row; check the empty wording and where Add a note leads.

**Acceptance Scenarios**:

1. **Given** a note repeater with no row, **Then** it shows "No note yet" and an **Add a note** control.
2. **When** Add a note is pressed, **Then** the section opens in its editor, as Edit does.
3. **Given** a section the reader cannot edit, **Then** "No note yet" shows without Add a note.

### Edge Cases

- A note of only whitespace or an empty value in a row reads as "No note yet" for that row, not as a
  blank block.
- A long word or pasted URL wraps inside the panel; the text is escaped, never read as markup.
- At 390px the controls are at least 44px tall and Read more spans the width (1f).
- A repeater holding a Long text beside other fields keeps today’s card per row rendering.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A repeater whose only value-bearing field is a Long text MUST render as a note block on
  the contact page, full width, without the label column.
- **FR-002**: Each row MUST show its text with line breaks preserved and "Added" with the row’s
  creation date, formatted with the reader’s date preference.
- **FR-003**: The repeater label MUST show as a heading above its notes only when it differs from the
  section name.
- **FR-004**: A note past the preview length MUST collapse behind a Read more / Show less control
  that carries its expanded state and the region it controls.
- **FR-005**: A note repeater with no row MUST show "No note yet", and, when the section is editable,
  an Add a note control opening the section editor.
- **FR-006**: Every other field type, and any repeater that does not meet FR-001, MUST render as today.
- **FR-007**: The section editor, the stored data and the API MUST NOT change.
- **FR-008**: Every new string MUST be translated in every configured locale, and the block MUST meet
  WCAG 2.2 AA: keyboard operable, visible focus, readable at 200%.

### Key Entities

- **Note repeater**: a repeater field whose single value-bearing child is a Long text.
- **Note**: one row of that repeater: its text and its creation date.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: On a contact with a note, the note text starts at the panel’s left edge on desktop and
  mobile, with no label column beside it.
- **SC-002**: A reader reaches and toggles Read more with the keyboard alone, and a screen reader
  announces its state.
- **SC-003**: No other field type on the contact page changes appearance (checked against 1a and 1d).

## Assumptions

- Scope is a Long text inside a repeater, as the user chose. A standalone Long text keeps its label
  and value row, although the design draws 1d’s "Medical history" as a note; here that block stands
  for a note repeater named "Medical history".
- Add a note opens the existing section editor; the prototype’s inline editor in 1c is not built, so
  there stays one way to edit a section.
- The preview length is set in planning (the design collapses after the second paragraph).
