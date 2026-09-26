---

description: "Task list for MCP tools for relationship types"
---

# Tasks: MCP tools for relationship types

**Input**: Design documents from `specs/059-mcp-relationship-type-tools/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[contracts/mcp-tools.md](contracts/mcp-tools.md), [quickstart.md](quickstart.md)

**Tests**: required. Constitution principle III and `CLAUDE.md` ("a change is done when what you
wrote is covered by a test"). Tests follow `.claude/rules/testing.md`: `declare(strict_types=1);`,
`#[Test]`, snake_case `it_...` names, `RefreshDatabase`, factories, The Office data, no comments.

**Model for every tool**: copy the shape of its entity type sibling in `app/Mcp/Tools`
(`GetEntityType`, `CreateEntityType`, `UpdateEntityType`, `DeleteEntityType`,
`SetEntityTypeActivation`, `SetEntityTypePosition`): `use MirrorsAnApiCapability;`,
`protected string $mirrors`, `#[Description]` ending "The change is written to the account journal."
for changing tools, `handle(Request $request): Response` with `$request->validate([...])`,
`schema(JsonSchema $schema)` returning `array<string, Type>`. A type answers
`Response::json(new RelationshipTypeResource($type->refresh()->load('relationshipCategory')->loadCount('relationships'))->resolve())`;
a category answers `RelationshipCategoryResource` with `loadCount('types')`. Import the action under
an `...Action` alias where the names collide (research R7).

Input descriptions: `relationship_type` is "The identifier of the relationship type, as
list-relationship-types gives it."; `relationship_category` and `relationship_category_id` are "The
identifier of the category, as list-relationship-categories gives it."

**Refusals** (research R2): catch the action's exception and answer `Response::error(__('...'))` with
the sentence from the R2 table, never `$e->getMessage()`, except the policy's `AuthorizationException`
on delete type, whose message is already translated.

**Model for every tool test**: `tests/Unit/Mcp/Tools/DeleteEntityTypeTest.php`:
`EricaServer::actingAs($user)->tool(Tool::class, [...])->assertOk()->assertHasNoErrors()` or
`->assertHasErrors(['sentence'])`, plus `it_names_the_api_capability_it_mirrors`. Factory states
available: `RelationshipType::factory()->system()`, `->directed()`, `->inactive()`;
`RelationshipCategory::factory()->system()`, `->inactive()`; `Relationship::factory()` to put a type
in use. Both models use `SoftDeletes`, so deletion is asserted with `assertSoftDeleted`.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

None. No dependency, folder, migration or route is added.

---

## Phase 2: Foundational (blocks every story)

- [X] T001 Add to `app/Mcp/Tools/MirrorsAnApiCapability.php`, after `entityType()`: `protected function relationshipType(Request $request, string $address): RelationshipType` (`$this->user($request)->account->relationshipTypes()->where('uuid', $address)->first()`, null throws `$this->doesNotExist(__('That relationship type does not exist.'))`) and `protected function relationshipCategory(Request $request, string $address): RelationshipCategory` (`account->relationshipCategories()`, `__('That relationship category does not exist.')`). Import both models (research R4).

**Checkpoint**: the lookups exist; the tool classes can be written.

---

## Phase 3: User Story 1 - Create and shape a relationship type (P1) MVP

**Goal**: an assistant reads, creates and updates a relationship type.

**Independent Test**: through MCP, create **Godparent** in a category with roles godparent and
godchild, read it back, change only `role_b`, and see the rest unchanged.

### Tests

