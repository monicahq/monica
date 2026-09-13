---
description: "Task list for the Manage relationships screen"
---

# Tasks: Manage relationships

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required. Constitution III is non-negotiable and `.claude/rules/testing.md` governs how they are written. No test asserts how the page looks.

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means a different file with nothing incomplete in front of it.

## Phase 1: Setup

- [X] T001 Confirm nothing new is needed: the `relationship`, `profile`, `gift`, `family` and `info` glyphs in `resources/views/components/icon.blade.php`, and `x-chip`, `x-delete-action`, `x-notice`, `x-badge`, `x-panel`, `x-contact-avatar`, `x-select` and `x-input` in `resources/views/components/`

## Phase 2: Foundational (blocks every story)

- [X] T002 [P] Add `scopeForContact()`, unioning both indexed sides with the type, its category and both contacts eager-loaded, in `app/Models/Relationship.php`
- [X] T003 [P] Test the scope: both directions, deleted excluded, another vault excluded, the same pair linked twice answered twice, in `tests/Unit/Models/RelationshipTest.php`
- [X] T004 Create the block object with its constructor, its grouping by category in the account's order, and the shared age lookup copied from `ImmediateFamilyBlock`, in `app/ViewModels/Vaults/RelationshipsBlock.php`
- [X] T005 Add the `PAGE_RELATIONSHIPS` key beside `PAGE_VERSION_HISTORY`, keep it out of `selectedPage()`, add `onRelationships()` and `relationships()`, and give the tab its count and `relationship` mark in `pages()`, in `app/ViewModels/Vaults/ContactViewModel.php`
- [X] T006 Create the page shell and its heading, in `resources/views/app/vaults/contacts/_relationships.blade.php`
- [X] T007 Branch to the new partial beside the version history branch, in `resources/views/app/vaults/contacts/show.blade.php`
- [X] T008 [P] Test that the tab is listed, carries the count, and is selected on its own address, in `tests/Unit/ViewModels/Vaults/ContactViewModelTest.php`

**Checkpoint**: the tab exists, is reachable and holds nothing yet.

## Phase 3: User Story 1 - Read every relationship somebody has (P1) 🎯 MVP

**Goal**: every link the contact has, grouped, counted, each row naming the person and the role.

**Independent Test**: record relationships under two categories, open the tab, see both groups with the right counts and no empty third.

- [X] T009 [US1] Add `groups()`: rows of name, URL, avatar, age, role read from this contact's side, and status, with empty groups dropped and a stable order, in `app/ViewModels/Vaults/RelationshipsBlock.php`
- [X] T010 [US1] Add `holdsNothing()` and the line offering the first relationship, in `app/ViewModels/Vaults/RelationshipsBlock.php`
- [X] T011 [US1] Add `namesWaiting()`: how many linked people are partial, and the URL of the list showing them, in `app/ViewModels/Vaults/RelationshipsBlock.php`
- [X] T012 [US1] Add the `NamesOnly` case with its label and `profile` mark, in `app/Enums/ContactGroupingEnum.php`
- [X] T013 [US1] Add that value to `forContact()`, so a contact whose slug reduces to it stays reachable, in `app/Helpers/ReservedAddressWords.php`
- [X] T014 [US1] Filter on `is_partial` for the new grouping, in `app/Models/Contact.php`
- [X] T015 [US1] Draw the groups: heading, count, rows with avatar, name, role, age, and the dashed mark and wording for a person who is a name only, in `resources/views/app/vaults/contacts/_relationships.blade.php`
- [X] T016 [US1] Draw the empty state, the way to add a relationship, and the names-waiting notice with its way to the filtered list, in `resources/views/app/vaults/contacts/_relationships.blade.php`
- [X] T017 [P] [US1] Test the block: grouping and order, empty groups dropped, counts, the role from each side, a partial person's row, an age present and absent, the names-waiting count, in `tests/Unit/ViewModels/Vaults/RelationshipsBlockTest.php`
- [X] T018 [P] [US1] Test the new grouping selects only partial contacts, in `tests/Unit/Models/ContactTest.php`
- [X] T019 [P] [US1] Test the page: the copy, the group headings, the links to each person, the absent link on a name-only row, the empty state and the notice, in `tests/Feature/Views/Vaults/RelationshipsTest.php`
- [X] T020 [P] [US1] Test the tab answers and another account's contact is not found, in `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php`

