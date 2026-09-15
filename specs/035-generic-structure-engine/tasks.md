---
description: "Task list for the generic structure engine"
---

# Tasks: Generic structure engine

**Input**: `spec.md`, `plan.md`, `research.md`, `data-model.md`, `quickstart.md`

**Tests**: required, not optional. Constitution principle III is non-negotiable, so every task that
changes behaviour carries its test and no class is renamed without its test file moving with it.

**Length**: principle XI asks for one line per task, phase headings and a dependency list, and
nothing else. The mechanical renames are therefore grouped by layer rather than split per file: each
one is a single find, replace and move across a named directory, proved by the suite and by the grep
in `quickstart.md` step 1. Only classes that change shape get a line of their own.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: different files, no dependency on an unfinished task
- **[Story]**: US1, US2, US3 from `spec.md`

---

## Phase 1: Settle the written record

**Purpose**: the target is defined in `spec/models` before any of it is built. These are the nine
contradictions in `research.md`.

- [X] T001 [P] Write `spec/models/056-structure-done.md` for Structure, saying why it sorts after 055 the way 055 already does
- [X] T002 [P] Rewrite `spec/models/013-structure-version-done.md` as StructureVersion, dropping the assumption that a version belongs to a template
- [X] T003 [P] Rewrite `spec/models/016-structure-version-section-done.md` as StructureVersionSection
- [X] T004 [P] Rewrite `spec/models/055-structure-version-page-done.md` as StructureVersionPage
- [X] T005 [P] Edit `spec/models/012-template-in-progress.md`: `published_version_id`, `applies_to` and `entity_type_id` move to the structure, D2
- [X] T006 [P] Rewrite the ActivityType section of `spec/models/035-activity-type-not-yet.md`: no `template_version_id`, the structure names the type, D1
- [X] T007 [P] Edit `spec/models/018-vault-default-template-done.md`, `019-contact-done.md`, `020-entity-done.md` and `023-record-snapshot-done.md` for `structure_version_id`
- [X] T008 [P] Edit `spec/models/047-marketplace-installation-done.md` for the third installable kind, D3
- [X] T009 Edit `spec/models/README.md`: the new model, its number and its place in the dependency order
- [X] T010 [P] Amend `.specify/memory/constitution.md` principle VI to say structure version, with a Sync Impact Report and a PATCH bump, 2.3.0 to 2.3.1
- [X] T011 [P] Edit `spec/spec.md` sections 5.2, 5.3, 6 and 9.3: the engine's name, and the asymmetry that an activity type has one structure account wide while an entity type may have several templates

**Checkpoint**: the target model is written down and reviewable before a line of code moves.

---

## Phase 2: Foundational, the database and the models

**Purpose**: blocks every story. The database is rebuilt with `migrate:fresh`, so create migrations
are edited in place and no rename migration is written.

- [X] T012 Add `database/migrations/2026_08_09_000000_create_structures_table.php` with `owner_type`, `owner_id`, `applies_to`, `entity_type_id`, `published_version_id`, unique on the owner pair, per `data-model.md`
- [X] T013 Rename `2026_08_09_000001_create_template_versions_table.php` to `create_structure_versions_table`, `template_id` becoming `structure_id`, and move the `published_version_id` foreign key onto `structures` with `Schema::table()`
- [X] T014 Rename `2026_08_09_000004_create_template_version_pages_table.php` to `create_structure_version_pages_table`
- [X] T015 Rename `2026_08_09_000005_create_template_version_sections_table.php` to `create_structure_version_sections_table`, both keys renamed
- [X] T016 Edit `2026_08_09_000000_create_templates_table.php`: drop `published_version_id`, `applies_to` and `entity_type_id`, keep the rest
- [X] T017 [P] Rename `template_version_id` to `structure_version_id` in the create migrations of `contacts`, `entities`, `record_snapshots` and `vault_default_templates`
- [X] T018 Add `database/migrations/2026_09_13_000001_create_activity_types_table.php` per `spec/models/035`, without any structure column
- [X] T019 Replace `app/Enums/TemplateAppliesToEnum.php` with `StructureAppliesToEnum` carrying the third case, Activity
- [X] T020 Add `app/Models/Structure.php` with `morphTo owner`, `publishedVersion`, `versions` and an `owner()` that reads as null once its owner is soft deleted
- [X] T021 Rename `app/Models/TemplateVersion.php` to `StructureVersion.php`, `template()` becoming `structure()`, keeping every booted guard
- [X] T022 [P] Rename `app/Models/TemplateVersionPage.php` to `StructureVersionPage.php`
- [X] T023 [P] Rename `app/Models/TemplateVersionSection.php` to `StructureVersionSection.php`
- [X] T024 Add `app/Models/ActivityType.php` with `morphOne Structure` and `belongsTo ActivityCategory`
- [X] T025 Edit `app/Models/Template.php`: `morphOne Structure`, versions and published version read through it, `applies_to` and `entityType` through it
- [X] T026 [P] Edit `app/Models/Contact.php`, `Entity.php`, `RecordSnapshot.php` and `VaultDefaultTemplate.php` for `structureVersion()`
- [X] T027 [P] Edit `app/Models/SectionVersion.php`: `templateVersions()` becomes `structureVersions()`, and the `usage_count` docblock counts structure versions
- [X] T028 [P] Edit `app/Models/MarketplaceInstallation.php` for the third installable kind, D3
- [X] T029 [P] Rename the four template version factories in `database/factories/` and add `StructureFactory` and `ActivityTypeFactory`
- [X] T030 [P] Rename the model tests in `tests/Unit/Models/` to match, and add `StructureTest` and `ActivityTypeTest` covering the owner morph and the one-owner index
- [X] T031 Run `php artisan migrate:fresh --seed --quiet` and confirm the schema matches `data-model.md`

