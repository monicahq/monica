---

description: "Task list for MCP tools for entity types"
---

# Tasks: MCP tools for entity types

**Input**: Design documents from `specs/058-mcp-entity-type-tools/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[contracts/mcp-tools.md](contracts/mcp-tools.md), [quickstart.md](quickstart.md)

**Tests**: required. Constitution principle III and `CLAUDE.md` ("a change is done when what you
wrote is covered by a test"). Tests follow `.claude/rules/testing.md`: `declare(strict_types=1);`,
`#[Test]`, snake_case `it_...` names, `RefreshDatabase`, factories, The Office data, no comments.

**Model for every tool**: copy the shape of its template sibling in `app/Mcp/Tools`
(`GetTemplate`, `CreateTemplate`, `UpdateTemplate`, `DeleteTemplate`, `SetTemplateActivation`,
`SetTemplatePosition`): `use MirrorsAnApiCapability;`, `protected string $mirrors`, `#[Description]`,
`handle(Request $request): Response` with `$request->validate([...])`, `schema(JsonSchema $schema)`
returning `array<string, Type>`, `Response::json(new EntityTypeResource($entityType)->resolve())`.
Import the action under an alias where the names collide (`use App\Actions\CreateEntityType as
CreateEntityTypeAction;`). The `entity_type` input is described as "The identifier of the entity
type, as list-entity-types gives it."

**Model for every tool test**: `tests/Unit/Mcp/Tools/DeleteTemplateTest.php`:
`MonicaServer::actingAs($user)->tool(Tool::class, [...])->assertOk()->assertHasNoErrors()` or
`->assertHasErrors(['sentence'])`, plus `it_names_the_api_capability_it_mirrors`.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

None. No dependency, folder, migration or route is added.

---

## Phase 2: Foundational (blocks every story)

- [X] T001 Add `protected function entityType(Request $request, string $address): EntityType` to `app/Mcp/Tools/MirrorsAnApiCapability.php`, after `template()`: `$this->user($request)->account->entityTypes()->where('uuid', $address)->first()`, and when null `throw $this->doesNotExist(__('That entity type does not exist.'));` (research R3). Import `App\Models\EntityType`.

**Checkpoint**: the lookup exists; the tool classes can be written.

---

## Phase 3: User Story 1 - Create and shape an entity type (P1) MVP

**Goal**: an assistant reads, creates and updates an entity type.

**Independent Test**: through MCP, create **Dunder Mifflin paper / Dunder Mifflin papers**, read it
back, change its colour only, and see names and slug unchanged.

### Tests

- [X] T002 [P] [US1] Write `tests/Unit/Mcp/Tools/GetEntityTypeTest.php`: `it_returns_an_entity_type` (asserts the name through `assertSee`), `it_reports_an_entity_type_of_another_account_as_not_existing` (`assertHasErrors(['That entity type does not exist.'])`), `it_names_the_api_capability_it_mirrors` (`api.v1.entityTypes.show`).
- [X] T003 [P] [US1] Write `tests/Unit/Mcp/Tools/CreateEntityTypeTest.php`: `it_creates_an_entity_type` with singular, plural, icon `pet` and colour `#4f8a49`, asserting `assertDatabaseHas('entity_types', [...])` with the account, `is_active` true and a derived slug; `it_refuses_a_slug_another_type_of_the_account_uses`; `it_refuses_a_slug_with_capitals_or_spaces`; `it_refuses_an_icon_outside_the_allowed_ones` (icon `dundie`); `it_names_the_api_capability_it_mirrors` (`api.v1.entityTypes.create`). Each refusal also asserts the row count is unchanged.
- [X] T004 [P] [US1] Write `tests/Unit/Mcp/Tools/UpdateEntityTypeTest.php`: `it_changes_only_what_it_is_given` (sends colour only, then `refresh()` and asserts names, description and slug unchanged, colour changed); `it_clears_the_description_when_given_null`; `it_refuses_a_colour_outside_the_allowed_ones`; `it_names_the_api_capability_it_mirrors` (`api.v1.entityTypes.update`).

### Implementation

