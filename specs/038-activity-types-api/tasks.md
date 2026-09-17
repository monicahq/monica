# Tasks: Activity type and category endpoints

**Input**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [contracts/endpoints.md](./contracts/endpoints.md)

**Tests**: required. Constitution III is non-negotiable, and the `api-writer` skill fixes the cases.

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means a different file and no
unfinished dependency.

## Phase 1: Setup

- [X] T001 Rename `resources/docs/api/19-mcp.php` to `resources/docs/api/20-mcp.php` so the new group sits beside the categories at 18 and the MCP guide stays last

## Phase 2: Foundational (blocks every story)

- [X] T002 [P] Add the `activityTypes()` HasMany, with its `@return HasMany<ActivityType, $this>` docblock, to `app/Models/Account.php`
- [X] T003 [P] Create `app/Http/Resources/ActivityTypeResource.php` with the attributes and `links.self` named in `contracts/endpoints.md`
- [X] T004 Add `it_has_many_activity_types` to `tests/Unit/Models/AccountTest.php`
- [X] T005 Create `resources/docs/api/19-activity-types.php` with the group name, its description, the shared example objects and an empty `sections` list

## Phase 3: User Story 1 — Read what an account records activities as (P1) 🎯 MVP

**Goal**: a caller can list every type, list the types of one category, and read one by uuid.

**Independent test**: list the types of a fresh account, list one category's types, read one back, and confirm a supplied name reads in the caller's language and another account's type answers 404.

- [X] T006 [US1] Create `app/Http/Controllers/Api/ActivityTypeController.php` with `index` and `show`, paging as `ActivityCategoryController::index` does and ordering by category then position then id
- [X] T007 [P] [US1] Create `app/Http/Controllers/Api/ActivityCategoryTypeController.php` with `index`, mirroring `RelationshipCategoryTypeController`
- [X] T008 [US1] Register `activityTypes`, `activityTypes.show` and `activityCategories.types` in `routes/api.php`, uuid-constrained, imports in alphabetical order
- [X] T009 [US1] Document those three endpoints in `resources/docs/api/19-activity-types.php`, with `returnFields`, `queryParams`, `pathParams`, `rateLimit` and a worked `response`
- [X] T010 [US1] Correct the four passages in `resources/docs/api/18-activity-categories.php` that say activity natures are not built yet, at lines 58, 98, 157 and 178
- [X] T011 [US1] Document `activity-categories-types` in `resources/docs/api/18-activity-categories.php`
- [X] T012 [P] [US1] Create `tests/Feature/Controllers/Api/ActivityTypeControllerTest.php` covering list, order, another account's types absent, show, another account's type 404, a non-uuid path 404, and a supplied name read in the caller's language
- [X] T013 [P] [US1] Create `tests/Feature/Controllers/Api/ActivityCategoryTypeControllerTest.php` covering one category's types, retired ones included, and another account's category 404

**Checkpoint**: the vocabulary is readable, and every category reports a true count.

## Phase 4: User Story 2 — Add a type, reword it, remove it (P2)

**Goal**: a caller can build and correct the vocabulary from a script.

**Independent test**: add a type, reword it, move it to another category, delete it, and confirm a supplied type refuses deletion.

- [X] T014 [US2] Add `create`, `update` and `destroy` to `app/Http/Controllers/Api/ActivityTypeController.php`, calling `CreateActivityType`, `UpdateActivityType` and `DestroyActivityType`, and converting the policy's `AuthorizationException` to a 422 the way `ActivityCategoryController::destroy` does
- [X] T015 [US2] Register `activityTypes.create`, `activityTypes.update` and `activityTypes.destroy` in `routes/api.php`
- [X] T016 [US2] Document those three endpoints in `resources/docs/api/19-activity-types.php`, including the refusal a supplied type answers with
- [X] T017 [US2] Extend `tests/Feature/Controllers/Api/ActivityTypeControllerTest.php` with create, an empty name refused, a category of another account refused, reword, a move landing last in the arriving category, delete soft, and a supplied type refused

