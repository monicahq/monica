---
description: "Task list for the entity list and creation form"
---

# Tasks: Entity list and creation form

**Input**: Design documents from `specs/053-entity-list-and-creation/`

**Prerequisites**: plan.md, spec.md, research.md, contracts/api.md

**Tests**: Required (constitution III). Follow `.claude/rules/testing.md`: strict types, `#[Test]`, snake_case `it_` names, named arguments, The Office data, no comments, no class string assertion. Each rule is tested once, in the layer the plan's tests table gives it.

**Organization**: the action and the policy are the foundation. US1 builds the form and the Add control, US2 proves the refusals, US3 the API.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

No setup: no dependency, configuration or schema change.

## Phase 2: Foundational

- [X] T001 `UserActionEnum::EntityCreation` ('entity_created', "Created the record :name") already exists; use it with `['name' => ...]` rather than adding a case (R8)
- [X] T002 [P] Create `app/Exceptions/EntityTemplateChanged.php`, shaped like `app/Exceptions/EntityNeedsAName.php` (R3)
- [X] T003 In `app/Policies/EntityTypePolicy.php`, add `addEntity(User $user, EntityType $entityType, Vault $vault): Response` (R2): deny `__('This type is turned off. You can view and edit the existing profiles, but you cannot add new ones.')` when `! is_active`; deny `__('Nothing can be added until this vault has a published template for :name.', ['name' => name_plural])` unless a `VaultDefaultTemplate` exists for `vault_id`, `applies_to = Entity`, `entity_type_id`, whose `structureVersion` is `Published` and whose structure owner `Template` is active (the same conditions as `EntityType::scopeVisibleInVault`); otherwise allow
- [X] T004 In `app/Models/Field.php`, add `canBeFilledIn(): bool`: false without `hasAnInput()`, false for a repeater with a required child lacking one (R4); `EntityType::defaultVersionIn(Vault): ?StructureVersion` carries the usable default for the policy and the action; tested in `tests/Unit/Models/FieldTest.php`
- [X] T005 Create `app/Actions/CreateEntity.php` (R1, R3), modelled on `app/Actions/CreateLifeEvent.php`, with a one-sentence class docblock. Constructor: `User $user`, `Vault $vault`, `EntityType $entityType`, `string $name`, `?StructureVersion $structureVersion = null`, `array $values = []`, `array $labels = []`, `array $labelWordings = []`, `array $yearIsUnknown = []`, `array $monthIsUnknown = []`. `execute(): Entity`: sanitize the name with `TextSanitizer::nullablePlainText`; `ModelNotFoundException` when the vault or type is of another account; `$this->user->authorize('addEntity', [$this->entityType, $this->vault])`; read the vault default's version and throw `EntityTemplateChanged` ('The template changed since the form was opened.') when `structureVersion` is given and is not that version; in `DB::transaction`: `Entity::query()->create([... 'avatar_colour' => Arr::random(Entity::AVATAR_COLOURS)])`, `$version->increment('record_count')`, `$entityType->increment('entity_count')`, then `new SetFieldValues(...)->execute()` for each section of `$version->assembly`; after it, `LogUserAction::dispatch(action: EntityCreation, parameters: ['name' => entity name, 'type' => name_singular], loggable: $entity)->onQueue('low')`
- [X] T006 [P] In `tests/Unit/Policies/EntityTypePolicyTest.php`, add `it_lets_somebody_add_an_entity_to_an_active_type_with_a_published_default`, `it_refuses_to_add_an_entity_to_a_turned_off_type`, `it_refuses_to_add_an_entity_without_a_usable_default` (no default, a draft, an inactive template), `it_refuses_to_add_an_entity_when_nobody_is_signed_in`
- [X] T007 Create `tests/Unit/Actions/CreateEntityTest.php`: `it_creates_an_entity_on_the_vault_default_with_its_values` (`assertDatabaseHas` on `entities` and `field_values`, `Queue::fake()` then `Queue::assertPushedOn('low', LogUserAction::class)`), `it_writes_nothing_for_an_empty_field`, `it_counts_the_record_on_its_version_and_its_type`, `it_leaves_no_record_when_a_later_section_refuses` (a required field empty in the second section), `it_refuses_a_version_that_is_no_longer_the_default`, `it_refuses_a_turned_off_type` (`AuthorizationException`), `it_refuses_a_vault_of_another_account` (`ModelNotFoundException`)

