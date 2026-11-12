---
description: "Task list for the MCP tools for templates, sections and fields"
---

# Tasks: MCP tools for templates, sections and fields

**Input**: Design documents from `specs/057-mcp-template-tools/`

**Prerequisites**: plan.md, spec.md, research.md, contracts/mcp-tools.md, contracts/api-section-create.md, quickstart.md

**Tests**: Required (constitution III). Follow `.claude/rules/testing.md`: `declare(strict_types=1);`, `#[Test]`, snake_case `it_` names, named arguments, no comments, The Office data, `RefreshDatabase` only when the database is touched. Tool tests follow `tests/Unit/Mcp/Tools/SetContactFavouriteTest.php`: `MonicaServer::actingAs($user)->tool(Tool::class, [...])`, then `assertOk()`/`assertHasErrors()` and `assertDatabaseHas()`, plus one `it_names_the_api_capability_it_mirrors` test. Do not re-test the actions' business rules; one relayed refusal per tool that has one is enough.

**Tool shape**: copy `app/Mcp/Tools/UpdateContactName.php` and `CreateContact.php`: `#[Description(...)]`, `use MirrorsAnApiCapability;`, `protected string $mirrors = '<route name>';`, `handle()` validates with the endpoint's rules, reaches records through the trait, calls the same action as the controller (imported `as <Name>Action` when the names collide), catches exactly what the controller catches and returns `Response::error($e->getMessage())`, returns `Response::json(new <Resource>(...)->resolve())`. `schema()` describes every argument; enum values are listed from the enum (`implode(', ', array_column(Enum::cases(), 'value'))`). Arguments, mirrors and annotations per tool are in [contracts/mcp-tools.md](contracts/mcp-tools.md).

**Registration**: `McpCatalogueTest` fails while a tool class exists that `MonicaServer::$tools` does not list, and `McpDocsViewModelTest::it_lists_exactly_the_tools_the_server_registers` fails while the docs table and the server disagree. Each story therefore ends by registering its tools and adding them to the docs table, so the suite is green at the end of every story.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

No setup: no dependency, configuration, route or schema change.

## Phase 2: Foundational

- [X] T001 Add a test to `tests/Feature/Controllers/Api/StructureVersionSectionControllerTest.php` that sends `PUT api.v1.templates.versions.sections.update` for a row of a **published** structure version (`status` Published) and asserts `assertUnprocessable()` with an error on `version`. Run it and record in `specs/057-mcp-template-tools/research.md` R4 whether it answered 500 (confirmed) or already 422 (then mark R4 "not a defect" and skip T002)
- [X] T002 If T001 answered 500: catch `App\Exceptions\PublishedVersionIsFrozen` and rethrow `ValidationException::withMessages(['version' => $e->getMessage()])` in `create`, `update` and `destroy` of `app/Http/Controllers/Api/StructureVersionSectionController.php`, `update` of `StructureVersionSectionPositionController.php`, `create`, `update` and `destroy` of `FieldController.php`, and `update` of `FieldPositionController.php` (all in `app/Http/Controllers/Api/`). Add one published version test each to `tests/Feature/Controllers/Api/StructureVersionSectionPositionControllerTest.php`, `FieldControllerTest.php` and `FieldPositionControllerTest.php`. Leave `StructureVersionSectionPageController` alone (pages are out of scope) and note it under Found
- [X] T003 Add to `app/Mcp/Tools/MirrorsAnApiCapability.php`, after `template()`, four lookups that each throw `$this->doesNotExist(__('...'))` when nothing matches: `version(Template $template, int $number): StructureVersion` (by `version_number`, "That version does not exist."), `section(StructureVersion $version, string $id): StructureVersionSection` (through `$version->assembly()->where('structure_version_sections.id', $id)`, as `TemplateScopedController::findRow()`, "That section does not exist."), `field(SectionVersion $sectionVersion, string $id): Field` (`$sectionVersion->fields()->whereKey($id)`, "That field does not exist."), `page(StructureVersion $version, string $uuid): StructureVersionPage` ("That page does not exist."). Extend the class docblock only if its first paragraph no longer describes the trait
- [X] T004 Run `php artisan monica:localize` and translate the four new sentences in every configured locale, following `.claude/rules/translations.md` (French U+2019 apostrophe and the glossary: "version", "section", "field" (champ), "page")

