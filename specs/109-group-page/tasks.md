# Tasks: Group page

Design: [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/routes.md](contracts/routes.md). Each task writes its test in the layer `.claude/rules/testing.md` assigns, and that test must pass before the task is done. Every new route is added to `tests/Feature/RouteProtectionTest.php` in the task that adds it. Never migrate the local database.

## Phase 1: Setup

- [X] T001 [P] Add `GroupNoteCreation`, `GroupNoteUpdate`, `GroupNoteDeletion`, `GroupFileAddition`, `GroupFileRemoval` to `app/Enums/UserActionEnum.php`, with labels in the style of the existing group cases (R12)
- [X] T002 [P] Add the `Group = 'group'` case to `app/Enums/LifeEventSourceEnum.php` (R1)

## Phase 2: Foundational

- [X] T003 Write `database/migrations/2026_11_09_000000_add_group_id_to_life_events_table.php` (`group_id` "int, nullable", `foreignIdFor(Group::class)->nullable()->constrained()->nullOnDelete()`, index with `happened_on`), `2026_11_09_000001_create_group_notes_table.php` and `2026_11_09_000002_create_group_files_table.php`, columns, indexes and the unique `(group_id, file_id)` exactly as in data-model.md; `up()` only
- [X] T004 [P] Create `app/Models/GroupNote.php` (`ResolvesModelInVault`, `SoftDeletes`, route key `uuid`) and `database/factories/GroupNoteFactory.php`
- [X] T005 [P] Create `app/Models/GroupFile.php` (`ResolvesModelInAccount`, route key `uuid`) and `database/factories/GroupFileFactory.php`
- [X] T006 Add `notes()`, `files()`, `lifeEvents()` and `reminders()` (morphMany on `remindable`) to `app/Models/Group.php`; `group()` to `app/Models/LifeEvent.php`; widen the `remindable` docblock in `app/Models/Reminder.php` to `Contact|Entity|Group`; update the model docblocks of `Group`, `File` ("to nothing else" now includes groups) and `spec/models/032-group-not-yet.md`

## Phase 3: User Story 3, read the group at a glance (P1)

Independent test: a group with members and a start date shows the header and three tabs; an empty group shows every empty state.