**Checkpoint**: the engine exists under its own name and nothing above it compiles yet.

---

## Phase 3: User Story 1, contacts and entities notice nothing (P1)

**Goal**: every surface built on the engine speaks the new name and behaves exactly as before.

**Independent test**: the template, contact, entity, vault default and snapshot suites pass with no
change of meaning, and `quickstart.md` step 2 is green.

- [X] T032 [US1] Rename the 26 actions in `app/Actions/` that name a template version, `CreateTemplateVersionDraft` and `PublishTemplateVersion` first since the rest read them
- [X] T033 [US1] Edit `app/Actions/CreateTemplate.php`, `DuplicateTemplate`, `ImportTemplate` and `app/Jobs/CreateDefaultTemplate.php` so each creates the structure it used to write columns for. `DestroyTemplate` is deliberately left alone: the delete is soft and the structure has to survive a restore
- [X] T034 [P] [US1] Rename the matching action tests in `tests/Unit/Actions/`
- [X] T035 [US1] Rename the 14 controllers in `app/Http/Controllers/App/Settings/Account/Templates/` and the 6 in `App/Vaults/`
- [X] T036 [US1] Rename the 11 API controllers in `app/Http/Controllers/Api/`, `TemplateStructureController` becoming `TemplateVersionScopedController` per `research.md`
- [X] T037 [US1] Edit `routes/web.php` and `routes/api.php`: bindings and controller imports only, every path and route name unchanged per D5
- [X] T038 [P] [US1] Rename the three resources in `app/Http/Resources/`. `ContactResource` and the three `type` discriminators take the structure spelling too, reversing the first version of D5
- [X] T039 [P] [US1] Edit the 13 view models under `app/ViewModels/`
- [X] T040 [P] [US1] Edit the 6 Blade files under `resources/views/` that name a template version
- [X] T041 [P] [US1] Edit `app/Helpers/ShareLinkSummary.php`, `app/Jobs/CreateDefaultTemplate.php`, `app/Http/Requests/SaveFieldValuesRequest.php` and `app/Exceptions/SectionIsAlreadyInTheTemplateVersion.php`
- [X] T042 [US1] Edit `app/Enums/UserActionEnum.php` per D4: no case renamed or removed, the changelog being append only
- [X] T043 [US1] Rename and edit the test files in the 16 directories listed in `plan.md`, including the DAV fixtures
- [X] T044 [US1] Add the SC-002 cases, in `tests/Unit/Actions/PublishingLeavesRecordsWhereTheyAreTest.php`: publishing moves no existing contact and no existing entity, and their values stay readable

**Checkpoint**: contacts and entities behave as they did, under the new name.

---

## Phase 4: User Story 2, an activity type owns a structure (P1)

**Goal**: creating an activity type creates its structure through the same actions a template uses.

**Independent test**: `quickstart.md` step 3 returns `true` then `1`.