**Checkpoint**: `php artisan test --compact tests/Feature/Controllers/Api tests/Unit/Mcp` green.

## Phase 3: User Story 1 - Shape a template's sections and fields (Priority: P1) 🎯 MVP

**Goal**: an assistant opens a draft, adds a new or existing section, changes, moves and removes sections and fields, publishes or discards the draft.

**Independent Test**: [quickstart.md](quickstart.md) steps 1 to 8 through MCP alone.

### API: create a new section (contracts/api-section-create.md)

- [X] T005 [US1] Write the failing tests in `tests/Feature/Controllers/Api/StructureVersionSectionControllerTest.php`: `it_creates_a_new_section_in_a_draft` (post `name: 'Sales'`, `icon`, `colour`; 201; `assertDatabaseHas('sections', ['name' => 'Sales', 'account_id' => ...])` and the row on the version), `it_refuses_a_new_section_on_a_published_version_and_writes_nothing` (422 on `version`; `assertDatabaseMissing('sections', ['name' => 'Sales'])`), `it_refuses_a_name_and_a_section_id_together` (422 on both keys)
- [X] T006 [US1] Extend `create` in `app/Http/Controllers/Api/StructureVersionSectionController.php`: rules `section_id` → `['required_without:name', 'prohibits:name', 'nullable', 'string', Rule::exists(...)]` (keep the existing exists rule), `name` → `['required_without:section_id', 'nullable', 'string', 'max:255']`, `icon` → `['nullable', 'string', Rule::in(Section::ICONS), 'prohibits:section_id']`, `colour` → `['nullable', 'string', Rule::in(VaultColourEnum::values()), 'prohibits:section_id']`, `section_version_number` gains `'prohibits:name'`. When `name` is given: unhappy path first, if `! $structureVersion->status->isEditable()` throw `ValidationException::withMessages(['version' => __('This version has been published. Open a draft to change it.')])`; then `new CreateSection(user:, name:, icon:, colour:)->execute()` and use `$section->versions()->first()` as the section version passed to `AddSectionToStructureVersion`, exactly as `app/Http/Controllers/App/Settings/Account/Templates/TemplateSectionController.php::create`. The `section_id` branch is unchanged. Run `monica:localize` for the new sentence
- [X] T007 [US1] Document the two forms in `resources/docs/api/11-templates.php` (the add section entry): the new `name`, `icon`, `colour` inputs with their allowed values, that exactly one of `name` and `section_id` is given, the 422 on a published version, and one example request for each form. Follow the `api-docs-writer` skill. Run `php artisan test --compact tests/Unit/ViewModels/Docs tests/Feature/Controllers/App/Docs`

### Tools: template versions