- [X] T007 [US3] Extend `app/ViewModels/Vaults/GroupViewModel.php` with the header (current members' avatars, `trans_choice(':count person belongs to it|:count people belong to it')`, the "Since" line at `started_precision` or null) and the tabs (Overview, Members with count, Files with count or absent when `FlagState::isOn('file-upload', $account)` is false); test in `tests/Unit/ViewModels/Vaults/GroupViewModelTest.php`
- [X] T008 [US3] Add the attendance count per member (R10: `__('No activities yet')` at zero, otherwise `trans_choice('At :count activity|At :count activities')`) to the members list in `app/ViewModels/Vaults/GroupViewModel.php`; test with The Office members in the same test file
- [X] T009 [US3] Add `groups.members.index` to `routes/web.php` and `index()` to `app/Http/Controllers/App/Vaults/GroupMemberController.php`; `groups.show` keeps the Overview; tests in `tests/Feature/Controllers/App/Vaults/GroupMemberControllerTest.php` and `GroupControllerTest.php` (renders, wrong vault 404)
- [X] T010 [US3] Write `resources/views/app/vaults/groups/_header.blade.php` (title, description, avatars, headcount, since line, Edit the group, Add somebody, the existing actions menu) and `_tabs.blade.php` with `x-tabs`/`x-tabs.item` `href`; rebuild `show.blade.php` as the Overview grid with the history column and the side column; create `members.blade.php` holding the existing `x-list-panel` member list with "Left" and "Outside the dates of the group" unchanged
- [X] T011 [US3] Rewrite the assertions of `tests/Feature/Controllers/App/Vaults/GroupControllerTest.php` that described the old member list on `groups.show`, so they target `groups.members.index`

## Phase 4: User Story 1, record an activity on the group (P1) MVP

Independent test: record an activity with five of seven members; it is on the group, on five timelines and not on two.

- [X] T012 [US1] Add `?Group $group = null` to `app/Actions/CreateLifeEvent.php`: refuse a group of another vault with `ModelNotFoundException`, write `group_id`; tests in `tests/Unit/Actions/CreateLifeEventTest.php` (written, refused, null by default)
- [X] T013 [US1] Assert in `tests/Unit/Actions/UpdateLifeEventTest.php` that an update leaves `group_id` unchanged
- [X] T014 [US1] Create `app/ViewModels/Vaults/GroupHistoryBlock.php` with activities only: newest first by `happened_on`, title, narrative, avatars, `trans_choice(':count of :total was there: :names|:count of :total were there: :names')`, edit URL to `contacts.lifeEvents.edit` on the first participant still in the vault (R3), the just-added mark; test in `tests/Unit/ViewModels/Vaults/GroupHistoryBlockTest.php`
- [X] T015 [US1] Create the composer data in `app/ViewModels/Vaults/GroupViewModel.php`: active life event types in the type picker's order with the first chosen, today's date, current members ticked and past members unticked (FR-007); test in `GroupViewModelTest.php`
- [X] T016 [US1] Create `app/Http/Controllers/App/Vaults/GroupLifeEventController.php` `create()` and route `groups.lifeEvents.create`, inline validation exactly as in contracts/routes.md, first attendee as `contact`, the rest as `participants`, `createdFrom: LifeEventSourceEnum::Group`; refusals back to the form; test in `tests/Feature/Controllers/App/Vaults/GroupLifeEventControllerTest.php` (created, no attendee refused, attendee of another vault 404, wrong vault 404)
- [X] T017 [US1] Write `resources/views/app/vaults/groups/_composer.blade.php` (Activity mode: `role="radiogroup"` choice, type select, date, title, narrative, `<fieldset>` of member checkboxes with its counted legend, counted help line) and `_history.blade.php` (activity entries, the "also on the history of each person who was there" line, `x-empty-state`)
- [X] T018 [US1] Show the group an activity was recorded on, linked, in `app/ViewModels/Vaults/ContactTimelineBlock.php` `entry()` (null when the group is deleted or the flag is off) and in `resources/views/app/vaults/contacts/_timeline.blade.php`; test in `tests/Unit/ViewModels/Vaults/ContactTimelineBlockTest.php`

## Phase 5: User Story 2, keep a note about the group (P1)

Independent test: a note is in the group history with its date and on no member's page.

- [X] T019 [P] [US2] Create `app/Actions/CreateGroupNote.php`, `UpdateGroupNote.php`, `DestroyGroupNote.php`: `body` "plain text, sanitized, required, at most 65,535 characters", account, vault and group checks, journal with T001's cases; tests in `tests/Unit/Actions/`
- [X] T020 [US2] Merge notes into `app/ViewModels/Vaults/GroupHistoryBlock.php` by `created_at` with paging as in R5 and "Show more"; test the interleaving and the second page in `GroupHistoryBlockTest.php`
- [X] T021 [US2] Create `app/Http/Controllers/App/Vaults/GroupNoteController.php` (`create`, `edit`, `update`, `destroy`) and its four routes from contracts/routes.md; a note of another group answers 404; test in `tests/Feature/Controllers/App/Vaults/GroupNoteControllerTest.php`
- [X] T022 [US2] Add the Note mode to `_composer.blade.php` (no attendees, help line "Saved on the group only"), note entries with in-place edit and `x-delete-action` with its warning to `_history.blade.php`

## Phase 6: User Story 4, set a reminder about the group (P2)

Independent test: a yearly reminder on a group is listed on it and in the vault's list naming the group; deleting the group silences it, restoring brings it back.

- [X] T023 [US4] Rename `app/Actions/CreateContactReminder.php` to `CreateManualReminder.php` taking `Contact|Group $record` (archive check for a contact only); rename every caller (`ContactReminderController` web and API, `app/Mcp/Tools/CreateContactReminder.php`, `WritesMonicaItems`, `WritesMonicaFourItems`) and move the tests to `tests/Unit/Actions/CreateManualReminderTest.php` with a group case
- [X] T024 [US4] Accept `Group` in `app/Actions/DeactivateReminders.php` and `ReactivateReminders.php`; call them with `RecordDeleted` from `DestroyGroup.php` and `RestoreGroup.php`; tests in `tests/Unit/Actions/DestroyGroupTest.php` and `RestoreGroupTest.php`
- [X] T025 [US4] Make the reminder readers hold a group: `morphWith` instead of `remindable.avatar` in `VaultDeadlines`, `VaultRemindersSentViewModel`, `VaultRemindersHistoryViewModel`; group name and link in `ReminderRow` and `ReminderViewModel`; `ReminderController::destroy` back to the group; `ReminderOccurrence::isStillWarranted` for a record without `is_archived`; tests in their existing test files
- [X] T026 [US4] Answer not found for an occurrence of a group in `app/Http/Controllers/Api/ReminderOccurrenceAnswerController.php` and `app/Mcp/Tools/AnswerReminderOccurrence.php` (R7); tests in their existing test files
- [X] T027 [US4] Create `app/ViewModels/Vaults/GroupRemindersBlock.php` (rules with next date, repeating mark, the due occurrence and its answer URL, empty state); test in `tests/Unit/ViewModels/Vaults/GroupRemindersBlockTest.php`
- [X] T028 [US4] Create `app/Http/Controllers/App/Vaults/GroupReminderController.php` (`new`, `create`, `edit`, `update`, `destroy`) and its five routes, validating what `contacts.reminders.*` validates; test in `tests/Feature/Controllers/App/Vaults/GroupReminderControllerTest.php`
- [X] T029 [US4] Write the Reminders panel in `show.blade.php`: list, tick posting to `reminders.occurrences.answer.update` with an `aria-label` naming the reminder, Add, the form reusing the contact reminder form partial

## Phase 7: User Story 5, keep files with the group (P3)

Independent test: two files on a group are listed, one downloads, used storage grows, removing one starts its grace period.

- [X] T030 [P] [US5] Create `app/Actions/AddGroupFile.php` (`CreateFile` then `AttachFile`, one `group_files` row) and `RemoveGroupFile.php` (delete the row, `DetachFile`), journal with T001's cases; tests in `tests/Unit/Actions/` including the storage refusal and `orphaned_at`
- [X] T031 [US5] Create `app/ViewModels/Vaults/GroupFilesBlock.php` (name, kind, size, added date, `files.show` URL, newest first, the kept-files sentence when the flag is off and the group holds files); test in `tests/Unit/ViewModels/Vaults/GroupFilesBlockTest.php`
- [X] T032 [US5] Create `app/Http/Controllers/App/Vaults/GroupFileController.php` (`index`, `create`, `destroy`) and routes `groups.files.index`, `groups.files.create`, `groups.files.destroy` with `flag:file-upload`; test in `tests/Feature/Controllers/App/Vaults/GroupFileControllerTest.php` (flag off 404, wrong vault 404)
- [X] T033 [US5] Write the Files panel in `show.blade.php` (latest few, link to the tab) and `resources/views/app/vaults/groups/files.blade.php` with Download and `x-delete-action`

## Phase 8: Polish

- [X] T034 Write group notes, group files, `life_events.group_id` and group reminders in `app/Actions/WriteAccountExport.php` and read them back in `app/Actions/WritesMonicaItems.php` (R11); tests in their existing test files
- [X] T035 Run `php artisan monica:localize` and translate every new string in every configured locale, following `.claude/rules/translations.md`
- [X] T036 Run the `accessibility-reviewer` and `rules-reviewer` agents on the branch and fix what they confirm
- [ ] T037 Run the whole suite, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`, then walk quickstart.md by hand
- [ ] T038 Trim `specs/109-group-page/spec.md` to its 120 line cap, or say in the pull request why it exceeds it (constitution XI)

## Phase 9: API and MCP (session 2026-11-10)

- [X] T039 [P] Group notes: `Api/GroupNoteController`, `GroupNoteResource`, five routes, five MCP tools, `28-group-notes.php`, tests (contracts/api.md)
- [X] T040 [P] Group activities: `Api/GroupLifeEventController`, `group` on `LifeEventResource`, two MCP tools, `29-group-activities.php`, tests
- [X] T041 [P] Group reminders: `Api/GroupReminder*Controller`s, `group` on both reminder resources, seven MCP tools, `30-group-reminders.php`, tests
- [X] T042 [P] Group files: `Api/GroupFileController`, `GroupFileContentController`, `GroupFileResource`, two MCP tools, `31-group-files.php`, tests
- [X] T043 Reference section count, sidebar and MCP tool count tests; translations of the new strings

## Dependencies

Phase 1 and 2 block everything. US3 (Phase 3) blocks US1, US2, US4 and US5, which all render into its page. US2's T020 needs US1's T014. US4 and US5 are independent of US1 and US2 and of each other. Phase 8 comes last; T034 needs T003. Within a phase, [P] tasks touch different files.
