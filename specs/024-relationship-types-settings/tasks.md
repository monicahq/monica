---

description: "Task list for relationship types in account settings"
---

# Tasks: Relationship types in account settings

**Input**: Design documents from `/specs/024-relationship-types-settings/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/routes.md](contracts/routes.md)

**Tests**: not optional here. Constitution Principle III requires a test for every model, action, controller and view model, so every producing task is paired with its test task.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel, different files, no dependency on an unfinished task
- **[Story]**: the user story from [spec.md](spec.md) the task belongs to

---

## Phase 1: Setup (shared infrastructure)

**Purpose**: the two records exist and can be built by a factory.

- [X] T001 Write the `relationship_categories` migration in `database/migrations/2026_09_07_000000_create_relationship_categories_table.php`, columns per [data-model.md](data-model.md), `up()` only, foreign key declared at the bottom, no `constrained()`
- [X] T002 Write the `relationship_types` migration in `database/migrations/2026_09_07_000001_create_relationship_types_table.php`, same rules
- [X] T003 [P] Create `app/Enums/RelationshipBehaviorEnum.php` with the cases `GenealogyParentage` and `Romantic`, backed by `genealogy_parentage` and `romantic`
- [X] T004 [P] Create `app/Models/RelationshipCategory.php` with the uuid hook, soft deletes, `belongsTo(Account::class)`, `hasMany(RelationshipType::class)` and a `types()` ordering by position
- [X] T005 [P] Create `app/Models/RelationshipType.php` with the uuid hook, soft deletes, `belongsTo(Account::class)`, `belongsTo(RelationshipCategory::class)`, the `system_behavior` cast to `RelationshipBehaviorEnum` and an `active()` scope
- [X] T006 [P] Create `database/factories/RelationshipCategoryFactory.php` with a `system()` state
- [X] T007 [P] Create `database/factories/RelationshipTypeFactory.php` with `system()`, `symmetric()`, `directed()` and `inactive()` states
- [X] T008 [P] Add `relationshipCategories()` and `relationshipTypes()` to `app/Models/Account.php`
- [X] T009 [P] Write `tests/Unit/Models/RelationshipCategoryTest.php` covering the relations and the uuid
- [X] T010 [P] Write `tests/Unit/Models/RelationshipTypeTest.php` covering the relations, the uuid, the enum cast and the `active()` scope
- [X] T011 [P] Write `tests/Unit/Enums/RelationshipBehaviorEnumTest.php`

---

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: every account, new and existing, holds the supplied vocabulary. No story can be seen on screen before this.

**CRITICAL**: no user story work starts until this phase is done.

- [X] T012 Add `relationshipCount(): int` to `app/Models/RelationshipType.php`, returning `0` with a one-line comment saying the relationship record does not exist yet, per [research.md](research.md) decision 2
- [X] T013 Create `app/Actions/CreateDefaultRelationshipTypes.php`, seeding the five categories and ten types of `spec/spec.md` §8.2 in order, Other left empty
- [X] T014 Call `CreateDefaultRelationshipTypes` from `app/Actions/CreateAccount.php` inside the existing transaction, beside `createDefaultValueLists()`
- [X] T016 [P] Write `tests/Unit/Actions/CreateDefaultRelationshipTypesTest.php` asserting the five categories, the ten types, their positions, their keys, their behaviours and that Other is empty
- [X] T017 [P] Extend `tests/Unit/Actions/CreateAccountTest.php` so a new account comes with its relationship vocabulary

---

## Phase 3: User Story 1, read the list (Priority: P1)

**Goal**: the screen exists and reads the account's vocabulary.

**Independent test**: sign in with a new account, open `settings/account/relationship-types`, and find ten types under five categories with their roles, their direction and their badges.

- [X] T018 [US1] Create `app/ViewModels/Settings/Account/RelationshipTypes/RelationshipTypesViewModel.php`, one query for the categories with their types, exposing per row the name, the roles, the direction arrow, the supplied and not offered flags and the behaviour sentence
- [X] T019 [US1] Create `app/Http/Controllers/App/Settings/Account/RelationshipTypes/RelationshipTypeController.php` with `index()`
- [X] T020 [US1] Add the `settings.relationshipTypes.index` route to `routes/web.php` per [contracts/routes.md](contracts/routes.md)
- [X] T021 [US1] Write `resources/views/app/settings/account/relationship-types/index.blade.php`, the header, the two header buttons and the closing notice, using `x-page-header`, `x-button` and `x-notice`
- [X] T022 [US1] Write `resources/views/app/settings/account/relationship-types/_category.blade.php`, the category heading and its panel, with the empty state for a category holding nothing
- [X] T023 [US1] Write `resources/views/app/settings/account/relationship-types/_row.blade.php`, one type, with `x-badge` for supplied and not offered and the Edit button
- [X] T024 [US1] Add the Relationship types entry to the settings navigation in `app/ViewModels/Settings/SettingsViewModel.php` and its partial, with the `relationship` icon
- [X] T025 [P] [US1] Write `tests/Unit/ViewModels/Settings/Account/RelationshipTypes/RelationshipTypesViewModelTest.php`, covering grouping, order, the direction arrow, the badges and that another account's rows are absent
- [X] T026 [P] [US1] Write `tests/Feature/Controllers/App/Settings/Account/RelationshipTypes/RelationshipTypeControllerTest.php` for `index`, including the signed out redirect

---

## Phase 4: User Story 2, add a type (Priority: P2)

**Goal**: a user creates a type the product did not supply.

**Independent test**: add a directed type with two roles and find it at the end of its category, unbadged.

- [X] T027 [US2] Create `app/Actions/CreateRelationshipType.php`, trimming the values, mirroring `role_a` into `role_b` when symmetric, refusing a duplicate name, placing the type at the end of its category, never writing `key`, `is_system` or `system_behavior`
- [X] T028 [US2] Create `app/ViewModels/Settings/Account/RelationshipTypes/RelationshipTypeFormViewModel.php`, serving the category options, the direction state, the preview sentences and the availability help
- [X] T029 [US2] Add `new()` and `create()` to `RelationshipTypeController`, validating inline with array notation
- [X] T030 [US2] Add the `new` and `create` routes to `routes/web.php`
- [X] T031 [US2] Write `resources/views/app/settings/account/relationship-types/form.blade.php`, the name and category section, the direction radios through `x-option-row`, the role fields and the sticky footer
- [X] T032 [US2] Add the live preview to the form with Alpine, two sentences reading from each side, in a polite live region
- [X] T033 [P] [US2] Write `tests/Unit/Actions/CreateRelationshipTypeTest.php`, covering the symmetric mirror, the duplicate name refusal, the position and that no behaviour is granted by name or category
- [X] T034 [P] [US2] Write `tests/Unit/ViewModels/Settings/Account/RelationshipTypes/RelationshipTypeFormViewModelTest.php`
- [X] T035 [P] [US2] Extend `RelationshipTypeControllerTest` for `new` and `create`, including the validation failures

---

## Phase 5: User Story 3, change the words (Priority: P2)

**Goal**: any type can be reworded and moved, supplied ones included, without its meaning moving.

**Independent test**: rename a supplied type and one of its roles; the row reads anew and keeps its supplied badge.

- [X] T036 [US3] Create `app/Actions/UpdateRelationshipType.php`, refusing a direction change when `is_system` is true or `relationshipCount()` is above zero, never writing `key`, `is_system` or `system_behavior`
- [X] T037 [US3] Add `edit()` and `update()` to `RelationshipTypeController`
- [X] T038 [US3] Add the `edit` and `update` routes to `routes/web.php`
- [X] T039 [US3] Extend `form.blade.php` with the locked direction panel and its reason, and with the behaviour panel for a type that feeds the family tree or romantic history
- [X] T040 [P] [US3] Write `tests/Unit/Actions/UpdateRelationshipTypeTest.php`, covering the rewording of a supplied type, the refused direction change, the symmetric mirror on save, the duplicate name and the refusal across accounts
- [X] T041 [P] [US3] Extend `RelationshipTypeControllerTest` for `edit` and `update`, including another account's type returning not found

---

## Phase 6: User Story 4, stop offering a type (Priority: P3)

**Goal**: a type is retired without touching what is written.

**Independent test**: turn a supplied type off and find the not offered badge on its row and the type outside the `active()` scope.

- [X] T042 [US4] Add the availability checkbox and its help copy to `form.blade.php`, worded from whether the type is currently offered
- [X] T043 [US4] Handle `is_active` in `CreateRelationshipType` and `UpdateRelationshipType`
- [X] T044 [P] [US4] Cover the availability round trip in the two action tests and the not offered badge in `RelationshipTypesViewModelTest`
- [X] T045 [P] [US4] Cover the `active()` scope for the picker in `tests/Unit/Models/RelationshipTypeTest.php`, since the relationship picker itself is not built by this feature

---

## Phase 7: User Story 5, delete a type nothing uses (Priority: P3)

**Goal**: a user removes a type they created and never used, and is refused everywhere else with a reason.

**Independent test**: add a type, delete it, confirm, and find it gone.

- [X] T046 [US5] Create `app/Actions/DestroyRelationshipType.php`, refusing when `is_system` is true or `relationshipCount()` is above zero, soft deleting otherwise
- [X] T047 [US5] Add `destroy()` to `RelationshipTypeController` and its route to `routes/web.php`
- [X] T048 [US5] Write `resources/views/app/settings/account/relationship-types/_delete.blade.php`, the danger panel and the confirmation modal through `x-modal`, with the refusal copy for a supplied type and for one in use
- [X] T049 [P] [US5] Write `tests/Unit/Actions/DestroyRelationshipTypeTest.php`, covering the soft delete, both refusals and the refusal across accounts
- [X] T050 [P] [US5] Extend `RelationshipTypeControllerTest` for `destroy`

---

## Phase 8: User Story 6, organise the categories (Priority: P4)

**Goal**: a user adds, renames, moves and removes categories of their own.

**Independent test**: add a category, rename it, move it above another, then delete it while it is empty.

- [X] T051 [P] [US6] Create `app/Actions/CreateRelationshipCategory.php`, placing it at the end, never `is_system`
- [X] T052 [P] [US6] Create `app/Actions/UpdateRelationshipCategory.php`, refusing to rename a supplied category
- [X] T053 [P] [US6] Create `app/Actions/DestroyRelationshipCategory.php`, refusing a supplied one and refusing one that still holds a live type
- [X] T054 [P] [US6] Create `app/Actions/UpdateRelationshipCategoryPosition.php`, on the model of `UpdateEntityTypePosition`
- [X] T055 [US6] Create `app/Http/Controllers/App/Settings/Account/RelationshipTypes/RelationshipCategoryController.php` with `create()`, `update()` and `destroy()`
- [X] T056 [US6] Create `app/Http/Controllers/App/Settings/Account/RelationshipTypes/RelationshipCategoryPositionController.php`
- [X] T057 [US6] Add the four category routes to `routes/web.php`
- [X] T058 [US6] Extend `_category.blade.php` with the inline rename, the Rename and Delete links for a user category, the up and down buttons for every category and the delete confirmation naming how many types the category holds
- [X] T059 [US6] Add the Add a category button to `index.blade.php`
- [X] T060 [P] [US6] Write the four action tests under `tests/Unit/Actions/`, one file each, covering the refusals and the reordering
- [X] T061 [P] [US6] Write `tests/Feature/Controllers/App/Settings/Account/RelationshipTypes/RelationshipCategoryControllerTest.php` and `RelationshipCategoryPositionControllerTest.php`, including another account's category returning not found

---

## Phase 9: User Story 7, reorder the types (Priority: P4)

**Goal**: a type moves inside its category.

**Independent test**: drag a type to the top of its category, reload, and find the order held.

- [X] T062 [US7] Create `app/Actions/UpdateRelationshipTypePosition.php`, one-based, renumbering the siblings of the category
- [X] T063 [US7] Create `app/Http/Controllers/App/Settings/Account/RelationshipTypes/RelationshipTypePositionController.php` and add its route
- [X] T064 [US7] Add the drag handle through `sortableRows` to `_row.blade.php`, per [research.md](research.md) decision 5
- [X] T065 [P] [US7] Write `tests/Unit/Actions/UpdateRelationshipTypePositionTest.php`, covering a move up, a move down and a move that lands out of range
- [X] T066 [P] [US7] Write `tests/Feature/Controllers/App/Settings/Account/RelationshipTypes/RelationshipTypePositionControllerTest.php`

---

## Phase 10: Polish and cross-cutting concerns

- [X] T067 Run `php artisan monica:localize` and translate everything it reports, in all nine locales, then run `php artisan monica:localize --check`
- [X] T068 Walk both screens with the keyboard alone and with a screen reader, zoomed to 200%, in light and dark, and confirm no meaning is carried by colour alone
- [X] T069 Rename the two model notes to `-done.md`, record the translation key columns in their field lists, and update `spec/models/README.md`
- [X] T070 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --memory-limit=2G` and fix what they report
- [X] T071 Run the full suite once, per [quickstart.md](quickstart.md)

---

## Dependencies

- Phase 1 blocks everything. Phase 2 blocks every story.
- US1 blocks US2, US4, US5 and US7, which all render into its views. US1 blocks US6 for the same reason.
- US2 blocks US3, US4 and US5: they extend the form and the actions it introduces.
- US6 and US7 are independent of each other.
- T012 blocks T036 and T046.
- Phase 10 comes last, and T067 must follow every task that adds copy.

## Parallel opportunities

- T003 to T011 run together once T001 and T002 are applied.
- T016 and T017 run together.
- Every test task marked [P] runs beside the others in its phase.
- T051 to T054 are four separate files and run together.

## Implementation strategy

The MVP is Phase 1, Phase 2 and US1: a seeded, readable vocabulary. It is worth shipping on its own, because it is what a user needs to know before they change anything. US2 and US3 make it editable and are the natural second release. US4 to US7 are comfort and can land one at a time.
