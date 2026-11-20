---
description: "Task list for a second supplied animal template"
---

# Tasks: A second supplied animal template

**Input**: Design documents from `specs/137-animal-template/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: Included. Principle III of the constitution makes tests a merge condition. Follow
`.claude/rules/testing.md`: one rule tested once, in the layer that owns it; no assertion on classes or
markup; data from The Office (US).

**Revised 2026-11-19**: Animal is added beside Basic animal in every account and never replaces it;
the form that adds an animal lets the person pick the template.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependency on an unfinished task)
- **[Story]**: The user story the task serves (US1, US2, US3)

---

## Phase 1: Setup

- [X] T001 Create branch `2026-11-19-animal-template` from local branch `2026-11-19-changelog-panda-redesign` (it holds commit `6d12e152`, "feat(templates): record page and empty list"), carrying the untracked `specs/137-animal-template/` along; confirm `app/ViewModels/Vaults/EntityViewModel.php` and `resources/views/app/vaults/entities/show.blade.php` exist on it

---

## Phase 2: Foundational (blocking)

**Purpose**: Describe Animal in the enums and teach `CreateDefaultTemplate` to write it.

- [X] T002 [P] Create `app/Enums/NeuterStatusEnum.php` implementing `App\Contracts\SuppliedValueListItem`, modelled on `app/Enums/AnimalSexEnum.php`: cases `Yes = 'yes'`, `No = 'no'`, `Unknown = 'unknown'`
- [X] T003 [P] Add `case NeuterStatuses = 'neuter_statuses'` to `app/Enums/ValueListEnum.php`: label "Neuter statuses", description "Whether an animal is neutered or spayed", items `NeuterStatusEnum::cases()`
- [X] T004 Add the new field cases to `app/Enums/DefaultFieldEnum.php` with keys, labels and kinds exactly as in `data-model.md`; reuse `Species`, `Breed`, `Sex`, `Birthday`, `AdoptionDate`, `Caregivers`, `Photo`, `Notes`, `Allergies`, `MicrochipNumber`, `Document`
- [X] T005 Add the seven Animal sections to `app/Enums/DefaultSectionEnum.php` (slugs, labels, icons, colours, fields and label overrides as in `data-model.md`)
- [X] T006 Add `DefaultSectionEnum::isHighlighted(DefaultFieldEnum $field): bool` with a per section list read first; `AnimalIdentity` highlights exactly Species, Breed and Birthday
- [X] T007 Add `AnimalProfile` (`profile`, "Profile") and `AnimalDailyLife` (`daily-life`, "Daily life and care") to `app/Enums/DefaultPageEnum.php`
- [X] T008 Add `case Animal = 'animal'` to `app/Enums/DefaultTemplateEnum.php`: label "Animal", the spec's description, applies to Entity, the two pages, the same entity type array as `BasicAnimal`, and `isVaultDefault()` false
- [X] T009 In `app/Jobs/CreateDefaultTemplate.php`, `createEntityType()` reuses the account's non deleted entity type with the template's slug; `createFields()` reads `$section?->isHighlighted($field) ?? $field->isHighlighted()`; class docblock updated
- [X] T010 Extend `tests/Unit/Jobs/CreateDefaultTemplateTest.php`: Animal writes two pages and seven sections in the order of `data-model.md` with the overridden labels; Species, Breed and Birthday are its only highlighted fields; Basic animal still highlights Birthday only; Animal supplied to an account holding the `animals` kind applies to that kind, creates no second kind and writes no vault default; Neutered or spayed points at the account's `neuter_statuses` list

**Checkpoint**: `php artisan test --compact tests/Unit/Jobs/CreateDefaultTemplateTest.php` passes.

---

## Phase 3: User Story 1, every account has Animal beside Basic animal (P1) 🎯 MVP

**Goal**: New accounts get both templates; existing accounts receive Animal through a backfill; nothing they had changes.

**Independent Test**: Create an account and run the job on an existing one; compare templates, vault defaults and values.

### Tests

- [X] T011 [P] [US1] Create `tests/Unit/Jobs/SupplyAnimalTemplateTest.php`, modelled on `tests/Unit/Jobs/SupplyContactTemplatesTest.php`: an account holding Basic animal ends with both templates, one `animals` kind, every vault default still on Basic animal and its animals' values unchanged; the `neuter_statuses` list is added; a second run adds nothing; Animal deleted after a first run is not brought back; a non system template or section with an Animal slug, or a soft deleted `animals` kind, makes the job add nothing and log a warning; an account without Basic animal still receives Animal
- [X] T012 [P] [US1] In `tests/Unit/Actions/CreateAccountTest.php`, assert that a new account holds system templates `basic-animal` and `animal` on one `animals` kind, and that its first vault starts animals on Basic animal

### Implementation

- [X] T013 [US1] Create `app/Jobs/SupplyAnimalTemplate.php` (`ShouldQueue`, `Queueable`, constructor `public User $user`, class docblock of one or two sentences), modelled on `app/Jobs/SupplyContactTemplates.php`: create `ValueListEnum::NeuterStatuses` through `CreateDefaultValueLists`; skip with `Log::warning` when the account holds a non system template or section with an Animal slug (soft deleted included) or a soft deleted `animals` kind; otherwise `new CreateDefaultTemplate(user: $this->user, supplied: DefaultTemplateEnum::Animal)->handle()`
- [X] T014 [US1] In `app/Actions/CreateAccount.php`, add `new SupplyAnimalTemplate(user: $this->user)` to the `seedTheAccount()` chain right after Basic animal; keep Basic animal
- [X] T015 [US1] Create `database/migrations/2026_11_19_000001_backfill_supplied_animal_template.php`, `up()` only, dispatching `SupplyAnimalTemplate` per user on the `low` queue with `chunkById(200)`, as `2026_11_18_000004_backfill_supplied_contact_templates.php` does; read `.claude/skills/migrations/SKILL.md` first; never run it locally

**Checkpoint**: T011 and T012 pass.

---

## Phase 4: User Story 2, picking Animal when adding an animal (P1)

**Goal**: The add form offers the templates of the kind; the action accepts the one picked; the page shows the highlighted fields.

**Independent Test**: In an account holding both, add an animal on Animal, then open it.

### Tests

- [X] T016 [P] [US2] In `tests/Unit/Actions/CreateEntityTest.php`: a published version of another active template of the same kind is accepted and the entity is written on it; a version of another kind, of another account or of an inactive template throws `ModelNotFoundException`; a version no longer published throws `EntityTemplateChanged`; without a version the vault default is used
- [X] T017 [P] [US2] In `tests/Unit/ViewModels/Vaults/EntityFormViewModelTest.php`: `templates()` lists the active templates of the kind with a published version, the vault's default first and marked current, each with its `entities.new` link; `showsTemplates()` is false with one; inactive and other kinds are left out
- [X] T018 [P] [US2] Create `tests/Unit/ViewModels/Vaults/EntityViewModelTest.php` testing `headerFacts()`: Angela's cat Sprinkles on Animal with species "Cat", breed "Maine Coon" and a birthday with `year_is_unknown` lists Species, Breed or mix, Date of birth in that order, the date without a year; an empty highlighted field reads "Not recorded"; a version highlighting four fields lists three; one highlighting none returns an empty list

### Implementation

- [X] T019 [US2] In `app/Actions/CreateEntity.php`, take the given structure version when there is one, as `CreateContact::readTheVaultDefault()` does; refuse with `ModelNotFoundException` a version of another account, whose structure's `entity_type_id` is not the entity type's, or whose template is inactive; throw `EntityTemplateChanged` when it is not the published version of its structure; without one, keep today's vault default lookup
- [X] T020 [US2] In `routes/web.php`, make the template an optional last segment of `entities.new`: `vaults/{vault}/entities/{entityType}/new/{template?}`; in `app/Http/Controllers/App/Vaults/EntityController.php`, `new()` draws the chosen template's published version when it is one of the kind's on offer (404 otherwise), the vault default otherwise, and the first on offer when the vault has none; `create()` reads the fields of the posted version rather than of the vault default
- [X] T021 [US2] In `app/ViewModels/Vaults/EntityFormViewModel.php`, add `templates()` (`list<array{name: string, url: string, isCurrent: bool}>`) and `showsTemplates()`; in `resources/views/app/vaults/entities/form.blade.php`, when `showsTemplates()`, render "What this :name starts from" with one link per template, `aria-current="page"` on the current one, above the fields; read `.claude/rules/components.md` and reuse an existing component where one fits
- [X] T022 [US2] In `app/Http/Controllers/Api/EntityController.php`, accept an optional `structure_version_id` (nullable integer) and pass the version to `CreateEntity`, answering a refusal with 422 on `structure_version_id`; in `app/Mcp/Tools/CreateEntity.php`, add it to the schema; update the API reference under `resources/docs/api/` and the API changelog with the `api-docs-writer` skill
- [X] T023 [US2] Add `headerFacts()` to `app/ViewModels/Vaults/EntityViewModel.php` (first three highlighted fields of the record's version in page, section, field order, formatted value or "Not recorded"), and render it in `resources/views/app/vaults/entities/show.blade.php` under the title as a `<ul>` modelled on `resources/views/app/vaults/contacts/_facts.blade.php`, without the link and reminder branches, absent when empty
- [X] T024 [US2] In `tests/Feature/Controllers/App/Vaults/EntityControllerTest.php`: `entities.new` with Animal's slug draws an Animal field, with an unknown slug answers 404; posting Animal's version writes the animal on it; the show page of an animal on Animal displays its species; in `tests/Feature/Controllers/Api/EntityControllerTest.php`, `structure_version_id` of Animal is accepted and one of another kind answers 422

**Checkpoint**: US2 tests pass.

---

## Phase 5: User Story 3, moving between the two templates (P3)

- [X] T025 [US3] In `tests/Unit/Actions/MoveRecordToStructureVersionTest.php`, assert that an entity on Basic animal with species, breed, sex, birthday, adoption date, microchip number and two caregiver rows, moved to Animal's version, shows all of them there, and that its food and routine, health and care and notes values are set aside and not deleted

---

## Phase 6: Polish and cross-cutting

- [X] T026 Run `php artisan monica:localize` and translate every new key in every `lang/*.json` with subagents on model sonnet, following `.claude/skills/translations/SKILL.md`, the glossary and typography rules of `.claude/rules/translations.md`, and the French wording of the tables in `spec.md`; then `php artisan monica:localize --check` reports nothing
- [X] T027 [P] Update `docs/portal/en/9-templates-and-customisation/9-profile-types.md` and `docs/portal/en/3-people/6-profiles.md`: Animal beside Basic animal, and how to pick it when adding an animal; `writer` skill; no dashes as punctuation
- [X] T028 [P] Write the changelog entry with the `changelog` skill
- [X] T029 Run `vendor/bin/pint --dirty --format agent` and PHPStan on the touched files
- [X] T030 Run the whole suite with `PAO_DISABLE=true php artisan test --parallel`
- [X] T031 Run the `rules-reviewer` and `accessibility-reviewer` agents on the diff, check their evidence, and fix what holds
- [X] T032 Run the `definition-of-done` skill, then commit (`feat(templates): second animal template`) and, after asking, open the pull request against `2026-11-19-changelog-panda-redesign`, that branch's own pull request against `main` first if it has none

---

## Dependencies and execution order

- Phase 2 before everything. US1 and US2 are independent after T009. US3 needs T009 only.
- Within US2: T019 before T020 and T022; T021 after T020; T024 last.

## Implementation strategy

1. Phase 2, then US1: everybody holds Animal. Shippable, but nobody can pick it yet.
2. US2: the choice, the API parameter and the header.
3. US3, then polish, then one pull request on the stack.
