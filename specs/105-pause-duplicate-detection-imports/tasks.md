# Tasks: Pause the duplicate search while an import writes into a vault

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: every changed rule is tested once, in the layer that owns it, per `.claude/rules/testing.md`. Each test fakes the queue with `Queue::fake()` and finds a search by `uniqueId()`, as tests/Unit/Actions/WriteAccountImportItemTest.php:448 does, except T016, which needs the real `sync` queue. An import being written is an `AccountImport::factory()` row with `status` `writing` and the vault in `vault_id` or `created_vault_ids`; the factory has no state for it, so pass the columns.

## Phase 1: Setup

- [X] T001 Create branch `2026-11-02-pause-duplicate-detection-imports` from an up to date `main` and carry the files of specs/105-pause-duplicate-detection-imports/ onto it (if `.git/config.lock` blocks `checkout -b`, build it with plumbing)

## Phase 2: Foundational

- [X] T002 Add `vaultIds(): array` with a `@return list<int>` docblock to app/Models/AccountImport.php (research R1): `vault_id` and every id in `created_vault_ids`, nulls dropped, each id once, in a short docblock sentence saying it is every vault the import writes into
- [X] T003 Add `isBeingImportedInto(): bool` to app/Models/Vault.php (research R2): `AccountImport::query()->where('account_id', $this->account_id)->whereIn('status', [Writing, RollingBack])->get()` and `contains()` an import whose `vaultIds()` holds `$this->id`, with a docblock sentence saying an account runs one import at a time
- [X] T004 [P] Add to tests/Unit/Models/AccountImportTest.php: `vaultIds()` for an import into an existing vault (that one id), into a new vault (the id once), for a file of several vaults (every created id) and before a vault is chosen (empty)
- [X] T005 [P] Add to tests/Unit/Models/VaultTest.php: true while an import is `writing` or `rolling_back` naming Scranton through `vault_id` and through `created_vault_ids`; false for `read`, `completed`, `rolled_back` and `rollback_failed`; false for Stamford while Scranton is imported into

Checkpoint: a vault can say whether an import is writing into it; nothing reads it yet.

## Phase 3: User Story 1, an import ends with one search of the vault (P1) MVP

Independent test: quickstart rows US1 and US1, other vault. An import creates contacts only through `CreateContact` (app/Actions/WritesMonicaItems.php:300, app/Actions/WritesMonicaFourItems.php:129), whose inner name update looks for no duplicates (app/Actions/CreateContact.php:184).

- [X] T006 [US1] In app/Actions/CreateContact.php, make `lookForDuplicates()` return first when `$this->vault->isBeingImportedInto()` (research R3)
- [X] T007 [US1] In app/Jobs/WriteAccountImportBatch.php, at the end of `complete()`, queue `DetectDuplicateContacts::dispatch($vault)->onQueue('low')` for each vault of `Vault::query()->whereKey($this->import->vaultIds())->get()` (research R4)
- [X] T008 [P] [US1] Add to tests/Unit/Actions/CreateContactTest.php: no `DetectDuplicateContacts` pushed while an import is writing into the vault; one pushed while an import is writing into another vault of the account
- [X] T009 [US1] Add to tests/Unit/Jobs/WriteAccountImportBatchTest.php: an import that completes pushes exactly one `DetectDuplicateContacts` on `low`, whose `uniqueId()` names its vault, and none naming a contact
- [X] T010 [US1] In tests/Unit/Actions/WriteAccountImportItemTest.php:447-449, replace the per contact `Queue::assertPushedOn()` with `Queue::assertNotPushed()` for each contact (research R7)

Checkpoint: an import queues no search while it writes and one per vault when it completes.

## Phase 4: User Story 2, an import that is undone leaves no search behind (P1)

Independent test: quickstart rows US2 and US2, new vault. Depends on T002 and T003.