- [X] T002 [P] [US1] Write `tests/Unit/Mcp/Tools/GetRelationshipTypeTest.php`: `it_returns_a_relationship_type` (`assertSee` the name and the category's uuid); `it_reports_a_relationship_type_of_another_account_as_not_existing` (`assertHasErrors(['That relationship type does not exist.'])`); `it_names_the_api_capability_it_mirrors` (`api.v1.relationshipTypes.show`).
- [X] T003 [P] [US1] Write `tests/Unit/Mcp/Tools/CreateRelationshipTypeTest.php`: `it_creates_a_directed_relationship_type` (Godparent, godparent, godchild, `is_symmetric` false; `assertDatabaseHas('relationship_types', [...])` with account, category id, `is_active` true); `it_creates_a_symmetric_type_whose_second_role_is_the_first` (only `role_a` Teammate); `it_refuses_a_directed_type_without_a_second_role`; `it_refuses_a_name_another_type_of_the_account_uses` (`assertHasErrors(['Another type in this account already goes by that name.'])`); `it_refuses_a_category_of_another_account` (`assertHasErrors(['That relationship category does not exist.'])`); `it_names_the_api_capability_it_mirrors` (`api.v1.relationshipTypes.create`). Each refusal also asserts the row count is unchanged.
- [X] T004 [P] [US1] Write `tests/Unit/Mcp/Tools/UpdateRelationshipTypeTest.php`: `it_changes_only_what_it_is_given` (directed type, sends `role_b` only, `refresh()`, asserts name, category, `role_a`, `is_active` unchanged); `it_moves_a_type_to_another_category`; `it_refuses_turning_a_symmetric_type_directed_without_a_second_role`; `it_refuses_changing_the_direction_of_a_type_in_use` (`Relationship::factory()` on it, `assertHasErrors(['This type keeps the direction it was written with.'])`); `it_refuses_a_name_another_type_of_the_account_uses`; `it_names_the_api_capability_it_mirrors` (`api.v1.relationshipTypes.update`).

### Implementation

- [X] T005 [P] [US1] Create `app/Mcp/Tools/GetRelationshipType.php`: `#[IsReadOnly]`, `#[Description('Return one kind of relationship: its name, the role of each contact, its category, whether it reads the same both ways, whether it is offered, whether it came with the account and how many relationships use it.')]`, mirrors `api.v1.relationshipTypes.show`; validates `relationship_type` `['required', 'string']`.
- [X] T006 [P] [US1] Create `app/Mcp/Tools/CreateRelationshipType.php`: mirrors `api.v1.relationshipTypes.create`; rules from `App\Http\Controllers\Api\RelationshipTypeController::create()` except the category, which goes through `$this->relationshipCategory()`: `relationship_category_id` `['required', 'string']`, `name` and `role_a` `['required', 'string', 'max:255']`, `role_b` `[Rule::requiredIf(fn (): bool => ! $request->boolean('is_symmetric', true)), 'nullable', 'string', 'max:255']` (check `Laravel\Mcp\Request` offers `boolean()`; otherwise read `$request->get('is_symmetric', true)` with `filter_var(..., FILTER_VALIDATE_BOOL)`), `is_symmetric` and `is_active` `['nullable', 'boolean']`. Calls `CreateRelationshipTypeAction` with `roleB: $validated['role_b'] ?? $validated['role_a']`, `isSymmetric` default true, `isActive` default true; `catch (RelationshipTypeNameIsAlreadyUsed)` answers `Response::error(__('Another type in this account already goes by that name.'))`. Schema: `role_a` "The role of the first contact, such as parent.", `role_b` "The role of the other contact, such as child. Required when is_symmetric is false.", `is_symmetric` "True when both contacts hold the same role, as with siblings. Defaults to true."
- [X] T007 [P] [US1] Create `app/Mcp/Tools/UpdateRelationshipType.php`: mirrors `api.v1.relationshipTypes.update`; every attribute optional (research R3): `relationship_category_id` `['sometimes', 'required', 'string']`, `name` and `role_a` `['sometimes', 'required', 'string', 'max:255']`, `role_b` `['nullable', 'string', 'max:255']`, `is_symmetric` `['sometimes', 'boolean']`. Look the type up first, as the API controller does, so the `role_b` rule can be `Rule::requiredIf(fn (): bool => ! $wantsSymmetric && $type->is_symmetric)` with `$wantsSymmetric` read from the input or the stored value, and the refusal is the framework's own `required` message; `roleB` is `$validated['role_b'] ?? ($isSymmetric ? $roleA : $type->role_b)`; `isActive: $type->is_active`. Catches `RelationshipTypeNameIsAlreadyUsed` and `CannotChangeARelationshipTypeDirection` with the R2 sentences. Description says anything left out keeps its value and whether it is offered is changed with set-relationship-type-activation.
- [X] T008 [US1] Register `GetRelationshipType`, `CreateRelationshipType` and `UpdateRelationshipType` in `$tools` in `app/Mcp/Servers/EricaServer.php`, next to `ListRelationshipTypes`, and import them.

**Checkpoint**: T002 to T004 pass; `tests/Feature/Mcp/McpCatalogueTest.php` passes.

---

## Phase 4: User Story 2 - Organise and retire relationship types (P2)

**Goal**: an assistant switches a type off or on, moves it within its category, and deletes one.

**Independent Test**: through MCP, deactivate a type, move it first, reactivate it, delete one
nobody uses, and be refused on a supplied one and on one in use.

### Tests

- [X] T009 [P] [US2] Write `tests/Unit/Mcp/Tools/SetRelationshipTypeActivationTest.php`: `it_switches_a_relationship_type_off`, `it_switches_a_relationship_type_on` (from `->inactive()`); `it_journals_nothing_when_the_type_is_already_in_that_state` (`Queue::fake()`, `Queue::assertNotPushed(LogUserAction::class)`); `it_names_the_api_capability_it_mirrors` (`api.v1.relationshipTypes.activation.update`).
- [X] T010 [P] [US2] Write `tests/Unit/Mcp/Tools/SetRelationshipTypePositionTest.php`: `it_moves_a_relationship_type_within_its_category` (three types in one category, third to position 1, assert the order of `$category->types()->orderBy('position')->pluck('uuid')`); `it_names_the_api_capability_it_mirrors` (`api.v1.relationshipTypes.position.update`).
- [X] T011 [P] [US2] Write `tests/Unit/Mcp/Tools/DeleteRelationshipTypeTest.php`: `it_deletes_a_relationship_type_nobody_uses` (`assertSoftDeleted`); `it_refuses_a_relationship_type_that_came_with_the_account` (`->system()`, `assertHasErrors(['The type came with your account and stays.'])`); `it_refuses_a_relationship_type_in_use` (`Relationship::factory()`, `assertHasErrors(['Relationships are written with this type, so it stays.'])`); `it_names_the_api_capability_it_mirrors` (`api.v1.relationshipTypes.destroy`). Refusals assert `assertNotSoftDeleted`.

### Implementation

- [X] T012 [P] [US2] Create `app/Mcp/Tools/SetRelationshipTypeActivation.php`: mirrors `api.v1.relationshipTypes.activation.update`; validates `is_active` `['required', 'boolean']`; returns the resource unchanged when it equals `$type->is_active` (research R6, as `RelationshipTypeActivationController::settle()`); otherwise `ActivateRelationshipType` or `DeactivateRelationshipType`. Description: "Offer a kind of relationship when contacts are linked, or stop offering it. Relationships already written with it stay. The change is written to the account journal."
- [X] T013 [P] [US2] Create `app/Mcp/Tools/SetRelationshipTypePosition.php`: mirrors `api.v1.relationshipTypes.position.update`; `position` `['required', 'integer', 'min:1']`, described "Where it goes within its category, counting from 1."; calls `UpdateRelationshipTypePosition` with `(int) $validated['position']`.
- [X] T014 [P] [US2] Create `app/Mcp/Tools/DeleteRelationshipType.php`: `#[IsDestructive]`, mirrors `api.v1.relationshipTypes.destroy`; `DestroyRelationshipType` inside `try`, `catch (AuthorizationException $e) { return Response::error($e->getMessage()); }`; answers `Response::text(__('The relationship type is deleted.'))`. Description: "Delete a kind of relationship the account created. One that came with the account, or that relationships use, is refused; stop offering it instead. The change is written to the account journal."
- [X] T015 [US2] Register `SetRelationshipTypeActivation`, `SetRelationshipTypePosition` and `DeleteRelationshipType` in `$tools` in `app/Mcp/Servers/EricaServer.php`.

**Checkpoint**: T009 to T011 pass; `McpCatalogueTest` passes.

---

## Phase 5: User Story 3 - Manage relationship categories (P2)

**Goal**: an assistant reads, lists the types of, creates, renames, moves, switches and deletes a
category.

**Independent Test**: through MCP, create **Sports**, rename it, list its types, move it, switch it
off and on, delete it once empty; be refused renaming or deleting a supplied one and deleting one
holding types.

### Tests

- [X] T016 [P] [US3] Write `tests/Unit/Mcp/Tools/GetRelationshipCategoryTest.php`: `it_returns_a_relationship_category`; `it_reports_a_relationship_category_of_another_account_as_not_existing`; `it_names_the_api_capability_it_mirrors` (`api.v1.relationshipCategories.show`).
- [X] T017 [P] [US3] Write `tests/Unit/Mcp/Tools/ListRelationshipCategoryTypesTest.php`: `it_lists_the_types_of_a_category_by_position` (two categories; only the asked one's types, in position order); `it_names_the_api_capability_it_mirrors` (`api.v1.relationshipCategories.types`).
- [X] T018 [P] [US3] Write `tests/Unit/Mcp/Tools/CreateRelationshipCategoryTest.php`: `it_creates_a_relationship_category` (Sports, `assertDatabaseHas`); `it_names_the_api_capability_it_mirrors` (`api.v1.relationshipCategories.create`).
- [X] T019 [P] [US3] Write `tests/Unit/Mcp/Tools/UpdateRelationshipCategoryTest.php`: `it_renames_a_relationship_category`; `it_refuses_renaming_a_category_that_came_with_the_account` (`->system()`, `assertHasErrors(['The category came with your account and keeps its name.'])`); `it_names_the_api_capability_it_mirrors` (`api.v1.relationshipCategories.update`).
- [X] T020 [P] [US3] Write `tests/Unit/Mcp/Tools/DeleteRelationshipCategoryTest.php`: `it_deletes_an_empty_relationship_category` (`assertSoftDeleted`); `it_refuses_a_category_that_came_with_the_account` (`The category came with your account and stays.`); `it_refuses_a_category_still_holding_types` (`The category still holds types and stays.`); `it_names_the_api_capability_it_mirrors` (`api.v1.relationshipCategories.destroy`).
- [X] T021 [P] [US3] Write `tests/Unit/Mcp/Tools/SetRelationshipCategoryActivationTest.php` and `tests/Unit/Mcp/Tools/SetRelationshipCategoryPositionTest.php`, one class per file, mirroring T009 and T010 (`api.v1.relationshipCategories.activation.update`, `api.v1.relationshipCategories.position.update`; position order asserted on `account->relationshipCategories()->orderBy('position')->pluck('uuid')`).

### Implementation

- [X] T022 [P] [US3] Create `app/Mcp/Tools/GetRelationshipCategory.php` (`#[IsReadOnly]`, mirrors `api.v1.relationshipCategories.show`, "Return one category of relationship types: its name, position, whether it is offered, whether it came with the account and how many types it holds.") and `app/Mcp/Tools/ListRelationshipCategoryTypes.php` (`#[IsReadOnly]`, mirrors `api.v1.relationshipCategories.types`, "List the kinds of relationship filed under one category, in the order they are offered."; `$category->types()->with('relationshipCategory')->withCount('relationships')->orderBy('position')->orderBy('id')->get()`, research R5).
- [X] T023 [P] [US3] Create `app/Mcp/Tools/CreateRelationshipCategory.php` (mirrors `api.v1.relationshipCategories.create`; `name` `['required', 'string', 'max:255']`; `CreateRelationshipCategoryAction`) and `app/Mcp/Tools/UpdateRelationshipCategory.php` (mirrors `api.v1.relationshipCategories.update`; `name` required as in the API; `UpdateRelationshipCategoryAction`; `catch (CannotChangeASuppliedRelationshipCategory)` answers `__('The category came with your account and keeps its name.')`).
- [X] T024 [P] [US3] Create `app/Mcp/Tools/DeleteRelationshipCategory.php`: `#[IsDestructive]`, mirrors `api.v1.relationshipCategories.destroy`; `DestroyRelationshipCategory`, with separate catches: `CannotChangeASuppliedRelationshipCategory` answers `__('The category came with your account and stays.')`, `CannotDeleteARelationshipCategoryInUse` answers `__('The category still holds types and stays.')`; success `Response::text(__('The relationship category is deleted.'))`. Description: "Delete a category the account created. One that came with the account, or that still holds types, is refused; move its types first. The change is written to the account journal."
- [X] T025 [P] [US3] Create `app/Mcp/Tools/SetRelationshipCategoryActivation.php` (as T012 with `ActivateRelationshipCategory` / `DeactivateRelationshipCategory`, skipping when unchanged as `RelationshipCategoryActivationController::settle()`) and `app/Mcp/Tools/SetRelationshipCategoryPosition.php` (as T013 with `UpdateRelationshipCategoryPosition`, "Where it goes in the list of categories, counting from 1.").
- [X] T026 [US3] Register the seven category tools in `$tools` in `app/Mcp/Servers/EricaServer.php`, next to `ListRelationshipCategories`.

**Checkpoint**: T016 to T021 pass; `McpCatalogueTest` passes.

---

## Phase 6: User Story 4 - Know what the assistant can now change (P3)

**Goal**: `/docs/mcp` and `/docs/mcp.md` list the new tools and explain relationship types.

**Independent Test**: `McpDocsViewModelTest::it_lists_exactly_the_tools_the_server_registers` passes.

- [X] T027 [US4] In `app/ViewModels/Docs/McpDocsViewModel.php` `toolGroups()`, "Relationship vocabulary" group: keep the two list tools and add `get-relationship-type` ("Returns one kind of relationship: its roles, its category, whether it is offered and how many relationships use it."), `get-relationship-category` ("Returns one category and how many types it holds."), `list-relationship-category-types` ("Lists the kinds of relationship filed under one category."). Add after it `['name' => 'Relationship vocabulary, changing', 'changesData' => true, 'tools' => [...]]` with, in this order: `create-relationship-type` ("Adds a kind of relationship, such as godparent and godchild."), `update-relationship-type` ("Renames a kind of relationship, changes its roles or moves it to another category. What you leave out keeps its value."), `set-relationship-type-activation` ("Offers a kind of relationship when contacts are linked, or stops offering it. Existing relationships stay."), `set-relationship-type-position` ("Moves a kind of relationship within its category."), `delete-relationship-type` ("Deletes a kind of relationship you created and nobody uses."), `create-relationship-category` ("Adds a category, such as Sports."), `update-relationship-category` ("Renames a category you created."), `set-relationship-category-activation` ("Offers a category or stops offering it."), `set-relationship-category-position` ("Moves a category to another place in the list."), `delete-relationship-category` ("Deletes a category you created that holds no type.").
- [X] T028 [US4] In the same file: add `['id' => 'relationship-types', 'label' => 'Manage relationship types']` to `SECTIONS` after `entity-types`; add `public const array RELATIONSHIP_TYPES` of two paragraphs: (1) a relationship type names how two contacts are linked, with a role for each side, such as parent and child, or the same role for both, such as siblings, and is filed under a category such as Family or Work; (2) types and categories that came with your account, and any that are in use, cannot be deleted; stop offering them with `set-relationship-type-activation` or `set-relationship-category-activation` instead. In `markdown()`, after the entity types paragraphs, write `## Manage relationship types`, a blank line and `$this->paragraphs(self::RELATIONSHIP_TYPES)`. No dashes as punctuation.
- [X] T029 [US4] In `resources/views/app/docs/mcp.blade.php`, after the `entity-types` section (around line 268), add a `relationship-types` section copied from it: `aria-labelledby="relationship-types-heading"`, heading `Manage relationship types`, `@foreach (McpDocsViewModel::RELATIONSHIP_TYPES as $paragraph)` rendered the same way the entity types paragraphs are.
- [X] T030 [US4] In `tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php` `it_marks_only_the_groups_that_change_data`, append `'Relationship vocabulary, changing'` after `'Entity types, changing'`. In `tests/Feature/Controllers/App/Docs/McpDocsControllerTest.php`, add `$response->assertSee('Manage relationship types');` after `assertSee('Manage entity types')`.
- [X] T031 [US4] Add one sentence to the `#[Instructions]` text in `app/Mcp/Servers/EricaServer.php`, after the entity type paragraph: a relationship type gives each of two linked contacts a role and is filed under a category; types and categories that came with the account, or that are in use, cannot be deleted, only switched off with set-relationship-type-activation or set-relationship-category-activation.

**Checkpoint**: `tests/Unit/ViewModels/Docs` and `tests/Feature/Controllers/App/Docs` pass.

---

## Phase 7: Polish

- [X] T032 In `tests/Feature/Mcp/McpCompartmentTest.php`, add `it_does_not_delete_a_relationship_type_of_another_account` and `it_does_not_delete_a_relationship_category_of_another_account`, as `it_does_not_delete_an_entity_type_of_another_account` (not found sentence, `assertNotSoftDeleted`).
- [X] T033 In `tests/Feature/Mcp/McpChangelogTest.php`, add `it_records_a_relationship_type_change_as_having_arrived_over_mcp`, as the entity type case (invoke `create-relationship-category` with the key named `Dundie awards bot`, assert the `logs` row with `LogSourceEnum::Mcp` and that `source_detail`). One case covers both, since both kinds of tool reach the journal through the same `LogUserAction`.
- [X] T034 Run `php artisan monica:localize`, translate what it reports missing (`That relationship type does not exist.`, `That relationship category does not exist.`, `The relationship type is deleted.`, `The relationship category is deleted.`) in every configured locale following `.claude/rules/translations.md` and the glossary (`relationship` row), then `php artisan monica:localize --check`.
- [X] T035 Add one line to `docs/FEATURES.md` in the style of the existing MCP entries: `* MCP: Let an assistant create, change, reorder, switch off and delete relationship types and their categories.`
- [X] T036 Run `php artisan test --compact tests/Unit/Mcp tests/Feature/Mcp tests/Unit/ViewModels/Docs tests/Feature/Controllers/App/Docs`, then `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`, and walk the scenarios in [quickstart.md](quickstart.md).

---

## Dependencies

- T001 blocks every tool (T005 to T007, T012 to T014, T022 to T025).
- US1, US2 and US3 are independent of each other once T001 is done; each registers its tools in
  `EricaServer.php` (T008, T015, T026, then T031), so those tasks run one after another.
- US4 (T027 to T031) needs every tool registered, since
  `it_lists_exactly_the_tools_the_server_registers` compares against the server.
- T032 to T036 come last.

## Parallel execution

- US1: T002, T003, T004 together; then T005, T006, T007 together; then T008.
- US2: T009, T010, T011 together; then T012, T013, T014 together; then T015.
- US3: T016 to T021 together; then T022 to T025 together; then T026.

## Implementation strategy

MVP is US1 (T001 to T008): an assistant can add the relationship type somebody is missing in an
existing category. US2 adds organising and deleting types, US3 adds categories. US4 must land in the
same pull request, since the docs test fails as soon as a tool is registered without being listed.
