# Tasks: Relationship category and type endpoints

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[contracts/relationship-categories.md](contracts/relationship-categories.md),
[contracts/relationship-types.md](contracts/relationship-types.md), [quickstart.md](quickstart.md)

**Format**: `- [ ] [ID] [P?] [Story?] description with the file path`. `[P]` means a different file with
nothing incomplete in front of it.

**Tests are not optional here.** Principle III makes a test part of the change rather than a decision,
so every phase carries its own.

**Shape of this file**: Principle XI asks for one line per task, phase headings and the dependency list,
so the template's parallel examples, strategy and notes sections are left out.

## Phase 1: Setup (shared infrastructure)

**Purpose**: the two objects all fifteen endpoints answer with.

- [X] T001 [P] Create RelationshipCategoryResource in `app/Http/Resources/RelationshipCategoryResource.php` with the fields of [contracts/relationship-categories.md](contracts/relationship-categories.md), `id` the uuid, `type_count` read from a loaded `types_count` and the timestamps as Unix integers
- [X] T002 [P] Create RelationshipTypeResource in `app/Http/Resources/RelationshipTypeResource.php` with the fields of [contracts/relationship-types.md](contracts/relationship-types.md), `relationship_category_id` the category's uuid rather than its id, `system_behavior` the enum's value or null, and `relationship_count` read from a loaded `relationships_count`

## Phase 2: Foundational (blocking prerequisites)

**None.** Every gesture US1, US2 and US3 need already has a tested action, and no shipped file changes
behaviour. The four actions US4 needs block US4 alone, so they sit in its phase rather than here.

## Phase 3: User Story 1 (P1) - Read the vocabulary an account uses 🎯 MVP

**Goal**: list an account's categories and types in picker order, retired ones included, and read one of
either by uuid.

**Independent test**: on a freshly created account, the five categories and ten types come back grouped and
ordered, `Parent / Child` carries `genealogy_parentage`, the supplied wording reads in the language asked
for, and one of each reads back by uuid.

- [X] T003 [US1] Add `index` and `show` with a private `find` to `app/Http/Controllers/Api/RelationshipCategoryController.php`: account scoped, ordered by `position` then `id`, `withCount('types')`, `per_page` clamped to `monica.api_maximum_items_per_page`, answering hand built `data` and `meta`, `firstOrFail` giving the 404
- [X] T004 [US1] Add `index` and `show` with a private `find` to `app/Http/Controllers/Api/RelationshipTypeController.php`, joined to the category so the order is the category's `position`, then the type's, then `id`, with `withCount('relationships')`
- [X] T005 [P] [US1] Create `app/Http/Controllers/Api/RelationshipCategoryTypeController.php` with `index`, resolving the category by uuid within the caller's account first so a foreign category answers 404 before any type is read, then answering that category's types alone
- [X] T006 [US1] Register the five GET routes in `routes/api.php` inside the sanctum group, every parameter constrained to `[0-9a-fA-F-]{36}`, named `relationshipCategories`, `relationshipCategories.show`, `relationshipCategories.types`, `relationshipTypes` and `relationshipTypes.show`
- [X] T007 [US1] Create `resources/docs/api/16-relationship-categories.php` with the category object, the paging parameters and its three GET sections, in the same commit as T006 or `ApiDocsViewModelTest` fails both ways
- [X] T008 [US1] Create `resources/docs/api/17-relationship-types.php` with the type object, the paging parameters and its two GET sections, in the same commit as T006
- [X] T009 [P] [US1] Create `tests/Feature/Controllers/Api/RelationshipCategoryControllerTest.php` covering the shape, position order with retired categories among the rest, `type_count`, paging and clamping, and 404 for another account's category and a deleted one
- [X] T010 [P] [US1] Create `tests/Feature/Controllers/Api/RelationshipTypeControllerTest.php` covering the shape, the order across categories, `relationship_count`, a supplied name reading through the translator under `Accept-Language` and a reworded one reading the user's words in every language, and the 404s
- [X] T011 [P] [US1] Create `tests/Feature/Controllers/Api/RelationshipCategoryTypeControllerTest.php` covering one category's types in position order, an empty category answering an empty list, and 404 for another account's category
- [X] T012 [US1] Assert the two new sidebar groups in `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php`

**Checkpoint**: a caller can report the words an account uses about the people in it. This is the MVP.

## Phase 4: User Story 2 (P2) - Add a relationship type, reword it, remove it

