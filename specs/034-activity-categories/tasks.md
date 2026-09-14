# Tasks: Activity category endpoints

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[data-model.md](data-model.md), [contracts/activity-categories.md](contracts/activity-categories.md),
[quickstart.md](quickstart.md)

**Format**: `- [ ] [ID] [P?] [Story?] description with the file path`. `[P]` means a different file with
nothing incomplete in front of it.

**Tests are not optional here.** Principle III makes a test part of the change rather than a decision,
so every phase carries its own.

**Shape of this file**: Principle XI asks for one line per task, phase headings and the dependency list,
so the template's parallel examples, strategy and notes sections are left out.

**Three reference tests move in every phase that adds a section.** `ApiDocsViewModelTest` compares the
reference against the route table both ways and asserts the sidebar, `ApiDocsControllerTest` asserts an
exact section count, and `ApiDocsReturnFieldsTest` calls every documented endpoint and diffs the fields
it answers with. Each story phase therefore carries one task that updates all three.

## Phase 1: Setup (the record)

**Purpose**: the table, the model and the eight names, which every story reads.

- [X] T001 Generate the migration with `php artisan make:migration create_activity_categories_table --no-interaction`, rename it to `database/migrations/2026_09_13_000000_create_activity_categories_table.php`, and write the columns of [data-model.md](data-model.md): `up()` only, every column commented, `unique(['account_id', 'key'])`, and the `account_id` foreign key declared at the bottom rather than with `constrained()`
- [X] T002 [P] Create `app/Enums/ActivityCategoryEnum.php` with the eight cases, keys and labels of [data-model.md](data-model.md), implementing `Translatable` so `monica:localize` finds the names, following `app/Enums/RelationshipCategoryEnum.php`
- [X] T003 Create `app/Models/ActivityCategory.php` with `HasUuids`, `ResolvesModelInAccount`, `SoftDeletes`, the `#[Fillable]` attribute, the `belongsTo` account, the `name` accessor falling back to `__($this->name_translation_key)`, and `activityTypeCount(): int` returning 0 with a docblock saying it is the one place that changes when activity natures land
- [X] T004 [P] Create `database/factories/ActivityCategoryFactory.php` with `system(ActivityCategoryEnum $case)` and `inactive()` states, following `database/factories/RelationshipCategoryFactory.php`
- [X] T005 Add the `activityCategories()` relation to `app/Models/Account.php`, beside `relationshipCategories()`
- [X] T006 [P] Create `tests/Unit/Models/ActivityCategoryTest.php` covering the account relation, the name accessor reading the supplied wording through the translator and the user's words when set, the casts, and `activityTypeCount()` answering 0

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: the object all seven endpoints answer with.

- [X] T007 Create `app/Http/Resources/ActivityCategoryResource.php` with the fields of [contracts/activity-categories.md](contracts/activity-categories.md), `id` the uuid, `activity_type_count` read from `activityTypeCount()`, and the timestamps as Unix integers

## Phase 3: User Story 1 (P1) - Read the groups an account starts with 🎯 MVP

**Goal**: an account is given eight usable groups at creation, and a caller can list them and read one.

**Independent test**: create an account, list its groups, and confirm the eight supplied ones come back
in order, worded in the language asked for, each marked as supplied and holding nothing.

- [X] T008 [US1] Create `app/Actions/CreateDefaultActivityCategories.php` writing the eight in one transaction, each with its `key`, `is_system` true, `name` null, `name_translation_key` from the enum, offered, positioned 1 to 8, and dispatching `LogUserAction` on the `low` queue, following `app/Actions/CreateDefaultRelationshipTypes.php`
- [X] T009 [US1] Add `ActivityCategoriesSeeded` to `app/Enums/UserActionEnum.php` with its journal sentence, worded as `RelationshipTypesSeeded` is
- [X] T010 [US1] Call the seeding action from `app/Actions/CreateAccount.php`, inside its transaction, beside `createDefaultRelationshipTypes()`
- [X] T011 [P] [US1] Create `tests/Unit/Actions/CreateDefaultActivityCategoriesTest.php` covering the eight rows, their order, their keys, `is_system` true with `name` null, the queued log, and that two accounts each get their own set
- [X] T012 [US1] Add `index` and `show` with a private `find` to `app/Http/Controllers/Api/ActivityCategoryController.php`: account scoped, ordered by `position` then `id`, `per_page` clamped to `monica.api_maximum_items_per_page`, answering hand built `data` and `meta`, `firstOrFail` giving the 404
- [X] T013 [US1] Register the two GET routes in `routes/api.php` inside the sanctum group, the parameter constrained to `[0-9a-fA-F-]{36}`, named `activityCategories` and `activityCategories.show`
- [X] T014 [US1] Create `resources/docs/api/18-activity-categories.php` with the category object, the paging parameters and the two GET sections, in the same commit as T013
- [X] T015 [US1] Update the three reference tests for the new group and its two sections: the sidebar list and the section count, and an arm each in `tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php`
- [X] T016 [P] [US1] Create `tests/Feature/Controllers/Api/ActivityCategoryControllerTest.php` covering the shape, the eight of a freshly created account in position order, a supplied name read through the translator and a renamed one read in the user's words, `activity_type_count` answering 0, paging and clamping, and 404 for another account's category, a deleted one and an id sent in place of the uuid
- [X] T017 [US1] Run `php artisan monica:localize` and translate the eight supplied names and the seeding sentence in all 31 locales, following `.claude/rules/translations.md` for the typography and the glossary of each

