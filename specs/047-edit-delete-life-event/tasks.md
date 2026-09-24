---
description: "Task list for editing and deleting a life event"
---

# Tasks: Edit and delete a life event

**Input**: Design documents from `specs/047-edit-delete-life-event/`

**Prerequisites**: plan.md, spec.md, research.md, contracts/life-event-edit-delete.md, quickstart.md

**Tests**: Required (constitution III). Follow `.claude/rules/testing.md`: strict types, `#[Test]`, snake_case `it_` names, named arguments, The Office data, no comments, no class string or markup assertion. Each rule is tested once, in the layer that owns it. The helper `lifeEventTypeAsking()` in `tests/TestCase.php` builds a type with custom fields.

**Organization**: the foundation is the recount and the journal cases. US1 (edit) and US2 (delete, undo) both touch the timeline entry and the controller, so they run in order.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

No setup: no dependency, migration or configuration change (plan.md).

## Phase 2: Foundational

- [X] T001 [P] In `app/Enums/UserActionEnum.php`, add after `LifeEventCreation`: `LifeEventUpdate = 'life_event_updated'` ('Changed the life event :type on the timeline of :name'), `LifeEventDeletion = 'life_event_deleted'` ('Deleted the life event :type from the timeline of :name'), `LifeEventRestoration = 'life_event_restored'` ('Brought back the life event :type on the timeline of :name')
- [X] T002 Create the trait `app/Actions/RecountsLifeEvents.php` (research R1) with `private function recountLifeEventsOf(array $contactIds): void`: for each id, set `life_event_count` to the count and `last_life_event_at` to the max `happened_on` (start of day, null when none) over `life_event_participants` joined to `life_events` where `participant_type` = `Contact::class`, `participant_id` = the id and `life_events.deleted_at` is null, written with `Contact::query()->whereKey($id)->update([...])`
- [X] T003 Create `app/Actions/DestroyLifeEvent.php` (`User $user, Vault $vault, Contact $contact, LifeEvent $lifeEvent`; class docblock of one sentence): refuse with `ModelNotFoundException` a life event of another account or vault and a contact who does not participate in it; in a transaction soft delete it, decrement its version’s `record_count` where above 0, recount its contact participants; log `LifeEventDeletion` (`type`, `name`) on `low`
- [X] T004 Create `app/Actions/RestoreLifeEvent.php` (same inputs, the life event trashed): refuse as T003 and refuse one not trashed; in a transaction restore it, increment its version’s `record_count`, recount; log `LifeEventRestoration` on `low`
- [X] T005 [P] Create `tests/Unit/Actions/DestroyLifeEventTest.php` and `tests/Unit/Actions/RestoreLifeEventTest.php` proving: soft deleted and restored with values, participants and files kept (a file field value’s `File` keeps `reference_count` 1 and `orphaned_at` null); both participants’ counts and last dates recomputed each way, including a contact whose latest life event was the deleted one; `record_count` moved; the log pushed; another vault and a non participant refused

**Checkpoint**: the two action tests pass.

## Phase 3: User Story 1 - Correct a life event (P1)

**Goal**: Edit reopens the panel filled in and saves through `UpdateLifeEvent` (research R2 to R4).

**Independent Test**: edit a Move’s date, a detail, a custom value and its people; read both timelines and counts.