**Checkpoint**: US1 works on its own and is the MVP.

## Phase 4: User Story 2 - Remove a relationship (P2)

**Goal**: a link taken back for good, warned first, gone from both sides.

**Independent Test**: add a relationship, remove it from the tab, confirm it is gone from both contacts and both records still stand.

- [X] T021 [P] [US2] Add the `RelationshipDeletion` case and its journal wording, in `app/Enums/UserActionEnum.php`
- [X] T022 [US2] Create the action: soft-delete the row, refuse another account or another vault as not found, leave both contacts standing, log to `low`, in `app/Actions/DestroyRelationship.php`
- [X] T023 [US2] Add `destroy()` delegating to it and redirecting back to the tab with a status, in `app/Http/Controllers/App/Vaults/ContactRelationshipController.php`
- [X] T024 [US2] Declare the `contacts.relationships.destroy` route, in `routes/web.php`
- [X] T025 [US2] Add the per-group `x-data="{ manage: false }"`, the `x-chip` carrying `aria-pressed`, and the row controls it reveals, in `resources/views/app/vaults/contacts/_relationships.blade.php`
- [X] T026 [US2] Draw the removal behind `x-delete-action`, naming the person and saying the link goes for good, in `resources/views/app/vaults/contacts/_relationships.blade.php`
- [X] T027 [P] [US2] Test the action: the row soft-deleted, both contacts standing including a partial one, gone from both sides, another account and another vault refused as not found, the journal written, in `tests/Unit/Actions/DestroyRelationshipTest.php`
- [X] T028 [P] [US2] Test the route: removal works, a second confirmation is not found, another account's relationship is not found, in `tests/Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php`
- [X] T029 [P] [US2] Test the Manage chip's `aria-pressed` and that the warning stands before the removal, in `tests/Feature/Views/Vaults/RelationshipsTest.php`

**Checkpoint**: US1 and US2 both work on their own.

## Phase 5: User Story 3 - Correct a relationship (P3)

**Goal**: nature, direction, status and dates put right, read from both sides at once.

**Independent Test**: change a relationship's nature and mark it ended, confirm the other person reads the new roles and the Immediate family panel drops an ended partner.

- [X] T030 [P] [US3] Add the `RelationshipUpdate` case and its journal wording, in `app/Enums/UserActionEnum.php`
- [X] T031 [US3] Create the picker object holding `typeGroups()` and `typeMap()`, taking one optional type to keep even when deactivated, in `app/ViewModels/Vaults/RelationshipTypeChoices.php`
- [X] T032 [US3] Delegate to it and delete the moved methods, leaving the add form behaving exactly as before, in `app/ViewModels/Vaults/RelationshipFormViewModel.php`
- [X] T033 [P] [US3] Test the picker: active types grouped in the account's order, a withdrawn type absent, and present when it is the one kept, in `tests/Unit/ViewModels/Vaults/RelationshipTypeChoicesTest.php`
- [X] T034 [US3] Move the ancestry walk out of the private methods so both writing actions share it, in `app/Actions/CreateRelationship.php`
- [X] T035 [US3] Create the action: change nature, swap direction, set status and both dates, refuse a backwards range and an ancestry loop, refuse another account as not found, re-order the sides when the new nature is symmetric, log to `low`, in `app/Actions/UpdateRelationship.php`
- [X] T036 [US3] Create the view model: both people, the current nature kept even when withdrawn, the direction, the status and the dates, in `app/ViewModels/Vaults/RelationshipEditViewModel.php`
- [X] T037 [US3] Add `edit()` and `update()`, validating inline and returning each refusal to the field it belongs to, in `app/Http/Controllers/App/Vaults/ContactRelationshipController.php`
- [X] T038 [US3] Declare the `contacts.relationships.edit` and `contacts.relationships.update` routes, in `routes/web.php`
- [X] T039 [US3] Draw the form: the two people, the nature, the direction, the status and the two dates, in `resources/views/app/vaults/contacts/relationships/edit.blade.php`
- [X] T040 [US3] Draw the way to the form on a managed row, and say on a row when its link has ended and what dates it holds, in `resources/views/app/vaults/contacts/_relationships.blade.php`
- [X] T041 [P] [US3] Test the action: nature changed, direction swapped, ended and current again, dates written and cleared, a backwards range refused, an ancestry loop refused, a withdrawn nature kept on save, another account refused as not found, the journal written, in `tests/Unit/Actions/UpdateRelationshipTest.php`
- [X] T042 [P] [US3] Test the routes: the form answers, the change saves, validation failures come back, another account's relationship is not found on both, in `tests/Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php`
- [X] T043 [P] [US3] Test the form's copy, its fields and their values, in `tests/Feature/Views/Vaults/RelationshipsTest.php`
- [X] T044 [US3] Test the whole path FR-022 asks for: marking a romantic link ended through the action drops that person from the contact's current partners, in `tests/Unit/Actions/UpdateRelationshipTest.php`

