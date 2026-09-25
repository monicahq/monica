# Feature Specification: Relation, file and image fields in the template builder

**Feature Branch**: `2026-09-25-template-relation-file-image-fields` | **Created**: 2026-09-25 | **Status**: Draft

**Input**: "we are missing some fields when designing a template: relation (Caregivers > Person), file
and image." Clarified: set them up in the builder, and fill a relation in on the new record form.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Say what a relation field points at (Priority: P1)

Somebody designing a template adds a relation field and chooses what it points at: somebody in the
vault, or a record of one of the account's entity types.

**Why this priority**: today the builder adds a relation that always points at a contact, so a
"Veterinarian" or "Mother" pointing at another animal cannot be designed on screen.

**Independent Test**: add a relation field pointing at the Animal entity type, then open it in the
inspector and read the target back.

**Acceptance Scenarios**:

1. **Given** the field type screen, **When** Relation is picked, **Then** a choice of target appears,
   offering "Somebody in the vault" and every entity type of the account.
2. **Given** a relation field, **When** it is open in the inspector, **Then** its target is shown and
   cannot be changed, the same way the type cannot.

### User Story 2 - Fill in a relation on a new record (Priority: P1)

Somebody creating an animal names its caregivers by picking contacts from the vault.

**Why this priority**: the Animal template ships with Caregivers > Person, and the new record form
leaves the whole Caregivers group out because a relation has no control yet.

**Independent Test**: create an animal with two caregivers on the new record form; both rows are written.

**Acceptance Scenarios**:

1. **Given** a relation field, **Then** the form shows a control that searches, by name, the records of
   its target kind in the current vault, and nothing from another vault or account.
2. **Given** Caregivers, **Then** the group is drawn, a row can be added and removed, and a row
   without a Person is refused with a message on that row.
3. **Given** a list marked "distinct within its list", **When** the same person is picked twice,
   **Then** the save is refused and says which row repeats.

### User Story 3 - Add a field inside a repeater (Priority: P2)

Somebody designing a template adds fields to a repeater, so that a group like Caregivers (Person,
Role) can be built by hand.

**Why this priority**: the builder only adds fields at the top of a section, so no repeater made on
screen can hold anything.

**Independent Test**: add a repeater, then a relation and a short text inside it; both list under it.

**Acceptance Scenarios**:

1. **Given** a repeater in a draft, **Then** it offers to add a field inside it, via the field type screen.
2. **Given** that screen reached from a repeater, **Then** Repeater is not offered.

### User Story 4 - Choose which files a file or image field takes (Priority: P2)

Somebody designing a template limits a File field to PDFs, or an Image field to PNG and JPEG.

**Why this priority**: the builder adds file and image fields but cannot set the kinds they accept,
which the shipped Documents and Photos sections carry.

**Independent Test**: limit an image field to PNG, then try to upload a JPEG on a new record.

**Acceptance Scenarios**:

1. **Given** a file or image field in the inspector, **Then** it shows the kinds of file it takes and
   lets them be changed in a draft.
2. **Given** no kinds set, **Then** a file field takes any file and an image field any image.
3. **Given** a kind the field does not take, **When** it is uploaded, **Then** the form refuses it and
   names the kinds it takes.

### Edge Cases

- A published version: every setting above is shown and nothing can be changed, as today.
- An entity type pointed at by a relation is deactivated: the field keeps its target and says the type
  is inactive; values already written stay.
- No entity type in the account: the only target is somebody in the vault.
- Nothing to pick in the vault: the relation control says so rather than showing an empty list.
- A field inside a repeater is edited, reordered among its siblings and removed like any other.
- An unknown or malformed file kind typed in the inspector is refused with the value named.
- A required field inside a repeater makes each row need it, not the group.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The builder MUST ask for a relation's target when the field is added, and keep it fixed.
- **FR-002**: The target choices MUST be somebody in the vault and each active entity type of the account.
- **FR-003**: A repeater in a draft MUST allow fields to be added inside it, any type except a repeater.
- **FR-004**: The inspector MUST show and edit the accepted kinds of a file or image field in a draft.
- **FR-005**: An image field MUST accept only image kinds as its setting.
- **FR-006**: Every screen that fills in fields MUST offer a relation control that searches by name and
  lists only records of the target kind in the same vault.
- **FR-007**: A repeating group holding a relation MUST be drawn on the new record form.
- **FR-008**: The relation control MUST work with the keyboard alone and announce the chosen record.
- **FR-009**: An upload MUST be refused, with the accepted kinds named, when the field does not take its kind.
- **FR-010**: The API MUST cover all of the above; it already takes a target, a parent and options.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: The Animal template's People, Documents and Photos sections can be rebuilt on screen alone.
- **SC-002**: Every available field type but Profile photo can be set up and filled in on screen.
- **SC-003**: An animal with two caregivers can be created in under one minute, with the keyboard only.
- **SC-004**: No relation control ever lists a record from another vault or account.

## Assumptions

- File and image uploads already work on the new record form; only their accepted kinds are new here.
- Profile photo keeps no control of its own; it is out of scope.
- Viewing and editing an existing entity stay out of scope, as in 053.
- The value of a relation is written by the existing value rules, which already refuse a record from
  another vault, another account or another entity type.
