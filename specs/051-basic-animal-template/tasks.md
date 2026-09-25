---
description: "Task list for the Basic animal template"
---

# Tasks: Basic animal template

**Input**: Design documents from `specs/051-basic-animal-template/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: Required (constitution III). Follow `.claude/rules/testing.md`: strict types, `#[Test]`, snake_case `it_` names, named arguments, The Office data, no comments. Each rule is tested once, in the layer that owns it.

**Organization**: the enums are the foundation. US1 seeds new accounts, US2 new vaults, US3 the caregiver rules, US4 the backfill. US2 and US4 need US1.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

No setup: no dependency, configuration or schema change.

## Phase 2: Foundational

- [X] T001 [P] Create `app/Enums/AnimalSexEnum.php` implementing `SuppliedValueListItem` like `GenderEnum`: `Female = 'female'` "Female", `Male = 'male'` "Male", `Unknown = 'unknown'` "Unknown"
- [X] T002 In `app/Enums/ValueListEnum.php`, add `AnimalSexes = 'animal_sexes'`, label "Animal sexes", description "Whether an animal is female or male", items `AnimalSexEnum::cases()`
- [X] T003 In `app/Enums/DefaultFieldEnum.php`, add `Species`, `Breed`, `Sex`, `AdoptionDate`, `MicrochipNumber`, `Caregivers`, `CaregiverPerson`, `CaregiverRole`, `FoodAndRoutine`, `HealthAndCare`, `AnimalNotes` with the keys, types and labels of data-model.md ("Adoption date", "Microchip number", "Caregivers", "Person", "Role", "Food and routine", "Health and care notes", "Notes"); `Sex` → `ValueListEnum::AnimalSexes`; `Caregivers` children `[CaregiverPerson, CaregiverRole]`, `repetitionOrder()` `NewestLast`; add `isRequired(): bool` (true only for `CaregiverPerson`), `relationTarget(): ?RelationTargetEnum` (`Contact` for `CaregiverPerson`), and `options()` `['distinct_in_list' => true]` for `CaregiverPerson`; `Birthday` is reused as is
- [X] T004 In `app/Enums/DefaultSectionEnum.php`, add `AnimalDetails = 'animal-details'` "Animal details" icon `pet` colour Amber, fields `[Species, Breed, Sex, Birthday, AdoptionDate, MicrochipNumber]`; `AnimalPeople = 'animal-people'` "People" icon `people` colour Blue, `[Caregivers]`; `AnimalCare = 'animal-care'` "Care" icon `health` colour Teal, `[FoodAndRoutine, HealthAndCare]`; `AnimalNotes = 'animal-notes'` "Notes" icon `note` colour Green, `[AnimalNotes]` (icons from `Section::ICONS`)
- [X] T005 In `app/Enums/DefaultPageEnum.php`, add `AnimalGeneral = 'animal-general'`, label "General", sections the four of T004 in that order; keep `DefaultTemplateEnum::BasicContact->pages()` returning only the three contact pages (replace `DefaultPageEnum::cases()` with the explicit list)
- [X] T006 In `app/Enums/DefaultTemplateEnum.php`, add `BasicAnimal = 'basic-animal'`, label "Basic animal", `appliesTo()` `Entity`, pages `[AnimalGeneral]`, and `entityType(): ?array{slug, singular, plural, description, icon, colour}` returning for BasicAnimal `animals`, "Animal", "Animals", "Pets and other animals you want to remember.", `pet`, `#9a6a24`, null for BasicContact; add these three strings to `translationKeys()`
- [X] T007 [P] In `app/Models/EntityType.php`, add `'pet'` to `ICONS` (research R3)

## Phase 3: User Story 1 - A new account has animals ready (Priority: P1)

**Goal**: seeding writes the type, the published template and the vault defaults, once.

**Independent Test**: spec US1.

- [X] T008 [US1] In `app/Jobs/CreateDefaultTemplate.php`, add `public DefaultTemplateEnum $supplied = DefaultTemplateEnum::BasicContact` to the constructor and use it everywhere `BasicContact` is named; `handle()` returns early when a template with `is_system` and `slug = $supplied->value` exists in the account (R4); inside the transaction, before the template, create the `EntityType` from `entityType()` when `appliesTo()->needsEntityType()`: names through `__()` in `app()->getLocale()` set to the user's locale, `position` after the account's last, `is_active` true, `entity_count` 0; set `structures.entity_type_id` to it; `position` of the template after the account's last
- [X] T009 [US1] In the same job, write each field's `is_required`, `options`, `relation_target` and `relation_entity_type_id` (null) from T003; replace `updateOrCreate` in `seedTheVaultDefaults()` with `firstOrCreate` keyed on `vault_id`, `applies_to` from `$supplied->appliesTo()`, `entity_type_id` (the type, or null)
- [X] T010 [US1] In `app/Actions/CreateAccount.php`, chain `new CreateDefaultTemplate(user: $this->user, supplied: DefaultTemplateEnum::BasicAnimal)` right after the Basic contact one
- [X] T011 [US1] In `tests/Unit/Jobs/CreateDefaultTemplateTest.php`, keep every existing test passing unchanged and add: `it_gives_the_account_an_animal_type_before_its_template`, `it_restricts_the_animal_template_to_that_type`, `it_publishes_the_animal_template_and_each_of_its_sections`, `it_lays_the_animal_template_out_as_supplied` (page, section order, field keys and types of data-model.md), `it_offers_female_male_and_unknown_with_none_chosen`, `it_asks_for_a_person_on_every_caregiver_row_and_nothing_else`, `it_writes_no_value_and_no_row`, `it_sets_the_animal_default_of_every_vault`, `it_keeps_a_default_somebody_chose`, `it_writes_nothing_the_second_time`, `it_leaves_another_account_alone`
- [X] T012 [P] [US1] In `tests/Unit/Actions/CreateAccountTest.php`, assert the chain holds `CreateDefaultTemplate` for `BasicContact` then `BasicAnimal` (follow how the file already asserts the chain)
- [X] T013 [P] [US1] In `tests/Unit/ViewModels/Vaults/ContactFormViewModelTest.php`, add `it_does_not_offer_the_animal_template_to_a_contact`