- [X] T005 [P] [US1] Create `app/Mcp/Tools/GetEntityType.php`: `#[IsReadOnly]`, `#[Description('Return one kind of record the account keeps besides contacts: its names, slug, icon, colour, position, whether new records can be added and how many exist.')]`, mirrors `api.v1.entityTypes.show`; validates `entity_type` required string; returns the resource of `$this->entityType($request, ...)`.
- [X] T006 [P] [US1] Create `app/Mcp/Tools/CreateEntityType.php`: mirrors `api.v1.entityTypes.create`, description ends "The change is written to the account journal."; rules copied from `App\Http\Controllers\Api\EntityTypeController::create()` verbatim: `name_singular` and `name_plural` `['required', 'string', 'max:255']`, `description` `['nullable', 'string', 'max:65535']`, `icon` `['nullable', 'string', Rule::in(EntityType::ICONS)]`, `colour` `['nullable', 'string', Rule::in(EntityType::COLOURS)]`, `is_active` `['nullable', 'boolean']`, `slug` `['nullable', 'string', 'max:'.EntityType::SLUG_LENGTH, 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('entity_types', 'slug')->where('account_id', $user->account_id)]`. Calls `CreateEntityTypeAction` with `isActive: $validated['is_active'] ?? true`. Schema: the `icon` and `colour` descriptions list the allowed values with `implode(', ', EntityType::ICONS)` / `COLOURS` (research R6); `slug` says it is fixed once created and derived from the singular name when left out.
- [X] T007 [P] [US1] Create `app/Mcp/Tools/UpdateEntityType.php`: mirrors `api.v1.entityTypes.update`; every attribute optional (research R2): `name_singular`, `name_plural` `['nullable', 'string', 'max:255']`, `description` `['nullable', 'string', 'max:65535']`, `icon` `['nullable', 'string', Rule::in(EntityType::ICONS)]`, `colour` `['nullable', 'string', Rule::in(EntityType::COLOURS)]`. Fills what is missing from the stored type (`$validated['name_singular'] ?? $entityType->name_singular`; `description` with `array_key_exists`, as `UpdateTemplate` does) and calls `UpdateEntityTypeAction`. Description says anything left out keeps its value and the slug cannot change. Returns the resource of `$entityType->refresh()`.
- [X] T008 [US1] Register `GetEntityType`, `CreateEntityType` and `UpdateEntityType` in `$tools` in `app/Mcp/Servers/MonicaServer.php`, next to `ListEntityTypes`, and import them.

**Checkpoint**: T002 to T004 pass; `tests/Feature/Mcp/McpCatalogueTest.php` passes.

---

## Phase 4: User Story 2 - Organise and retire entity types (P2)

**Goal**: an assistant switches a type off or on, moves it, and deletes an empty one.

**Independent Test**: through MCP, deactivate a type, move it first, reactivate it, delete an empty
one, and be refused on one holding records.

### Tests

- [X] T009 [P] [US2] Write `tests/Unit/Mcp/Tools/SetEntityTypeActivationTest.php`: `it_switches_an_entity_type_off` and `it_switches_an_entity_type_on` (`refresh()` then `assertFalse`/`assertTrue` on `is_active`); `it_journals_nothing_when_the_type_is_already_in_that_state` (`Queue::fake()`, then `Queue::assertNotPushed(LogUserAction::class)`); `it_names_the_api_capability_it_mirrors` (`api.v1.entityTypes.activation.update`).
- [X] T010 [P] [US2] Write `tests/Unit/Mcp/Tools/SetEntityTypePositionTest.php`: `it_moves_an_entity_type` (three types, moves the third to position 1, asserts the order of `account->entityTypes()->orderBy('position')->pluck('uuid')`); `it_names_the_api_capability_it_mirrors` (`api.v1.entityTypes.position.update`).
- [X] T011 [P] [US2] Write `tests/Unit/Mcp/Tools/DeleteEntityTypeTest.php`: `it_deletes_an_entity_type_holding_no_record` (`assertSoftDeleted`, since `EntityType` uses `SoftDeletes`); `it_refuses_an_entity_type_still_holding_records` (`entity_count` 3, `assertHasErrors(['The type still holds records and stays.'])`, `assertModelExists`); `it_names_the_api_capability_it_mirrors` (`api.v1.entityTypes.destroy`).

### Implementation

- [X] T012 [P] [US2] Create `app/Mcp/Tools/SetEntityTypeActivation.php`: mirrors `api.v1.entityTypes.activation.update`; validates `entity_type` required string, `is_active` `['required', 'boolean']`; returns early with the resource when `$validated['is_active'] === $entityType->is_active` (research R5, as `EntityTypeActivationController::settle()`); otherwise `ActivateEntityType` or `DeactivateEntityType`. Description: "Switch a kind of record on, so new records of it can be added, or off. Existing records stay. The change is written to the account journal."
- [X] T013 [P] [US2] Create `app/Mcp/Tools/SetEntityTypePosition.php`: mirrors `api.v1.entityTypes.position.update`; validates `position` `['required', 'integer', 'min:1']`; calls `UpdateEntityTypePosition` with `(int) $validated['position']`; returns the resource of `$entityType->refresh()`.
- [X] T014 [P] [US2] Create `app/Mcp/Tools/DeleteEntityType.php`: `#[IsDestructive]`, mirrors `api.v1.entityTypes.destroy`; calls `DestroyEntityType` inside `try`, `catch (AuthorizationException $e) { return Response::error($e->getMessage()); }` (research R4); answers `Response::text(__('The entity type is deleted.'))`. Description: "Delete a kind of record. A type still holding records is refused; switch it off instead. The change is written to the account journal."
- [X] T015 [US2] Register `SetEntityTypeActivation`, `SetEntityTypePosition` and `DeleteEntityType` in `$tools` in `app/Mcp/Servers/MonicaServer.php`.
- [X] T016 [US2] In `tests/Feature/Mcp/McpCompartmentTest.php`, add `it_does_not_delete_an_entity_type_of_another_account` (as `it_does_not_delete_a_template_of_another_account`: `EntityType::factory()->create()`, `assertHasErrors(['That entity type does not exist.'])`, `assertModelExists`).
- [X] T017 [US2] In `tests/Feature/Mcp/McpChangelogTest.php`, add `it_records_an_entity_type_change_as_having_arrived_over_mcp` (as the template case: invoke `create-entity-type` with the key named `Dundie awards bot`, then `assertDatabaseHas('logs', ['source' => LogSourceEnum::Mcp->value, 'source_detail' => 'Dundie awards bot'])`).
- [X] T018 [US2] Add one sentence to the `#[Instructions]` text in `app/Mcp/Servers/MonicaServer.php`: an entity type is a kind of record besides contacts, its records follow a template created with create-template for that entity type, and a type still holding records can only be switched off, not deleted.