**Goal**: add a type the product did not supply, reword any type including a supplied one, and delete one
that nothing uses.

**Independent test**: add a directed type, reword it, then delete it, and confirm the deletion is refused
once a relationship uses it and once the type is supplied.

- [X] T013 [US2] Add `create` to `app/Http/Controllers/Api/RelationshipTypeController.php`, resolving `relationship_category_id` as a uuid within the account, requiring `name` and `role_a`, requiring `role_b` unless `is_symmetric`, calling `CreateRelationshipType` and catching `RelationshipTypeNameIsAlreadyUsed` to answer 422 with its message
- [X] T014 [US2] Add `update` to the same file passing the type's current `is_active` so a rewording never retires it, and its current `is_symmetric` when none is sent so the direction holds, catching `RelationshipTypeNameIsAlreadyUsed` and `CannotChangeARelationshipTypeDirection` and answering 422 with each message
- [X] T015 [US2] Add `destroy` to the same file, catching `CannotDeleteASuppliedRelationshipType` and `CannotDeleteARelationshipTypeInUse` and answering 422 with each message
- [X] T016 [US2] Register the three routes in `routes/api.php`, named `relationshipTypes.create`, `relationshipTypes.update` and `relationshipTypes.destroy`
- [X] T017 [US2] Add the three sections to `resources/docs/api/17-relationship-types.php`, in the same commit as T016
- [X] T018 [US2] Extend `tests/Feature/Controllers/Api/RelationshipTypeControllerTest.php` with a symmetric type copying its single role to both sides, a directed type missing `role_b` refused, a duplicate name refused, a name or role of only spaces refused, a category of another account answering 404, the rewording leaving `is_active`, `position`, `is_system` and `system_behavior` alone, a reworded directed type keeping role A on person A, a direction change refused for a supplied type and for one in use and accepted for a user-created unused one, deletion, both deletion refusals, and the 404s on all three

**Checkpoint**: a vocabulary can be carried over from another tool without opening a browser.

## Phase 5: User Story 3 (P3) - Organise the categories

**Goal**: add a grouping, rename it, and remove it once it holds nothing.

**Independent test**: add a category, rename it, then delete it while empty, and confirm the deletion is
refused while it still holds a type and that a supplied category refuses both rename and deletion.

- [X] T019 [US3] Add `create` and `update` to `app/Http/Controllers/Api/RelationshipCategoryController.php`, requiring `name`, calling `CreateRelationshipCategory` and `UpdateRelationshipCategory`, catching `CannotChangeASuppliedRelationshipCategory` and answering 422 with its message
- [X] T020 [US3] Add `destroy` to the same file, catching `CannotChangeASuppliedRelationshipCategory` and `CannotDeleteARelationshipCategoryInUse` and answering 422 with each message
- [X] T021 [US3] Register the three routes in `routes/api.php`, named `relationshipCategories.create`, `relationshipCategories.update` and `relationshipCategories.destroy`
- [X] T022 [US3] Add the three sections to `resources/docs/api/16-relationship-categories.php`, in the same commit as T021
- [X] T023 [US3] Extend `tests/Feature/Controllers/Api/RelationshipCategoryControllerTest.php` with a category placed last and offered when added, a name of only spaces refused, the rename, a supplied category refusing rename and deletion, deletion of an empty user-created one, deletion refused while it holds a type, and the 404s on all three

**Checkpoint**: the list can be grouped the way the account wants it.

## Phase 6: User Story 4 (P4) - Decide what is offered and in which order

**Goal**: the two gestures that are not corrections to wording, each at its own address, for both records.

**Independent test**: stop a type being offered and offer it again, then move it inside its category, and
confirm the relationships using it are untouched and every position is renumbered from one. Retire a
category and confirm it and its types leave the relationship picker.