**Checkpoint**: US1 tests pass.

## Phase 4: User Story 2 - Vaults created later get the Animal default (Priority: P1)

- [X] T014 [US2] In `app/Actions/CreateVault.php`, after `seedTheContactDefault()`, add `seedTheAnimalDefault()`: the account's template with `is_system`, slug `DefaultTemplateEnum::BasicAnimal->value` and a published structure; when found, create a `VaultDefaultTemplate` with `applies_to` Entity, its `structure->entity_type_id` and `published_version_id`; nothing when absent
- [X] T015 [US2] In `tests/Unit/Actions/CreateVaultTest.php`, add `it_gives_a_new_vault_the_animal_default` and `it_gives_no_animal_default_when_the_account_has_no_animal_template`

## Phase 5: User Story 3 - Caregivers link an animal to people (Priority: P2)

- [X] T016 [US3] In `app/Actions/SetFieldValue.php`, in `refuseADuplicate()`, when `($this->field->options['distinct_in_list'] ?? false) === true` and the field sits in a repeater, refuse a value of the same field on the same record in another row with the same column value, with `FieldValueIsNotUnique` "{label} is already in this list."
- [X] T017 [US3] In `app/Actions/SetFieldValues.php`, in `saveTheRepetitions()`, before writing a row that has anything filled in, throw `FieldIsRequired` "{label} has to be filled in." for a child with `is_required` given nothing (research R7)
- [X] T018 [P] [US3] In `tests/Unit/Actions/SetFieldValueTest.php`, add `it_refuses_the_same_contact_twice_in_a_list_that_asks_for_distinct_people` and `it_allows_the_same_contact_twice_without_the_setting`
- [X] T019 [P] [US3] In `tests/Unit/Actions/SetFieldValuesTest.php`, add `it_refuses_a_row_missing_a_required_field` (Role "Pet sitter", no Person) and `it_drops_a_row_left_entirely_empty`

## Phase 6: User Story 4 - An existing account gets it on request (Priority: P2)

- [X] T020 [US4] In `app/Actions/CreateDefaultValueLists.php`, add `private readonly ?array $lists = null` (`list<ValueListEnum>`): when given, create only those keys the account lacks, skipping the "already has lists" guard; unchanged when null
- [X] T021 [US4] Create `app/Console/Commands/SeedAnimalTemplateCommand.php` per the contract: `#[Signature('monica:seed-animal-template {--account= : Id of one account}')]`; per account, skip when a non-system template holds `basic-animal`, an entity type holds `animals` without the system template, or a non-system section holds an R6 slug; else run `CreateDefaultValueLists(lists: [AnimalSexes])` and `CreateDefaultTemplate(supplied: BasicAnimal)->handle()` synchronously for the account's user, then fill missing Animal defaults with `firstOrCreate`; print the contract's lines and the summary
- [X] T022 [P] [US4] In `tests/Unit/Actions/CreateDefaultValueListsTest.php`, add `it_adds_only_the_lists_an_account_lacks`
- [X] T023 [US4] Create `tests/Unit/Console/Commands/SeedAnimalTemplateCommandTest.php`: `it_seeds_an_account_that_has_no_animal_template`, `it_writes_nothing_on_a_second_run`, `it_skips_an_account_whose_own_type_holds_the_slug`, `it_keeps_the_edits_made_to_the_supplied_template`, `it_fails_on_an_unknown_account`

## Phase 7: Polish

- [X] T024 Run `php artisan monica:localize`, translate every new string in every configured locale per `.claude/rules/translations.md` (reuse each locale's words for "Animals", "Notes", "Birthday"), `php artisan monica:localize --check`; never `lang:update`
- [X] T025 Run `php artisan test --compact --parallel`; the three `another_alphabet` tests fail on `main` already
- [X] T026 Run `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`

## Dependencies

- T001 → T002 → T003 → T004 → T005 → T006; T007 [P] beside them. Phase 2 blocks every story.
- US1: T008 → T009 → T010 → T011; T012, T013 [P] after T010.
- US2 after US1 (T014 reads the template T008 writes). US3 is independent of US1 and US2.
- US4 after US1 (T021 calls the job). Polish last.

## Parallel Opportunities

T007 with Phase 2; T012 and T013; US3 (T016 to T019) beside US2; T022 beside T021.

## Implementation Strategy

MVP is Phase 2 and US1: new accounts get animals. Then US2, US3, US4. Commit after each phase.
