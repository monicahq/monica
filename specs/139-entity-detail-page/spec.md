# Feature Specification: Record of an invented type, read and edited

**Feature Branch**: `2026-11-19-changelog-panda-redesign`

**Created**: 2026-11-19

**Status**: Draft

**Input**: User description: "Build the page that shows the detail of a record other than a contact (an animal, for instance). It must use every field, section and page the template defines, and follow layout 1A of `Entity Detail Page.dc.html`." Clarified: editing through a full-page form reusing the creation form, with the API, the MCP tools and the documentation for every entity capability.

## Design reference

Design 1a, "Fiche : pages en colonne, sections en tableaux label / valeur", of the Claude Design project `e80a62cb-60bb-4e94-a26f-ed7cac97c519`, file `Entity Detail Page.dc.html`:

- Header: a square mark with the type's icon on a tint of the type's colour, the singular type name as a small caption in that colour above the record's name, and an Edit button on the right. No round avatar, no timeline, no family tree.
- Below, two columns. Left: a "Pages" caption, one link per page of the template with the number of fields it holds, the selected page marked as current, and under a hairline the template name and its version. Right: one panel per section of the selected page, titled with the section name, holding a two-column list of label and value.
- An empty field stays on its line and reads "Not recorded". A contact chosen in a field is shown with its initials and links to that contact.
- The design's Edit link on each section is replaced by the single Edit button, which opens the full form (clarification).

## User Scenarios & Testing *(mandatory)*

### User Story 1: Read a record (Priority: P1)

Somebody opens Biscuit from the Animals list and sees everything written about Biscuit, organised by the pages and sections of the template Biscuit was written with.

**Why this priority**: Today a record can be created but never looked at again. Reading is the minimum.

**Independent Test**: Create Biscuit from a template with two pages and three sections, open it from the list. Every section of the first page shows each of its fields; choosing the second page shows that page's sections.

**Acceptance Scenarios**:

1. **Given** a list of Animals, **When** somebody activates Biscuit's row, **Then** Biscuit's page opens.
2. **Given** Biscuit's page, **When** it opens, **Then** the first page of the template is selected and every section on it is shown in the template's order, each field on its own line in the template's order.
3. **Given** a field with no value, **When** the page is shown, **Then** its line reads "Not recorded".
4. **Given** a template with several pages, **When** somebody chooses another page, **Then** that page's sections are shown and the page is marked as current, at an address of its own.
5. **Given** a page with no sections, **When** it is selected, **Then** the page says so and offers the way to the template.
6. **Given** an archived record, **When** its page opens, **Then** it says the record is archived.

### User Story 2: Correct a record (Priority: P1)

Somebody notices Biscuit's breed is wrong, presses Edit, changes it on a form laid out like the creation form, and lands back on Biscuit's page with the change.

**Why this priority**: A record nobody can correct is a record nobody trusts.

**Independent Test**: From Biscuit's page, press Edit, change the name and one field, save. The page shows both changes; nothing else changed.

**Acceptance Scenarios**:

1. **Given** Biscuit's page, **When** somebody presses Edit, **Then** a form opens with the name and every field of Biscuit's template version, filled with what is recorded.
2. **Given** that form, **When** somebody saves, **Then** the values are stored and Biscuit's page opens, confirming the save.
3. **Given** a required field left empty or an invalid value, **When** somebody saves, **Then** the form is shown again with what they typed and the reason.
4. **Given** the template of Biscuit has a newer version, **When** somebody edits Biscuit, **Then** the form asks what Biscuit's own version asks; the record is not moved to the newer version.
5. **Given** a turned off type, **When** somebody edits an existing record, **Then** it is allowed, as the list already promises.

### User Story 3: Reach a record from outside (Priority: P2)

A script or an assistant connected through MCP reads Biscuit with its values and corrects it, exactly as the screen does.

**Why this priority**: Principle X; and assistants today can do nothing with records of an invented type.

**Independent Test**: Through the API, read Biscuit and receive its name, type, template version and every value; update one value and read it back. The same through the MCP tools.

**Acceptance Scenarios**:

1. **Given** a token for the account, **When** it reads one record, **Then** it receives the record and every value, each named by its field.
2. **Given** that token, **When** it updates a record, **Then** the same rules as the form apply: fields left out are kept, fields sent as empty are cleared, the name cannot be cleared.
3. **Given** a record of another account or another vault, **When** it is read or updated, **Then** it does not exist.
4. **Given** an assistant connected through MCP, **When** it lists, creates, reads or updates records of a type, **Then** each answer is the API's answer.

### Edge Cases

- **A field removed in a later template version**: Biscuit's own version still has it, so the page still shows it.
- **A repeated field** (several vets): each repetition is shown under its label, in order.
- **A file or image field**: shown as on a contact, with the link to the file.
- **A record reached through the wrong vault**: not found.
- **A deleted record**: not found.
- **Many pages**: the column holds them all, scrolling with the page; below 1024px it sits above the sections.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Each row of a type's list MUST lead to that record's page.
- **FR-002**: The record's page MUST show every page, section and field of the template version the record points at, in the template's order.
- **FR-003**: The page MUST follow design 1a: type mark and caption, name, Edit button, pages column with field counts and the template name and version, one panel per section of label and value lines.
- **FR-004**: A field with no value MUST read "Not recorded" on its line.
- **FR-005**: Each page of the template MUST have its own address; the first page is shown by default.
- **FR-006**: Values MUST be shown the way the contact page shows the same field types.
- **FR-007**: The Edit button MUST open a full-page form showing the name and every fillable field of the record's version, prefilled.
- **FR-008**: Saving MUST apply the same validation as creating a record and keep the record on its version.
- **FR-009**: The API MUST offer reading one record with its values and updating one record, with the update semantics of the other endpoints.
- **FR-010**: Each entity endpoint (list, create, read, update) MUST have an MCP tool answering through it.
- **FR-011**: The API reference MUST document every entity endpoint, and the API changelog MUST record the new ones.
- **FR-012**: The product documentation MUST explain how to read and edit a record of an invented type.
- **FR-013**: Every string added MUST be translated into every shipped locale.
- **FR-014**: The page and the form MUST meet WCAG 2.2 AA: the pages column is a navigation with the current page announced, every value has its label.

### Key Entities

- **Type**: Animals; singular and plural names, icon, colour.
- **Record**: Biscuit; a name and the values of one template version, in one vault.
- **Template version**: frozen pages, sections and fields; the record points at one.

## Success Criteria *(mandatory)*

- **SC-001**: For 100% of records, every field of their template version appears on their page.
- **SC-002**: Somebody corrects one value of a record in at most three actions from the list: open, edit, save.
- **SC-003**: A record read through the API, the MCP tool and the screen shows the same values.
- **SC-004**: No horizontal scroll at 320px wide, on the page or the form.

## Assumptions

- Section-by-section editing as on the contact page is not built; the single form replaces it.
- No timeline, reminders, relationships or version history panel on a record: the design leaves them out.
- Archiving, deleting and changing a record's avatar are out of scope.
- The template caption names the template and its version number, linking to the template like the contact page does.
