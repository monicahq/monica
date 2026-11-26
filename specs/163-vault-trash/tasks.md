# Tasks: Vault trash

**Input**: `specs/163-vault-trash/` (spec.md, plan.md, research.md, data-model.md, contracts/api.md, quickstart.md)

**Tests**: required by constitution principle III, each rule in the layer that owns it (`.claude/rules/testing.md`). No test of how anything looks. Data from The Office.

**Design**: `Vault Trash.dc.html`, claude.ai/design project `00fa6920-3a94-478a-b273-a9c3923da137`. Read it again with `DesignSync get_file` before T030. Departures: no "deleted by" line, no search box, no row selection (spec Assumptions).

## Phase 1: Setup

- [X] T001 Read the `actions`, `controllers`, `routes`, `migrations`, `models`, `view-models`, `views`, `components`, `api-writer`, `api-docs-writer`, `mcp-development`, `help-bubbles`, `writer`, `changelog`, `translations` skills and `.claude/rules/components.md`, `flags.md`, `mcp.md`, `policies.md`, `testing.md` before writing code

## Phase 2: Foundational (blocks every story)

- [X] T002 Create a migration adding `nullableMorphs('deleted_with')` after `deleted_at` to `relationships`, `life_events`, `reminders`, `group_members` and `group_notes`, and `softDeletes()` plus `nullableMorphs('deleted_with')` to `group_files`, in `database/migrations/2026_11_26_000000_add_deleted_with_to_trashable_tables.php`
- [X] T003 Add `use SoftDeletes` to `app/Models/GroupFile.php`, and a documented `deletedWith(): MorphTo` to `Relationship`, `LifeEvent`, `Reminder`, `GroupMember`, `GroupNote` and `GroupFile` in `app/Models/`
- [X] T004 [P] Add `'trash_retention_in_days' => env('MONICA_TRASH_RETENTION_IN_DAYS', 30)` to `config/monica.php`
- [X] T005 [P] Create `app/Enums/TrashItemKindEnum.php` with the nine cases, values, models and flags of data-model.md, plus `label()` and `pluralLabel()` translated, `model(): string`, and `isAvailableFor(Account $account): bool` reading `FlagState::isOn()`; test it in `tests/Unit/Enums/TrashItemKindEnumTest.php`
- [X] T006 [P] Add `RelationshipRestoration`, `ReminderRestoration`, `GroupMembershipRestoration`, `GroupNoteRestoration`, `GroupFileRestoration`, `TrashItemDestruction` and `TrashEmptied` with their log sentences to `app/Enums/UserActionEnum.php` (`ContactRestoration`, `EntityDeletion` and `EntityRestoration` already exist)
- [X] T007 Create `app/Helpers/VaultTrash.php` (R3): constructor `(Vault $vault, Account $account)`; `paginate(?TrashItemKindEnum $kind, int $perPage, int $page): LengthAwarePaginator` of `TrashItem`, one `UNION ALL` over the nine tables restricted to `onlyTrashed` rows of the vault (`group_members`, `group_files` through `groups.vault_id`), newest `deleted_at` first; skips `deleted_with_vault`, contacts absorbed by a merge not undone, rows whose `deleted_with_type` is `ContactMerge`, and kinds unavailable to the account; without a kind, skips rows whose owner is still trashed (FR-003); `counts(): array<string, int>` per kind plus the top level total; `find(TrashItemKindEnum $kind, string $uuid): TrashItem` throwing `ModelNotFoundException` outside the vault or when not trashed
- [X] T008 Create `app/Helpers/TrashItem.php` (R4): kind, record, `title()`, `context()` (data-model.md), `deletedAt()`, `expiresAt()`, `daysLeft()`, `owner(): ?TrashItem` when `deleted_with` points at a trashed record, `obstacle(): ?string` (owner trashed: "Deleted with :name. Restore :name to bring this back."; other end of a relationship or membership trashed; entity type deleted), `dependents(): Collection` of the records whose `deleted_with` is this record
- [X] T009 Test T007 and T008 in `tests/Unit/Helpers/VaultTrashTest.php` and `tests/Unit/Helpers/TrashItemTest.php`: ordering, kind filter, top level only without a kind, another vault and account left out, `deleted_with_vault` and merge leftovers left out, a flag off removes its kinds, each obstacle, paging

## Phase 3: User Story 3, every kind can be deleted on screen (P1)

Ahead of US1 because the cascade decides what US1 lists. **Independent test**: delete one of each kind, nine trashed rows with the right owners.

