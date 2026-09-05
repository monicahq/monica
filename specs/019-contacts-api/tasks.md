# Tasks: Contact endpoints

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[contracts/contacts.md](contracts/contacts.md), [quickstart.md](quickstart.md)

**Format**: `- [ ] [ID] [P?] [Story?] description with the file path`. `[P]` means a different file with
nothing incomplete in front of it.

**Tests are not optional here.** Principle III makes a test part of the change rather than a decision,
so every phase carries its own.

**Shape of this file**: Principle XI asks for one line per task, phase headings and the dependency list,
so the template's parallel examples, strategy and notes sections are left out.

## Phase 1: Setup (shared infrastructure)

**Purpose**: the object all nine endpoints answer with.

- [X] T001 Create ContactResource in `app/Http/Resources/ContactResource.php`, with the fields, the withheld columns and the avatar object of [contracts/contacts.md](contracts/contacts.md)

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: move the list query out of the view model so both surfaces can hold it. Blocks User Story 1,
and lands first regardless because it edits shipped code and has to prove the screen did not move.

- [X] T002 [P] Create ContactGroupingEnum in `app/Enums/ContactGroupingEnum.php` with `all`, `favourites` and `archived`
- [X] T003 [P] Create ContactSortEnum in `app/Enums/ContactSortEnum.php` with `name`, `nickname`, `last-spoke` and `added`
- [X] T004 Add `scopeInGrouping`, `scopeMatchingSearch` and `scopeSortedBy` to `app/Models/Contact.php`, moved from the view model, keeping the nulls last ordering and the tie on the identifier
- [X] T005 Cover the three scopes in `tests/Unit/Models/ContactTest.php`, including nulls last on all three database drivers
- [X] T006 Point `app/ViewModels/Vaults/ContactsViewModel.php` at the enums and the scopes, deleting its `FILTER_*`, `SORT_*` and `DIRECTION_*` constants and its private narrowing and ordering methods
- [X] T007 Point the filter `whereIn` in `routes/web.php` and the `$filter` argument of `app/Http/Controllers/App/Vaults/ContactController.php` at ContactGroupingEnum
- [X] T008 Run `tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php` and `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php` unchanged, which is the proof the screen did not move

**Checkpoint**: the screen answers exactly as before, from a query the API can now reach.

## Phase 3: User Story 1 (P1) - Read the people a vault holds 🎯 MVP

**Goal**: list a vault's contacts with the screen's groupings, search, sorts and paging, and read one by uuid.

**Independent test**: on a vault holding favourites, archived people and a partial one, every grouping,
every sort and the search box answer as the screen does, and one contact reads back by uuid.

- [X] T009 [US1] Add `index` to `app/Http/Controllers/Api/ContactController.php`: account and vault scoped, reading `filter`, `query`, `sort`, `direction` and `per_page` clamped to the configured maximum, answering hand built `data` and `meta`
- [X] T010 [US1] Add `show` to the same file, looking the contact up through the vault by uuid and letting `firstOrFail` give the 404
- [X] T011 [US1] Register the two GET routes in `routes/api.php` inside the sanctum group, uuid constrained, named `contacts` and `contacts.show`
- [X] T012 [US1] Create `resources/docs/api/09-contacts.php` with the contact object, the query parameters and the two GET sections, in the same commit as T011 or `ApiDocsViewModelTest` fails both ways
- [X] T013 [US1] Create `tests/Feature/Controllers/Api/ContactControllerTest.php` covering the shape, the three groupings, the four sorts in both directions, the search fold, partial contacts absent, paging and clamping, the empty vault, and 404 for another account and another vault
- [X] T014 [US1] Assert the new sidebar group in `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php`

**Checkpoint**: a caller can read every person an account knows. This is the MVP.

## Phase 4: User Story 2 (P2) - Write somebody down, correct them, remove them

**Goal**: create a contact from a first name alone, replace its name, and remove it.

**Independent test**: write a contact down, correct the name to a full one, then remove them, and confirm
they appear in the list, sort where the new name puts them, and leave what they took part in behind.

