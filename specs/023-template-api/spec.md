# Feature Specification: Template endpoints

**Feature Branch**: `2026-09-05-api-templates` | **Created**: 2026-09-05 | **Status**: Draft

**Input**: "i want to manage templates through the api + docs", with the whole builder planned and shipped one phase at a time. Import, export and sharing are out of scope. The five stories below are the five phases, in order: reading before writing, the template before its versions, and the structure inside a version last, because nothing inside a version can be addressed before a draft exists to hold it.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read a template and the structure it imposes (Priority: P1)

A template is what decides what a record shows, so a caller holding contact data has no way to know what any of it means until they can read the structure behind it: which templates the account has, which version is published, and what that version is made of, page by page, section by section, field by field.

**Why this priority**: it is the only phase that stands entirely alone, it hands over every identifier the four that follow need, and a caller who has only it can already render a record correctly. **Independent test**: read an account holding a published template and a draft beside it, and confirm the list, the version history and one version's full structure come back with nothing of another account in them.

**Acceptance Scenarios**:

1. **Given** a caller holding a valid credential, **When** they list the templates of their account, **Then** each carries its identifier, its slug, its name, its description, what it applies to and which type when it applies to one, whether it is a system template, whether it is offered, its place in the order, its published version, when it was created and last changed, and a link to itself, with unoffered templates among them rather than left out.
2. **Given** a template with a history, **When** the caller lists its versions, **Then** each carries its number, its status, its change note, when it was published and retired, how many records point at it and the version it came from, newest first.
3. **Given** one version, **When** the caller reads it, **Then** they get its pages in order, the sections placed on each page in order with the title shown and whether it starts collapsed, and the fields of each section in order with the full configuration each field carries.

---

### User Story 2 - Declare a template, correct it, retire it (Priority: P2)

Creating the container is the gesture that opens a structure, and today it can only be made in a browser. A caller setting an account up from a script has to be able to make one, reword it, duplicate one that is close enough, decide whether it is still offered and where it sits, and delete one that was a mistake.

**Why this priority**: it turns the API into something that can build an account, and it touches only the container, so nothing about versions or structure has to exist yet. **Independent test**: declare a template, correct its wording, duplicate it, stop offering it, move it, then delete it, and confirm each answers as the same gesture in the browser does.

**Acceptance Scenarios**:

1. **Given** a caller supplying a name and what the template applies to, **When** they declare it, **Then** it exists with a settled slug, is placed last, is offered, holds an empty draft version and no published one, and the answer describes it.
2. **Given** a caller correcting a template, duplicating it, saying whether it is offered or moving it, **When** the change is made, **Then** only that changes: the slug, the versions and every record are untouched, and a duplicate is a separate template with its own identifier carrying a copy of the structure as a draft.
3. **Given** a caller who names anything of another account in any of these requests, **When** the answer comes back, **Then** it is not found and nothing is changed. A template the product supplied is changed and deleted like any other, because nothing in the application treats it as locked.

---

### User Story 3 - Draft a version and publish it (Priority: P3)

A published version never changes again, so changing a structure means opening a draft beside it, working in the draft, and publishing when it is right. That gesture is the whole reason the version exists, and it has to be reachable from a script.

**Why this priority**: it is what makes phases four and five addressable at all, and it is worth shipping alone, because opening and publishing a draft that was built in the browser is already useful. **Independent test**: open a draft from a published version, confirm the published one refuses every write, confirm a second open answers with the same draft, publish it, and confirm the records on the old version stayed there.

**Acceptance Scenarios**:

1. **Given** a template whose published version exists, **When** the caller opens a draft, **Then** it carries the next number, copies the structure of the version it came from, names that version as its origin, and a second request answers with the draft that is already open rather than a third.
2. **Given** a draft, **When** the caller publishes it with a change note, **Then** it becomes the version the template offers, every section it carries that was still a draft is published with it, and the version it replaces is left exactly as it was, so the records on it go on working until somebody moves them deliberately.
3. **Given** a published or retired version, **When** any request tries to change or delete it, or anything inside it, **Then** it is refused and the version is untouched, while a draft that no record points at deletes.

---

### User Story 4 - Lay out the pages and sections of a draft (Priority: P4)

Inside a draft, a page is a tab and a section is a block placed on it. A caller has to be able to add a page, rename it, reorder pages, place a section, move it to another page, reorder it, override the title it shows and take it off.

**Why this priority**: it is the layout, and it is worth shipping before fields because a caller can already assemble sections that exist. **Independent test**: build a draft of two pages, place a section on each, move one between pages, reorder both, and confirm every position is renumbered from one and the published version beside it never moved.

**Acceptance Scenarios**:

1. **Given** a draft, **When** the caller adds a page, **Then** it is placed last with a settled key that never changes again, and renaming it later leaves that key alone.
2. **Given** a caller who places a section on a page of a draft, **When** the answer comes back, **Then** the placement carries its position, the title it shows and whether it starts collapsed, and the section itself is unchanged, because where a section sits belongs to the assembly rather than to the section.
3. **Given** a caller who moves or reorders a page or a section, **When** the answer comes back, **Then** everything at that level is numbered from one again and a place past the end lands last. **Given** a caller who deletes a page that still holds sections, or the only page a version has, **Then** it is refused and the page stays.

---

### User Story 5 - Define the fields a section holds (Priority: P5)

A field is where the structure meets the data. A caller has to be able to add one to a section of a draft, configure it fully, reorder it and remove it, with the same choices the builder screen offers and no fewer.

**Why this priority**: it is the deepest layer and the largest surface, and everything above it is already useful without it. **Independent test**: add a field of every type the product offers to a section of a draft, configure the ones that carry options, reorder them, and confirm a section that is already published is left alone while a draft of it takes the change.

