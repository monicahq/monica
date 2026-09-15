# Feature Specification: Generic structure engine

**Feature Branch**: `2026-09-13-structure`

**Created**: 2026-09-13

**Status**: Draft

**Input**: Refactor the versioned field engine so its technical concepts are no longer named after
templates. A Template and an ActivityType must both use one generic engine. Template stays the
user-facing product concept; TemplateVersion stops being the shared technical abstraction.

## Why

The engine is called a template all the way down: a contact carries a `template_version_id`, a page
belongs to a template version, a placement is a template version section. Activity types need the
same engine for their custom fields (spec 9.3), and naming it after one caller leaves only bad
options: an activity type borrows a Template it is not, or a second field engine grows beside the
first, which principle VII forbids.

## Scope

Ships here: the Structure container, the rename of the engine, the ActivityType table and model with
its structure created alongside it, and every specification the rename invalidates.

Not here: activity type settings screens, the ActivityType API, the Activity record itself, and the
40 supplied types the product ships with. Each arrives with its own feature and consumes the engine
this one makes generic. FR-014 and FR-015 are therefore stated but discharged later; see
[plan.md](plan.md). The supplied catalogue is deferred with the screens because it is 1,200
translations of product content, and because nothing can show it until those screens exist.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Contacts and entities notice nothing (Priority: P1)

Somebody who manages contact and entity templates sees no change. Screens, words, record pages,
version history and the publish flow behave exactly as today.

**Why this priority**: the engine under every contact and entity is being renamed. Preserving
behaviour is the condition for shipping, not a nice to have.

**Independent Test**: the existing template, contact, entity, vault default and snapshot suites pass
unchanged in meaning.

**Acceptance Scenarios**:

1. **Given** a contact on a published version, **When** a newer version is published, **Then** the
   contact stays on the version it was on.
2. **Given** a template with three pages, **When** its record screen opens, **Then** the tabs, their
   order and their URL keys are what they were.
3. **Given** a vault default naming a version, **When** a contact is created there, **Then** it
   starts on that exact version.
4. **Given** a record moved to another version, **When** the move happens, **Then** a restorable
   snapshot of the previous state is written first.

---

### User Story 2 - An activity type owns a structure (Priority: P1)

Creating an activity type creates the structure its custom fields will live in, through the same
engine templates use, with no template involved and no second engine.

**Why this priority**: it is the point of the refactoring. Without it the engine is renamed and
nothing is gained.

**Independent Test**: create an activity type, confirm it has a published structure version, add a
section and a field to it through the engine's own actions, publish, and confirm the first version
is untouched.

**Acceptance Scenarios**:

1. **Given** a new activity type, **When** it is saved, **Then** it has one structure with one
   published version holding one page and no sections.
2. **Given** an activity type structure with a published version, **When** a field is changed,
   **Then** a draft version is created and the published one is untouched.
3. **Given** an activity type in another account, **When** its structure is reached, **Then** the
   answer is not found.
4. **Given** a template and an activity type, **When** each publishes a version, **Then** both went
   through the same publish action, the same freezing rules and the same page validation.

---

### User Story 3 - The marketplace carries both kinds (Priority: P2)

An author publishes an activity type, with its fields, to the marketplace. An installer gets a
private copy complete with its structure. Templates and standalone sections keep working.

**Why this priority**: spec 9.3 states it as a consequence of the design and the payload format is
shared, so it is settled in the same pass. Second because the engine has to be generic first.

**Independent Test**: export an activity type, install it into a second account, confirm the copy
carries the same fields and is independent of the original.

**Acceptance Scenarios**:

1. **Given** an activity type with a published structure, **When** it is exported, **Then** the
   payload is self contained, with its pages, placements, fields and value lists.
2. **Given** that payload, **When** it is installed elsewhere, **Then** a local activity type and a
   local structure are created and editing the copy changes nothing in the original.

---

### Edge Cases

- An activity type whose field builder is never opened renders a record with no custom fields rather
  than failing to render.
- An activity type is deactivated while records point at versions of its structure. They stay
  readable.
- A value exists for a field that later left the structure. It stays attached to its historical
  version and keeps its detached marking, as it does for contacts today.
- Two owners try to claim one structure. Refused: a structure has exactly one owner.
- A version is published while a section it carries is a draft. The section publishes first.
- A version is published with no page, or with an empty page among several. Refused, as today.
- A record is moved to a version describing another kind of record. Refused.

## Requirements *(mandatory)*

- **FR-001**: One versioned record structure engine exists, named after what it is, and both
  templates and activity types MUST use it.
- **FR-002**: A Structure is the stable container for a field structure. It holds no field data and
  identifies the version currently published.
- **FR-003**: A Structure has versions numbered from one, each draft, published or retired.
- **FR-004**: Editing a published Structure MUST create a draft and MUST leave the published version
  untouched.
