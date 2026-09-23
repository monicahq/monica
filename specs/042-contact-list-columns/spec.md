# Feature Specification: Contact list columns

**Feature Branch**: `2026-09-22-contact-lists`

**Created**: 2026-09-22

**Status**: Draft

**Input**: User description: "implement a new design for the contact list [Contact list.dc.html]. We have also a new button to customize the contact list headers [Customize contact list.dc.html]. It raises many questions especially about all the fields available in the vault: a contact follows one template, and a vault can have many templates." Follow-up: "don't have a preview panel on the customize screen".

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The People list is a table (Priority: P1)

Somebody opens People and sees a table: one row per person, with Name, Email, Phone number and Last spoke columns. They can sort by a heading. In Everybody, favourites are grouped at the top, above everybody else.

**Why this priority**: it is the redesign itself, and it works with the default columns before any customisation exists.

**Independent Test**: open People in a vault holding contacts on two different templates and read each person's name, first email, first phone and last spoke in the row.

**Acceptance Scenarios**:

1. **Given** Rachel has two emails, **When** the list is shown, **Then** her row shows the first email and a "+1" control, and activating it reveals the second in place.
2. **Given** Joey has no email, **When** the list is shown, **Then** his email cell shows a dash, not an empty cell.
3. **Given** the list is on Everybody with two favourites, **When** it is shown, **Then** a Favourites group of two comes first, followed by an Everybody else group.
4. **Given** somebody activates the Last spoke heading twice, **When** the list reloads, **Then** it is sorted by last spoke in the opposite direction, and the heading announces that direction.

### User Story 2 - Choose the columns (Priority: P2)

From a button next to the search box, somebody opens Customize contact list. They tick columns to show or hide them, reorder them by dragging or with the keyboard, rename a heading, remove a column and add one from a menu of every field the vault's templates carry. Save applies the result to the list. Cancel discards it.

**Why this priority**: it is what makes the table fit a vault whose templates hold different fields.

**Independent Test**: add Company, rename it Employer, move it before Email, save, and see an Employer column in second place on People.

**Acceptance Scenarios**:

1. **Given** the add menu is open, **When** it lists fields, **Then** it separates fields every template carries from fields only some templates carry, and it names the templates that carry each of the latter.
2. **Given** a Company column, **When** Joey's template does not carry Company, **Then** his cell shows a dash.
3. **Given** the grip of a column is focused, **When** somebody presses the down arrow, **Then** the column moves one place down and its new position is announced.
4. **Given** unsaved changes, **When** somebody chooses Reset to defaults and then Save, **Then** the list shows the default columns again.
5. **Given** the Name column, **When** the screen is shown, **Then** Name cannot be hidden, moved or removed.

### Edge Cases

- **A column's field disappears from every template.** The column is kept and marked on the customise screen as no longer supplied, with the reason. The list leaves it out. Removing it is the person's choice.
- **A contact moves template.** Their row shows the values their new template carries. No column changes.
- **A heading is cleared.** The column falls back to the field's own name, translated when the product supplied it.
- **A sensitive field.** It is never offered as a column, since the list cannot mask a value.
- **A long list.** Loading more keeps the columns, the grouping, the sort and the search.
- **A narrow screen.** The table scrolls sideways inside its panel, and the page does not.

## Requirements *(mandatory)*

### Functional Requirements

**The list**

- **FR-001**: People MUST show contacts as a table whose columns are the vault's saved column configuration, with Name always first.
- **FR-002**: A cell whose field holds several values MUST show the first and a control revealing the rest in place.
- **FR-003**: A cell with no value MUST show a dash. A Last spoke cell with no value MUST show "Not yet".
- **FR-004**: Everybody MUST group favourites first, then everybody else. The other groupings MUST NOT be divided.
- **FR-005**: The grouping chips MUST each show how many people they hold.
- **FR-006**: Sorting MUST be offered on the headings of Name, Nickname, Last spoke and Date added, and MUST keep the recording of 041. Other headings MUST NOT sort.
- **FR-007**: The search placeholder MUST describe what the search actually matches.

**Customising**

- **FR-008**: The customise screen MUST let somebody show, hide, reorder, rename, remove and add columns, and MUST apply nothing until Save.
- **FR-009**: Reordering MUST be possible with the keyboard alone, and each move MUST be announced.
- **FR-010**: The add menu MUST offer the shared columns (Nickname, Email, Phone number, Birthday, Template, Last spoke, Date added) and every field carried by the published version of any active contact template in the account.
- **FR-011**: A template field MUST be identified by its section and its field key, so that one column covers every template placing that section, in every version.
- **FR-012**: Email and Phone number MUST collect every email field and every phone field of the contact, whatever its template calls them.
- **FR-013**: The add menu MUST NOT offer sensitive fields, or fields that hold no value (headings, dividers, help text).
- **FR-014**: A heading left as the default MUST follow the reader's language. A renamed heading MUST be shown as written.
- **FR-015**: A column whose field no longer exists in any template MUST be kept, marked with the reason, and left out of the list.
- **FR-016**: The column configuration MUST be readable and writable through the API, and the API contact list MUST return the values of the configured columns.

### Key Entities

- **Contact list column**: one column of a vault's People table. It holds the source (a shared column, or a section and field key), a heading when renamed, a position and whether it is shown. The defaults apply until somebody saves.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: In a vault whose contacts use three templates, every row shows the right value or a dash in every column, with no empty cell.
- **SC-002**: Adding a column, renaming it and moving it takes one visit to the customise screen and one Save.
- **SC-003**: Every control on both screens can be reached and operated with the keyboard alone, and a screen reader announces each column's heading, state and position.
- **SC-004**: Everything the customise screen does can also be done through the API.

## Assumptions

- **The configuration belongs to the vault.** A family vault and a work vault want different columns. The grouping and sort of 041 stay per user.
- **Fields are offered from the published version of each template.** A field only an older version carries is still shown for contacts on that version once its column exists, since the identity is the section and key.
- **Birthday is matched by its key**, the way the address book export does. Email and phone are matched by field type.
- **Letter headings go.** Grouping favourites in Everybody replaces them, as the design does.
- **Names only stays** as a fourth chip, since the relationship panel links to it.
- **Reset to defaults and Remove need no confirmation.** Nothing is applied until Save, and Cancel undoes both.
- **Last spoke is read, not computed.** It stays a default column. Nothing records it today, so every cell reads "Not yet" until a later feature does.

## Out of Scope

- A preview on the customise screen.
- A density setting, and sorting by any column other than those in FR-006.
- Extending search to nicknames or emails.
- Recording last spoke.