- [X] T010 [US3] In `app/Actions/DestroyContact.php`, inside the existing transaction, soft delete with `deleted_with` set to the contact its live relationships (either side), live group memberships, reminders whose remindable is it, and life events whose only contact or entity participant is it (R2); extend `tests/Unit/Actions/DestroyContactTest.php`, including a shared life event left alone
- [X] T011 [US3] In `app/Actions/DestroyGroup.php`, soft delete with `deleted_with` set to the group its live memberships, notes, reminders and group files; extend `tests/Unit/Actions/DestroyGroupTest.php`
- [X] T012 [US3] Create `app/Actions/DestroyEntity.php`: account and vault check, soft delete, decrement `entity_types.entity_count`, log `EntityDeletion`; test in `tests/Unit/Actions/DestroyEntityTest.php`
- [X] T013 [US3] In `app/Actions/RemoveGroupFile.php`, soft delete the attachment and stop calling `DetachFile` (R6); extend its test in `tests/Unit/Actions/` (AddGroupFile left alone: an upload always makes a new file, research R6)
- [X] T014 [US3] In `app/Actions/MergeContacts.php`, set `deleted_with` to the `ContactMerge` on the relationships and memberships it soft deletes (R7); extend `tests/Unit/Actions/MergeContactsTest.php`
- [X] T015 [US3] Add `destroy` to `app/Http/Controllers/App/Vaults/EntityController.php` and its route `entities.destroy` in `routes/web.php`; a delete control through `x-delete-section` on `resources/views/app/vaults/entities/show.blade.php`; test in `tests/Feature/Controllers/App/Vaults/EntityControllerTest.php`
- [X] T016 [US3] Add `destroy` to `app/Http/Controllers/Api/EntityController.php`, route `entities.destroy` in `routes/api.php`, MCP tool `app/Mcp/Tools/DestroyEntity.php` registered in `app/Mcp/Servers/MonicaServer.php`; test in `tests/Feature/Controllers/Api/EntityControllerTest.php`
- [X] T017 [US3] Rewrite the deletion confirmation copy of the nine kinds (find each with `grep -rn "x-delete-action\|x-delete-section\|This cannot be undone" resources/views/app/vaults`) to say the item stays in the vault's trash for 30 days (FR-014)

## Phase 4: User Story 1, see what was deleted (P1) 🎯 MVP

**Independent test**: delete a person and a reminder, open Vault settings, Trash, two rows with "Today" and 30 days left.

