---

description: "Task list for MCP tools for life event types"
---

# Tasks: MCP tools for life event types

**Input**: Design documents from `specs/060-mcp-life-event-type-tools/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[contracts/mcp-tools.md](contracts/mcp-tools.md), [quickstart.md](quickstart.md)

**Tests**: required. Constitution principle III and `CLAUDE.md`. Tests follow
`.claude/rules/testing.md`: `declare(strict_types=1);`, `#[Test]`, snake_case `it_...` names,
`RefreshDatabase`, factories, The Office data, no comments.

**Model for every tool**: copy its relationship sibling in `app/Mcp/Tools` (`GetRelationshipType`,
`CreateRelationshipType`, `UpdateEntityType` for the keep-what-is-left-out update,
`DeleteRelationshipType`, `SetRelationshipTypeActivation`, `SetRelationshipTypePosition`,
`ListRelationshipCategoryTypes`, `CreateRelationshipCategory`, `UpdateRelationshipCategory`,
`SetRelationshipCategoryActivation`, `SetRelationshipCategoryPosition`): `use MirrorsAnApiCapability;`,
`protected string $mirrors`, `#[Description]` ending "The change is written to the account journal."
for changing tools, `handle(Request $request): Response` with `$request->validate([...])`,
`schema(JsonSchema $schema)` returning `array<string, Type>`. A type answers
`Response::json(new LifeEventTypeResource($type->refresh())->resolve())`; a category
`LifeEventCategoryResource`. Import the action under an `...Action` alias where names collide
(research R8).

Input descriptions: `life_event_type` is "The identifier of the life event type, as
list-life-event-types gives it."; `life_event_category` and `life_event_category_id` are "The
identifier of the category, as list-life-event-categories gives it."

**Model for every tool test**: `tests/Unit/Mcp/Tools/DeleteRelationshipTypeTest.php`:
`MonicaServer::actingAs($user)->tool(Tool::class, [...])`, `assertHasErrors(['sentence'])`, plus
`it_names_the_api_capability_it_mirrors`. Factory states: `LifeEventType::factory()->supplied()`,
`->inactive()` (its `configure()` creates the structure); `LifeEventCategory::factory()->system()`,
`->inactive()`; `LifeEvent::factory()->create(['vault_id' => $vault->id, 'life_event_type_id' => $type->id])`
with `$vault = Vault::factory()->create(['account_id' => $user->account_id])` puts a type in use.
Allowed values for tests: icon `briefcase`, colour `#4f8a49`, field kinds `text`, `textarea`, `date`.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

None. No dependency, folder, migration or route is added.

---

## Phase 2: Foundational (blocks every story)

- [X] T001 Add to `app/Mcp/Tools/MirrorsAnApiCapability.php`, after `relationshipCategory()`: `protected function lifeEventType(Request $request, string $address): LifeEventType` (`$this->user($request)->account->lifeEventTypes()->where('uuid', $address)->first()`, null throws `$this->doesNotExist(__('That life event type does not exist.'))`) and `protected function lifeEventCategory(Request $request, string $address): LifeEventCategory` (`account->lifeEventCategories()`, `__('That life event category does not exist.')`). Import both models (research R4).

**Checkpoint**: the lookups exist.

---

## Phase 3: User Story 1 - Create and shape a life event type (P1) MVP

**Goal**: an assistant lists, reads, creates and updates a life event type.

**Independent Test**: through MCP, list the types, create **Won a Dundie**, read it back, change only
its colour.

### Tests

