# Tasks: Life events through the API and the MCP server

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/api.md](contracts/api.md), [contracts/mcp.md](contracts/mcp.md)

**Tests**: required by constitution III; each test task precedes the code it proves.

**Note**: `ApiDocumentationTest` fails on an undocumented route and `McpDocsViewModelTest::it_lists_exactly_the_tools_the_server_registers` on an unlisted tool, so every route and tool ships with its docs entry in the same story.

## Phase 1: Setup

- [X] T001 Read `.claude/skills/api-writer/SKILL.md`, `.claude/skills/api-docs-writer/SKILL.md`, `.claude/skills/mcp-development/SKILL.md`, `.claude/rules/testing.md`, then `app/Http/Controllers/Api/ContactReminderController.php`, `app/Mcp/Tools/CreateContactReminder.php` and `tests/Unit/Mcp/Tools/CreateContactReminderTest.php` as the references to copy

## Phase 2: Foundational

- [X] T002 [P] Add a test that `CreateLifeEvent` stores the `createdFrom` it is given (`LifeEventSourceEnum::Api`) in tests/Unit/Actions/CreateLifeEventTest.php
- [X] T003 [P] Add a test that `UpdateLifeEvent` with `values: null` keeps every stored field value, and one that a type change with `values: null` refuses a required field of the new type, in tests/Unit/Actions/UpdateLifeEventTest.php
- [X] T004 Add `private readonly LifeEventSourceEnum $createdFrom = LifeEventSourceEnum::Contact` as the last constructor parameter and write it to `created_from` in app/Actions/CreateLifeEvent.php (research R8: same signature as branch `2026-10-11-dashboard-life-events`)
- [X] T005 Type `$values` as `?array` defaulting to `[]` in app/Actions/UpdateLifeEvent.php: `null` skips the `SetFieldValues` loop when the type is unchanged and becomes `[]` when it changes (research R5); update the `@param` docblock
- [X] T006 Create app/Http/Resources/LifeEventResource.php with `public bool $withSensitive = false` and the shape of contracts/api.md: `type` `life_event`, `id` uuid, type `{id, name}`, `happened_on` as `Y-m-d`, participants `{id, name, position}` for contacts only, `fields` from `fieldValues` each `{field_id, key, label, type, row, value, is_hidden}` with `value` from `new FieldValueFormatter($request->user())->format()` and null plus `is_hidden: true` for a sensitive field unless `withSensitive`, `created_from`, `deleted_at`/`created_at`/`updated_at` as Unix integers, `links.self` to `api.v1.lifeEvents.show`
- [X] T007 Add `lifeEvent(Vault $vault, string $address, bool $withTrashed = false): LifeEvent` to app/Mcp/Tools/MirrorsAnApiCapability.php, matching `uuid` inside the vault, eager loading `lifeEventType`, `participants.participant`, `fieldValues.field`, and throwing `$this->doesNotExist(__('That life event does not exist.'))`
- [X] T008 Run `php artisan test --compact tests/Unit/Actions/CreateLifeEventTest.php tests/Unit/Actions/UpdateLifeEventTest.php` and the web life event controller tests to prove the action changes break nothing

**Checkpoint**: actions, resource and trait ready.

## Phase 3: User Story 1, read life events from outside (P1, MVP)

**Independent test**: three life events, one without Jim; list the vault, list it for Jim, read one, over the API and with the two read tools.

