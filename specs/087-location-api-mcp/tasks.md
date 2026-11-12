# Tasks: Location API and MCP Tools

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/api-and-mcp.md](contracts/api-and-mcp.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md` (The Office data).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel (different files, no dependency on an unfinished task)
- **[Story]**: the user story of spec.md the task serves

## Phase 1: Setup

- [X] T001 Read the `api-writer`, `api-docs-writer` and `mcp-development` skills, and `app/Http/Controllers/Api/RelationshipTypeController.php`, `app/Mcp/Tools/UpdateRelationshipType.php`, `app/Mcp/Tools/DeleteRelationshipType.php` and their tests as the reference implementation

## Phase 2: Foundational

**Blocks every story**: the resource and every tool read the tree from `LocationTree`.

- [X] T002 Create `app/Helpers/LocationTree.php` per data-model.md: `forAccount(int $accountId)` loading the account's locations in one query and setting each one's `children` relation from that collection; `ordered()` depth first with `LocaleCollation::sortByCollator()` on `name` at each level; `childrenOf(?Location)`; `path(Location)` joined by " › "; `addressHolder(Location)` (itself if `address !== null`, else the nearest ancestor with one, else null); `childCount(Location)`; `descendantIds(Location)`. No query beyond the first
- [X] T003 Write `tests/Unit/Helpers/LocationTreeTest.php`: order across three levels with two "Annex" under different parents, path, address holder for the location itself, a grandchild and a tree with no address, child count, descendant ids, another account left out
- [X] T004 Rewrite `app/ViewModels/Settings/Account/Locations/LocationsViewModel.php` to read from `LocationTree`, deleting its private `locations()`, `childrenOf()`, `parentOf()`, `path()`, `nearestWithAddress()`, `descendantIds()` and `flatten()`; its public methods and their answers do not change. `tests/Unit/ViewModels/Settings/Account/Locations/LocationsViewModelTest.php` and `tests/Feature/Controllers/App/Settings/Account/Locations/LocationControllerTest.php` pass unchanged
- [X] T005 Create `app/Http/Resources/LocationResource.php` taking a `Location` and its `LocationTree`: `type` `location`, `id` uuid, `attributes` `parent_id` (parent uuid or null), `name`, `emoji`, `address`, `path`, `effective_address` (null, or `{address, location_id, location_name}` from `addressHolder()`), `location_count`, `created_at` and `updated_at` as timestamps (`updated_at` nullable), `links.self` `route('api.v1.locations.show', uuid)`, `links.web` `route('settings.locations.edit', $location)`

**Checkpoint**: the screen behaves as before on top of the helper.

## Phase 3: User Story 1, read the locations (P1) MVP

Independent test: spec US1.

- [X] T006 [US1] Create `app/Http/Controllers/Api/LocationController.php` with `index` (whole tree from `LocationTree::forAccount()->ordered()`, sliced by `page` and `per_page` clamped between 1 and `config('monica.api_maximum_items_per_page')`, default `config('monica.api_default_items_per_page')`; answer `data` plus `meta {page, per_page, total}`) and `show` (string uuid looked up in `$request->user()->account->locations()`, `firstOrFail()`); add `GET locations` named `locations` and `GET locations/{location}` named `locations.show` with `->where('location', '[0-9a-fA-F-]{36}')` in `routes/api.php` next to `relationship-types`
- [X] T007 [US1] Write `tests/Feature/Controllers/Api/LocationControllerTest.php` with a shared `$jsonStructure`: index answers in tree order with `meta`, another account's locations left out, `per_page` pages the tree, an empty account answers an empty `data`; show answers the shape with `path` and `effective_address`, another account's location answers 404
- [X] T008 [P] [US1] Add `location(Request, string $address): Location` to `app/Mcp/Tools/MirrorsAnApiCapability.php` next to `relationshipType()`, throwing `doesNotExist(__('That location does not exist.'))` for one outside the account
- [X] T009 [P] [US1] Create `app/Mcp/Tools/ListLocations.php` (`#[IsReadOnly]`, mirrors `api.v1.locations`, every location in tree order, no paging) and `app/Mcp/Tools/GetLocation.php` (`#[IsReadOnly]`, mirrors `api.v1.locations.show`, input `location`), both answering `LocationResource`
- [X] T010 [US1] Write `tests/Unit/Mcp/Tools/ListLocationsTest.php` and `tests/Unit/Mcp/Tools/GetLocationTest.php` on the model of `ListRelationshipTypesTest` and `GetRelationshipTypeTest`: same data as the API, another account's location answers "That location does not exist."

## Phase 4: User Story 2, add and change a location (P1)

Independent test: spec US2.

- [X] T011 [US2] Add `create` (201) and `update` (200) to `app/Http/Controllers/Api/LocationController.php`, validating inline `name` `['required', 'string', 'max:255']`, `parent_id` `['nullable', 'string', Rule::exists('locations', 'uuid')->where('account_id', $request->user()->account_id)]`, `emoji` `['nullable', 'string', 'max:32', new SingleEmoji]`, `address` `['nullable', 'string', 'max:2000']`; call `CreateLocation` / `UpdateLocation` with a parent absent on `PUT` meaning the top level (research R6); turn `InvalidArgumentException` into `ValidationException::withMessages(['parent_id' => $e->getMessage()])`; add `POST locations` named `locations.create` and `PUT locations/{location}` named `locations.update` in `routes/api.php`
- [X] T012 [US2] Extend `tests/Feature/Controllers/Api/LocationControllerTest.php`: create with a name only and with every field, missing name 422 on `name`, `ab` emoji 422 on `emoji`, a parent of another account 422 on `parent_id`; update changes and moves, a `PUT` without `parent_id`, `emoji` and `address` clears them, a parent inside the location 422 on `parent_id` with the screen's wording, another account's location 404
- [X] T013 [P] [US2] Create `app/Mcp/Tools/CreateLocation.php` (mirrors `api.v1.locations.create`, inputs `name` required, `parent_id`, `emoji`, `address`) and `app/Mcp/Tools/UpdateLocation.php` (mirrors `api.v1.locations.update`, input `location` required, then `sometimes` on the four values: a value left out keeps what it was, a value sent as null clears it, told apart with `array_key_exists` on the validated input), with the same validation rules as T011; a refusal returns `Response::error()` with the action's message
- [X] T014 [US2] Write `tests/Unit/Mcp/Tools/CreateLocationTest.php` and `tests/Unit/Mcp/Tools/UpdateLocationTest.php`: created inside a parent, an update sending only a name keeps the parent, emoji and address, an explicit null parent moves to the top level, the cycle refused with the API's wording, a parent of another account refused

## Phase 5: User Story 3, delete a location (P2)

Independent test: spec US3.

- [X] T015 [US3] Add `destroy` (204) to `app/Http/Controllers/Api/LocationController.php`, turning `AuthorizationException` into `ValidationException::withMessages(['location' => $e->getMessage()])`; add `DELETE locations/{location}` named `locations.destroy` in `routes/api.php`
- [X] T016 [US3] Extend `tests/Feature/Controllers/Api/LocationControllerTest.php`: an empty location deleted with 204, one holding another 422 on `location` saying "1 location is inside it. Move it first.", another account's 404
- [X] T017 [P] [US3] Create `app/Mcp/Tools/DeleteLocation.php` (`#[IsDestructive]`, mirrors `api.v1.locations.destroy`, input `location`, answers `__('The location is deleted.')`, refusal as `Response::error()`)
- [X] T018 [US3] Write `tests/Unit/Mcp/Tools/DeleteLocationTest.php`: deleted, refused while holding another with the counted wording

## Phase 6: User Story 4, through an assistant (P2)

Independent test: spec US4.

- [X] T019 [US4] Register the five tools in `app/Mcp/Servers/MonicaServer.php`, and add one paragraph to its instructions: a location is a place of the account that can sit inside another, is chosen by its path when two share a name, and cannot be deleted while it holds another; find the parent with list-locations before create-location
- [X] T020 [US4] Add `it_records_a_location_change_as_having_arrived_over_mcp` to `tests/Feature/Mcp/McpChangelogTest.php` on the model of the relationship type case
- [X] T021 [US4] Run `tests/Feature/Mcp/McpCatalogueTest.php`: every new tool mirrors a route and is registered

## Phase 7: User Story 5, documentation (P3)

Independent test: spec US5.

- [X] T022 [P] [US5] Write `resources/docs/api/25-locations.php` with the `api-docs-writer` skill, on the model of `17-relationship-types.php`: the five endpoints, fields, an example location from The Office, and each refusal with its wording; `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php` passes
- [X] T023 [P] [US5] Add a "Locations" group to `toolGroups()` in `app/ViewModels/Docs/McpDocsViewModel.php`: `list-locations` and `get-location` in a reading group, `create-location`, `update-location` ("What you leave out keeps its value.") and `delete-location` ("Deletes a location that holds no other.") in a group badged as changing data; update the tool count in the prose and the plain text version

## Phase 8: Polish

- [X] T024 Remove "No API endpoint and no MCP tool." from `spec/models/058-location-in-progress.md`
- [X] T025 Run `php artisan monica:localize` and translate every reported string in every locale per `.claude/rules/translations.md`; `php artisan monica:localize --check` passes
- [X] T026 [P] `vendor/bin/phpstan analyse --no-progress` reports nothing; `vendor/bin/pint --dirty --format agent` leaves nothing
- [X] T027 Run the whole suite, `tests/Feature/RouteProtectionTest.php` included
- [ ] T028 Hand quickstart.md's checks by hand to the user; the pull request states that API changes are logged as `web` like every other endpoint (research R7)
- [X] T029 Record the feature in `docs/FEATURES.md` with the feature-documentator skill (always the last task)

## Dependencies

- Phase 2 before every story: T002 → T003, T004, T005.
- US1 before US2 and US3: they add methods to the controller and the test file US1 creates. US2 and US3 are independent of each other.
- The MCP tool of each story needs T008 and T005, not the API controller.
- US4 needs the tools of US1 to US3. US5 needs the routes and the tools to exist.
- Polish last; T029 is the last task.

## Parallel examples

- Phase 3: T008 and T009 with T006, then T007 and T010.
- Phase 4: T013 with T011, then T012 and T014.
- Phase 5: T017 with T015.
- Phase 7: T022 with T023.

## Strategy

MVP is phases 1 to 3: a key and an assistant can read every location, paths included. US2 makes the
API useful for writing, US3 completes the five capabilities, US4 and US5 make them reachable and known.

## Departures while building

- T004: `LocationTree` also exposes `find()`, `parentOf()` and `isEmpty()`, which the view model and the resource need, and `ordered()` takes an optional location to list only what is inside it.
- T020: the changelog test also asserts the `location_created` action, so it cannot pass on another tool's row.
- T022: `ApiDocsControllerTest` counts 161 sections; it holds no list of group titles, so the `assertSee()` the `api-docs-writer` skill asks for has nowhere to go. `ApiDocsViewModelTest` and `McpDocsViewModelTest` gained "Locations" in their expected lists.
- T027: the whole suite passes except `BillingControllerTest::it_answers_a_free_account_with_its_ceilings_and_every_offer`, which reads `MONICA_FREE_VAULT_LIMIT=2` from the local `.env`; `phpunit.xml` does not pin it. Unrelated to this change.