**Checkpoint**: a new account arrives with a usable vocabulary of groups and a caller can read it. This is the MVP.

## Phase 4: User Story 2 (P2) - Add a group, rename it, remove it

**Goal**: add a group, rename any group including a supplied one, and delete one that was added and holds nothing.

**Independent test**: add a group, rename it, rename a supplied one and confirm it stays supplied, then
delete the added one and confirm a supplied one refuses deletion.

- [X] T018 [P] [US2] Create `app/Exceptions/CannotDeleteASuppliedActivityCategory.php` and `app/Exceptions/CannotDeleteAnActivityCategoryInUse.php`, both deletion-only refusals, following the exceptions in `app/Exceptions` for relationship categories
- [X] T019 [US2] Create `app/Actions/CreateActivityCategory.php` sanitizing the name with `TextSanitizer::plainText`, placing it last, offered, `is_system` false and `key` null, and logging
- [X] T020 [US2] Create `app/Actions/UpdateActivityCategory.php` renaming any category, supplied or not, writing `name` and leaving `name_translation_key` ignored, refusing only a category of another account, and logging
- [X] T021 [US2] Create `app/Actions/DestroyActivityCategory.php` refusing a supplied category and one whose `activityTypeCount()` is above zero, soft deleting otherwise, and logging
- [X] T022 [US2] Add `ActivityCategoryCreation`, `ActivityCategoryUpdate` and `ActivityCategoryDeletion` to `app/Enums/UserActionEnum.php` with their journal sentences
- [X] T023 [P] [US2] Create `tests/Unit/Actions/CreateActivityCategoryTest.php`, `UpdateActivityCategoryTest.php` and `DestroyActivityCategoryTest.php` covering the placement, the sanitizing, the rename of a supplied category succeeding and leaving `is_system` and `key` alone, both deletion refusals, the soft delete, the queued logs and the cross-account `ModelNotFoundException`
- [X] T024 [US2] Add `create`, `update` and `destroy` to `app/Http/Controllers/Api/ActivityCategoryController.php`, requiring `name`, catching the two deletion exceptions and answering 422 with each message
- [X] T025 [US2] Register the three routes in `routes/api.php`, named `activityCategories.create`, `activityCategories.update` and `activityCategories.destroy`
- [X] T026 [US2] Add the three sections to `resources/docs/api/18-activity-categories.php`, in the same commit as T025, saying plainly that renaming a supplied category is allowed and only deletion is closed off
- [X] T027 [US2] Update the section count and add the three arms to `tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php`
- [X] T028 [US2] Extend `tests/Feature/Controllers/Api/ActivityCategoryControllerTest.php` with a category placed last and not supplied when added, a name of only spaces refused, the rename of a user-created and of a supplied category, the supplied one keeping `is_system` and reading the new words in every language, deletion of an added category, the supplied deletion refusal, and the 404s on all three
- [X] T029 [US2] Run `php artisan monica:localize` and translate the three new sentences in all 31 locales

**Checkpoint**: an account's set of groups can be shaped entirely through the API.

## Phase 5: User Story 3 (P3) - Decide what is offered and in which order

**Goal**: the two gestures that are not corrections to wording, each at its own address.

**Independent test**: stop a group being offered and offer it again, then move it up the order, and
confirm every group is renumbered from one with the retired ones counted.