**Checkpoint**: all three stories work on their own.

## Phase 6: Polish

- [X] T045 Run `php artisan monica:localize` and translate everything it reports, in every configured locale, in `lang/*.json`
- [X] T046 Run `php artisan monica:localize --check` and correct every counted phrase and typography breach it names
- [X] T047 Run `vendor/bin/pint --dirty --format agent`
- [X] T048 Run `vendor/bin/phpstan analyse --memory-limit=2G` and clear what it reports at level 5
- [X] T049 Run `php artisan test --compact` in full: the form from 029 and the panel from 030 share this code
- [X] T050 Walk [quickstart.md](quickstart.md) end to end, including every row of its failure table

## Dependencies & Execution Order

- **Phase 1** blocks nothing; it only confirms.
- **Phase 2** blocks all three stories. T002 blocks T004; T004 blocks T005; T005 blocks T007.
- **US1 (Phase 3)** depends on Phase 2 only. T012 blocks T013 and T014. T009 blocks T015. T011 blocks T016.
- **US2 (Phase 4)** depends on Phase 2, and on T015 for a row to hang its controls on. T021 blocks T022; T022 blocks T023; T023 blocks T024.
- **US3 (Phase 5)** depends on Phase 2, and on T025 for the Manage toggle that reveals the way to the form. T031 blocks T032 and T036. T034 blocks T035. T035 blocks T037; T037 blocks T038.
- **US2 and US3 are otherwise independent** and may be built in either order or at once, except that both draw into `_relationships.blade.php`, so T026 and T040 touch one file and are not `[P]` with each other.
- **Phase 6** depends on every story that is shipping.

## Parallel Opportunities

- T002 and T003 together; T003 fails until T002 lands, which is the point.
- Within US1: T017, T018, T019 and T020 are four files and run together once T009 to T016 are in.
- Within US2: T027, T028 and T029 together.
- Within US3: T041, T042 and T043 together. T044 shares a file with T041 and follows it.
- `[P]` holds **within** a phase, and the phases run in order. Three files are written by more than one phase: `app/Enums/UserActionEnum.php` (T021, T030), `tests/Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php` (T028, T042) and `tests/Feature/Views/Vaults/RelationshipsTest.php` (T019, T029, T043). If US2 and US3 are built at the same time by different people, those pairs are not parallel and one waits.

## Implementation Strategy

1. Phases 1 and 2, then **stop**: the tab is reachable and empty.
2. Phase 3, then **stop and validate**. US1 is the MVP and is worth shipping alone: it is the first time the product shows a contact's relationships at all.
3. Phase 4. Removal is the sharpest gap, and the product has never had it.
4. Phase 5. Correction, and the first writing of status and dates.
5. Phase 6 before any pull request.

## Notes

- T032 and T034 change code [029](../029-add-relationship-form/spec.md) shipped. The add form's tests must still pass untouched; if one needs editing, the extraction changed behaviour and is wrong.
- T005 adds a third reserved page key. A template page keyed `relationships` would be shadowed, as one keyed `version-history` already would be. Out of scope, recorded in [research.md](research.md) D1.
- T040's ended wording is the only place a row says anything a person did not write; keep it to what the record holds and never infer a date.
- No API task appears here. That deferral is [research.md](research.md) D9 and is owed.