- [X] T018 [US1] Create `resources/views/app/vaults/settings/_tabs.blade.php` with `x-tabs` linking `vaults.settings.index` (General) and `vaults.trash.index` (Trash, with its count), like `settings/account/billing/_tabs.blade.php`; include it in `resources/views/app/vaults/settings.blade.php`; add `trashCount()` to `app/ViewModels/Vaults/VaultSettingsViewModel.php`
- [X] T019 [US1] Create `app/ViewModels/Vaults/VaultTrashViewModel.php` over `VaultTrash`: rows (title, kind label and icon, context, "Today"/"Yesterday"/counted days and weeks ago through `trans_choice()`, days left, warning when three or fewer, the final date in the user's date format, owner line, obstacle, restore and delete URLs), menu entries with counts, the paging range label, the empty line, the confirmation wordings of US4; test in `tests/Unit/ViewModels/Vaults/VaultTrashViewModelTest.php`
- [X] T020 [US1] Create `app/Http/Controllers/App/Vaults/VaultTrashController.php` `index` (kind and page from the query) and route `vaults.trash.index` `GET vaults/{vault}/settings/trash` in `routes/web.php`
- [X] T021 [US1] Create `resources/views/app/vaults/settings/trash.blade.php` per the design: `x-breadcrumb.vault` ending "Trash", the tabs, the Show menu through `x-actions-menu` with `role="menuitemradio"` and `aria-checked`, the table, the countdown with a tooltip on hover and focus (`role="tooltip"`, `aria-describedby`), the owner line, Restore marked `aria-disabled` with `aria-describedby` on the obstacle (policies.md), paging, the empty state, an `x-help` per the help-bubbles skill
- [X] T022 [US1] Test in `tests/Feature/Controllers/App/Vaults/VaultTrashControllerTest.php`: the tab renders with "Trash", the wrong vault answers 404

## Phase 5: User Story 2, restore (P1)

**Independent test**: delete a person with two life events and a reminder, restore the person, all four back.

- [X] T023 [P] [US2] Create `RestoreContact` (increment `vault.contact_count` and `record_count`, `ReactivateReminders`, log `ContactRestoration`) and `RestoreEntity` (refuse when the type is deleted, increment `entity_count`, log `EntityRestoration`) in `app/Actions/`, each restoring its dependents and clearing their `deleted_with`; tests in `tests/Unit/Actions/`
- [X] T024 [P] [US2] Create `RestoreRelationship`, `RestoreReminder` (reactivate its occurrences), `RestoreGroupMembership`, `RestoreGroupNote`, `RestoreGroupFile` in `app/Actions/`, each logging its new `UserActionEnum` case; tests in `tests/Unit/Actions/`
- [X] T025 [US2] Extend `app/Actions/RestoreGroup.php` and `app/Actions/RestoreLifeEvent.php` to restore the dependents owned by the record and clear their `deleted_with`; extend their tests
- [X] T026 [US2] Create `app/Actions/RestoreTrashItem.php`: `(User $user, Vault $vault, TrashItemKindEnum $kind, string $uuid)`, resolves through `VaultTrash::find()`, refuses with the obstacle, calls the kind's restore action, returns the restored records; test in `tests/Unit/Actions/RestoreTrashItemTest.php`
- [X] T027 [US2] Create `app/Http/Controllers/App/Vaults/VaultTrashRestorationController.php` `update` and route `vaults.trash.restoration.update`; flash the notice ("… is back" or counted by kind, and what came with it); Restore button in T021's rows; test the redirect, the flash and one refusal in `tests/Feature/Controllers/App/Vaults/VaultTrashRestorationControllerTest.php`

## Phase 6: User Story 4, delete for good, empty (P2)

- [X] T028 [US4] Create `app/Actions/DestroyTrashItem.php` (force delete dependents then the record; a group file runs `DetachFile`; a contact also withdraws from search like `RollBackAccountImport`; logs `TrashItemDestruction`) and `app/Actions/EmptyVaultTrash.php` (every top level item through it; logs `TrashEmptied`); tests in `tests/Unit/Actions/`
- [X] T029 [US4] Create `app/Http/Controllers/App/Vaults/VaultTrashItemController.php` `destroy` and add `destroy` (empty) to `VaultTrashController`, routes `vaults.trash.destroy` and `vaults.trash.empty`; feature tests next to T022
- [X] T030 [US4] In `resources/views/app/vaults/settings/trash.blade.php`, the "Delete for good" icon button per row and "Empty the trash" through `x-delete-action`, with the counted wordings from T019 and "Keep them"

## Phase 7: User Story 5, the trash empties itself (P2)

- [X] T031 [US5] Create `app/Jobs/PurgeVaultTrash.php` (items past `trash_retention_in_days` through `DestroyTrashItem`, merge leftovers and `deleted_with_vault` skipped) and schedule it on `low` at 03:25 with `sentryMonitor()` in `routes/console.php`; test in `tests/Unit/Jobs/PurgeVaultTrashTest.php`
- [X] T032 [US5] Create the data migration `database/migrations/2026_11_26_000001_backfill_trash_owners.php` (data-model.md steps 1 and 2); test in `tests/Unit/Migrations/BackfillTrashOwnersTest.php`

## Phase 8: User Story 6, API and MCP (P2)

- [X] T033 [US6] Create `app/Http/Resources/TrashItemResource.php` with the item shape of contracts/api.md
- [X] T034 [US6] Create `TrashController` (`index`, `destroy` for empty), `TrashItemController` (`destroy`), `TrashRestorationController` (`update`, 422 with the obstacle) in `app/Http/Controllers/Api/`, routes in `routes/api.php`; tests in `tests/Feature/Controllers/Api/`, including another account's items left out of the list
- [X] T035 [US6] Create `ListTrash`, `RestoreTrashItem`, `DestroyTrashItem`, `EmptyTrash` in `app/Mcp/Tools/` through `MirrorsAnApiCapability::answerThroughApi()`, registered in `app/Mcp/Servers/MonicaServer.php`; `tests/Feature/Mcp/McpCatalogueTest.php` must pass
- [X] T036 [US6] Write `resources/docs/api/43-trash.php` and add entity deletion to `resources/docs/api/07-entity-types.php` with the api-docs-writer skill

## Phase 9: Polish

- [X] T037 Run `php artisan monica:localize`, translate every missing key with Sonnet subagents, then `php artisan monica:localize --check`
- [X] T038 Write the product documentation page on the trash (writer skill) and the changelog entry (changelog skill)
- [X] T039 Run `vendor/bin/pint --dirty --format agent`, PHPStan, `composer test:parallel`, then the accessibility-reviewer and rules-reviewer agents on the change
- [ ] T040 Walk quickstart.md by hand and take screenshots of the tab (take-screenshot skill)

## Dependencies

T002–T009 before everything. US3 (T010–T017) before US1, since the cascade fills the trash. US1 before US2 and US4 (their buttons live on the tab). US2's T026 before T027 and T034. T028 before T031. US6 after US2 and US4. Polish last.

## Parallel examples

T004, T005, T006 together. T023 and T024 together. T012, T013, T014 together after T003.

## Strategy

MVP is Phases 1–5: kinds deletable, the tab, restore. US4 and US5 make "30 days" true; US6 completes principle X before the pull request.