- [X] T030 [P] [US3] Create `app/Actions/ActivateActivityCategory.php` and `app/Actions/DeactivateActivityCategory.php` following `app/Actions/ActivateRelationshipCategory.php`: refuse a category of another account, set `is_active`, and log on the `low` queue
- [X] T031 [P] [US3] Create `app/Actions/UpdateActivityCategoryPosition.php` renumbering every category of the account from one inside a transaction, retired ones counted, a position past the end landing last, following `app/Actions/UpdateRelationshipCategoryPosition.php`
- [X] T032 [US3] Add `ActivityCategoryActivated`, `ActivityCategoryDeactivated` and `ActivityCategoryPositionUpdate` to `app/Enums/UserActionEnum.php` with their journal sentences
- [X] T033 [P] [US3] Create `tests/Unit/Actions/ActivateActivityCategoryTest.php`, `DeactivateActivityCategoryTest.php` and `UpdateActivityCategoryPositionTest.php` covering the column, the place kept across a retirement, the renumbering with retired categories counted, the queued logs and the cross-account refusals
- [X] T034 [P] [US3] Create `app/Http/Controllers/Api/ActivityCategoryActivationController.php` with `update`, requiring `is_active` as the state wanted rather than a toggle, doing nothing when it already holds
- [X] T035 [P] [US3] Create `app/Http/Controllers/Api/ActivityCategoryPositionController.php` with `update`, requiring `position` as an integer of at least 1
- [X] T036 [US3] Register the two routes in `routes/api.php`, named `activityCategories.activation.update` and `activityCategories.position.update`
- [X] T037 [US3] Add the two sections to `resources/docs/api/18-activity-categories.php`, in the same commit as T036, saying that retiring records a state nothing reads yet because activity natures do not exist
- [X] T038 [US3] Update the section count and add the two arms to `tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php`
- [X] T039 [P] [US3] Create `tests/Feature/Controllers/Api/ActivityCategoryActivationControllerTest.php` covering both states, the same state sent twice answering 200 and not inverting, a supplied category retiring like any other, the name and place untouched, and the 404s
- [X] T040 [P] [US3] Create `tests/Feature/Controllers/Api/ActivityCategoryPositionControllerTest.php` covering a move to the front, a position past the end landing last, renumbering from one with retired categories counted, `position` below 1 refused, and the 404s
- [X] T041 [US3] Run `php artisan monica:localize` and translate the three new sentences in all 31 locales

**Checkpoint**: every gesture the future settings screen will need is reachable through the API alone.

## Phase 6: Polish

- [X] T042 Run `php artisan monica:localize --check` and fix what it reports
- [X] T043 [P] Run `vendor/bin/pint --dirty --format agent`
- [X] T044 [P] Run `vendor/bin/phpstan analyse` and fix rather than ignore
- [X] T045 Walk [quickstart.md](quickstart.md) end to end against a running instance, rebuilt with `migrate:fresh` because the table is new, including every refusal in section 4

## Dependencies

- T001 blocks T003 and everything after it, because there is no table to read until it exists. T002 blocks T003 and T008, which both name its cases. T003 blocks T004, T005, T006 and T007.
- T007 blocks every controller task, because the resource is what each of them answers with.
- Within each story the order is action, then enum case, then controller, then route, then documentation, then test. T014, T026 and T037 must each land in the same commit as the routes above them, because `ApiDocsViewModelTest` compares the reference against the route table in both directions and either half alone fails it.
- `resources/docs/api/18-activity-categories.php` is created by T014 and edited by T026 and T037, so those three are serial across the stories. So are T015, T027 and T038, which all move the same three reference tests.
- T012 and T024 both edit `ActivityCategoryController`, so they are serial. T009, T022 and T032 all edit `UserActionEnum`, so they are serial. The four single gesture classes of US3 are the genuinely parallel ones.
- T017, T029 and T041 each depend on the enum cases of their own phase, and each is what keeps that phase shippable on its own: a phase that ships an untranslated sentence breaks principle V.
- Stories are otherwise independent: US2 and US3 need only Phases 1 and 2, and could be built beside US1.
- Phase 6 depends on whichever stories are being shipped. T042 reports nothing if each phase ran its own `monica:localize`.

## Suggested MVP

Phases 1, 2 and 3. An account is then given eight usable groups at creation and a caller can read
them, which is the story that hands over the uuids the other two need.
