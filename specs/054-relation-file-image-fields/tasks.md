---
description: "Task list for relation, file and image fields in the template builder"
---

# Tasks: Relation, file and image fields in the template builder

**Input**: Design documents from `specs/054-relation-file-image-fields/`

**Prerequisites**: plan.md, spec.md, research.md

**Tests**: Required (constitution III). Follow `.claude/rules/testing.md`: strict types, `#[Test]`, snake_case `it_` names, named arguments, The Office data, no comments, no class string or markup assertion. Each rule is tested once, in the layer the plan's tests table gives it.

**Organization**: the relation value path and the inspector fix are the foundation. US1 sets a relation's target, US2 fills it in on a record, US3 adds fields inside a repeater, US4 sets accepted file kinds.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

No setup: no dependency, configuration or schema change.

## Phase 2: Foundational

- [X] T001 In `app/Http/Controllers/App/Settings/Account/Templates/FieldController.php` `update`, pass the target field's current `isUnique`, `isIdentifying`, `defaultValue`, `options` and `validation` to `UpdateField` so a save changes only what the inspector shows (R7); add `it_keeps_the_settings_the_inspector_does_not_show` to `tests/Feature/Controllers/App/Settings/Account/Templates/FieldControllerTest.php` (a field with `options.distinct_in_list`, a `default_value` and `is_unique` survives a label change)
- [X] T002 [P] In `app/Helpers/FieldSettingsCatalogue.php`, add public static `target(string $value): ?array{0: RelationTargetEnum, 1: ?int}` parsing `contact` and `entity:ID` (null for anything else) and public static `commaSeparated(string $value): list<string>`; in `app/Actions/ReplaceLifeEventTypeFields.php`, make `pointAt()` and `commaSeparated()` use them (R5); cover `target()` in `tests/Unit/Helpers/FieldSettingsCatalogueTest.php` (`contact`, `entity:12`, `entity:`, `nonsense`)
- [X] T003 [P] Move `MIME_TYPE` and `MAX_MIME_TYPES` from `app/Helpers/StructureFileReader.php` to public constants `Field::MIME_TYPE_PATTERN` and `Field::MAX_ACCEPTED_MIME_TYPES` in `app/Models/Field.php`, and make `StructureFileReader` read them (R8)

## Phase 3: User Story 1 - Say what a relation field points at (Priority: P1)

**Goal**: the field type screen asks for the target of a relation; the inspector shows it, fixed.

**Independent Test**: spec US1.