**Checkpoint**: the vocabulary is editable, and nothing recorded is destroyed.

## Phase 5: User Story 3 — Decide what is offered and in which order (P3)

**Goal**: retiring a type and moving it are each their own gesture.

**Independent test**: retire a type and offer it again, move it inside its category, and confirm the activities recorded with it are untouched.

- [X] T018 [P] [US3] Create `app/Http/Controllers/Api/ActivityTypeActivationController.php` with `update`, calling `ActivateActivityType` or `DeactivateActivityType` from the `is_active` sent
- [X] T019 [P] [US3] Create `app/Http/Controllers/Api/ActivityTypePositionController.php` with `update`, mirroring `ActivityCategoryPositionController`
- [X] T020 [US3] Register `activityTypes.activation.update` and `activityTypes.position.update` in `routes/api.php`
- [X] T021 [US3] Document both endpoints in `resources/docs/api/19-activity-types.php`
- [X] T022 [P] [US3] Create `tests/Feature/Controllers/Api/ActivityTypeActivationControllerTest.php` covering retire, offer again, the same state sent twice, and another account's type 404
- [X] T023 [P] [US3] Create `tests/Feature/Controllers/Api/ActivityTypePositionControllerTest.php` covering a move, renumbering from one with retired types counted, a place past the end landing last, and another account's type 404

**Checkpoint**: every gesture the settings screens offer is reachable except the fields.

## Phase 6: User Story 4 — Read and rewrite what an activity of one nature asks for (P4)

**Goal**: a caller can read a type's custom fields and send a new layout.

**Independent test**: read a supplied type's fields, send a new layout, read it back, and confirm an activity recorded beforehand still reads as it was written.

- [X] T024 [US4] Create `app/Http/Controllers/Api/ActivityTypeFieldController.php` with `index`, answering `StructureVersionSectionResource` over the sections of the type's published version, empty rather than 404
- [X] T025 [US4] Add `update` to it, validating the nested body and turning each group's index into the `reference` `ReplaceActivityTypeFields` takes
- [X] T026 [US4] Register `activityTypes.fields` and `activityTypes.fields.update` in `routes/api.php`
- [X] T027 [US4] Document both endpoints in `resources/docs/api/19-activity-types.php`, with a worked layout in both directions
- [X] T028 [US4] Create `tests/Feature/Controllers/Api/ActivityTypeFieldControllerTest.php` covering an empty layout, a written layout read back, a kept field matched rather than replaced, an unknown field type refused leaving the layout untouched, and another account's type 404

**Checkpoint**: principle X is discharged for activity types.

## Phase 7: Polish

- [X] T029 Run `php artisan monica:localize` and translate whatever it reports as missing, in every configured locale
- [X] T030 Run `vendor/bin/pint --dirty --format agent`
- [X] T031 Run `vendor/bin/phpstan analyse --no-progress` and clear what level 5 reports
- [X] T032 Run `php artisan test --compact` and confirm the three reference tests agree in both directions

## Dependencies

- T001 before T005, so the new group is numbered into a directory the rename has already settled.
- T002 before T004 and before T006. T003 before T006, T007, T018, T019.
- **Routes and their reference sections ship together.** `ApiDocsViewModelTest` fails on a route no file documents, and `BuildOpenApiDocumentTest` fails in both directions, so T008 with T009, T015 with T016, T020 with T021, T026 with T027. Splitting either pair across a commit leaves the suite red.
- T010 and T011 belong to US1 because scenario 4 of that story is the count a category reports.
- Stories are otherwise independent and may be taken in any order after Phase 2, though T014, T018, T019, T024 all read the controller or resource Phase 2 creates.
- Phase 7 after whichever stories ship.

**MVP**: Phase 1, Phase 2 and Phase 3. That is a readable vocabulary, a corrected reference, and the OpenAPI document describing it.

**Parallel**: T002 with T003; T012 with T013; T018 with T019; T022 with T023. Every other pair inside a phase touches `routes/api.php`, the controller or the one reference file.
