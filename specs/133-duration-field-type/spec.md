# Feature Specification: Duration field type, and a quieter field type picker

**Feature Branch**: `2026-11-18-public-changelog` (the work stays on the current branch)

**Created**: 2026-11-18

**Status**: Draft

**Input**: User description: "Sur la base de la discussion qu'on vient d'avoir, implémente le champ duration en minutes seulement. Aussi, je veux que dans le formulaire d'ajout de champ, tu enlèves les textes qui sont associés à chaque catégorie. Par exemple, pour le champ simple, on a le texte one value type straight in, il faut que tu enlèves ce genre de phrase."

## Context

The field type picker lists Duration under the Date family with a "Later" badge: it is a catalogue
entry, and nothing can store, validate or show a duration. Life events already record a duration as
a whole number of minutes, entered in one box and shown as "90 minutes". A Duration field does
exactly the same, and nothing larger. Separately, the sentence beside each family name in the picker
("One value, typed straight in.") is removed.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Add a duration field and fill it (Priority: P1)

Somebody editing a template picks Duration, names the field ("Usual walk length") and saves. On a
record built from the template they type 45 in the field and save; the record shows "45 minutes".

**Why this priority**: it is the feature.

**Independent Test**: add the field, fill it on a record, reload, read the value back.

**Acceptance Scenarios**:

1. **Given** the picker, **When** it opens, **Then** Duration is selectable, with no "Later" badge.
2. **Given** a Duration field, **When** 90 is entered and saved, **Then** the record shows "90 minutes".
3. **Given** a stored duration, **When** the field is opened for editing, **Then** it shows the minutes.
4. **Given** a stored duration, **When** the box is emptied and saved, **Then** the field holds nothing.
5. **Given** 0, -5, 2.5, "abc" or 100001, **When** saved, **Then** it is refused with a message and
   the stored value is unchanged.

---

### User Story 2 - The same duration through the API, the assistant and the export (Priority: P2)

A duration is written through the API and the MCP tools as a whole number of minutes, and survives
an account export and its import unchanged. Where the API reads a field value as text, a duration
reads as the same text the screen shows.

**Why this priority**: required by the constitution (principle X), and it follows story 1.

**Independent Test**: write 90 through the API, export the account, import it, find 90 minutes.

**Acceptance Scenarios**:

1. **Given** a Duration field, **When** a client writes 90, **Then** the record holds 90 minutes.
2. **Given** a record with a duration, **When** the account is exported then imported, **Then** the
   imported record holds the same number of minutes.

---

### User Story 3 - A picker without the family sentences (Priority: P3)

The picker shows each family by its name only.

**Why this priority**: cosmetic, independent of the rest.

**Independent Test**: open the picker and see no sentence beside any family name.

**Acceptance Scenarios**:

1. **Given** the picker, **When** it is displayed, **Then** no family shows a sentence, and every
   type keeps its own description, example and preview.

### Edge Cases

- A template field changed to or from Duration follows what any change of type already does.
- A life event type can carry a Duration field too, since it offers every field type.
- A contact list column can show a Duration field, as it shows a Number field. No field column is
  sortable today, and this feature does not change that.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The picker MUST offer Duration as an available type in the Date family.
- **FR-002**: A duration MUST be a whole number of minutes from 1 to 100,000, the bounds life events use.
- **FR-003**: A value outside those bounds MUST be refused with a translated message.
- **FR-004**: A duration MUST be entered in a single box, in minutes.
- **FR-005**: A duration MUST be shown with the wording life events use ("1 minute", "90 minutes").
- **FR-006**: The API and the MCP tools MUST accept a duration as a whole number of minutes.
- **FR-007**: An account export and its import MUST carry a duration unchanged.
- **FR-008**: A Duration field MUST be available as a contact list column.
- **FR-009**: The picker MUST NOT show a sentence beside any family name.

### Key Entities

- **Field**: gains one possible type, Duration.
- **Field value**: for a Duration field, a whole number of minutes, or nothing.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A duration written through any surface reads back as the same number of minutes
  through every other surface, in every case.
- **SC-002**: No family sentence and no "Later" badge on Duration remain in the picker.

## Assumptions

- Minutes only, entered and shown as minutes, as life events do. Hours and minutes as two boxes, and
  units above minutes, are out of scope.
- The two sentences that carried information go with the rest: "Structure. These store nothing and
  are skipped by search and export." and "Coming after the first version.". The "Later" badges
  already say the second.
- The sentences are removed from the code, so their translations leave the locale files.
- No reminder, sort or calculation is driven by a duration.