## Phase 3: User Story 1 - Add a record from its template (Priority: P1)

**Goal**: a form drawn from the vault default, reached from the list, that creates the record.

**Independent Test**: spec US1.

- [X] T008 [US1] In `routes/web.php`, after `entities.index`, add `Route::get('vaults/{vault}/entities/{entityType}/new', [EntityController::class, 'new'])->name('entities.new');` and `Route::post('vaults/{vault}/entities/{entityType}', [EntityController::class, 'create'])->name('entities.create');`
- [X] T009 [US1] Create `app/ViewModels/Vaults/EntityFormViewModel.php` (R4, R6), read `ContactFormViewModel` first: `user()`, `vault()`, `title()` (`__('New :name', ['name' => name_singular])`), `action()`, `listUrl()`, `structureVersionId()`, `name()` (`old('name', '')`), `holdsAFile(): bool`, `pages(): list<array{name, sections: list<array{name, fields: list<array{id, field: Field}>}>}>` from the default's version `pages` and `assembly`, keeping only top-level fields where `canBeFilledIn()`; a page or section left with no field is dropped; section name is `title_override ?? sectionVersion->section->name`
- [X] T010 [US1] In `app/Http/Controllers/App/Vaults/EntityController.php`, add `new(Request, Vault, EntityType): View` (`abort_unless(visibleInVault)` as `index` does, then `abort_unless($request->user()->can('addEntity', [$entityType, $vault]), 404)`) and `create(Request, Vault, EntityType): RedirectResponse`: validate `name` `['required', 'string', 'max:255']`, `structure_version_id` `['required', 'integer']`, `labels`, `label_wordings`, `year_is_unknown`, `month_is_unknown` `['array']`, then the values with `FieldValueRules::for($fields)` the way `ContactLifeEventController::valuesFrom()` does, over `$version->inputFields()->filter->canBeFilledIn()`; call `CreateEntity`; catch `AuthorizationException` and `EntityTemplateChanged` into `withErrors(['name' => ...])`, and the `SetFieldValues` exceptions caught in `ContactSectionController::update` into `withErrors(['fields' => ...])`, both `back()->withInput()`; on success redirect to `entities.index` with `status` `__(':name is written down.', ['name' => $entity->name])`
- [X] T011 [US1] Create `resources/views/app/vaults/entities/form.blade.php` in the shell of `resources/views/app/vaults/contacts/form.blade.php` (read it first): breadcrumb to the list, `x-vault.sidebar` with the list's `current`, `x-form method="post" :upload="$viewModel->holdsAFile()"`, a hidden `structure_version_id`, `x-notice tone="danger"` for `$errors->first('fields')`, the name `x-input` required, then one `x-page-section` per page and a heading per section, each field through `x-field-input :field :value="null" :reminder="null" :messages="$errors" :error="$errors->get('fields.'.$id)"`, and the submit and cancel buttons of the contact form
- [X] T012 [US1] In `app/ViewModels/Vaults/EntitiesViewModel.php`, add `addUrl(): string` (`entities.new`), `addLabel(): string` (`__('Add :name', ['name' => name_singular])`) and `addRefusal(): ?string` (the message of `$user->inspect('addEntity', [$type, $vault])` when denied); in `resources/views/app/vaults/entities/index.blade.php`, put the control in the `x-page-header` `actions` slot and in the empty state: a link when `addRefusal()` is null, otherwise a button with `aria-disabled="true"` and `aria-describedby` pointing at the 052 notice when the type is turned off, or at a line printing `addRefusal()` otherwise (`.claude/rules/policies.md`)
- [X] T013 [P] [US1] Create `tests/Unit/ViewModels/Vaults/EntityFormViewModelTest.php`: `it_lists_the_pages_and_sections_of_the_default_in_order`, `it_leaves_out_relation_and_avatar_fields`, `it_leaves_out_headings_dividers_and_help_lines`
- [X] T014 [P] [US1] In `tests/Unit/ViewModels/Vaults/EntitiesViewModelTest.php`, add `it_offers_the_way_to_add_one` and `it_says_why_nothing_can_be_added`
- [X] T015 [US1] In `tests/Feature/Controllers/App/Vaults/EntityControllerTest.php`, add `it_shows_the_form_of_a_type`, `it_answers_not_found_for_the_form_of_a_hidden_type`, `it_answers_not_found_for_the_form_through_the_wrong_vault`, `it_creates_an_entity_and_goes_back_to_the_list` (`assertRedirect`, `assertSessionHas('status', ...)`), `it_refuses_an_entity_without_a_name` (`assertSessionHasErrors('name')`), `it_shows_a_refusal_of_the_action_on_the_form` (a required field left empty, error on `fields`)