**Acceptance Scenarios**:

1. **Given** a caller adding a field to a section of a draft, **When** they supply a label and a type, **Then** the field exists, is placed last, and the answer describes it with every setting it carries at its default.
2. **Given** a caller configuring a field, **When** they send its help text and placeholder, whether it is required, sensitive, unique or highlighted, the bounds and order of its repetitions, the list its choices come from, its validation and its default, **Then** each is kept, and a setting that does not apply to the type sent is refused as invalid. Whether a field repeats and what a relation points at follow from the type and are settled when the field is added, not afterwards.
3. **Given** a caller who changes a field of a section that is already published, **When** the request is answered, **Then** a draft of that section takes the change, the published section is untouched, and the answer says which section version now holds the field.

---

### Edge Cases

- Two callers open a draft of the same template at once. The second is handed the draft the first opened, because a template has at most one.
- A template is deleted while a record still points at one of its versions. The record keeps rendering from the version it holds, which is what makes the version rather than the template the thing a record points at.
- A section is placed in two templates and changed through one of them. The other keeps the version it was placed at, because a placement names a version and not a section.
- A field is removed from a draft that is then published. The values already written under it are kept and detached rather than deleted, and the reference says so.
- A caller publishes a draft holding no page at all. It is refused: a version nobody can fill is not a structure, which is also why the last page of a version cannot be deleted.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The API MUST offer a way to list the templates of the caller's account, in the order they are shown, with unoffered and system templates included and each marked as such.
- **FR-002**: The API MUST offer a way to read one template, its versions newest first, and one version in full, the version carrying its pages, the sections placed on them and the fields of each, nested and in order.
- **FR-003**: The API MUST offer a way to declare a template from a name and what it applies to, naming the entity type when it applies to one, and MUST settle a slug, place it last, open an empty draft, and never rebuild that slug afterwards.
- **FR-004**: The API MUST offer a way to correct a template's name and description, to duplicate one, to say whether it is offered and to move it in the order, each at its own address, leaving its slug, its versions and its records untouched.
- **FR-005**: The API MUST offer a way to delete a template, recoverably, and MUST refuse to delete one that still renders records, naming that reason. A template the product supplied MUST answer every request here exactly as one the account made, since `is_system` labels where a template came from and locks nothing.
- **FR-006**: The API MUST offer a way to open a draft version, which copies the structure of the version it comes from and MUST return the draft already open rather than a second one.
- **FR-007**: The API MUST offer a way to publish a draft with a change note, publishing with it every section it carries that is still a draft, and MUST leave the version it replaces and every record pointing at it exactly as they were.
- **FR-008**: The API MUST refuse every change to a published or retired version and to anything inside it, and MUST allow a draft that no record points at to be deleted.
- **FR-009**: The API MUST offer a way to add, rename, reorder and delete the pages of a draft, MUST settle each page a key that never changes, and MUST refuse to delete a page that still holds sections or the only page a version has, naming that reason.
- **FR-010**: The API MUST offer a way to place a section on a page of a draft, to override the title it shows and whether it starts collapsed, to move it between pages, to reorder it and to take it off, without changing the section itself.
- **FR-011**: The API MUST offer a way to add, configure, reorder and remove the fields of a section of a draft, carrying every setting the builder screen offers and no more, and MUST refuse a setting that does not apply to the type sent. Adding a field MUST take its type, its label, the list its choices come from and, for a relation, what it points at, because those are settled once.
- **FR-012**: A change to a field of a section that is already published MUST open a draft of that section, leave the published one untouched, and name in the answer the section version that took it.
- **FR-013**: Every position the API sets MUST renumber its level from one, and a position past the end MUST land last.
- **FR-014**: Every request here MUST answer not found for anything outside the caller's account, MUST disclose nothing about it, and MUST be recorded in the account's journal the way the same gesture made in a browser is recorded.
- **FR-015**: The public API reference MUST document the endpoints of each phase as that phase ships, in a group of its own, with the same worked request and answer the rest of the reference gives.

### Key Entities

- **Template**: the container and the stable identity a record's structure hangs from. Holds no fields.
- **Template version**: one frozen state of a template. Immutable once published, freely editable while a draft.
- **Page**: a tab inside a version. Not reusable, addressed by a key settled once.
- **Section placement**: where a section version sits on a page, with its title override and its collapsed state.
- **Section version**: one frozen state of a reusable section, and the thing that owns fields.
- **Field**: one piece of data a section collects, with its type and its configuration.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every gesture the templates settings screens and the builder offer can be made through the API by the end of phase five, with none left browser-only.
- **SC-002**: Each phase ships on its own, with its endpoints, its tests and its reference group, and leaves the API in a state a caller can use.
- **SC-003**: A caller can build an account's full set of templates, published and ready for records, without opening a browser.
- **SC-004**: No request through the API can change a published version, and every one of those refusals is covered by a test.
- **SC-005**: A request naming anything of another account discloses nothing, on every endpoint, and each of those refusals is covered by a test.

## Assumptions

- A template and a page are addressed by uuid, matching vaults, contacts and entity types. A version is addressed by its number within its template, which is what a change note refers to and what a reader recognises, rather than by the row id the browser happens to use. A section placement and a field carry no uuid and are addressed by their id, as the browser addresses them.
- Lists are paginated the way the other collection endpoints are, with the same page size bounds. A version read in full is not paginated: a structure is one document, and splitting it would make it unreadable.
- Sections and fields are reached through the template that places them, as the builder screen reaches them. Managing the section library on its own, and publishing a section version on its own, are a separate capability and stay out of scope.
- Moving records between versions is an existing separate capability and is not re-exposed here.
- The reference grows by one group per phase in the existing docs portal, and needs no new page, route or controller.