- [X] T002 [P] [US1] Write `tests/Unit/Mcp/Tools/ListLifeEventTypesTest.php`: `it_lists_the_types_by_category_then_position` (two categories at positions 1 and 2, types placed so id order differs from the expected order; call the tool's `handle()` directly after `$this->actingAs($user)`, as `ListRelationshipCategoryTypesTest` does, and assert the names in order); `it_leaves_out_the_types_of_another_account` (`assertDontSee`); `it_names_the_api_capability_it_mirrors` (`api.v1.lifeEventTypes`).
- [X] T003 [P] [US1] Write `tests/Unit/Mcp/Tools/GetLifeEventTypeTest.php`: `it_returns_a_life_event_type` (`assertSee` name and category uuid); `it_reports_a_life_event_type_of_another_account_as_not_existing`; `it_names_the_api_capability_it_mirrors` (`api.v1.lifeEventTypes.show`).
- [X] T004 [P] [US1] Write `tests/Unit/Mcp/Tools/CreateLifeEventTypeTest.php`: `it_creates_a_life_event_type` (Won a Dundie, icon `briefcase`, colour `#4f8a49`; `assertDatabaseHas('life_event_types', [...])` with account and category id); `it_refuses_an_icon_outside_the_allowed_ones` (icon `dundie`, row count unchanged); `it_refuses_a_category_of_another_account` (`That life event category does not exist.`); `it_names_the_api_capability_it_mirrors` (`api.v1.lifeEventTypes.create`).
- [X] T005 [P] [US1] Write `tests/Unit/Mcp/Tools/UpdateLifeEventTypeTest.php`: `it_changes_only_what_it_is_given` (type with description and icon, send colour only, `refresh()`, assert name, category, description, icon, `is_active` unchanged); `it_clears_the_description_when_given_null`; `it_moves_a_type_to_another_category`; `it_refuses_a_colour_outside_the_allowed_ones`; `it_names_the_api_capability_it_mirrors` (`api.v1.lifeEventTypes.update`).

### Implementation

- [X] T006 [P] [US1] Create `app/Mcp/Tools/ListLifeEventTypes.php`: `#[IsReadOnly]`, mirrors `api.v1.lifeEventTypes`, "List the kinds of life event the account can record, such as Moved house or Started a job, grouped by category."; the query of `App\Http\Controllers\Api\LifeEventTypeController::index()` with `->get()` instead of `paginate()` (join `life_event_categories`, `select('life_event_types.*')`, `with('lifeEventCategory')`, order by category position, type position, type id); `LifeEventTypeResource::collection(...)->resolve()`.
- [X] T007 [P] [US1] Create `app/Mcp/Tools/GetLifeEventType.php`: `#[IsReadOnly]`, mirrors `api.v1.lifeEventTypes.show`, "Read one kind of life event: its name, description, icon, colour, category, position, whether it is offered and whether it came with the account."
- [X] T008 [P] [US1] Create `app/Mcp/Tools/CreateLifeEventType.php`: mirrors `api.v1.lifeEventTypes.create`; rules `life_event_category_id` `['required', 'string']`, `name` `['required', 'string', 'max:255']`, `description` `['nullable', 'string', 'max:255']`, `icon` `['nullable', 'string', Rule::in(Section::ICONS)]`, `colour` `['nullable', 'string', Rule::in(VaultColourEnum::values())]`; category through `$this->lifeEventCategory()`; calls `CreateLifeEventTypeAction`. Schema lists icons and colours with `implode(', ', ...)` (research R6). Description: "Create a kind of life event, such as Ran a marathon, in one of the account’s categories. Give it fields of its own with replace-life-event-type-fields. The change is written to the account journal."
- [X] T009 [P] [US1] Create `app/Mcp/Tools/UpdateLifeEventType.php`: mirrors `api.v1.lifeEventTypes.update`; `life_event_category_id` `['sometimes', 'required', 'string']`, `name` `['sometimes', 'required', 'string', 'max:255']`, `description` `['nullable', 'string', 'max:255']`, `icon` `['nullable', 'string', Rule::in(Section::ICONS)]`, `colour` `['nullable', 'string', Rule::in(VaultColourEnum::values())]`. Fills what is missing from the stored type; `description`, `icon`, `colour` use `array_key_exists` so `null` clears (research R3). Calls `UpdateLifeEventTypeAction`. Description says anything left out keeps its value, null clears description, icon or colour, and whether it is offered changes with set-life-event-type-activation.
- [X] T010 [US1] Register `ListLifeEventTypes`, `GetLifeEventType`, `CreateLifeEventType`, `UpdateLifeEventType` in `$tools` in `app/Mcp/Servers/MonicaServer.php`, next to `ListLifeEventCategories`, and import them.

**Checkpoint**: T002 to T005 pass; `tests/Feature/Mcp/McpCatalogueTest.php` passes.

---

## Phase 4: User Story 2 - Organise and retire life event types (P2)

**Goal**: an assistant switches a type off or on, moves it within its category, and deletes one.

**Independent Test**: deactivate, move first, reactivate, delete an unused one; be refused on a
supplied one and on one in use.

### Tests

- [X] T011 [P] [US2] Write `tests/Unit/Mcp/Tools/SetLifeEventTypeActivationTest.php`: off, on (from `->inactive()`), `it_journals_nothing_when_the_type_is_already_in_that_state` (`Queue::fake()`, `Queue::assertNotPushed(LogUserAction::class)`), mirrors `api.v1.lifeEventTypes.activation.update`.
- [X] T012 [P] [US2] Write `tests/Unit/Mcp/Tools/SetLifeEventTypePositionTest.php`: `it_moves_a_life_event_type_within_its_category` (three types in one category, third to position 1, assert `$category->lifeEventTypes()->orderBy('position')->pluck('uuid')`), mirrors `api.v1.lifeEventTypes.position.update`.
- [X] T013 [P] [US2] Write `tests/Unit/Mcp/Tools/DeleteLifeEventTypeTest.php`: `it_deletes_a_life_event_type_nobody_used` (`assertSoftDeleted`; both life event models use `SoftDeletes`); `it_refuses_a_life_event_type_that_came_with_the_account` (`->supplied()`, `The type came with your account and stays.`); `it_refuses_a_life_event_type_life_events_were_recorded_with` (`Life events were recorded with this type, so it stays.`); mirrors `api.v1.lifeEventTypes.destroy`.

### Implementation

- [X] T014 [P] [US2] Create `app/Mcp/Tools/SetLifeEventTypeActivation.php`: as `SetRelationshipTypeActivation` with `ActivateLifeEventType` / `DeactivateLifeEventType`, skipping when unchanged (research R9). "Offer a kind of life event when somebody records one, or stop offering it. Life events already recorded with it stay. The change is written to the account journal."
- [X] T015 [P] [US2] Create `app/Mcp/Tools/SetLifeEventTypePosition.php`: as `SetRelationshipTypePosition` with `UpdateLifeEventTypePosition`; `position` "Where it goes within its category, counting from 1."
- [X] T016 [P] [US2] Create `app/Mcp/Tools/DeleteLifeEventType.php`: `#[IsDestructive]`, `DestroyLifeEventType` inside `try`, `catch (AuthorizationException $e) { return Response::error($e->getMessage()); }`, success `Response::text(__('The life event type is deleted.'))`. "Delete a kind of life event the account created. One that came with the account, or that life events were recorded with, is refused; stop offering it instead. The change is written to the account journal."
- [X] T017 [US2] Register the three tools in `app/Mcp/Servers/MonicaServer.php`.

**Checkpoint**: T011 to T013 pass.

---

## Phase 5: User Story 3 - Manage life event categories (P2)

**Goal**: an assistant reads, lists the types of, creates, renames, moves, switches and deletes a
category.

**Independent Test**: create **Sales**, rename it, list its types, move it, switch it off and on,
delete it once empty; be refused on a supplied one and on one holding types.

### Tests

- [X] T018 [P] [US3] Write `tests/Unit/Mcp/Tools/GetLifeEventCategoryTest.php` (returns, another account not found, mirrors `api.v1.lifeEventCategories.show`) and `tests/Unit/Mcp/Tools/ListLifeEventCategoryTypesTest.php` (`it_lists_the_types_of_a_category_by_position` with `handle()` called directly and names asserted in order, another category's type left out; mirrors `api.v1.lifeEventCategories.types`).
- [X] T019 [P] [US3] Write `tests/Unit/Mcp/Tools/CreateLifeEventCategoryTest.php` (Sales, `assertDatabaseHas`, mirrors `...create`) and `tests/Unit/Mcp/Tools/UpdateLifeEventCategoryTest.php` (`it_renames_a_life_event_category`, `it_renames_a_category_that_came_with_the_account` since the action allows it, mirrors `...update`).
- [X] T020 [P] [US3] Write `tests/Unit/Mcp/Tools/DeleteLifeEventCategoryTest.php`: empty one deleted; `->system()` refused with `The category came with your account and stays.`; one holding a type refused with `Types are filed under this category, so it stays.`; mirrors `api.v1.lifeEventCategories.destroy`.
- [X] T021 [P] [US3] Write `tests/Unit/Mcp/Tools/SetLifeEventCategoryActivationTest.php` and `tests/Unit/Mcp/Tools/SetLifeEventCategoryPositionTest.php`, one class per file, as T011 and T012 (`api.v1.lifeEventCategories.activation.update`, `api.v1.lifeEventCategories.position.update`; order asserted on `$user->account->lifeEventCategories()->orderBy('position')->pluck('uuid')`).

### Implementation

- [X] T022 [P] [US3] Create `app/Mcp/Tools/GetLifeEventCategory.php` (`#[IsReadOnly]`, "Read one category of life event types: its name, position, whether it is offered, whether it came with the account and how many types it holds.") and `app/Mcp/Tools/ListLifeEventCategoryTypes.php` (`#[IsReadOnly]`, "List the kinds of life event filed under one category, in the order they are offered."; `$category->lifeEventTypes()->with('lifeEventCategory')->orderBy('position')->orderBy('id')->get()`).
- [X] T023 [P] [US3] Create `app/Mcp/Tools/CreateLifeEventCategory.php` (`name` `['required', 'string', 'max:255']`, `CreateLifeEventCategoryAction`) and `app/Mcp/Tools/UpdateLifeEventCategory.php` (`name` required, `UpdateLifeEventCategoryAction`, no refusal of its own).
- [X] T024 [P] [US3] Create `app/Mcp/Tools/DeleteLifeEventCategory.php`: `#[IsDestructive]`, `DestroyLifeEventCategory`, `catch (AuthorizationException $e)` relayed; success `__('The life event category is deleted.')`. "Delete a category the account created. One that came with the account, or that still holds types, is refused; move its types first. The change is written to the account journal."
- [X] T025 [P] [US3] Create `app/Mcp/Tools/SetLifeEventCategoryActivation.php` (`ActivateLifeEventCategory` / `DeactivateLifeEventCategory`, skipping when unchanged) and `app/Mcp/Tools/SetLifeEventCategoryPosition.php` (`UpdateLifeEventCategoryPosition`, "Where it goes in the list of categories, counting from 1.").
- [X] T026 [US3] Register the seven category tools in `app/Mcp/Servers/MonicaServer.php`, next to `ListLifeEventCategories`.

**Checkpoint**: T018 to T021 pass.

---

## Phase 6: User Story 4 - Choose what a life event type asks for (P3)

**Goal**: an assistant reads and replaces the fields of a type.

**Independent Test**: replace the fields of a type with a group holding two fields, read them back,
replace again keeping one field's key.

- [X] T027 [US4] Create `app/Helpers/LifeEventTypeFieldLayout.php` (research R5): move `COUNTED_SETTINGS`, `rules()`, `groups()`, `fields()` and `field()` out of `app/Http/Controllers/Api/LifeEventTypeFieldController.php` unchanged in behaviour, as public static methods taking plain arrays: `rules(): array`, `groups(array $groups): array` (from `$request->input('groups', [])`), `fields(array $groups, array $ungrouped): array`; `field()` stays private static. Keep the docblocks with their array shapes. Add a one sentence class docblock only if the other helpers carry one (check `FieldValueRules`).
- [X] T028 [US4] Update `app/Http/Controllers/Api/LifeEventTypeFieldController.php` to call `LifeEventTypeFieldLayout::rules()`, `::groups((array) $request->input('groups', []))` and `::fields((array) $request->input('groups', []), (array) $request->input('fields', []))`, and delete the moved private methods and constant. Run `php artisan test --compact tests/Feature/Controllers/Api/LifeEventTypeFieldControllerTest.php`: it must pass unchanged.
- [X] T029 [P] [US4] Write `tests/Unit/Mcp/Tools/GetLifeEventTypeFieldsTest.php`: `it_answers_an_empty_list_for_a_type_with_no_fields`; `it_reports_a_type_of_another_account_as_not_existing`; mirrors `api.v1.lifeEventTypes.fields`.
- [X] T030 [P] [US4] Write `tests/Unit/Mcp/Tools/ReplaceLifeEventTypeFieldsTest.php`: `it_writes_the_fields_of_a_type` (group Ceremony with Venue `text` required and Date `date`, plus an ungrouped Speech `textarea`; `assertSee(['Ceremony', 'Venue', 'Date', 'Speech'])`); `it_keeps_a_field_sent_back_under_its_key` (write Venue, read its key from `$type->refresh()->structure->publishedVersion` fields, replace with the same key labelled Place, assert the field with that key is now labelled Place and no other field exists); `it_refuses_a_kind_of_field_the_product_does_not_offer` (type `dundie`, `assertHasErrors()`, published version unchanged); `it_names_the_api_capability_it_mirrors` (`api.v1.lifeEventTypes.fields.update`).
- [X] T031 [P] [US4] Create `app/Mcp/Tools/GetLifeEventTypeFields.php`: `#[IsReadOnly]`, mirrors `api.v1.lifeEventTypes.fields`; `$version = $type->structure?->publishedVersion`; answers `[]` when null, else `StructureVersionSectionResource::collection($version->assembly)->resolve()`. "Read the fields a kind of life event asks for, in their groups, with the key that identifies each field."
- [X] T032 [P] [US4] Create `app/Mcp/Tools/ReplaceLifeEventTypeFields.php`: `#[IsDestructive]`, mirrors `api.v1.lifeEventTypes.fields.update`; validates `['life_event_type' => ['required', 'string'], ...LifeEventTypeFieldLayout::rules()]`; calls `ReplaceLifeEventTypeFieldsAction` with `groups: LifeEventTypeFieldLayout::groups((array) $request->get('groups', []))` and `fields: LifeEventTypeFieldLayout::fields((array) $request->get('groups', []), (array) $request->get('fields', []))`; `catch (ModelNotFoundException|AcceptedFileKindIsNotValid $e)` answers `Response::error($e->getMessage())` (research R2); answers the fields as T031 does from `$type->refresh()`. Schema: `groups` and `fields` as `$schema->array()` with descriptions covering the group shape (`placement_id` keeps an existing group, `name`, `fields`), the field shape (`key` keeps an existing field, `label`, `type` one of `implode(', ', FieldTypeEnum` values`)`, `is_required`, `help_text`, `settings`, `choices`), and that the set sent replaces the whole set while life events already recorded keep what they were written with. Check how `CreateTemplate` or another tool declares an array input, and follow it.
- [X] T033 [US4] Register `GetLifeEventTypeFields` and `ReplaceLifeEventTypeFields` in `app/Mcp/Servers/MonicaServer.php`.

**Checkpoint**: T029, T030 and `LifeEventTypeFieldControllerTest` pass.

---

## Phase 7: User Story 5 - Know what the assistant can now change (P3)

- [X] T034 [US5] In `app/ViewModels/Docs/McpDocsViewModel.php` `toolGroups()`: rename "Life event categories" to "Life event vocabulary" and list `list-life-event-categories`, `get-life-event-category` ("Returns one category and how many types it holds."), `list-life-event-category-types` ("Lists the kinds of life event filed under one category."), `list-life-event-types` ("Lists the kinds of life event your account can record, such as Moved house."), `get-life-event-type` ("Returns one kind of life event: its icon, colour, category and whether it is offered."), `get-life-event-type-fields` ("Returns the fields a kind of life event asks for, in their groups."). Add after it "Life event vocabulary, changing" (`changesData: true`): `create-life-event-type` ("Adds a kind of life event, such as Ran a marathon."), `update-life-event-type` ("Renames a kind of life event, changes its description, icon or colour, or moves it to another category. What you leave out keeps its value."), `set-life-event-type-activation` ("Offers a kind of life event, or stops offering it. Life events already recorded stay."), `set-life-event-type-position` ("Moves a kind of life event within its category."), `replace-life-event-type-fields` ("Replaces the fields a kind of life event asks for. A field you leave out is no longer asked for; life events already recorded keep theirs."), `delete-life-event-type` ("Deletes a kind of life event you created and nobody used."), `create-life-event-category` ("Adds a category, such as Sport."), `update-life-event-category` ("Renames a category."), `set-life-event-category-activation` ("Offers a category or stops offering it."), `set-life-event-category-position` ("Moves a category to another place in the list."), `delete-life-event-category` ("Deletes a category you created that holds no type."). No dashes as punctuation.
- [X] T035 [US5] In the same file: add `['id' => 'life-event-types', 'label' => 'Manage life event types']` to `SECTIONS` after `relationship-types`; add `public const array LIFE_EVENT_TYPES` of two paragraphs: (1) a life event type is a kind of thing that happens to a contact, such as Moved house, filed under a category such as Work or Home, and it can ask for fields of its own with `replace-life-event-type-fields`, which never changes a life event already recorded; (2) types and categories that came with your account, and any that are in use, cannot be deleted; stop offering them with `set-life-event-type-activation` or `set-life-event-category-activation` instead. In `markdown()`, after the relationship types paragraphs, write `## Manage life event types`, a blank line and the paragraphs.
- [X] T036 [US5] In `resources/views/app/docs/mcp.blade.php`, after the `relationship-types` section, add a `life-event-types` section copied from it (`aria-labelledby="life-event-types-heading"`, heading `Manage life event types`, `@foreach (McpDocsViewModel::LIFE_EVENT_TYPES as $paragraph)`).
- [X] T037 [US5] In `tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php`: `it_counts_the_tools_it_lists` to `MCP currently exposes 67 tools.`; `it_marks_only_the_groups_that_change_data` appends `'Life event vocabulary, changing'`. In `tests/Feature/Controllers/App/Docs/McpDocsControllerTest.php` add `$response->assertSee('Manage life event types');`.
- [X] T038 [US5] Add a paragraph to the `#[Instructions]` text in `app/Mcp/Servers/MonicaServer.php`, after the relationship paragraph: a life event type is a kind of thing that happens to a contact, filed under a category, and can ask for fields of its own; replace-life-event-type-fields replaces the whole set and never changes a life event already recorded; types and categories that came with the account, or that are in use, can only be switched off.

**Checkpoint**: `tests/Unit/ViewModels/Docs` and `tests/Feature/Controllers/App/Docs` pass.

---

## Phase 8: Polish

- [X] T039 In `tests/Feature/Mcp/McpCompartmentTest.php`, add `it_does_not_delete_a_life_event_type_of_another_account` and `it_does_not_delete_a_life_event_category_of_another_account`, as the relationship cases.
- [X] T040 In `tests/Feature/Mcp/McpChangelogTest.php`, add `it_records_a_life_event_type_change_as_having_arrived_over_mcp` (invoke `create-life-event-category` named Sales with the key `Dundie awards bot`, assert the `logs` row).
- [X] T041 Run `php artisan monica:localize`, translate the four sentences it reports (`That life event type does not exist.`, `That life event category does not exist.`, `The life event type is deleted.`, `The life event category is deleted.`) in every locale, reusing each locale's existing words for "life event type" and "category" and the glossary's `life event` row, then `php artisan monica:localize --check`.
- [X] T042 Add to `docs/FEATURES.md`, after the relationship lines: `* MCP: Let an assistant create, change, reorder, switch off and delete life event types and their categories, and choose the fields a type asks for.` and `* Docs: Listed the life event type tools on /docs/mcp and explained what a life event type is.`
- [X] T043 Run `php artisan test --compact tests/Unit/Mcp tests/Feature/Mcp tests/Feature/Controllers/Api tests/Unit/ViewModels/Docs tests/Feature/Controllers/App/Docs`, then `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`, and walk [quickstart.md](quickstart.md).

---

## Dependencies

- T001 blocks every tool.
- US1, US2, US3 are independent once T001 is done; US4 needs T027 and T028 before T032.
- Every story registers tools in `MonicaServer.php` (T010, T017, T026, T033, then T038): those tasks
  run one after another.
- US5 needs every tool registered, since `it_lists_exactly_the_tools_the_server_registers` compares
  against the server.
- Polish comes last.

## Parallel execution

- US1: T002 to T005 together; then T006 to T009 together; then T010.
- US2: T011 to T013; then T014 to T016; then T017.
- US3: T018 to T021; then T022 to T025; then T026.
- US4: T027, T028 in order; T029 to T031 together; then T032, T033.

## Implementation strategy

MVP is US1 (T001 to T010): an assistant can list the life event types and add the one somebody is
missing. US2 and US3 add organising and categories, US4 the fields. US5 lands in the same pull request,
since the docs test fails as soon as a tool is registered without being listed.
