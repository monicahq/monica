# Feature Specification: Edit a Possession

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-11-11 | **Status**: Draft

**Input**: "i want to be able to edit a possession. currently the edit button is blurred."

The possession page (098) draws Edit as a split button whose main part is unavailable, "Editing comes
in a later version." The record can already be changed through the API and the assistant. This
feature adds the screen: the main part of Edit opens the add form of 094, filled with what the
possession holds, and saving changes it.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Change what is known of a possession (Priority: P1)

Michael Scott opens his "World’s Best Boss mug", selects Edit, renames it "World’s Best Boss mug
(original)", adds a description, saves, and is returned to the possession's page with a confirmation.

**Independent Test**: Open Edit from the possession page, change the name, save; the page shows the
new name and the confirmation.

1. **Given** the possession page, **When** shown, **Then** the main part of Edit is available and
   opens the edit form; the menu part still unfolds featuring and deleting.
2. **Given** the edit form, **When** opened, **Then** every value the form shows is filled with what
   the possession holds: name, description, location, declared value and currency, tags, featured.
3. **Given** an emptied name, **When** saving, **Then** the form returns with everything typed kept
   and a message on the name.
4. **Given** Cancel, **When** selected, **Then** nothing changes and the possession's page is shown.
5. **Given** a possession of another vault of the same account, or of another account, **When** its
   edit address is opened, **Then** the answer is not found.

### User Story 2 - Change the values of its type's fields (Priority: P1)

Dwight Schrute corrects the Mileage of his Vehicle from 120000 to 121500 and empties Registration.

**Independent Test**: Change one field value and empty another, save; the page shows the new value and
no longer shows the emptied one.

1. **Given** the edit form, **Then** the type is shown, cannot be changed, and its fields appear
   grouped and ordered as the type defines, each filled with what the possession holds.
2. **Given** a value that does not fit its field, **When** saving, **Then** it is refused with a
   message on that field, and nothing at all is saved.
3. **Given** a field added to the type after the possession was added, **Then** it appears empty.

### User Story 3 - Move, tag or feature it from the form (Priority: P2)

Pam Beesly moves her watercolour from "Home › Living room" to "Home › Hallway", removes the tag
"to frame", and unticks "Feature this possession".

1. **Given** a new location, **When** saved, **Then** the page says it is kept in the new place.
2. **Given** a tag removed and a new name typed, **When** saved, **Then** the possession carries
   exactly the tags in the box, a new name being created as on the add form.
3. **Given** "Feature this possession" ticked or unticked, **When** saved, **Then** the possession
   joins the end of the vault's featured possessions or leaves them, as the Edit menu does.

### Edge Cases

- A location, tag or field deleted between opening and saving: treated as on the add form (094).
- The possession deleted between opening and saving: not found, nothing saved.
- Nothing changed: saving still returns to the page with the confirmation and writes a journal entry.
- What the form does not show (status, quantity, identifier, transactions, insurance, maintenance,
  the photo) MUST be left exactly as it was.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The main part of Edit on the possession page MUST open the edit form, and no longer
  carry the reason that editing comes later.
- **FR-002**: The edit form MUST be the add form of 094, in the vault shell, with a trail (vault, All
  possessions, the possession's name, Edit) and a foot bar holding Cancel and "Save changes".
- **FR-003**: Every value the form shows MUST be filled with what the possession holds when opened.
- **FR-004**: The type MUST be shown and MUST NOT be changeable; its fields MUST be editable, and an
  emptied field MUST stop holding a value.
- **FR-005**: A refused save MUST return the form with every value typed kept and each message beside
  its field, and MUST save nothing.
- **FR-006**: A save MUST write everything or nothing, return to the possession's page with a
  confirmation naming it, and be recorded in the journal as a change to the possession.
- **FR-007**: A save MUST leave unchanged everything the form does not show.
- **FR-008**: The edit form MUST be reachable only for a possession of the vault named in its address,
  in the person's account; anything else answers not found.
- **FR-009**: The Photos area MUST stay inert with its reason, as on the add form.
- **FR-010**: Every wording MUST be translated in every locale, and the form MUST meet WCAG 2.2 AA.

### Key Entities

- **Possession** (exists): nothing added; its name, description, location, declared value, tags,
  featured state and field values become changeable from a screen.

## Success Criteria *(mandatory)*

- **SC-001**: A possession's name is changed in under 20 seconds from its page.
- **SC-002**: Opening the form and saving it unchanged leaves every value of the possession identical.
- **SC-003**: Nothing typed is lost when a save is refused.
- **SC-004**: The form is opened, filled and saved with the keyboard alone and announced correctly by
  a screen reader.
- **SC-005**: No untranslated copy in any locale.

## Assumptions

- Same branch, as the user's memory asks; no new branch.
- The type never changes: the existing update rule says so, and changing it would orphan the values of
  its fields. Re-adding the possession under another type is the way to change it.
- Status, quantity and identifier have no place on the add form; editing them is a later feature.
- The API and the assistant already update a possession (091, 094), so principle X is met; this
  feature adds no API surface. If the screen needs a capability the API lacks, the API gains it too.
- Tags and featuring remain changeable from the page itself (101, 098); the form is a second way in.