- [X] T024 [P] [US4] Create `app/Actions/ActivateRelationshipType.php` and `app/Actions/DeactivateRelationshipType.php` following `ActivateEntityType`: refuse a type of another account with `ModelNotFoundException`, set `is_active`, and dispatch `LogUserAction` on the `low` queue
- [X] T025 [P] [US4] Create `app/Actions/ActivateRelationshipCategory.php` and `app/Actions/DeactivateRelationshipCategory.php` the same way
- [X] T026 [US4] Add `RelationshipTypeActivated`, `RelationshipTypeDeactivated`, `RelationshipCategoryActivated` and `RelationshipCategoryDeactivated` to `app/Enums/UserActionEnum.php` with a journal sentence each in the `description()` match, worded as the entity type pair is
- [X] T027 [P] [US4] Create `tests/Unit/Actions/ActivateRelationshipTypeTest.php`, `DeactivateRelationshipTypeTest.php`, `ActivateRelationshipCategoryTest.php` and `DeactivateRelationshipCategoryTest.php`, each asserting the column, the queued log and the cross-account `ModelNotFoundException`
- [X] T028 [P] [US4] Create `app/Http/Controllers/Api/RelationshipTypeActivationController.php` with `update`, requiring `is_active` as the state wanted rather than a toggle, doing nothing when it already holds
- [X] T029 [P] [US4] Create `app/Http/Controllers/Api/RelationshipCategoryActivationController.php` the same way
- [X] T030 [P] [US4] Create `app/Http/Controllers/Api/RelationshipTypePositionController.php` with `update`, requiring `position` as an integer of at least 1, calling `UpdateRelationshipTypePosition`, which renumbers inside the category
- [X] T031 [P] [US4] Create `app/Http/Controllers/Api/RelationshipCategoryPositionController.php` the same way, calling `UpdateRelationshipCategoryPosition`, which renumbers across the account
- [X] T032 [US4] Register the four routes in `routes/api.php`, named `relationshipTypes.activation.update`, `relationshipTypes.position.update`, `relationshipCategories.activation.update` and `relationshipCategories.position.update`
- [X] T033 [US4] Add the two sections to each of `resources/docs/api/16-relationship-categories.php` and `resources/docs/api/17-relationship-types.php`, in the same commit as T032
- [X] T034 [P] [US4] Create `tests/Feature/Controllers/Api/RelationshipTypeActivationControllerTest.php` and `RelationshipCategoryActivationControllerTest.php` covering both states, the same state sent twice answering 200 and not inverting, a supplied record retiring like any other, the wording and position untouched, the relationships still readable, and the 404s
- [X] T035 [P] [US4] Create `tests/Feature/Controllers/Api/RelationshipTypePositionControllerTest.php` and `RelationshipCategoryPositionControllerTest.php` covering a move to the front, a position past the end landing last, renumbering from one with retired records counted, a type renumbering only inside its category, `position` below 1 refused, and the 404s
- [X] T036 [US4] Run `php artisan monica:localize` and translate the four new journal sentences in all 31 locales, following `.claude/rules/translations.md` for the typography and the glossary of each

**Checkpoint**: a vocabulary managed through the API is indistinguishable from one managed in the browser, and one gesture more is reachable than any screen offers.

## Phase 7: Polish

- [X] T037 Run `php artisan monica:localize --check` and fix what it reports
- [X] T038 [P] Run `vendor/bin/pint --dirty --format agent`
- [X] T039 [P] Run `vendor/bin/phpstan analyse` and fix rather than ignore
- [X] T040 Walk [quickstart.md](quickstart.md) end to end against a running instance, including every refusal in section 3

## Dependencies

- T001 and T002 block every controller task, because the resources are what each of them answers with. They are the only two tasks of Phase 1 and they are parallel.
- Within each story the order is controller, then route, then documentation, then test. T007, T008, T017, T022 and T033 must each land in the same commit as the routes above them, because `ApiDocsViewModelTest` compares the reference against the route table in both directions and either half alone fails it.
- `resources/docs/api/16-relationship-categories.php` is created by T007 and edited by T022 and T033; `17-relationship-types.php` is created by T008 and edited by T017 and T033. Each file is therefore serial across the stories, and T033 touches both.
- T003, T019 and T020 all edit `RelationshipCategoryController`, so they are serial. T004, T013, T014 and T015 all edit `RelationshipTypeController`, so they are serial. The seven single gesture controllers are the genuinely parallel ones.
- T026 blocks T024 and T025, which name its cases, and all three block T027 and the two activation controllers.
- T036 depends on T026 alone, and it is what keeps US4 shippable on its own: the four sentences are the only new user-facing copy in the feature.
- Stories are otherwise independent: US2, US3 and US4 need only T001 and T002, and could be built beside US1.
- Phase 7 depends on whichever stories are being shipped. T037 reports nothing unless US4 shipped, since no other story introduces a string.

## Suggested MVP

Phases 1 and 3. A caller can then read an account's whole vocabulary, which is the story that hands over the
uuids the other three need.
