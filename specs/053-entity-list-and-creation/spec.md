# Feature Specification: Entity list and creation form

**Feature Branch**: `2026-09-25-entity-list-and-creation` | **Created**: 2026-09-25 | **Status**: Draft

**Input**: "now that we've added custom entities in the sidebar of a vault, i want to add two
things: 1. listing of the objects in this entity (for us, here, animals) 2. a form to add a custom
entity, based on the content of the structure of the entity itself"

## Context

052 put one navigation entry per entity type under People and a read-only list behind it. No screen
can add a record, so every list is empty outside tests. This feature adds a form drawn from the
template the vault uses for the type, and the way to it from the list. Nothing names Animal: every
rule applies to every type.

**Decided 2026-09-25**: the list gains only the way in to the form; saving returns to the list; a
field with no control on screens is left out; the API lists and creates records too.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Add a record from its template (Priority: P1)

On the Animals list of Schrute Farms, Dwight chooses **Add an animal**. The form asks for the name,
then the fields of the vault's default Animal template in its page, section and field order:
Species, Breed, Sex, Birthday, Adoption date, Microchip number, Food and routine, Health and care
notes, Notes. He writes "Mose’s goat", picks Female, and saves.

**Independent Test**: an account seeded with Basic animal; open the form, fill the name and a few
fields, save, and check the record, its version and its values.

**Acceptance Scenarios**:

1. **Given** an active type whose vault default template is active and published, **Then** the form
   shows the name, then every fillable field of that version under its page and section titles,
   with each field's label, help text, choices and required mark.
2. **Given** Dwight saves with a name and some fields, **Then** one record is created in this vault
   on that exact version, holding exactly the values he filled; empty fields write nothing.
3. **Given** a missing name, a required field left empty, or a value of the wrong shape, **Then**
   the form is refused with the error beside its field, nothing is created, and what he typed stays.
4. **Given** a heading, a divider or a help line, **Then** it is left out, as the record page does.
5. **Given** a repeating group the form draws, **Then** rows can be added and removed before saving,
   and each row is saved as one repetition.
6. **Given** a relation or avatar field, which has no control on screens yet, **Then** it is not
   drawn, nor is a repeating group holding a required one (Caregivers, whose Person is a relation).
   File and image fields use the upload control the record page already uses.
7. **Given** the record is saved, **Then** Dwight lands on the type's list, where it appears in its
   place by name and a confirmation names it ("Mose’s goat is written down.").
8. **Given** the record is saved, **Then** the account's journal records it.

---

### User Story 2 - Creation is refused where it cannot lead anywhere (Priority: P1)

**Acceptance Scenarios**:

1. **Given** a turned-off type, **Then** the Add control stays on the list, disabled, pointing at
   052's notice as its reason, and the form's address and its submission are refused.
2. **Given** an active type whose vault default is missing, inactive, not published, or requires a
   field the form cannot draw, **Then** the Add control stays on the list, disabled, with its reason
   beside it, and the form is refused.
3. **Given** a type hidden in this vault or of another account, **Then** the form answers not found.
4. **Given** the version the form was drawn from stops being the vault default or stops being
   published before saving, **Then** saving is refused with a message asking to open the form again.

---

### User Story 3 - Through the API (Priority: P2)

**Acceptance Scenarios**:

1. **Given** a token, **Then** listing a type's records in a vault returns them by name, paginated,
   archived ones marked, nothing from another vault or account.
2. **Given** a token, **Then** creating a record takes a name and the field values keyed by field,
   applies every rule of US1 and US2, and answers with the created record.

### Edge Cases

- Two vaults with different defaults for Animals: each form follows its own vault.
- A name made only of spaces counts as missing; a name over 255 characters is refused.
- A required field inside a repeating group is required per row that holds anything.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The list MUST offer the way to add a record, in its header and in its empty state,
  enabled only when the type is active and the vault's default for it is usable (US2).
- **FR-002**: The form MUST show the name first, then every fillable field of the default's
  published version in order, leaving out relation and avatar fields and any repeating group
  holding a required one or holding nothing else.
- **FR-003**: Each field MUST be validated by the rules the record page applies to it.
- **FR-004**: Saving MUST create the record and all its values together, or nothing; a field the
  form pre-fills, or the API is not sent, starts with its template default, as on a contact.
- **FR-005**: The record MUST be bound to the version the form was drawn from.
- **FR-006**: Every refusal of US2 MUST also hold on the server, whatever the screen offered.
- **FR-007**: Creating a record MUST write one entry in the account's journal.
- **FR-008**: The API MUST list and create records under the same rules, and be documented.
- **FR-009**: No rule may depend on a particular type.
- **FR-010**: Every new string MUST be translated in every configured locale.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Dwight adds an animal with a name and three fields in under one minute.
- **SC-002**: Every field of a published template, other than relation and avatar fields, can be
  filled on the form.
- **SC-003**: No refused submission creates a record or a value.
- **SC-004**: No form or endpoint ever writes on a draft, or on a template of another type or account.

## Assumptions

- The vault default is the only template offered; unlike the contact form there is no picker.
- The avatar is the generated one; a picture is added once a record page exists.
- The form is one screen, each template page a titled group, not a sequence of steps.
- Reminders on a date are not offered on the form; they belong to the record page.
- The free offer's ceiling on contacts does not apply to entities.
- Viewing, editing, archiving, deleting and duplicating a record stay out of scope; rows stay unlinked.
- Relation controls are a later feature, so caregivers cannot be named from a screen yet.