- **FR-005**: Publishing MUST change only which version new records are given. It MUST NOT move an
  existing record.
- **FR-006**: The engine MUST NOT assume the owner of a structure is a Template.
- **FR-007**: A Structure MUST have exactly one owner.
- **FR-008**: Pages and section placements MUST belong to a structure version and keep their current
  freezing rules.
- **FR-009**: A Template MUST reference exactly one Structure and MUST remain the user-facing concept
  on every screen and API path that shows it today, version numbers included.
- **FR-010**: An ActivityType MUST reference exactly one Structure and MUST NOT reference a version
  directly. Creating one MUST create a structure with one published version, one page, no sections.
- **FR-011**: Contact, Entity, Activity and record snapshots MUST name the exact structure version
  they are on, under a name that no longer says template.
- **FR-012**: Field values MUST stay attached to the field of the historical version they were
  entered under and MUST remain readable after a newer version is published.
- **FR-013**: Account and vault isolation MUST hold unchanged across every renamed concept: another
  account's structure, version, page, placement or value answers not found.
- **FR-014**: Screens that edit an ActivityType MUST say "custom fields" and MUST NOT show the words
  template, structure or version. *(Discharged by the activity type screens feature.)*
- **FR-015**: An Activity MUST record the version its type published when the activity was created
  and stay on it. *(Discharged by the Activity feature.)*
- **FR-016**: Marketplace serialisation, installation and update notification MUST work for the
  structure of a Template and of an ActivityType through one payload format.
- **FR-017**: Installing MUST copy, never link, and a copy MUST NOT update itself.
- **FR-018**: No name derived from "template" may remain on a concept that is not about templates, in
  code, database, specifications or tests.
- **FR-019**: The model specs under `spec/models`, the product specification and the constitution MUST
  be updated in the same change. The shipped feature folders under `specs/` are records of decisions
  taken on a date and are deliberately left alone: rewriting them would make them describe a change
  they predate. `spec/` is the current truth, which is what the constitution's Governance already
  says.

### Key Entities

- **Structure**: stable container for one field structure. Belongs to an account, says which kind of
  record it describes, identifies its published version, has exactly one owner, carries no fields.
- **StructureVersion**: one draft or frozen state of a Structure. Replaces TemplateVersion.
- **StructureVersionPage**: a named, ordered part of one version, not reusable, not publishable on
  its own. Replaces TemplateVersionPage.
- **StructureVersionSection**: the placement of a section version on a page of a version. Replaces
  TemplateVersionSection.
- **Template**: unchanged as a product concept, now referencing a Structure.
- **ActivityType**: the nature of an activity, now referencing a Structure created with it.
- **Section, SectionVersion, Field, FieldValue, FieldValueRow, ValueList, ValueListItem**: unchanged.

The tables, columns and relationships are in [data-model.md](data-model.md). The nine contradictions
with the specifications as written today, and the three invariants they force, are in
[research.md](research.md).

## Success Criteria *(mandatory)*

- **SC-001**: Creating an activity type yields a usable published structure, through the same actions
  a template uses, with no template row involved.
- **SC-002**: Publishing a version leaves every record created before it rendering what it rendered,
  every value readable, for contacts, entities and activity type structures alike.
- **SC-003**: A single search across `app/`, `database/`, `resources/`, `routes/`, `tests/`, `lang/`
  and `spec/` returns no name derived from "template" on a concept that is not a template, outside
  the vocabulary FR-009 preserves: the route names, the `UserActionEnum` cases and the prose that
  explains the rename.
- **SC-004**: The complete suite passes, every test that covered an old name covers the new one, and
  no test is deleted without its behaviour moving.
- **SC-005**: New tests prove, for a template and for an activity type: a new record copies the
  published version, a later publication does not move it, a value stays on its historical structure,
  and another account answers not found.
- **SC-006**: An activity type exported and installed into a second account renders the same fields
  and is independent of the original.
- **SC-007**: Every model spec, the product specification and the constitution name the engine
  consistently, and no updated spec exceeds its cap without saying so.

## Assumptions

- **The database is rebuilt, not migrated.** Pre-production; the migration rule edits create
  migrations in place until the end of 2026 and rebuilds with `migrate:fresh`. No rename migration and
  no backfill. FR-012 is about versions inside one database, not about moving data.
- **ActivityType and Activity do not exist yet.** Their specs are marked "to create". This feature
  builds the ActivityType table, model, factory and creation action only, because that is what proves
  the engine is shared.
- **Sections stay reusable across owners.** A section built for a contact template may be placed in an
  activity type structure.
- **Version numbering stays per structure**, so a template's version numbers and its
  `/templates/{template}/versions/{version}` paths keep their meaning.
- **Every existing engine invariant carries over**: a published version is frozen, a version with no
  page cannot be published, an empty page among several cannot be published, publishing a version
  publishes the draft sections it carries, a second version needs a change note.
