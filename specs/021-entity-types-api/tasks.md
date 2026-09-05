# Tasks: Entity type endpoints

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[contracts/entity-types.md](contracts/entity-types.md), [quickstart.md](quickstart.md)

**Format**: `- [ ] [ID] [P?] [Story?] description with the file path`. `[P]` means a different file with
nothing incomplete in front of it.

**Tests are not optional here.** Principle III makes a test part of the change rather than a decision,
so every phase carries its own.

**Shape of this file**: Principle XI asks for one line per task, phase headings and the dependency list,
so the template's parallel examples, strategy and notes sections are left out.

## Phase 1: Setup (shared infrastructure)

**Purpose**: the object all seven endpoints answer with.

- [X] T001 Create EntityTypeResource in `app/Http/Resources/EntityTypeResource.php`, with the fields and the withheld columns of [contracts/entity-types.md](contracts/entity-types.md), `id` the uuid and the timestamps as Unix integers

## Phase 2: Foundational (blocking prerequisites)

**None.** Every operation already has an action, tested, and no shipped file changes behaviour, so there is
nothing to move before the stories can start. This is the whole reason the change is small.

## Phase 3: User Story 1 (P1) - Read the kinds of object an account invented 🎯 MVP

**Goal**: list an account's types in navigation order, inactive ones included, and read one by uuid.

**Independent test**: on an account holding an active type, an inactive one and one that already holds
records, the list comes back in position order with the counts right, and one type reads back by uuid.

- [X] T002 [US1] Add `index` to `app/Http/Controllers/Api/EntityTypeController.php`: account scoped, ordered by `position` then `id`, `per_page` clamped to `monica.api_maximum_items_per_page`, answering hand built `data` and `meta`
- [X] T003 [US1] Add `show` and the private `find` to the same file, looking the type up by uuid within the caller's account and letting `firstOrFail` give the 404
- [X] T004 [US1] Register the two GET routes in `routes/api.php` inside the sanctum group, the parameter constrained to `[0-9a-fA-F-]{36}`, named `entityTypes` and `entityTypes.show`
- [X] T005 [US1] Create `resources/docs/api/10-entity-types.php` with the entity type object, the paging parameters and the two GET sections, in the same commit as T004 or `ApiDocsViewModelTest` fails both ways
- [X] T006 [US1] Create `tests/Feature/Controllers/Api/EntityTypeControllerTest.php` covering the shape, position order with inactive types among the rest, paging and clamping, an account holding no type, and 404 for another account's type, a deleted type and a slug sent in place of the uuid
- [X] T007 [US1] Assert the new sidebar group in `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php`

**Checkpoint**: a caller can report what an account keeps track of. This is the MVP.

## Phase 4: User Story 2 (P2) - Declare a type, correct it, remove it

**Goal**: declare a type from its two names, reword it later, and delete one that holds nothing.

**Independent test**: declare a type with names alone, correct its wording, then delete it, and confirm the
slug is derived, survives the rename, and stays taken afterwards.

- [X] T008 [US2] Add `create` to `app/Http/Controllers/Api/EntityTypeController.php`, requiring both names and accepting `description`, `icon`, `colour`, `is_active` and `slug`, with the slug rules of the web controller including the unique check that counts deleted types
- [X] T009 [US2] Add `update` to the same file with both names, `icon` and `colour` required and `description` `present` and nullable, so no field is erased by omission, and `slug` neither read nor written
- [X] T010 [US2] Add `destroy` to the same file, catching `CannotDeleteAnEntityTypeInUse` and answering 422 with its message
- [X] T011 [US2] Register the three routes in `routes/api.php`, named `entityTypes.create`, `entityTypes.update` and `entityTypes.destroy`
- [X] T012 [US2] Add the three sections to `resources/docs/api/10-entity-types.php`, in the same commit as T011
- [X] T013 [US2] Extend `tests/Feature/Controllers/Api/EntityTypeControllerTest.php` with a derived slug, a chosen slug, a declaration that is inactive from the start, the icon, colour and slug refusals, a slug held by a deleted type refused, the correction replacing while the slug, position and count stay, a correction missing `description` refused, deletion, the in-use refusal, and the 404s on all three

**Checkpoint**: an account's whole vocabulary can be built and reworded through the API alone.

## Phase 5: User Story 3 (P3) - Decide where a type sits and whether it is still offered

**Goal**: the two gestures that are not corrections to wording, each at its own address.

**Independent test**: stop a type being offered and offer it again, then move it up the order, and confirm
what is filed under it is untouched and every type is renumbered from one.

- [X] T014 [P] [US3] Create `app/Http/Controllers/Api/EntityTypeActivationController.php` with `update`, requiring `is_active` as the state wanted rather than a toggle, calling `ActivateEntityType` or `DeactivateEntityType`
- [X] T015 [P] [US3] Create `app/Http/Controllers/Api/EntityTypePositionController.php` with `update`, requiring `position` as an integer of at least 1, calling `UpdateEntityTypePosition`
- [X] T016 [US3] Register the two routes in `routes/api.php`, named `entityTypes.activation.update` and `entityTypes.position.update`
- [X] T017 [US3] Add the two sections to `resources/docs/api/10-entity-types.php`, in the same commit as T016
- [X] T018 [P] [US3] Create `tests/Feature/Controllers/Api/EntityTypeActivationControllerTest.php` covering both states, the same state sent twice, the position and the records untouched by either, and the 404s
- [X] T019 [P] [US3] Create `tests/Feature/Controllers/Api/EntityTypePositionControllerTest.php` covering a move to the front, a position past the end landing last, every type renumbered from one with inactive types counted, `position` below 1 refused, and the 404s

**Checkpoint**: a type managed through the API is indistinguishable from one managed in the browser.

## Phase 6: Polish

- [X] T020 Run `php artisan monica:localize` and translate anything it reports, in all nine locales
- [X] T021 [P] Run `vendor/bin/pint --dirty --format agent`
- [X] T022 [P] Run `vendor/bin/phpstan analyse --memory-limit=2G` and fix rather than ignore
- [X] T023 Walk [quickstart.md](quickstart.md) end to end against a running instance, including everything under "What must fail"

## Dependencies

- T001 blocks every controller task, because the resource is what each of them answers with.
- Within each story the order is controller, then route, then documentation, then test. T005, T012 and T017 must each land in the same commit as the routes above them, because `ApiDocsViewModelTest` compares the reference against the route table in both directions and either half alone fails it.
- T012 and T017 both edit `resources/docs/api/10-entity-types.php`, which T005 creates, so those three are serial across the stories. Nothing else is.
- T008, T009 and T010 all edit `EntityTypeController`, so they are serial with each other and with T002 and T003. T014 and T015 are the only genuinely parallel controllers.
- Stories are otherwise independent: US2 and US3 need only T001, and could be built beside US1.
- Phase 6 depends on whichever stories are being shipped. T020 is expected to report nothing: no new user-facing string is introduced, and the one refusal carries the exception's own English text.

## Suggested MVP

Phases 1 and 3. A caller can then read every type an account invented, which is the story that hands over
the uuids the other two need.