- [X] T011 [US2] In app/Actions/RollBackAccountImport.php, after the notification in `execute()`, queue the same per vault loop as T007 on `$this->import` (research R4)
- [X] T012 [US2] In app/Jobs/RollBackAccountImportBatch.php, after `deleteDirectory()` in `failed()`, queue the same per vault loop
- [X] T013 [US2] Add `public bool $deleteWhenMissingModels = true;` to app/Jobs/DetectDuplicateContacts.php with a one line docblock saying a rollback may remove the contact first (research R5)
- [X] T014 [P] [US2] Add to tests/Unit/Actions/RollBackAccountImportTest.php: a rollback into an existing vault pushes one `DetectDuplicateContacts` for that vault; a rollback of an import that created its vault pushes none
- [X] T015 [P] [US2] Add to tests/Unit/Jobs/RollBackAccountImportBatchTest.php: `failed()` on an import `rolling_back` into an existing vault pushes one `DetectDuplicateContacts` for that vault
- [X] T016 [US2] Add to tests/Unit/Jobs/DetectDuplicateContactsTest.php, without `Queue::fake()`: build `new DetectDuplicateContacts(subject: $jim)`, `forceDelete()` Jim, `dispatch()` the job on the `sync` queue, and assert it raises nothing and `contact_duplicate_candidates` stays empty (research R5, FR-006)

Checkpoint: every way an import ends queues one search per vault still there, none for what the rollback removed, and none fails.

## Phase 5: User Story 3, searches asked for during an import wait for it (P2)

Independent test: quickstart rows US3 and US3, nightly. Depends on T003.

- [X] T017 [US3] In app/Jobs/DetectDuplicateContacts.php, make `handle()` return after the null subject branch when the subject's vault (`$this->subject` for a vault, `$this->subject->vault` for a contact) `isBeingImportedInto()` (research R3)
- [X] T018 [P] [US3] In app/Actions/UpdateContactName.php, make `lookForDuplicates()` return first when `$this->contact->vault->isBeingImportedInto()` (User Story 3 scenario 3)
- [X] T019 [P] [US3] In app/Actions/UpdateContactNameRepresentations.php, make `lookForDuplicates()` return first when `$this->contact->vault->isBeingImportedInto()`
- [X] T020 [US3] In app/Http/Controllers/App/Vaults/ContactDuplicateSearchController.php, when `$vault->isBeingImportedInto()`, redirect to `vaults.duplicates.index` with `status` `__('The search waits for the import.')` and `status_description` `__('An import is writing into this vault. Monica looks for duplicates on its own once the import has finished.')`, and dispatch nothing (research R6)
- [X] T021 [US3] Add to tests/Unit/Jobs/DetectDuplicateContactsTest.php: for a vault being imported into, a job given the vault and a job given a contact of it each leave the vault's standing candidate untouched and write none
- [X] T022 [P] [US3] Add to tests/Unit/Actions/UpdateContactNameTest.php: no `DetectDuplicateContacts` pushed for a rename while an import is writing into the contact's vault
- [X] T023 [P] [US3] Add to tests/Unit/Actions/UpdateContactNameRepresentationsTest.php: no `DetectDuplicateContacts` pushed while an import is writing into the contact's vault
- [X] T024 [P] [US3] Add to tests/Feature/Controllers/App/Vaults/ContactDuplicateSearchControllerTest.php: during an import the redirect carries the new `status` and `Queue::assertNothingPushed()` holds

Checkpoint: neither a rename, the button nor the nightly pass reads a vault an import is writing into.

## Phase 6: Polish

- [X] T025 Run `php artisan monica:localize`, translate the two new phrases in every configured locale following `.claude/rules/translations.md`, then `php artisan monica:localize --check`
- [X] T026 [P] In docs/portal/en/7-keeping-records-tidy/2-review-possible-duplicates.md, add after line 24 one sentence saying a vault being imported into is searched once, when the import ends, written without dashes as punctuation
- [X] T027 Run the quickstart.md test command, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`; if many feature tests fail on `touch(): Utime failed`, run `php artisan view:clear` first
- [X] T028 Record the feature in docs/FEATURES.md with the feature-documentator skill (always the last task)

## Dependencies

- T002 before T003; T003 before every story phase.
- US1, US2 and US3 are independent of each other once Phase 2 is done. T011 and T012 copy the loop of T007, but none needs it to run.
- T013 and T017 both edit app/Jobs/DetectDuplicateContacts.php, and T016 and T021 both edit its test: do each pair one after the other.
- Within a phase, the code task before its test; T009 and T010 after T006, since the import writes through `CreateContact`.
- Phase 6 after the stories it documents; T028 last.

**Parallel**: T004 with T005; T014 with T015; T018 with T019; T022 to T024.

**MVP**: Phases 1 to 3. They remove the per contact searches and their cost; Phases 4 and 5 close the rollback, the renames, the button and the nightly pass.
