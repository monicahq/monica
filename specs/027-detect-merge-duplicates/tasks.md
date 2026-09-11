---

description: "Task list for 027-detect-merge-duplicates"
---

# Tasks: Detect and merge duplicate contacts

**Input**: Design documents from `/specs/027-detect-merge-duplicates/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/duplicates-and-merges.md](contracts/duplicates-and-merges.md), [quickstart.md](quickstart.md)

**Tests**: included. Principle III makes a test a condition of merge, so each test sits in the story that owes it rather than in a phase of its own.

**Organization**: by user story. User Story 1 ships on its own: somebody who has already spotted a duplicate can join it without detection existing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: different files, no dependency on an unfinished task
- Each story carries its own API routes, since Principle X makes them part of the capability

---

## Phase 1: Setup

**Purpose**: a branch and a working tree that can run the suite.

- [X] T001 Create the branch `2026-09-10-detect-merge-duplicates` from an up to date `main`
- [X] T002 Run `composer install` at the repository root, since this worktree has no `vendor/`
- [X] T003 [P] Run `bun install`, since this worktree has no `node_modules/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: the record of a merge, which User Stories 1 and 3 both write. User Story 2 needs none of it.

- [X] T004 Write `database/migrations/2026_09_10_000000_create_contact_merges_table.php` with the columns in [data-model.md](data-model.md), `up()` only, no `constrained()`, foreign keys declared at the bottom, and the index on `merged_contact_id, undone_at`
- [X] T005 Write `app/Models/ContactMerge.php` with `#[RouteKey('uuid')]`, `HasUuids`, `ResolvesModelInVault`, `#[Fillable]`, the casts for `resolutions`, `ledger` and the two timestamps, and the `belongsTo` relationships to Account, Vault, Contact twice, RecordSnapshot and User twice
- [X] T006 [P] Write `database/factories/ContactMergeFactory.php`
- [X] T007 [P] Add three cases after `RecordSnapshotRestored` in `app/Enums/UserActionEnum.php:135`: `ContactsMerged = 'contacts_merged'`, `ContactMergeUndone = 'contact_merge_undone'`, `DuplicateCandidateDismissed = 'duplicate_candidate_dismissed'`
- [X] T008 [P] Write `tests/Unit/Models/ContactMergeTest.php`: every relationship exists, the route key is the uuid, and a merge of another account does not resolve

**Checkpoint**: `php artisan migrate` runs, and `tests/Unit/Models/ContactMergeTest.php` is green.

---

## Phase 3: User Story 1 - Merge two contacts I have chosen (Priority: P1) 🎯 MVP

**Goal**: two contacts of one vault can be merged, previewed first with every conflict decided by the user, and the absorbed contact is retained with its address landing on the survivor.

**Independent Test**: create two contacts holding values, a file, a reminder and a share link, merge one into the other, and confirm the survivor holds everything, the vault lists one person, and the absorbed contact's old address lands on the survivor.