- [X] T008 [P] [US1] `app/Mcp/Tools/GetTemplateVersion.php`, `#[IsReadOnly]`, mirrors `api.v1.templates.versions.show`, args `template` (string), `version` (integer, min 1). Load the version with the same eager loads as `StructureVersionController::findVersion()` and return `new StructureVersionResource($version)->withStructure()->resolve()`. Test in `tests/Unit/Mcp/Tools/GetTemplateVersionTest.php`: returns the section title and a field label of a seeded draft; an unknown version number answers "That version does not exist."
- [X] T009 [P] [US1] `app/Mcp/Tools/CreateTemplateDraft.php`, mirrors `api.v1.templates.versions.create`, arg `template`. Same starting point logic as `StructureVersionController::create` (`publishedVersion ?? versions()->first()`; none → `Response::error` with the controller's sentence), then `CreateStructureVersionDraft`, return `StructureVersionResource` of the draft. Description says it returns the open draft when one exists. Test: a published template gets a draft (`assertDatabaseHas('structure_versions', ['status' => 'draft', ...])`); called twice leaves one draft
- [X] T010 [P] [US1] `app/Mcp/Tools/PublishTemplateVersion.php`, mirrors `api.v1.templates.versions.publication.update`, args `template`, `version`, `change_note` (nullable string, max 65535). Catch `InvalidArgumentException`, `PublishedVersionIsFrozen`, `CannotPublishAnEmptyPage` → `Response::error`. Test: a draft with a section becomes published; publishing an already published version returns the frozen message
- [X] T011 [P] [US1] `app/Mcp/Tools/DeleteTemplateDraft.php`, `#[IsDestructive]`, mirrors `api.v1.templates.versions.destroy`, args `template`, `version`. Catch `PublishedVersionIsFrozen`. Test: a draft is gone (`assertModelMissing`); a published version is refused and remains

### Tools: sections in a version

- [X] T012 [P] [US1] `app/Mcp/Tools/AddTemplateSection.php`, mirrors `api.v1.templates.versions.sections.create`, the rules of T006 plus `template`, `version`, `page` (nullable string, looked up with the trait's `page()`), `title_override`, `is_collapsed_by_default`, `section_version_number`. Mirror both branches of T006, including the published version refusal before `CreateSection` (return `Response::error` with the same sentence). For `section_id`, resolve the section version as `StructureVersionSectionController::sectionVersion()` does, with a missing section answering "That section does not exist.". Catch `SectionIsAlreadyInTheStructureVersion` and `InvalidArgumentException`. Description: give either `name` for a new section or `section_id` for one the account has. Test in `tests/Unit/Mcp/Tools/AddTemplateSectionTest.php`: a new section by name; an existing section by id; the same existing section twice is refused
- [X] T013 [P] [US1] `app/Mcp/Tools/UpdateTemplateSection.php`, mirrors `api.v1.templates.versions.sections.update`, args `template`, `version`, `section`, `title_override` (nullable string, max 255), `is_collapsed_by_default` (boolean). An argument left out keeps the row's current value (research R6): use `array_key_exists` on the validated array so an explicit `null` title clears it. Catch `PublishedVersionIsFrozen`. Test: changing only `is_collapsed_by_default` keeps the existing `title_override`; a published version is refused
- [X] T014 [P] [US1] `app/Mcp/Tools/RemoveTemplateSection.php`, `#[IsDestructive]`, mirrors `api.v1.templates.versions.sections.destroy`, args `template`, `version`, `section`. Catch `PublishedVersionIsFrozen`. Test: the row is gone from the draft
- [X] T015 [P] [US1] `app/Mcp/Tools/SetTemplateSectionPosition.php`, mirrors `api.v1.templates.versions.sections.position.update`, args `template`, `version`, `section`, `position` (integer, min 1). Catch `PublishedVersionIsFrozen`. Test: moving the second section to 1 swaps the positions

### Tools: fields in a section

- [X] T016 [P] [US1] `app/Mcp/Tools/CreateTemplateField.php`, mirrors `api.v1.templates.versions.sections.fields.create`, args `template`, `version`, `section` plus the controller's rules for `type`, `label`, `key`, `value_list_id`, `relation_target`, `relation_entity_type_id`, `parent_id`. Run `MakeSectionVersionEditable` first, resolve parent, value list and entity type inside the account (missing → "does not exist" through the trait's `doesNotExist`), catch `CannotNestARepeater|InvalidArgumentException`. Return `FieldResource`. Test: a text field "Beet variety" is created; a repeater inside a repeater is refused with "A repeater cannot sit inside another repeater."
- [X] T017 [P] [US1] `app/Mcp/Tools/UpdateTemplateField.php`, mirrors `api.v1.templates.versions.sections.fields.update`, args `template`, `version`, `section`, `field` plus every input of `FieldController::update`, none required. Carry the field by `key` across `MakeSectionVersionEditable` as `FieldController::keyOf()`/`fieldIn()` do. Every argument left out keeps the field's current value (research R6), then call `UpdateField` with the full set. Catch `InvalidArgumentException` and `AcceptedFileKindIsNotValid`. Description: the field may come back with a new id; use the returned one afterwards. Tests: setting only `is_required: true` keeps `help_text` and `is_sensitive`; a field of a published section comes back with a different id and the published field is unchanged
- [X] T018 [P] [US1] `app/Mcp/Tools/DeleteTemplateField.php`, `#[IsDestructive]`, mirrors `api.v1.templates.versions.sections.fields.destroy`, args `template`, `version`, `section`, `field`, same key carry as T017. Test: the field is gone from the draft section
- [X] T019 [P] [US1] `app/Mcp/Tools/SetTemplateFieldPosition.php`, mirrors `api.v1.templates.versions.sections.fields.position.update`, args `template`, `version`, `section`, `field`, `position` (integer, min 1), same key carry. Test: moving the second field to 1 swaps the positions

### Wiring

- [X] T020 [US1] Register T008 to T019 in `app/Mcp/Servers/MonicaServer.php` (`use` lines and `$tools`), and add one paragraph to its `#[Instructions]`: a template's sections and fields change only on a draft version; open one with create-template-draft, read section and field ids from get-template-version, use the ids a changing tool returns, and nothing reaches records until publish-template-version
- [X] T021 [US1] In `app/ViewModels/Docs/McpDocsViewModel.php::toolGroups()`, add after "Templates": `Template drafts` (changesData true: create-template-draft, publish-template-version, delete-template-draft), `Sections` (changesData true: add-template-section, update-template-section, remove-template-section, set-template-section-position), `Fields` (changesData true: create-template-field, update-template-field, delete-template-field, set-template-field-position), and add get-template-version to the read group. One sentence each, in the voice of the existing ones; the three removing tools start with "Deletes" or "Removes". Rewrite `it_marks_only_the_contact_changing_tools_as_changing_data` in `tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php` to assert the exact set of groups that change data
- [X] T022 [US1] Add to `tests/Feature/Mcp/McpCompartmentTest.php`: `it_reports_a_section_of_another_template_as_not_existing` (a row id from template B asked through template A of the same account answers "That section does not exist.") and `it_writes_nothing_into_a_template_of_another_account` (add-template-section on another account's template answers "That template does not exist." and no section is created). Add to `tests/Feature/Mcp/McpChangelogTest.php`: `it_records_a_template_change_as_having_arrived_over_mcp` using `add-template-section`
- [X] T023 [US1] Run `php artisan test --compact tests/Unit/Mcp tests/Feature/Mcp tests/Unit/ViewModels/Docs tests/Feature/Controllers/Api`

**Checkpoint**: US1 works end to end through MCP; the docs table lists its tools.

## Phase 4: User Story 2 - Manage templates themselves (Priority: P2)

**Goal**: an assistant reads, creates, renames, duplicates, activates, orders and deletes templates.

**Independent Test**: spec US2 through MCP alone.

- [X] T024 [P] [US2] `app/Mcp/Tools/GetTemplate.php`, `#[IsReadOnly]`, mirrors `api.v1.templates.show`, arg `template`; load `structure.entityType` and `publishedVersion`, return `TemplateResource`. Test: returns the template's name
- [X] T025 [P] [US2] `app/Mcp/Tools/CreateTemplate.php` (import the action `as CreateTemplateAction`), mirrors `api.v1.templates.create`, args `name` (required, max 255), `applies_to` (enum `StructureAppliesToEnum`), `description` (nullable, max 65535), `entity_type` (the controller's `entity_type_id` rules including `Rule::requiredIf` for `applies_to` entity and the account scoped exists). Test: a contact template "Dundie nominee" is created; an entity template without an entity type is refused
- [X] T026 [P] [US2] `app/Mcp/Tools/UpdateTemplate.php` (action `as UpdateTemplateAction`), mirrors `api.v1.templates.update`, args `template`, `name` (nullable string, max 255), `description` (nullable string, max 65535); a left out argument keeps the current value (R6). Test: changing only the description keeps the name
- [X] T027 [P] [US2] `app/Mcp/Tools/DeleteTemplate.php`, `#[IsDestructive]`, mirrors `api.v1.templates.destroy`, arg `template`; catch `CannotDeleteATemplateInUse`. Test: an unused template is gone; a template describing a contact is refused and remains
- [X] T028 [P] [US2] `app/Mcp/Tools/SetTemplateActivation.php`, mirrors `api.v1.templates.activation.update`, args `template`, `is_active` (required boolean); `ActivateTemplate` or `DeactivateTemplate` with two separate `if` blocks as `SetContactFavourite` does. Test: deactivating then activating flips `is_active`
- [X] T029 [P] [US2] `app/Mcp/Tools/SetTemplatePosition.php`, mirrors `api.v1.templates.position.update`, args `template`, `position` (integer, min 1). Test: moving the second template to 1 swaps the positions
- [X] T030 [P] [US2] `app/Mcp/Tools/DuplicateTemplate.php` (action `as DuplicateTemplateAction`), mirrors `api.v1.templates.duplicate.create`, args `template`, `name` (nullable, max 255), `slug` (nullable, max 255). Test: the copy exists with the given name
- [X] T031 [US2] Register T024 to T030 in `app/Mcp/Servers/MonicaServer.php`; add get-template to the read group and a `Templates, changing` group (changesData true) with the other six to `McpDocsViewModel::toolGroups()`; delete-template's sentence starts with "Deletes". Update the changing groups assertion from T021
- [X] T032 [US2] Add `it_reports_a_template_of_another_account_as_not_existing_when_deleting` to `tests/Feature/Mcp/McpCompartmentTest.php` (delete-template on another account's template answers "That template does not exist." and the template remains). Run `php artisan test --compact tests/Unit/Mcp tests/Feature/Mcp tests/Unit/ViewModels/Docs`

**Checkpoint**: US2 works through MCP.

## Phase 5: User Story 3 - Know what the assistant can now change (Priority: P3)

**Goal**: `/docs/mcp` and `/docs/mcp.md` explain editing a template through a draft, and list every tool.

**Independent Test**: spec US3.

- [X] T033 [US3] In `app/ViewModels/Docs/McpDocsViewModel.php`: add `['id' => 'templates', 'label' => 'Edit a template']` to the sections list after `tools`; add a method returning its copy: two short paragraphs (sections and fields change on a draft; nothing reaches records until it is published; the update tools keep what you leave out; a changed field may come back with a new id) and the worked example as an ordered list of the tool calls from `specs/057-mcp-template-tools/quickstart.md` steps 1 to 7. Write it into `markdown()` in the same place. No dashes as punctuation. Update `toolsSummary()` if its second sentence no longer holds
- [X] T034 [US3] Draw the new section in `resources/views/app/docs/mcp.blade.php` with the components the page already uses for its other sections (read the file first; `.claude/rules/components.md`)
- [X] T035 [US3] In `tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php`, extend `it_writes_every_section_and_configuration_in_the_markdown` to cover "Edit a template" and one tool name from the worked example; add a feature assertion in the existing `/docs/mcp` controller test that the page shows "Edit a template". Run `php artisan test --compact tests/Unit/ViewModels/Docs tests/Feature/Controllers/App/Docs`

**Checkpoint**: the page and its Markdown carry 32 tools and the new section.

## Phase 6: Polish

- [X] T036 [P] Add `#[IsReadOnly]` to the ten existing read tools in `app/Mcp/Tools/` (`GetContact`, `GetMeContact`, `ListContacts`, `ListEntityTypes`, `ListLifeEventCategories`, `ListRelationshipCategories`, `ListRelationshipTypes`, `ListTemplates`, `ListTemplateVersions`, `ListVaults`), research R7
- [X] T037 [P] Add the feature to `docs/FEATURES.md` following the `feature-documentator` skill
- [X] T038 Run `php artisan monica:localize --check`, `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, then the whole suite with `php artisan test --compact`, and fix what they report

## Dependencies

- Phase 2 blocks everything: T003 is used by every tool; T001/T002 decide what the section and field tools catch.
- US1: T005 → T006 → T007 and T012 (the tool mirrors the extended endpoint). T008 to T019 are independent files after T003. T020 and T021 after all of them; T022 and T023 last.
- US2 depends only on Phase 2. It can run before or alongside US1, except T031 and T020 both edit `MonicaServer.php` and `McpDocsViewModel.php`, so run those two tasks one after the other.
- US3 after US1 and US2, so the page lists the final set.
- Polish last.

## Parallel examples

- US1: T008, T009, T010, T011, T013, T014, T015, T016, T017, T018, T019 together once T003 is done; T012 once T006 is done.
- US2: T024 to T030 together.
- Polish: T036 and T037 together.

## Implementation strategy

1. Phase 2, then US1 (MVP): an assistant can build and publish a template's contents.
2. US2: template level management.
3. US3: the page explains it.
4. Polish and the four done checks from `CLAUDE.md`.