- [X] T009 [P] [US1] Write index and show tests in tests/Feature/Controllers/Api/LifeEventControllerTest.php: ordered by `happened_on` then id descending and paginated with `meta`, narrowed by `contact`, deleted ones left out, another account's left out, show with participants and a field value, sensitive value hidden without the `sensitive-fields` ability and shown with it, a life event of another vault of the same account 404, with a shared `$jsonStructure`
- [X] T010 [US1] Create app/Http/Controllers/Api/LifeEventController.php with `index` (validate `contact` nullable string; `per_page` clamped as `ContactReminderController@index`; answer `{data, meta: {page, per_page, total}}`) and `show`, private `vault()` and `lifeEvent()` lookups by address scoped to the account and vault, and the sensitive check copied from `SearchController::reachesSensitiveFields()`
- [X] T011 [US1] Add `GET vaults/{vault}/life-events` named `lifeEvents` and `GET vaults/{vault}/life-events/{lifeEvent}` named `lifeEvents.show` inside the `auth:sanctum` group of routes/api.php, import sorted alphabetically
- [X] T012 [US1] Create resources/docs/api/22-life-events.php with group name "Life events" and the `lifeEvents-index` and `lifeEvents-show` sections (path, query params, `returns` saying another account's or vault's life event answers 404, response built from the contract's sample, `paginated()` for the list), no em dashes
- [X] T013 [P] [US1] Write tests/Unit/Mcp/Tools/ListLifeEventsTest.php and tests/Unit/Mcp/Tools/GetLifeEventTest.php: happy path, narrowed by contact, a life event or vault out of reach answers the translated refusal, a sensitive value is never returned
- [X] T014 [P] [US1] Create app/Mcp/Tools/ListLifeEvents.php (`#[IsReadOnly]`, mirrors `api.v1.lifeEvents`, args `vault`, `contact`, `page`, `per_page`) and app/Mcp/Tools/GetLifeEvent.php (`#[IsReadOnly]`, mirrors `api.v1.lifeEvents.show`, args `vault`, `life_event`), each with a one sentence `#[Description]`
- [X] T015 [US1] Register both tools in app/Mcp/Servers/MonicaServer.php and add the "Life events, reading" group (`changesData` false) with their two descriptions to `toolGroups()` in app/ViewModels/Docs/McpDocsViewModel.php
- [X] T016 [US1] Run the US1 tests plus tests/Unit/Services/ApiDocumentationTest.php, tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php and tests/Feature/Mcp/McpCatalogueTest.php

## Phase 4: User Story 2, write down, correct and delete from outside (P1)

**Independent test**: create, update, delete and restore one life event through the API, then through the tools.

- [X] T017 [P] [US2] Add create, update and destroy tests to tests/Feature/Controllers/Api/LifeEventControllerTest.php: create 201 with `created_from` `api` and a text field value, validation 422 on missing `happened_on`, empty `participants`, a value for a file field; an inactive type 422 on `life_event_type`; update 200 keeping field values when `fields` is omitted; update with another first participant 422 on `participants`; destroy 204 with `assertSoftDeleted`
- [X] T018 [P] [US2] Write tests/Feature/Controllers/Api/LifeEventRestorationControllerTest.php: restore 200 with the resource, a life event that is not deleted 404
- [X] T019 [US2] Add `create`, `update` and `destroy` to app/Http/Controllers/Api/LifeEventController.php: rules from contracts/api.md, type by uuid among the account's types, participants by address inside the vault in the order sent, `fields` validated with `FieldValueRules::for()` of the target version with every file field's rule replaced by `['prohibited']`, `values: null` on update when `fields` is absent, `removedFieldIds: []`, `createdFrom: LifeEventSourceEnum::Api`, the first contact participant as `contact` for destroy, action exceptions rethrown as `ValidationException` on `life_event_type` or `fields`
- [X] T020 [US2] Create app/Http/Controllers/Api/LifeEventRestorationController.php with `update`, finding the life event `withTrashed()` and calling `RestoreLifeEvent` with its first contact participant
- [X] T021 [US2] Add the `lifeEvents.create`, `lifeEvents.update`, `lifeEvents.destroy` and `lifeEvents.restoration.update` routes of contracts/api.md to routes/api.php
- [X] T022 [US2] Add the create (201), update, delete (204) and restore sections to resources/docs/api/22-life-events.php, with `bodyParams` from contracts/api.md, an example body holding one `fields` entry, and a paragraph on reading field ids from `GET /life-event-types/{lifeEventType}/fields`
- [X] T023 [P] [US2] Write tests/Unit/Mcp/Tools/CreateLifeEventTest.php, UpdateLifeEventTest.php, DeleteLifeEventTest.php and RestoreLifeEventTest.php under tests/Unit/Mcp/Tools: happy path, `created_from` `mcp`, update keeping every argument left out, an inactive type answered as `Response::error`, a record out of reach refused
- [X] T024 [US2] Create app/Mcp/Tools/CreateLifeEvent.php, UpdateLifeEvent.php, DeleteLifeEvent.php (`#[IsDestructive]`) and RestoreLifeEvent.php per contracts/mcp.md, importing each action with an `Action` suffix; the update tool fills every omitted argument from the stored life event
- [X] T025 [US2] Register the four tools in app/Mcp/Servers/MonicaServer.php and add the "Life events, changing" group (`changesData` true) to `toolGroups()` in app/ViewModels/Docs/McpDocsViewModel.php
- [X] T026 [US2] Run the US2 tests plus the three docs and catalogue tests of T016

## Phase 5: User Story 3, find how to do it in the documentation (P2)

**Independent test**: following only `/docs` and `/docs/mcp`, create a life event with a custom field value.

- [X] T027 [US3] Prove the "Life events" group in the portal: covered by the sidebar list in tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php and the section count in tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php, so no second assertion
- [X] T028 [US3] In `toolsSummary()` of app/ViewModels/Docs/McpDocsViewModel.php drop "or life events" from the sentence on what contact responses leave out, and add the paragraph of contracts/mcp.md on types, participants and restoring
- [X] T029 [US3] Add the instructions paragraph of contracts/mcp.md to app/Mcp/Servers/MonicaServer.php, after the one on life event types
- [X] T030 [US3] Read resources/docs/api/22-life-events.php against app/Http/Resources/LifeEventResource.php and the controller rules, and fix any drift in names, types or samples

## Phase 6: Polish

- [X] T031 Run `php artisan monica:localize`, translate every reported string in every locale, then `php artisan monica:localize --check`
- [X] T032 Run `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`, fix what they report
- [X] T033 Run the whole suite with `php artisan test --compact` and walk through quickstart.md

## Dependencies

- T001 → Phase 2. T002, T003 before T004, T005. T004 to T007 block every story.
- US1 (T009 to T016) before US2, which reuses `LifeEventController`, the docs file and the tool groups.
- US3 after US1 and US2. Polish last.
- [P] pairs: T002/T003, T009/T013 then T014, T017/T018/T023.
