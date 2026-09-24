# Research: Edit and delete a life event

## R1. Three actions, one shared recount

- **Decision**: `UpdateLifeEvent`, `DestroyLifeEvent`, `RestoreLifeEvent`. Each recomputes
  `life_event_count` and `last_life_event_at` for the contacts it touched through a trait,
  `RecountsLifeEvents`, beside `RefusesALoopInParentage` in `app/Actions`: one count and one max over
  `life_event_participants` joined to `life_events` where `deleted_at` is null, written with a query
  update so no contact hook runs. `CreateLifeEvent` keeps its increment.
- **Rationale**: three callers and a rule worth naming (spec FR-003); an increment cannot undo a
  removal, a recount can.
- **Alternatives**: decrement and guess the previous date (wrong when the removed one was the latest).

## R2. Editing reuses the panel, opened by the server

- **Decision**: Edit is a link to `contacts.lifeEvents.edit`, which renders the contact page with the
  panel in edit mode, targeting `contact-timeline`, as `ContactSectionController::edit` does for a
  section. `LifeEventFormBlock` takes an optional `LifeEvent`: its projection opens at `details` with
  the type, date, people and the pieces and fields holding a value; `fieldsOf()` fills each field with
  its stored value; the form posts `PUT` to `contacts.lifeEvents.update`. It works before Alpine starts.
- **Alternatives**: filling the panel from a JSON payload in the script (custom fields and files need
  server rendering anyway).

## R3. The version follows the type, not the calendar

- **Decision**: same type → fields of `lifeEvent->structureVersion`; the fields fragment gets an
  optional `lifeEvent` query so it draws those fields and values. Changed type → the new type’s
  published version: the old values are destroyed through `DestroyFieldValue` / `DestroyFieldValueRow`
  (files released), `record_count` moves from the old version to the new, and `structure_version_id`
  is set before `SetFieldValues` runs.

## R4. A removed file field is said out loud

- **Decision**: `SetFieldValues` leaves a file alone unless an upload arrives, so an open file field
  posts `kept_files[]` with its id. The controller passes `removedFieldIds` (file fields of the version
  neither kept nor uploaded) and `UpdateLifeEvent` destroys those values. Every other field is cleared
  by the engine when it is not posted, which is what a closed piece does.

## R5. Deleting is a soft delete that holds its files

- **Decision**: `DestroyLifeEvent` soft deletes the row and decrements the version’s `record_count`,
  as `DestroyContact` does; field values and participants stay, so files keep their reference count
  and `PurgeOrphanedFiles` leaves them. `RestoreLifeEvent` undoes both.
- **Restore route**: takes the uuid as a string and queries `LifeEvent::onlyTrashed()` within the
  vault. `ResolvesModelInVault` does not cover a trashed binding, and `RouteProtectionTest` accepts a
  string parameter scoped by the controller’s own query, which its test proves.

## R6. The confirmation is inline, as the design draws it

- **Decision**: Delete toggles Alpine state on the entry and reveals a `role="alertdialog"` block with
  "Delete life event" (a `DELETE` form targeting `contact-timeline`) and "Keep it"; focus moves in and
  returns to Delete on Keep it. It departs from `x-delete-action`, a modal, because the design asks for
  the confirmation in place; the markup lives in one file, so no component is extracted.

## R7. Undo is a notice with a form

- **Decision**: after deleting, the redirect flashes `lifeEventDeleted` (uuid) and its sentence; the
  timeline section renders an `x-notice` above the list with an Undo form (`PUT` restore, target
  `contact-timeline`) and a dismiss button. Nothing is flashed after a reload, so Undo is gone.
- **"Edited"**: flashed `lifeEventEdited` id, drawn like "Just added"; "Editing" comes from the life
  event the edit screen was opened for.

## R8. Actions show on hover, focus, or always without hover

- **Decision**: the entry is a `group`; Edit and Delete are `opacity-0 group-hover:opacity-100
  group-focus-within:opacity-100 pointer-coarse:opacity-100`, named "Edit :name" / "Delete :name".
  Opacity keeps them in the tab order, so focusing one reveals both.