**Checkpoint**: T009 to T011, T016 and T017 pass; `McpCatalogueTest` passes (it checks the
`delete-` prefix carries `destructiveHint`).

---

## Phase 5: User Story 3 - Know what the assistant can now change (P3)

**Goal**: `/docs/mcp` and `/docs/mcp.md` list the new tools and explain entity types.

**Independent Test**: `McpDocsViewModelTest::it_lists_exactly_the_tools_the_server_registers` passes.

- [X] T019 [US3] In `app/ViewModels/Docs/McpDocsViewModel.php` `toolGroups()`: add `['name' => 'get-entity-type', 'description' => 'Returns one kind of record: its names, icon, colour, whether new records can be added and how many exist.']` to the "Entity types" group; add after it `['name' => 'Entity types, changing', 'changesData' => true, 'tools' => [...]]` with `create-entity-type` ("Adds a kind of record, such as Animal or Wine bottle."), `update-entity-type` ("Renames a kind of record or changes its description, icon or colour. What you leave out keeps its value."), `set-entity-type-activation` ("Switches a kind of record on or off for new records. Existing ones stay."), `set-entity-type-position` ("Moves a kind of record to another place in the list."), `delete-entity-type` ("Deletes a kind of record that holds no record.").
- [X] T020 [US3] In the same file: add `['id' => 'entity-types', 'label' => 'Manage entity types']` to `SECTIONS` after `templates`; add `public const array ENTITY_TYPES` (one or two paragraphs: an entity type is a kind of record kept besides contacts; records of it follow a template, created with `create-template` and `applies_to` set to `entity`; a type still holding records cannot be deleted, only switched off); in `markdown()`, after the template steps, write `## Manage entity types`, a blank line and `$this->paragraphs(self::ENTITY_TYPES)`. No dashes as punctuation in the copy.
- [X] T021 [US3] In `resources/views/app/docs/mcp.blade.php`, after the `templates` section, add `<section id="entity-types" aria-labelledby="entity-types-heading" ...>` with the same classes as the `templates` section, an `<h2 id="entity-types-heading" class="{{ $heading }}">Manage entity types</h2>`, and `@foreach (McpDocsViewModel::ENTITY_TYPES as $paragraph)` rendered with `{!! $inline($paragraph) !!}` if the paragraphs carry backticks, as the steps do, otherwise `{{ $paragraph }}` like `TEMPLATES`.
- [X] T022 [US3] In `tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php` `it_marks_only_the_groups_that_change_data`, add `'Entity types, changing'` to the expected list after `'Fields'`. In `tests/Feature/Controllers/App/Docs/McpDocsControllerTest.php`, add `$response->assertSee('Manage entity types');` next to `assertSee('Edit a template')`.

**Checkpoint**: `tests/Unit/ViewModels/Docs` and `tests/Feature/Controllers/App/Docs` pass.

---

## Phase 6: Polish

- [X] T023 Run `php artisan monica:localize`, translate what it reports missing (`That entity type does not exist.`, `The entity type is deleted.`) in every configured locale following `.claude/rules/translations.md` and the glossary, then `php artisan monica:localize --check`.
- [X] T024 Add one line to `docs/FEATURES.md` in the style of the existing MCP entries: `* MCP: Let an assistant create, change, reorder, switch off and delete entity types.`
- [X] T025 Run `php artisan test --compact tests/Unit/Mcp tests/Feature/Mcp tests/Unit/ViewModels/Docs tests/Feature/Controllers/App/Docs`, then `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`, and walk the scenarios in [quickstart.md](quickstart.md).

---

## Dependencies

- T001 blocks every tool (T005 to T007, T012 to T014).
- US1 and US2 are independent of each other once T001 is done; both edit `MonicaServer.php` (T008,
  T015, T018), so those tasks run one after another.
- US3 (T019 to T022) needs every tool registered, since `it_lists_exactly_the_tools_the_server_registers`
  compares against the server.
- T023 to T025 come last.

## Parallel execution

- US1: T002, T003, T004 together; then T005, T006, T007 together; then T008.
- US2: T009, T010, T011 together; then T012, T013, T014 together; then T015 to T018 in order.

## Implementation strategy

MVP is US1 (T001 to T008): an assistant can create and shape a type, which is what the template
tools need before an entity template can exist. US2 adds organising and deleting. US3 must land in the
same pull request, since the docs test fails as soon as a tool is registered without being listed.