- [X] T009 [US1] Write `app/Actions/PreviewContactMerge.php`: constructor takes `User $user, Contact $kept, Contact $other`, one `execute()` returns the shape in [contracts/duplicates-and-merges.md](contracts/duplicates-and-merges.md) with an array-shape docblock, and it refuses another account or another vault with `ModelNotFoundException`, the same contact twice and an absorbed `is_me` with `InvalidArgumentException`
- [X] T010 [US1] Write `app/Actions/MergeContacts.php`: validate as T009, snapshot the survivor through `CreateRecordSnapshot` with `SnapshotReasonEnum::Merge`, then inside one `DB::transaction` repoint `field_values`, `field_value_rows`, `reminders`, `share_links` and every other contact's `field_values.related_contact_id`, apply the avatar and value resolutions, soft delete the absorbed contact, write `ledger` and `resolutions`, and log `UserActionEnum::ContactsMerged` through `LogUserAction`
- [X] T011 [US1] In `MergeContacts`, detach values whose field has no counterpart on the survivor's template version, reusing the `is_detached` and `detached_at` writes at `app/Actions/MoveRecordToTemplateVersion.php:117-147`, so they reach the set-aside screen instead of being dropped
- [X] T012 [US1] Leave `record_snapshots` pointing at the contact they captured, and say so in the class docblock of `MergeContacts`: a snapshot is that record's history, not content to move
- [X] T013 [US1] Write `app/Http/Controllers/App/Vaults/ContactMergeController.php` with `new`, `show` and `create`, each opening with `abort_unless($contact->isReachableThrough($request->user(), $vault), 404)`, and inline `$request->validate()` on the resolutions payload
- [X] T014 [P] [US1] Write `app/ViewModels/Vaults/ContactMergeViewModel.php`, reading `PreviewContactMerge` for the preview and the vault's other contacts for the picker, and holding no business logic
- [X] T015 [P] [US1] Write `resources/views/app/vaults/contacts/merge/new.blade.php`: the opening comment line, the `@var` block, and a contact picker that excludes the contact itself, and link to it from `resources/views/app/vaults/contacts/show.blade.php`
- [X] T016 [P] [US1] Write `resources/views/app/vaults/contacts/merge/show.blade.php`: a real comparison table, one radio group per conflict so no choice is carried by colour, the counts of what moves, and a destructive warning above the confirm button
- [X] T017 [US1] Add `contacts.merge.new`, `contacts.merge.show` and `contacts.merge.create` above the catch-all `contacts.show` at `routes/web.php:221`, with `whereUuid` on both contact parameters
- [X] T018 [US1] Write `app/Http/Controllers/App/Vaults/AbsorbedContactController.php` and hang it off `contacts.show` with `->missing()`, so a uuid found in `contact_merges` with `undone_at` null redirects to the survivor and anything else stays 404
- [X] T019 [P] [US1] Write `app/Http/Resources/ContactMergeResource.php` and `app/Http/Resources/ContactMergePreviewResource.php`
- [X] T020 [US1] Write `app/Http/Controllers/Api/ContactMergeController.php` with `show` and `create`, reading the same two actions, and add the two routes beside `contacts.destroy` at `routes/api.php:126`
- [X] T021 [US1] Write `tests/Unit/Actions/PreviewContactMergeTest.php`: conflicts reported with both sides, a one-sided value not reported as a conflict, values with nowhere to land listed as set aside, and the three refusals
- [X] T022 [US1] Write `tests/Unit/Actions/MergeContactsTest.php`: everything moves, the snapshot is taken first with reason `merge`, the absorbed contact is soft deleted, the ledger records what moved, `is_me` cannot be absorbed, and a contact of another account or another vault raises `ModelNotFoundException`
- [X] T023 [P] [US1] Write `tests/Unit/ViewModels/Vaults/ContactMergeViewModelTest.php`
- [X] T024 [US1] Write `tests/Feature/Controllers/App/Vaults/ContactMergeControllerTest.php`: the preview renders both sides, a merge redirects with a status, the absorbed contact's address redirects to the survivor, and another account's or another vault's contact answers 404
- [X] T025 [US1] Write `tests/Feature/Controllers/Api/ContactMergeControllerTest.php` with a `$jsonStructure` property: `Sanctum::actingAs`, 200 on the preview, 201 on the merge, 422 on a resolution the preview never reported, and 404 for another account

**Checkpoint**: two contacts can be merged from the interface and from the API, the absorbed one redirects, and the suite is green.

---

## Phase 4: User Story 2 - Find the duplicates in a vault (Priority: P2)

**Goal**: a vault screen lists the pairs that look like the same person, strongest first, each naming its resemblance, with a way into the preview and a way to dismiss.

**Independent Test**: create several near identical contacts and several plainly different ones, run the job, open the screen, and confirm only the near identical ones are listed and a dismissed pair does not return.

