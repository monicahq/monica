# Feature Specification: Edit and delete a life event

**Feature Branch**: `2026-09-23-contact-timeline` | **Created**: 2026-09-23 | **Status**: Draft

**Input**: "In the same branch, edit and delete a life event based on `Contact profile.dc.html`."

## Context

[Spec 046](../046-contact-timeline/spec.md) records a life event from a contact’s page and lists it on
the timeline, and leaves editing and deleting to this feature. The design adds both to each timeline
entry: **Edit** and **Delete** appear when the entry is hovered or focused. Edit reopens the panel of
046 filled in; Delete asks for confirmation inside the entry, then offers **Undo**.

The design also draws a second, inline editor for what it calls activities. The application has one
concept, the life event (046, Context), so every entry uses the panel and that editor is not built.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Correct a life event (Priority: P1)

Michael recorded "Move" on Jim’s page with the wrong date and forgot Pam. He presses **Edit** on the
entry, the panel opens filled in, he fixes the date, adds Pam, presses **Save changes**, and the entry
moves to its new place, marked "Edited", and shows on Pam’s timeline too.

**Independent Test**: edit a life event’s date, a detail, a custom value and its people; read both
timelines and both contacts’ counts.

**Acceptance Scenarios**:

1. **Given** a timeline entry, **When** it is hovered or holds focus, **Then** Edit and Delete are
   shown, named for the entry ("Edit Move", "Delete Move") for assistive technology; where nothing can
   hover, they are always shown.
2. **Given** Edit is pressed, **Then** the panel opens in place of the entry on the timeline, at its
   second step titled "Edit life event", captioned "Change anything, then save", scrolled into view,
   with the type, the date, the other people, and every piece and custom field holding a value
   already open and filled.
3. **Given** the panel is open for editing, **Then** its main button reads "Save changes", and Change
   type captions step 1 "Choose a different type".
4. **Given** Save changes, **Then** the life event carries what the panel holds: a piece or custom
   field closed with Remove is cleared, a file field keeps its file unless another is chosen or the
   field is removed; the panel closes; the entry is marked "Edited" until the page is next loaded; the
   note reads "Changes saved. Move, 3 July 2026, is on the timeline of Jim and Pam."
5. **Given** the type is unchanged, **Then** the custom fields are those of the version the life event
   was recorded on, even if the type has published a newer one since.
6. **Given** a new type is chosen, **Then** the life event moves to the version that type publishes
   now, and the values of fields the new type does not ask for are dropped, as when recording (046 US2).
7. **Given** people are added or removed, **Then** each affected contact’s life event count and last
   life event date are recomputed; the contact whose page it is cannot be removed.
8. **Given** a value is refused, **Then** nothing changes and the panel stays open with what was
   written and the error on its field.
9. **Given** Cancel or close, **Then** nothing changes and the entry is back in its place.

### User Story 2 - Delete a life event, and take it back (Priority: P1)

Michael recorded a call twice. He presses **Delete** on one; the entry asks "Delete this life event?"
and says "It is removed from the timeline of Jim and Pam. The people stay." He presses **Delete life
event**; the entry leaves both timelines and a note offers **Undo**.

**Independent Test**: delete a life event with two participants, check both timelines and counts,
undo, check again.

**Acceptance Scenarios**:

1. **Given** Delete is pressed, **Then** a confirmation opens inside the entry, announced as an alert
   dialog, with "Delete life event" and "Keep it"; focus moves into it.
2. **Given** Keep it, **Then** nothing changes and focus returns to Delete.
3. **Given** Delete life event, **Then** the life event leaves the timeline of every participant, each
   participant’s count and last life event date are recomputed, and a note above the timeline reads
   "Move, 3 July 2026, is deleted from the timeline of Jim and Pam." with **Undo**.
4. **Given** Undo, **Then** the life event returns with its date, details, custom values, files and
   people, and the counts are recomputed again.
5. **Given** the note is dismissed or the page reloaded, **Then** Undo is no longer offered.

### Edge Cases

- A life event whose type was deactivated since can still be edited, keeping that type.
- Editing or deleting from another participant’s page behaves the same way.
- A life event of another vault or account is never reached.
- A contact removed from a life event keeps the rest of their timeline.
- Deleting the only life event of a contact brings back the blank state of 046 US4.
- Editing the date of an entry that is not on the first page moves it on the next load.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Updating a life event MUST write its type, structure version, date, pieces, custom
  values and people in one step, all or nothing.
- **FR-002**: An unchanged type MUST keep the life event’s structure version; a changed type MUST take
  the version the new type publishes, and move the record counts of both versions.
- **FR-003**: Every contact added to or removed from a life event, and every participant of one
  deleted or restored, MUST have their life event count and last life event date recomputed.
- **FR-004**: Deleting MUST be recoverable: the life event and everything attached to it stay until
  restored, and files it holds are not released while it can be restored.
- **FR-005**: Update, deletion and restoration MUST each write one entry to the account journal.
- **FR-006**: Update and deletion MUST refuse a life event of another account or vault, and a contact
  who is not a participant opening it from their page.
- **FR-007**: The confirmation MUST precede every deletion, as the constitution asks of destructive
  actions.
- **FR-008**: Edit, Delete and the confirmation MUST be operable by keyboard alone and announced.
- **FR-009**: New wording MUST go through the translation workflow in every configured locale.

### Key Entities

- **Life event** and **life event participant**: defined in 046. A deleted life event is kept and
  can be restored.

## Success Criteria *(mandatory)*

- **SC-001**: A wrong date is corrected in under 15 seconds without leaving the page.
- **SC-002**: A deleted life event is back, whole, with one press of Undo.
- **SC-003**: After any edit, delete or undo, every participant’s timeline and counts agree.

## Assumptions

- "Edited" is marked only on the entry just saved, like "Just added"; nothing records it permanently.
- Undo lives in a note above the timeline, as "Undo the merge" does, since the toast holds no action.
- There is no screen listing deleted life events; Undo is the only way back in this feature.
- The API, MCP and webhooks do not change, as in 046.