- [X] T004 [US1] Refuse a relation to an inactive entity type at validation in `app/Http/Controllers/Api/FieldController.php` (`relation_entity_type_id` exists with `is_active` true), proven in `tests/Feature/Controllers/Api/FieldControllerTest.php`; not in `CreateField`, because the life event editor may still name an inactive type (found in the audit)
- [X] T005 [US1] In `app/ViewModels/Settings/Account/Templates/FieldCatalogueViewModel.php`, add `relationTargets(Collection $entityTypes): array<string, string>` (`contact` => `__('Somebody in the vault')`, then `entity:ID` => `name_plural` for each) and `relationTypes(): list<string>` (the relation key); cover both in `tests/Unit/ViewModels/Settings/Account/Templates/FieldCatalogueViewModelTest.php`
- [X] T006 [US1] In `FieldController::new`, pass `entityTypes`: the account's `EntityType` rows with `is_active` true, ordered by `position`; in `create`, validate `relation_target` `['nullable', 'string']` and, for a relation, resolve it with `FieldSettingsCatalogue::target()` and an `EntityType` of the account with `is_active` true, returning `back()->withErrors(['relation_target' => __('Choose what this field points at.')])` when it resolves to nothing; pass `relationTarget` and `relationEntityType` to `CreateField`
- [X] T007 [US1] In `resources/views/app/settings/account/field-type.blade.php`, add an Alpine `relationTypes` array and `isRelation` getter beside `needsAList`, and a `<section x-cloak x-show="isRelation">` with an `x-panel` holding an `x-select name="relation_target"` built from `relationTargets()`, shaped like the value list section; `::required="isRelation"`
- [X] T008 [US1] In `app/ViewModels/Settings/Account/Templates/TemplateBuilderViewModel.php` `selectedField()`, add `isRelation`, `relationTargetLabel` (`__('Somebody in the vault')` or the entity type's `name_plural`) and `relationTargetIsInactive`; load `relationEntityType` in `findSelectedField()`; in `resources/views/app/settings/account/templates/_inspector.blade.php`, after Type, show the target as the Type block does, with `__('Fixed when the field was added.')` and, when inactive, `__('This kind of record is turned off. Values already written stay.')`; cover in `tests/Unit/ViewModels/Settings/Account/Templates/TemplateBuilderViewModelTest.php`
- [X] T009 [US1] In `FieldControllerTest.php`, add `it_adds_a_relation_pointing_at_an_entity_type` (`assertDatabaseHas('fields', relation_target, relation_entity_type_id)`) and `it_refuses_a_relation_pointing_at_an_inactive_entity_type` (`assertSessionHasErrors('relation_target')`)

## Phase 4: User Story 2 - Fill in a relation on a new record (Priority: P1)

**Goal**: a searchable picker fills in a relation wherever fields are filled in; Caregivers is drawn.

**Independent Test**: spec US2.

- [X] T010 [US2] In `app/Enums/FieldTypeEnum.php`, add `self::Relation` to `hasAnInput()` (R1); in `tests/Unit/Enums/FieldTypeEnumTest.php` (create it if absent) assert relation has an input and avatar has none; rewrite the 053 tests expecting relation to be left out: `EntityFormViewModelTest::it_leaves_out_relation_and_avatar_fields` keeps avatar only, and any `EntityTypePolicyTest` case refusing a required relation in a repeater now allows it; update the relation note in `resources/docs/api/07-entity-types.php` (line ~422)
- [X] T011 [US2] In `app/Helpers/FieldValueRules.php`, give `for()` and the row rules an optional `?Vault $vault` and give a relation `['nullable', 'string', Rule::exists('contacts', 'uuid')->where('vault_id', $vault->id)]`, or `entities` with `vault_id` and, when set, `entity_type_id` (R2); pass the vault from `app/Http/Requests/SaveFieldValuesRequest.php`, `app/Http/Controllers/App/Vaults/EntityController.php`, `app/Http/Controllers/Api/EntityController.php` and every other caller of `FieldValueRules::for` (grep); cover in `tests/Unit/Helpers/FieldValueRulesTest.php` (another vault, another entity type, unknown uuid refused; the vault's own accepted)
- [X] T012 [US2] In `app/Actions/SetFieldValues.php` `saveOne()`, for a relation resolve the uuid to a `Contact` or `Entity` in `$this->record->vault_id` and pass `relatedContact` / `relatedEntity` to `SetFieldValue` with `value: null`; cover in `tests/Unit/Actions/SetFieldValuesTest.php`: `it_writes_a_relation_to_a_contact_by_its_uuid`, `it_writes_a_relation_to_an_entity_by_its_uuid`, `it_writes_a_relation_in_a_repeater_row`, `it_refuses_the_same_person_twice_in_a_distinct_list` (`FieldValueIsNotUnique`)
- [X] T013 [P] [US2] In `app/Models/Entity.php`, add `scopeMatchingSearch(Builder $query, string $term): void` matching `name` case insensitively with `whereLike`, nothing for a blank term; cover in `tests/Unit/Models/EntityTest.php`
- [X] T014 [US2] Create `app/ViewModels/Vaults/FieldChoicesViewModel.php` (`user`, `vault`, `field`, `query`): `candidates(): list<array{uuid: string, name: string}>` (contacts of the vault through `matchingSearch($query, $user->locale)`, or entities of the vault and of `relation_entity_type_id` through `matchingSearch`, `orderBy('sort_name')`, `limit(25)`), `noCandidatesLine(): string` (`__('Nothing in this vault matches “:query”.')`, or `__('Nothing in this vault to choose yet.')` for a blank query), `query()`, `field()`; create `tests/Unit/ViewModels/Vaults/FieldChoicesViewModelTest.php` (vault only, target kind only, by name, at most 25, both empty lines)
- [X] T015 [US2] Create `app/Http/Controllers/App/Vaults/FieldChoiceController.php` `index(Request, Vault, Field): View`: `abort_unless` the field is a relation whose `sectionVersion->section->account_id` (check the real path to the account on `Field`) is the user's, render `components.record-picker-candidates` with the view model; add `Route::get('vaults/{vault}/fields/{field}/choices', [FieldChoiceController::class, 'index'])->whereNumber('field')->name('fields.choices.index');` in `routes/web.php` beside the vault routes; create `tests/Feature/Controllers/App/Vaults/FieldChoiceControllerTest.php` (answers with the candidates; 404 for another account's field; 404 for a field that is not a relation)
- [X] T016 [US2] Create `resources/js/record-picker.js` (`recordPicker({ url, chosen, chosenName })`: `query`, `chosen`, `chosenName`, `search()` through `this.$ajax(url + '?query=' + ..., { target })`, `choose(uuid, name)`, `forget()`; the target id is the component's own) and register it in `resources/js/app.js` as the other components are; read `resources/js/relationship-form.js` first
- [X] T017 [US2] Create `resources/views/components/record-picker.blade.php` (props `name`, `id`, `url`, `chosen`, `chosenName`, `label`, `error`) and `resources/views/components/record-picker-candidates.blade.php`, following `resources/views/app/vaults/contacts/relationships/_candidates.blade.php`: a hidden input `name` holding `chosen`, the chosen name with a Clear button when set, a search `x-input` with `aria-controls` the list, the list `x-merge="replace"` with `aria-live="polite"`, one button per candidate with `aria-pressed`, the empty line; open with the one-line comment and `@var` block (`code-style.md`)
- [X] T018 [US2] In `resources/views/components/field-input.blade.php`, add a `vault` prop and a `@case($type->isRelation())` before the generic input case, drawing `x-record-picker` with `url` `route('fields.choices.index', [$vault, $field])` and the chosen record from `$value?->relatedContact ?? $value?->relatedEntity` (uuid, name); forward `vault` from `resources/views/components/field-repetitions.blade.php` to both of its `x-field-input`; pass `:vault` at `resources/views/app/vaults/contacts/_section.blade.php`, `resources/views/app/vaults/contacts/_life-event-fields.blade.php` and `resources/views/app/vaults/entities/form.blade.php`; eager load `relatedContact` and `relatedEntity` where those screens load their values
- [X] T019 [P] [US2] Create `tests/Feature/Views/Components/RecordPickerTest.php`: the hidden input carries `name` and `chosen`, the search is labelled, the list has `aria-live`, a candidate has `aria-pressed`, the empty line shows with no candidate
- [X] T020 [US2] In `tests/Feature/Controllers/App/Vaults/EntityControllerTest.php`, add `it_creates_an_animal_with_two_caregivers` (rows in `field_value_rows`, two `field_values` with `related_contact_id`) and `it_refuses_a_caregiver_from_another_vault` (`assertSessionHasErrors`); in `tests/Feature/Controllers/Api/EntityControllerTest.php`, add `it_creates_an_entity_with_a_relation` and `it_refuses_a_relation_from_another_vault` (`assertJsonValidationErrors`); document the relation value (the uuid) in `resources/docs/api/07-entity-types.php` following `.claude/skills/api-docs-writer/SKILL.md`

## Phase 5: User Story 3 - Add a field inside a repeater (Priority: P2)

**Goal**: a repeater in a draft offers to add a field inside it.

**Independent Test**: spec US3.

- [X] T021 [US3] Create `app/Http/Controllers/App/Settings/Account/Templates/ChildFieldController.php` with `new` only, drawing the field type screen with a hidden `parent_id` posted to the existing `settings.fields.create`, so the create logic stays in one place (changed from a second `create`); `FieldController::create` takes `parent_id`, 404s unless it is a repeater of the row's section version, finds it again by `key` after `MakeSectionVersionEditable`, and catches `CannotNestARepeater` into an error on `type`; route `settings.childFields.new`
- [X] T022 [US3] In `FieldCatalogueViewModel`, add a `bool $insideARepeater = false` constructor property that drops `repeater` from `families()`, `types()` and everything built on them; `ChildFieldController::new` passes true, a `parentLabel` and its own `action`; in `field-type.blade.php`, show `__('Inside :repeater', ['repeater' => $parentLabel])` under the title when set; cover in `FieldCatalogueViewModelTest.php`
- [X] T023 [US3] In `TemplateBuilderViewModel::selectedField()`, add `addInsideUrl` (`settings.childFields.new`, only for a repeater in an editable draft); in `_inspector.blade.php` show an `x-button.secondary` `__('Add a field inside')` linking to it; in `resources/views/app/settings/account/templates/_field.blade.php`, let a child be dragged and carry its `x-position-form` (drop the `! $isChild` from `$mayReorder`); cover `addInsideUrl` in `TemplateBuilderViewModelTest.php`
- [X] T024 [US3] Create `tests/Feature/Controllers/App/Settings/Account/Templates/ChildFieldControllerTest.php`: `it_shows_the_field_types_without_the_repeater`, `it_adds_a_field_inside_a_repeater` (`parent_field_id`), `it_adds_a_field_inside_a_repeater_of_a_published_version` (lands in the new draft under the copied parent), `it_answers_not_found_when_the_parent_is_not_a_repeater`

## Phase 6: User Story 4 - Choose which files a file or image field takes (Priority: P2)

**Goal**: the inspector edits accepted kinds; bad kinds and wrong uploads are refused.

**Independent Test**: spec US4.

- [X] T025 [US4] Create `app/Exceptions/AcceptedFileKindIsNotValid.php` shaped like the other field exceptions; in `app/Actions/CreateField.php` and `app/Actions/UpdateField.php`, when `options.accepted_mime_types` is set, throw it for a kind not matching `Field::MIME_TYPE_PATTERN` (`__('“:kind” is not a kind of file.', ['kind' => ...])`), more than `Field::MAX_ACCEPTED_MIME_TYPES`, or a kind not starting `image/` on an image or avatar field (`__('An image field only takes kinds of image.')`); cover in `CreateFieldTest.php` and `UpdateFieldTest.php`
- [X] T026 [US4] In `FieldController::update`, validate `accepted_mime_types` `['nullable', 'string', 'max:1000']` and, for a file or image field, replace `accepted_mime_types` in the kept options with `FieldSettingsCatalogue::commaSeparated()` of it (dropping the key when empty); catch `AcceptedFileKindIsNotValid` into `back()->withErrors(['accepted_mime_types' => $e->getMessage()])`; in `app/Http/Controllers/Api/FieldController.php` `update`, catch it into `ValidationException::withMessages(['options' => ...])`
- [X] T027 [US4] In `TemplateBuilderViewModel::selectedField()`, add `holdsAFile` and `acceptedMimeTypes` (comma joined); in `_inspector.blade.php`, for a file or image field, an `x-input name="accepted_mime_types"` labelled `__('Kinds of file taken')`, placeholder `image/png, application/pdf`, help `__('Leave empty to take any file. An image field takes images only.')`, disabled when not editable
- [X] T028 [US4] In `FieldControllerTest.php`, add `it_saves_the_kinds_of_file_a_field_takes`, `it_refuses_a_kind_that_is_not_a_mime_type`; in `tests/Feature/Controllers/Api/FieldControllerTest.php`, `it_refuses_a_malformed_kind_of_file` (422); in `EntityControllerTest.php`, `it_refuses_an_upload_of_a_kind_the_field_does_not_take` (`UploadedFile::fake()->create('scranton.pdf')` on a PNG only image field, `assertSessionHasErrors`)

## Phase 7: Polish

- [X] T029 Run `php artisan monica:localize`, translate every new string in every locale following `.claude/rules/translations.md` and its glossary, then `php artisan monica:localize --check`
- [X] T030 Run `php artisan test --compact --parallel`, `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`; fix the feature tests that asserted the old relation behaviour

## Dependencies

- T001 before T026; T002 before T006 and T026; T003 before T025.
- US1: T004 → T006 → T009; T005 → T006 → T007; T008 after T005.
- US2: T010 → T011 → T012 → T020; T013 → T014 → T015 → T017 → T018 → T020; T016 before T017; T019 after T017.
- US3 after T006 (shares its validation): T021 → T022 → T023 → T024.
- US4 after T001, T002, T003: T025 → T026 → T027 → T028. Polish last.

## Parallel Opportunities

T002 and T003 beside T001; T013 beside T010 to T012; T016 beside T014; T019 beside T018; US4 beside US2 once the foundation is done.

## Implementation Strategy

MVP is Phase 2, US1 and US2: a relation is set up in the builder and filled in on a new record, so the Animal template's Caregivers works end to end. Then US3 and US4. Commit after each phase.