## Phase 4: User Story 2 - Creation is refused where it cannot lead anywhere (Priority: P1)

- [X] T016 [US2] In `EntityControllerTest.php`, add `it_answers_not_found_for_the_form_of_a_turned_off_type`; every other refusal is proven by T006 and T007

## Phase 5: User Story 3 - Through the API (Priority: P2)

- [X] T017 [US3] Create `app/Http/Resources/EntityResource.php` with the shape in `contracts/api.md`, read `ContactResource` first
- [X] T018 [US3] In `routes/api.php`, after the entity type routes, add `Route::get('vaults/{vault}/entity-types/{entityType}/entities', [EntityController::class, 'index'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entities');` and the matching `post` named `entities.create`, with the `use App\Http\Controllers\Api\EntityController`
- [X] T019 [US3] Create `app/Http/Controllers/Api/EntityController.php` (`index`, `create`), following `app/Http/Controllers/Api/ContactController.php`: resolve the vault through `addressedBy`, the type through `account->entityTypes()->where('uuid')->visibleInVault($vault)->firstOrFail()`; `index` paginates as the contact index does; `create` validates as T010 without `structure_version_id`, over the default version's `inputFields()->filter->canBeFilledIn()`, calls `CreateEntity` with no version, turns every refusal into `ValidationException::withMessages` (`name` or `fields`), answers 201 with `EntityResource`
- [X] T020 [US3] Create `tests/Feature/Controllers/Api/EntityControllerTest.php` with a `$jsonStructure`: `it_lists_the_entities_of_a_type_in_a_vault` (`assertJsonCount`, `meta.total`), `it_leaves_out_the_entities_of_another_vault`, `it_answers_not_found_for_a_hidden_type`, `it_creates_an_entity` (`assertCreated`, `assertJsonPath('data.attributes.name', ...)`), `it_refuses_an_entity_without_a_name` (`assertJsonValidationErrors('name')`)
- [X] T021 [US3] Following `.claude/skills/api-docs-writer/SKILL.md`, add the sections `entities-index` and `entities-create` to `resources/docs/api/07-entity-types.php`, add the two arms to `answer()` in `tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php`, and run the docs tests

## Phase 6: Polish

- [X] T022 Run `php artisan monica:localize`, translate every new string in every locale following `.claude/rules/translations.md` and its glossary, then `php artisan monica:localize --check`
- [X] T023 Run `php artisan test --compact --parallel`, `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`

## Dependencies

- T001, T002 and T004 before T005; T003 before T005 and T006; T005 before T007 and every story.
- US1: T008 → T010; T009 → T010 → T011 → T015; T012 after T003; T013 after T009; T014 after T012.
- US2 after T010. US3: T017 → T019; T018 → T019 → T020 → T021; independent of US1. Polish last.

## Parallel Opportunities

T002 beside T001; T006 beside T005; T013 and T014; US3 beside US1 once T005 is done.

## Implementation Strategy

MVP is Phase 2 and US1: a record can be added from the list. Then the refusal test, then the API and its docs. Commit after each phase.