- [X] T045 [US2] Add `app/Actions/CreateActivityType.php`: save the type, create its structure, publish the first version with one page and no sections
- [X] T046 [US2] Add `app/Actions/DestroyActivityType.php`, refusing a supplied type and leaving the structure in place like T033, since the delete is soft
- [X] T047 [P] [US2] Add `tests/Unit/Actions/CreateActivityTypeTest.php` and `DestroyActivityTypeTest.php`
- [X] T048 [US2] Add `tests/Unit/Models/StructureOwnershipTest.php` for SC-001: a template and an activity type reach publication through the same action, the same freezing rules and the same page validation
- [X] T049 [US2] Add the SC-005 cases, in `tests/Unit/Actions/PublishingLeavesRecordsWhereTheyAreTest.php` and `tests/Unit/Models/StructureOwnershipTest.php`, for an activity type structure: a later publication moves nothing, a value stays on its historical version, another account answers not found
- [~] T050 [US2] Deferred to the activity type screens feature. The supplied catalogue in `spec/models/035` is 40 types, each needing a translation key and a wording in 30 locales, which is 1,200 translations of product content and nothing to do with whether the engine is shared. Seeding it here would also put 40 structures and 120 inserts into account creation before a single screen can show them

**Checkpoint**: two owners, one engine, proved by test rather than by assertion.

---

## Phase 5: User Story 3, the marketplace carries both kinds (P2)

**Goal**: an activity type publishes and installs with its structure, through the shared payload.

**Independent test**: export an activity type, install it into a second account, confirm the copy
carries the same fields and is independent.

- [X] T051 [US3] Edit `app/Helpers/MarketplacePayload.php` to serialise a structure from either owner, carrying the owner's own attributes per D3
- [X] T052 [US3] Edit `app/Helpers/StructureFile.php` and `StructureFileReader.php` for the `activity_type` format, keeping `FORMAT_VERSION` in step
- [X] T053 [US3] Add `app/Actions/ImportActivityType.php`, matching the category by key before name and falling back to Other
- [X] T054 [P] [US3] Edit the matching tests in `tests/Unit/Helpers/` and `tests/Unit/Actions/`
- [X] T055 [US3] Add the SC-006 cases to `tests/Unit/Actions/ImportActivityTypeTest.php`: an activity type installed elsewhere renders the same fields and is independent of the original

**Checkpoint**: all three stories stand.

---

## Phase 6: Polish

- [X] T056 [P] Edit `resources/docs/api/` where it names a template version, keeping every path and route name
- [X] T057 Run `php artisan monica:localize` and translate everything it reports, in every configured locale
- [X] T058 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse`
- [X] T059 Run `quickstart.md` step 1 and confirm the grep returns only the two documented exceptions
- [X] T060 Run `php artisan test --compact` over `tests/` and confirm against `git diff --stat` that no test was deleted without its behaviour moving

---

## Dependencies & Execution Order

- **Phase 1** has no dependency and gates nothing technically, but it is where the nine contradictions
  are resolved, so it is reviewed before Phase 2 is written.
- **Phase 2** blocks every story. T012 to T018 are the schema; T019 to T028 are the models and depend
  on it; T031 proves both.
- **Phase 3 (US1)** depends on Phase 2 in full. T032 and T033 come before T035 to T041, which read the
  actions. T043 follows the code it tests.
- **Phase 4 (US2)** depends on Phase 3, because `CreateActivityType` calls the publish action T032
  renames. It does not depend on Phase 5.
- **Phase 5 (US3)** depends on Phase 4 for something to publish.
- **Phase 6** depends on everything.

### On independence

The three stories are not independently deliverable and this plan does not pretend otherwise. A
rename cannot be half applied: the engine compiles under one name or the other, so US1 is the whole
rename and US2 and US3 build on it. What is independently testable is each story's acceptance, which
is what the checkpoints assert.

### Parallel opportunities

- T001 to T008, T010 and T011 are eleven separate specification files.
- T017, T022, T023, T026 to T030 touch disjoint files once T012 to T016 have landed.
- T038 to T041 are four disjoint layers once T032 to T037 have landed.
- T047 and T054 are test files with no shared subject.

## Implementation Strategy

Phase 1 is reviewed on its own, because it is where the disagreements are and they are cheaper to
settle in prose. Phases 2 and 3 land together: there is no useful halfway point in a rename, and the
suite is the proof. Phases 4, 5 and 6 land one at a time, each with its checkpoint green.