- [X] T026 [US2] Write `database/migrations/2026_09_10_000001_add_is_identifying_to_fields_table.php` adding `is_identifying` boolean default false after `is_unique`
- [X] T027 [US2] Write `database/migrations/2026_09_10_000002_add_match_key_to_contacts_table.php` adding `match_key` string 64 with an index on `vault_id, match_key`
- [X] T028 [US2] Write `database/migrations/2026_09_10_000003_create_contact_duplicate_candidates_table.php` with the columns, the unique on `contact_id, other_contact_id` and the index on `vault_id, dismissed_at, score` in [data-model.md](data-model.md)
- [X] T029 [P] [US2] Add `LocaleCollation::matchKey()` beside `foldForSearch` at `app/Helpers/LocaleCollation.php:54`, folding its argument and truncating to 64
- [X] T030 [US2] Add a private `buildMatchToken()` beside `buildSortName()` at `app/Models/Contact.php:456` returning the last name, or the first name, nickname or maiden name when there is no last name, and write `match_key` from it in the `saving` hook at `app/Models/Contact.php:150-155`
- [X] T031 [US2] Add a migration `2026_09_10_000004_backfill_contact_match_keys.php` that saves every existing contact so the hook fills `match_key`
- [X] T032 [P] [US2] Add `isIdentifying()` to `app/Enums/DefaultFieldEnum.php:49`, true for `Email` and `Phone` and false elsewhere, and read it beside `is_unique` at `app/Jobs/CreateDefaultTemplate.php:201`
- [X] T033 [US2] Carry `is_identifying` through `app/Actions/CreateField.php:136` and `app/Actions/UpdateField.php:81` as a promoted `readonly` parameter, and refuse writing it on a field whose section version is published
- [X] T034 [P] [US2] Add `is_identifying` to `app/Http/Resources/FieldResource.php:32`, to the validation at `app/Http/Controllers/Api/FieldController.php:88` and the call at `:114`, and to the web field form under `app/Http/Controllers/App/Settings/Account/Templates/FieldController.php`
- [X] T035 [US2] Write `app/Models/ContactDuplicateCandidate.php` and `database/factories/ContactDuplicateCandidateFactory.php`, with the pair always stored lowest id first
- [X] T036 [US2] Write `app/Jobs/DetectDuplicateContacts.php`, constructed with a `Contact` or a `Vault`, grouping by `match_key` in SQL and comparing full folded names in PHP, matching identifying values by grouping non-detached `field_values` on `value_string`, then upserting by pair and deleting only rows that stopped matching and were never dismissed
- [X] T037 [US2] Schedule `DetectDuplicateContacts` for every vault on the `low` queue at 03:40 in `routes/console.php:22`, and dispatch the contact form from `app/Actions/CreateContact.php` and `app/Actions/UpdateContactName.php`
- [X] T038 [P] [US2] Write `app/Actions/DismissDuplicateCandidate.php`, setting `dismissed_at` and `dismissed_by_user_id` and logging `UserActionEnum::DuplicateCandidateDismissed`
- [X] T039 [US2] Write `app/Http/Controllers/App/Vaults/ContactDuplicateController.php` and `ContactDuplicateDismissalController.php`, and `app/ViewModels/Vaults/ContactDuplicatesViewModel.php` ordering by score and paginating with `simplePaginate` as `ContactsViewModel:253` does
- [X] T040 [P] [US2] Write `resources/views/app/vaults/duplicates/index.blade.php` with its opening comment line and `@var` block, naming each pair's resemblance in words, and an empty state rather than an empty table
- [X] T041 [P] [US2] Add a `duplicates` entry with its badge count to `resources/views/components/vault/sidebar.blade.php:18`
- [X] T042 [US2] Add the two web routes and, with `app/Http/Resources/ContactDuplicateCandidateResource.php` and `app/Http/Controllers/Api/ContactDuplicateController.php` and `ContactDuplicateDismissalController.php`, the two API routes
- [X] T043 [US2] Write `tests/Unit/Jobs/DetectDuplicateContactsTest.php`: a similar name proposes a pair, a shared identifying value proposes a pair whose names differ, a shared value on a field that is not identifying proposes nothing, a pair is stored once whichever side it is found from, a contact is never its own duplicate, a dismissed pair survives a rerun, and no pair crosses a vault
- [X] T044 [P] [US2] Write `tests/Unit/Actions/DismissDuplicateCandidateTest.php` and `tests/Unit/Models/ContactDuplicateCandidateTest.php`
- [X] T045 [P] [US2] Add `it_marks_email_and_phone_as_identifying` to `tests/Unit/Jobs/CreateDefaultTemplateTest.php`, and the `is_identifying` cases to `tests/Unit/Actions/CreateFieldTest.php` and `UpdateFieldTest.php`
- [X] T046 [US2] Write `tests/Feature/Controllers/App/Vaults/ContactDuplicateControllerTest.php` and `tests/Feature/Controllers/Api/ContactDuplicateControllerTest.php`, both covering the dismissal and both compartment failures
- [X] T047 [P] [US2] Write `tests/Unit/Helpers/LocaleCollationTest.php` additions for `matchKey`, and `tests/Unit/ViewModels/Vaults/ContactDuplicatesViewModelTest.php`

**Checkpoint**: the duplicates screen lists a seeded pair, dismissal sticks, and `php artisan test --compact tests/Unit/Jobs/DetectDuplicateContactsTest.php` is green on all three drivers.

---

## Phase 5: User Story 3 - Undo a merge (Priority: P2)

**Goal**: a merge can be reversed, returning both contacts to what they held beforehand, and an undo that cannot complete is refused whole.