- [X] T006 [US1] Create `app/Actions/UpdateLifeEvent.php` using `RecountsLifeEvents`: inputs of `CreateLifeEvent` plus `LifeEvent $lifeEvent` and `array $removedFieldIds = []`; refuse as T003, and a changed type that is inactive or of another account (`LifeEventTypeIsNotAvailable`; an unchanged deactivated type is allowed); in a transaction: when the type changes, destroy every field value and row of the life event through `DestroyFieldValue` / `DestroyFieldValueRow`, decrement the old version’s and increment the new published version’s `record_count`, set `life_event_type_id` and `structure_version_id`; update date, title, narrative, location, duration; sync participants (the page’s contact kept at position 0, others in the order given, `participant_count` updated); destroy the values of `removedFieldIds`; call `SetFieldValues` per section of the life event’s version `assembly`; recount the union of old and new contact participants; log `LifeEventUpdate` on `low`
- [X] T007 [US1] Create `tests/Unit/Actions/UpdateLifeEventTest.php` proving: fields written; a person added and one removed with both counts recomputed; the page’s contact never removed; an unchanged type keeps an older version although a newer one is published (use `lifeEventTypeAsking()` then republish with `ReplaceLifeEventTypeFields`); a changed type moves the version and both `record_count`s and drops the old values; a removed file field destroyed; a deactivated unchanged type accepted, a deactivated new type refused; the log pushed
- [X] T008 [US1] In `app/ViewModels/Vaults/LifeEventFormBlock.php`, accept `?LifeEvent $lifeEvent = null`: `isEditing()`, `title()` ("Add a life event" / "Edit life event"), `captions()` (pick and details wording per mode, contract table), `submitLabel()`, `action()` and `method()` (create POST or update PUT), `cancelUrl()`; when editing and no old input, `projection()` opens at `details` with the type, date, other participants and `openPieces` for every filled piece and field; `fieldsOf()` reads the life event’s own version when the type is its own, and fills `rawValue` from its stored values (old input still wins); the fields url carries `?lifeEvent={uuid}`
- [X] T009 [US1] In `app/ViewModels/Vaults/ContactTimelineBlock.php`, add per entry `editUrl` (`contacts.lifeEvents.edit`), `isEditing` and `isEdited` (constructor `?int $editingId`, `?int $editedId`), and in `ContactViewModel` accept `?LifeEvent $editingLifeEvent` and `?int $lifeEventEditedId`, passing them to both blocks
- [X] T010 [US1] In `app/Http/Controllers/App/Vaults/ContactLifeEventController.php`, add `edit()` (404 unless the contact participates; renders `app.vaults.contacts.show` with the editing view model, as `ContactSectionController::edit` does) and `update()` (validation of `create()` against the right version per research R3, `kept_files[]` to `removedFieldIds`, the same catches, redirect with `status` "Changes saved. :type, :date, is on the timeline of :names." and flash `lifeEventEdited`); in `ContactLifeEventFieldController::index()` read an optional `lifeEvent` uuid scoped to the vault; in `ContactController::show()` pass `lifeEventEdited` from the session
- [X] T011 [US1] In `routes/web.php`, add `contacts.lifeEvents.edit` and `contacts.lifeEvents.update` before `contacts.show`, one line each
- [X] T012 [US1] In `_life-event-panel.blade.php`, use the block’s title, captions, submit label, method and action; in `_life-event-fields.blade.php`, add `<input type="hidden" name="kept_files[]" value="{id}">` inside each open file field; in `_timeline.blade.php`, make each entry a `group`, add the Edit link (`x-target="contact-timeline"`, `aria-label` "Edit :name", visible per research R8) and the "Editing" / "Edited" badges; in `life-event-panel.js`, scroll the panel into view when it opens in edit mode and send Cancel to `cancelUrl`
- [X] T013 [US1] Extend `LifeEventFormBlockTest.php` (edit projection and stored values, own version kept) and `ContactTimelineBlockTest.php` (`editUrl`, `isEditing`, `isEdited`); in `ContactLifeEventControllerTest.php`, prove edit renders "Edit life event" and the stored title, update saves and redirects with the status, a refused value returns on its field, and a life event the contact is not in answers 404

**Checkpoint**: a life event is corrected from the page and every timeline agrees.

## Phase 4: User Story 2 - Delete a life event, and take it back (P1)

- [X] T014 [US2] Add `destroy()` to `ContactLifeEventController` (redirect flashing `lifeEventDeleted` = uuid and `lifeEventDeletedLine` = ":type, :date, is deleted from the timeline of :names.") and create `app/Http/Controllers/App/Vaults/ContactLifeEventRestorationController.php` `update()` (string uuid, `LifeEvent::onlyTrashed()->where('vault_id')->where('uuid')->firstOrFail()`, status ":type, :date, is back on the timeline of :names."); routes `contacts.lifeEvents.destroy` and `contacts.lifeEventRestoration.update`
- [X] T015 [US2] In `ContactTimelineBlock`, add per entry `destroyUrl` and `deleteLine` ("It is removed from the timeline of :names. The people stay."); in `ContactViewModel`, `deletedNotice(): ?array` (line and restore url) from the two flashed values
- [X] T016 [US2] In `_timeline.blade.php`, add Delete beside Edit (`aria-label` "Delete :name") toggling `x-data="{ confirming: false }"` on the entry, and the `role="alertdialog"` block (question "Delete this life event?", the line, a `DELETE` form "Delete life event" targeting `contact-timeline`, "Keep it" returning focus with `x-ref`); in `show.blade.php`, draw the Undo `x-notice` above the timeline with a `PUT` form to the restore url and a dismiss button
- [X] T017 [US2] Create `tests/Feature/Controllers/App/Vaults/ContactLifeEventRestorationControllerTest.php` (restores and redirects; another vault’s trashed life event not found) and extend `ContactLifeEventControllerTest.php` (destroy redirects with both flashes; the page then shows the line and "Undo")

## Phase 5: Polish

- [X] T018 Run `php artisan monica:localize`, translate every reported string in all 30 locales per `.claude/rules/translations.md` (counted phrases and typography as in 046), then `php artisan monica:localize --check`
- [X] T019 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress`, `bun run build` and the full suite `php artisan test --compact --parallel`; the three pre-existing "another alphabet" failures are known
- [ ] T020 Walk `quickstart.md` by hand, keyboard only and with VoiceOver

## Dependencies

- T001 and T002 before T003, T004 and T006; T003 and T004 before T005.
- US1 (T006 to T013) before US2 (T014 to T017): both edit `_timeline.blade.php`, the controller and `ContactTimelineBlock`.
- Parallel: T001 with T002; T005 beside US1 once T003 and T004 exist.