- [X] T015 [US2] Add `create` to `app/Http/Controllers/Api/ContactController.php`, requiring `first_name` and validating `template_version_id` with the `Rule::exists` the web form already uses
- [X] T016 [US2] Add `destroy` to the same file, catching `CannotDeleteTheMeContact` and answering 422 with its message
- [X] T017 [P] [US2] Create `app/Http/Controllers/Api/ContactNameController.php` with `update`, all eight name parts `present` and nullable so nothing is erased by omission
- [X] T018 [US2] Register the three routes in `routes/api.php`, named `contacts.create`, `contacts.destroy` and `contacts.name.update`
- [X] T019 [US2] Add the three sections to `resources/docs/api/09-contacts.php`, in the same commit as T018
- [X] T020 [US2] Extend `tests/Feature/Controllers/Api/ContactControllerTest.php` with create, the structure refusals, a partial contact off the list, destroy, the Me refusal and the 404s
- [X] T021 [P] [US2] Create `tests/Feature/Controllers/Api/ContactNameControllerTest.php` covering the replace, a body missing one part refused, the rebuilt sort key, and the 404s

**Checkpoint**: an account can be built from empty through the API alone.

## Phase 5: User Story 3 (P3) - The marks that are not fields

**Goal**: favourite state, picture on and off, and moving between published versions of the structure.

**Independent test**: mark and unmark a favourite, put a picture on and take it off, move between versions,
and confirm each shows on the record while nothing written down about the contact moves.

- [X] T022 [P] [US3] Create `app/Http/Controllers/Api/ContactFavouriteController.php` with `update`, taking the state wanted rather than a toggle
- [X] T023 [P] [US3] Create `app/Http/Controllers/Api/ContactAvatarController.php` with `update` and `destroy`, multipart on the POST, catching `NotEnoughStorageLeft` and answering 422
- [X] T024 [P] [US3] Create `app/Http/Controllers/Api/ContactTemplateVersionController.php` with `update`, falling back to what the structure publishes now and answering 404 for anything the move does not offer
- [X] T025 [US3] Catch `NotEnoughStorageLeft` in `app/Http/Controllers/App/Vaults/ContactAvatarController.php` and send it back to the edit screen as an error, since a full account gets a 500 there today
- [X] T026 [US3] Register the four routes in `routes/api.php`, named `contacts.favourite.update`, `contacts.avatar.update`, `contacts.avatar.destroy` and `contacts.templateVersion.update`
- [X] T027 [US3] Add the four sections to `resources/docs/api/09-contacts.php`, in the same commit as T026
- [X] T028 [P] [US3] Create `tests/Feature/Controllers/Api/ContactFavouriteControllerTest.php` covering both states, the same state sent twice, the favourites grouping, and the 404s
- [X] T029 [P] [US3] Create `tests/Feature/Controllers/Api/ContactAvatarControllerTest.php` covering the upload, the wrong kind, the oversized file, the quota refusal, removal returning to initials on an unchanged background, and the 404s
- [X] T030 [P] [US3] Create `tests/Feature/Controllers/Api/ContactTemplateVersionControllerTest.php` covering a named version, no version, a draft, a foreign version, and that no value moved
- [X] T031 [US3] Cover the quota refusal on the web path in `tests/Feature/Controllers/App/Vaults/ContactAvatarControllerTest.php`

**Checkpoint**: a contact built through the API is indistinguishable from one built in the browser.

## Phase 6: Polish

- [X] T032 Run `php artisan monica:localize` and translate everything it reports, in all nine locales
- [X] T033 [P] Run `vendor/bin/pint --dirty --format agent`
- [X] T034 [P] Run `vendor/bin/phpstan analyse --memory-limit=2G` and fix rather than ignore
- [X] T035 Walk [quickstart.md](quickstart.md) end to end against a running instance, including everything under "What must fail"

## Dependencies

- T001 blocks every controller task. T002 and T003 block T004; T004 blocks T005, T006 and T009. T006 blocks T007, and T007 blocks T008.
- Within each story the order is controller, then route, then documentation, then test. T012, T019 and T027 must each land in the same commit as the routes above them, because `ApiDocsViewModelTest` compares the reference against the route table in both directions and either half alone fails it.
- T019 and T027 both edit `resources/docs/api/09-contacts.php`, which T012 creates, so the three are serial across the stories. Nothing else is.
- Stories are otherwise independent: US2 and US3 need only Phase 1, and could be built beside US1 once T001 exists.
- Phase 6 depends on whichever stories are being shipped.

## Suggested MVP

Phases 1, 2 and 3. A caller can then read every person an account knows, which is the story that hands
over the identifiers the other two need, and the screen has been proved unmoved by the refactor.