**Independent Test**: merge two contacts, undo it, and confirm both contacts and all their content match what a snapshot taken before the merge holds.

- [X] T048 [US3] Write `app/Actions/UndoContactMerge.php`: refuse unless `undone_at` is null and the absorbed contact is still retrievable, then inside one `DB::transaction` replay the `ledger` backwards, reverse the resolutions, restore the absorbed contact, stamp `undone_at` and `undone_by_user_id`, and log `UserActionEnum::ContactMergeUndone`
- [X] T049 [US3] Add `destroy` to `app/Http/Controllers/App/Vaults/ContactMergeController.php` and to `app/Http/Controllers/Api/ContactMergeController.php`, with the `vaults.merges.destroy` route on both files
- [X] T050 [P] [US3] Offer the undo on the survivor's page in `resources/views/app/vaults/contacts/show.blade.php`, warning first, and reachable with the keyboard alone
- [X] T051 [US3] Write `tests/Unit/Actions/UndoContactMergeTest.php`: both contacts come back holding what they held, a detached value is reattached, an already undone merge is refused, a merge whose absorbed contact is gone is refused, and nothing is applied in part
- [X] T052 [US3] Add the undo cases to `tests/Feature/Controllers/App/Vaults/ContactMergeControllerTest.php` and `tests/Feature/Controllers/Api/ContactMergeControllerTest.php`, the API answering 204 and 422

**Checkpoint**: a merge and its undo leave the vault exactly as it started.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T053 Update `spec/models/034-contact-merge-done.md`: fill its empty Fields table from [data-model.md](data-model.md), answer its two open questions, and replace `relationship_resolutions` with `resolutions`
- [X] T054 [P] Add the five routes and the `is_identifying` attribute to `resources/docs/api/06-contacts.php` and `11-templates.php`, following the `api-docs-writer` skill
- [X] T055 Run `php artisan monica:localize` and translate everything it reports in all 31 locales, giving every counted phrase its `trans_choice()` form and every non-inflecting one an explicit `[0,*]`
- [X] T056 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse`
- [X] T057 Run `php artisan test --compact` in full, and let CI run it on SQLite, MySQL and PostgreSQL (run here on SQLite: 5014 passing. MySQL and PostgreSQL are not reachable in this environment, so those two runs are CI's)
- [ ] T058 Walk [quickstart.md](quickstart.md) by hand, including the keyboard and screen reader pass on both screens, 200% zoom and phone width

---

## Dependencies & Execution Order

### Phase Dependencies

- Phase 1 comes first.
- Phase 2 blocks Phases 3 and 5. Phase 4 needs nothing but Phase 1.
- Phase 5 depends on Phase 3: there is nothing to undo until a merge runs.
- Phase 4 is independent of Phases 3 and 5, and only its screen links into the preview.
- Phase 6 depends on every phase before it.

### Task Dependencies

- T004 blocks T005, which blocks T006, T008, T010 and T048.
- T009 blocks T010, T013, T014 and T021. T010 blocks T011, T012, T022, T024 and T048.
- T013 blocks T017 and T018. T019 blocks T020.
- T026 blocks T032, T033, T034 and T036. T027 blocks T030, which blocks T031 and T036.
- T028 blocks T035, which blocks T036, T038 and T039. T036 blocks T037, T043 and T046.
- T035 and T039 block T042. T048 blocks T049, which blocks T050 and T052.
- T024 and T052 both edit `ContactMergeControllerTest.php`, T025 and T052 both edit its API counterpart. Each pair runs in order.

### Parallel Opportunities

- T002 with T003, then T006, T007 and T008 together.
- T014, T015 and T016 together once T013 lands. T019 alongside them.
- T026, T027 and T028 together, then T029 with T032.
- T040, T041, T044, T045 and T047 together once T039 lands.

---

## Implementation Strategy

### MVP

Phases 1, 2 and 3. Somebody who has spotted a duplicate can join it, preview it and rely on the
absorbed contact's address still working. It ships without detection and without the undo, and the
snapshot taken by T010 means nothing is lost while the undo is still being written.

### Incremental delivery

Phase 4 adds the screen that raises pairs nobody looked for, and is the larger half of the feature.
Phase 5 adds the undo, which is what makes the merge comfortable rather than merely safe. Phase 6
closes: the product specification, the API reference, 31 locales and the accessibility pass.

### Order inside Phase 4

The two columns before the table, the table before the job, the job before the screen. T033 is where
the frozen structure rule bites: `is_identifying` may not be written onto a published section version,
and its test belongs with T045 rather than at the end.
