# Feature Specification: Bulk actions on the contact list

**Feature Branch**: `2026-11-25-contact-bulk-actions`

**Created**: 2026-11-25

**Status**: Draft

**Input**: Issue #8007, "Bulk actions on the contact list: selecting several contacts to delete them, or to change a category or a field", drawn in the Claude Design file `People.dc.html`.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Select several people on the list (Priority: P1)

Somebody tidying a vault ticks a box on each row, or the box in the heading to take every row of the page. As soon as one row is ticked, the filters and the search give way to a bar saying how many are selected and what can be done with them.

**Why this priority**: Neither action exists without a selection.

**Independent Test**: Tick three rows, read "3 selected", clear the selection, and see the filters come back.

**Acceptance Scenarios**:

1. **Given** a page of 12 people, **When** two rows are ticked, **Then** the bar reads "2 selected", the ticked rows are marked, and the heading box shows a mixed state.
2. **Given** nothing is ticked, **When** the heading box is ticked, **Then** every row of the page is selected and the box shows checked.
3. **Given** a selection, **When** the clear control is used or Escape is pressed, **Then** nothing is selected and the filters and the search return.
4. **Given** a selection, **When** the page, a filter, the search or the sort changes, **Then** the selection is emptied.

---

### User Story 2 - Delete the selected people (Priority: P1)

Somebody selects several people they no longer want and deletes them in one go, after a confirmation naming who goes and what goes with them.

**Why this priority**: The first half of the request, and the costliest to do one by one today.

**Independent Test**: Select three people, delete, confirm, and find the three gone from the list.

**Acceptance Scenarios**:

1. **Given** one person selected, **When** Delete is chosen, **Then** the confirmation reads "Delete Aude?" and its button "Delete the contact", as the single delete does.
2. **Given** five people selected, **When** Delete is chosen, **Then** the confirmation reads "Delete 5 people?", names the first two and "3 others", and says everything written on their records is deleted with them, as the single delete does.
3. **Given** the confirmation is open, **When** Cancel is chosen, **Then** nothing is deleted and the selection stays.
4. **Given** the confirmation is accepted, **Then** each person is deleted exactly as the single delete does it, and a notice says how many were deleted, with no Undo.

---

### User Story 3 - Change the category of the selected people (Priority: P2)

Somebody files several people at once under one of the account's contact categories (Family, Friends, Work), or takes the category off them.

**Why this priority**: The second half of the request; useful, but less frequent than clearing out.

**Independent Test**: Select four people, choose "Change category", pick Work, and see the Work badge on the four rows.

**Acceptance Scenarios**:

1. **Given** a selection, **When** "Change category" is opened, **Then** a menu lists the active categories of the account's list under "Move to", then "Remove category".
2. **Given** a category is picked, **Then** each selected person holds that category and no other, and a notice reads "4 people moved to Work".
3. **Given** "Remove category" is picked, **Then** each selected person holds no category, and the notice reads "Category removed from 4 people".
4. **Given** the notice is showing, **When** Undo is chosen, **Then** every person gets back exactly the categories held before.

---

### Edge Cases

- The selection holds the contact that represents you: the confirmation says it stays, and only the others are deleted.
- A selected person's template has no field drawing on the contact categories list: they are left unchanged, and the notice says how many were not changed and why.
- The category field is sensitive and the caller does not reach sensitive fields: those people are treated as having no such field.
- A selected person was deleted or moved elsewhere in the meantime: they are skipped, and the others still change.
- "Remove category" for a person whose category field is required: they are left unchanged, and counted as such.
- More than 500 people selected (possible only with "All" rows per page): both actions are refused, saying to select 500 or fewer.
- The account's category list is empty: "Change category" stays on the bar, refused, saying the list has no category yet.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every row of the contact list MUST carry a selection checkbox named after the person, and the heading MUST carry one for the whole page, checked, unchecked or mixed.
- **FR-002**: A selection MUST be limited to the page shown, and MUST be emptied when the page, filter, search, sort or page size changes.
- **FR-003**: While anything is selected, the filters and the search MUST be replaced by a toolbar announcing the count, offering "Change category", "Delete" and a way to clear the selection.
- **FR-004**: Clicking a row while a selection exists MUST toggle that row rather than open the person.
- **FR-005**: Deleting MUST ask for confirmation first, naming the people (one name, up to three names, or two names and a count), with the consequence the single delete already states.
- **FR-006**: Each deletion MUST behave exactly as deleting that one person does, including the account log entry, the revoked share links and the deactivated reminders.
- **FR-007**: A person who cannot be deleted MUST be kept and named in the confirmation, and MUST NOT stop the others being deleted.
- **FR-008**: Changing the category MUST replace whatever the person's field drawing on the account's contact categories list holds with the chosen category, as editing the person would.
- **FR-009**: "Remove category" MUST empty that field for each selected person.
- **FR-010**: After either action, a notice MUST say what was done and to how many people, including how many were left unchanged.
- **FR-011**: The category change MUST be undoable from that notice, restoring the exact previous categories.
- **FR-012**: Every capability here MUST be available through the API and the MCP server, with the same rules and the same refusals.
- **FR-013**: The selection, the toolbar, the menu and the confirmation MUST be operable with the keyboard alone, announced by a screen reader, and free of animation under reduced motion.
- **FR-014**: Every new wording MUST be translated, and every wording carrying a count MUST vary with it.

### Key Entities

- **Selection**: the people ticked on the current page. Never stored.
- **Contact category**: a choice of the account's "Contact categories" value list, held in a multiple choice field of the person's template.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Deleting 20 people takes one selection, one confirmation and under 15 seconds, against 20 separate deletions today.
- **SC-002**: Filing 20 people under one category takes under 10 seconds from the list, without opening any of them.
- **SC-003**: A bulk deletion leaves the vault exactly as 20 single deletions would: same log entries, same revoked links, same deactivated reminders.
- **SC-004**: Every step of both actions completes with the keyboard alone and is announced by a screen reader.

## Assumptions

- Only category and deletion are in scope. The issue's "or a field" is left for later: the design draws neither, and which fields would qualify is a question of its own.
- A bulk deletion offers no Undo, departing from the design: no deleted contact can be brought back today, and restoring one is a feature of its own. The confirmation is the safeguard, as it is for a single deletion.
- Archiving, favouriting and moving to another vault are not offered on the bar.
- Rows per page stays as it is today, so a selection holds at most one page, and never more than 500 people.
- The notice after a category change is the one the groups list uses for its Undo, rather than the design's floating toast.
- The Email and Phone columns of the design stand for the configurable columns the list already has.
