# Tasks: MCP Call History

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/api.md](contracts/api.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III and by the brief's "Vérifications attendues". Follow `.claude/rules/testing.md`; data from The Office.

## Phase 1: Setup

- [X] T001 Add `'mcp_call_retention_in_days' => env('MONICA_MCP_CALL_RETENTION_IN_DAYS', 90)` next to `notification_retention_months` in config/monica.php, with the same comment style
- [X] T002 [P] Create `McpCallResultEnum` (`Succeeded = 'succeeded'`, `Refused = 'refused'`, `InvalidParameters = 'invalid_parameters'`, `Error = 'error'`, `Interrupted = 'interrupted'`) with translated `label()` and a `tone()` for `x-badge` in app/Enums/McpCallResultEnum.php
- [X] T003 [P] Create `McpOperationEnum` (`Read`, `Create`, `Update`, `Delete`, values lower case) with `label()` and `static fromHttpMethod(string): ?self` (GET read, POST create, PUT or PATCH update, DELETE delete, anything else null) in app/Enums/McpOperationEnum.php
- [X] T004 [P] Create `McpConnectionTypeEnum` (`ApiKey = 'api_key'`, `OauthClient = 'oauth_client'`) with `label()` in app/Enums/McpConnectionTypeEnum.php
- [X] T005 [P] Test each enum's labels, `tone()` and `fromHttpMethod()` in tests/Unit/Enums/McpCallResultEnumTest.php, McpOperationEnumTest.php, McpConnectionTypeEnumTest.php

## Phase 2: Foundational

- [X] T006 Create the `mcp_calls` migration, `up()` only, every column, type, nullability and index exactly as data-model.md lists them (`started_at` is `dateTime('started_at', precision: 3)`, `ip_address` is `string(45)`, `vault_id` is `foreignIdFor(Vault::class)->nullable()->constrained()->nullOnDelete()`), in database/migrations/2026_10_23_000000_create_mcp_calls_table.php
- [X] T007 Create `McpCall` with `HasUuids` (App\Models\Concerns), `MassPrunable`, `#[Fillable]`, enum and boolean casts, `account()`, `user()`, `vault()` BelongsTo with docblocks, and `prunable()` selecting `started_at` older than `max(1, (int) config('monica.mcp_call_retention_in_days'))` days, documented in full like app/Models/Notification.php, in app/Models/McpCall.php
- [X] T008 Add `mcpCalls()` HasMany with its generic docblock to app/Models/Account.php
- [X] T009 [P] Create `McpCallFactory` with states `refused()`, `invalidParameters()`, `failed()`, `interrupted()` (duration null) and `overOAuth()` in database/factories/McpCallFactory.php
- [X] T010 Create `McpPayload` with `mask(array $payload, Account $account, string $tool): array` (R5: secret like keys at any depth become `[masked]`; for `create-life-event` and `update-life-event` only, each `fields` value whose key is not a non sensitive field of the account becomes `[masked]`) and `encode(array): array{text: string, truncated: bool}` (JSON, cut at 16 384 bytes on a character boundary) in app/Helpers/McpPayload.php
- [X] T011 Extend `SetMcpLogSource` to set `mcp_connection_type` (`McpConnectionTypeEnum`) and `mcp_connection_id` (Sanctum token id or Passport client id) beside `log_source_detail`, in app/Http/Middleware/SetMcpLogSource.php

## Phase 3: User Story 1, every call recorded with its true outcome (P1) MVP

Independent test: quickstart rows 1 to 7.

- [X] T012 [P] [US1] Make `vault()` set request attribute `mcp_vault` and every record lookup set `mcp_subject` once the record is found, never on a refusal, in app/Mcp/Tools/MirrorsAnApiCapability.php
- [X] T013 [US1] Create `RecordedCallTool implements Errable, Method` using `InteractsWithResponses`: resolve the tool as `CallTool` does, insert the row with result `interrupted` when `mcp_connection_type` is set (none otherwise), run the handler, classify per research R2 (keep the exception in an overridden `toErrorResponse()`, treat a `ValidationException` whose only key is `address` as refused), set operation from the mirrored route's method via `Route::getRoutes()->getByName()`, then close the row once with result, error code and message, masked and encoded parameters and response, vault, subject (`getMorphClass()`, key, label) and `duration_in_milliseconds` from `hrtime()`, in app/Mcp/Servers/RecordedCallTool.php
- [X] T014 [US1] Record a missing `name` or unknown tool as `invalid_parameters` with code -32602 before rethrowing the `JsonRpcException`, in app/Mcp/Servers/RecordedCallTool.php
- [X] T015 [US1] Override `$methods` so `tools/call` maps to `RecordedCallTool::class`, keeping every other entry of the parent's array, in app/Mcp/Servers/MonicaServer.php
- [X] T016 [P] [US1] Create the fixture tool that returns the count of `mcp_calls` rows and the result and duration stored on its own row while it runs, and a fixture server extending `MonicaServer` that adds it, in tests/Fixtures/Mcp/Tools/ReadOwnCallHistory.php and tests/Fixtures/Mcp/Servers/ServerWithHistoryReader.php; the test registers it with `Mcp::web()` and the middleware of routes/ai.php
- [X] T017 [US1] Test quickstart rows 1 to 7 over `POST /mcp` with a real key, the way tests/Feature/Mcp/McpTransportTest.php does, including that HTTP 200 with `isError: true` is stored as `error`, in tests/Feature/Mcp/McpCallRecordingTest.php
- [X] T018 [US1] Run tests/Feature/Mcp and tests/Unit/Mcp to prove the 92 existing tool tests still pass and record nothing

## Phase 4: User Story 2, the account holder reads the history (P1)

Independent test: quickstart rows 11 and 12, and the screen renders.

- [X] T019 [US2] Create `McpCallsViewModel` (user, filters, size): `entries()` through `PageSize::paginate()` default 20 newest `started_at` first, `days()` grouped in the user's time zone, `description(McpCall)` with the sixteen wordings of plan.md (operation, succeeded or not, subject known or not), `retentionInDays()`, `isFiltered()`, in app/ViewModels/Settings/Account/Security/McpCallsViewModel.php
- [X] T020 [US2] Create `McpCallController@index`, thin (the four filters and their validation land with T031 to T033), in app/Http/Controllers/App/Settings/Account/Security/McpCallController.php
- [X] T021 [US2] Add `Route::get('settings/account/security/mcp-history', ...)->name('settings.mcpCalls.index')` behind `flag:mcp-server`, next to the security routes, in routes/web.php
- [X] T022 [US2] Create the screen per plan.md "Screen" with `x-settings.page`, `x-page-header`, `x-empty-state`, `x-pagination`, the masked sentence and the retention sentence through `trans_choice()`, in resources/views/app/settings/account/security/mcp-calls.blade.php
- [X] T023 [US2] Create the row as `<details>`/`<summary>` with `x-badge` carrying the result in words, `x-notice` for the reason, `x-detail-list` for the facts, `x-timestamp` for the full date, and two `<pre>` blocks pretty printed when the JSON is valid with a "Truncated" note when cut, in resources/views/app/settings/account/security/_mcp-call-row.blade.php
- [X] T024 [US2] Link the history from the connected assistants section, under its panel and only while the server is on, in resources/views/app/settings/account/security/index.blade.php (the partial is an `x-merge` target)
- [X] T025 [P] [US2] Create `McpCallResource` with the shape in contracts/api.md, in app/Http/Resources/McpCallResource.php
- [X] T026 [US2] Create `Api\McpCallController` with `index` and `show` (by uuid, scoped to the account, 404 otherwise) shaped like app/Http/Controllers/Api/LogController.php, in app/Http/Controllers/Api/McpCallController.php
- [X] T027 [US2] Add `mcp-calls` (`mcpCalls`) and `mcp-calls/{mcpCall}` (`mcpCalls.show`) inside a `flag:mcp-server` group, in routes/api.php
- [X] T028 [P] [US2] Test the view model: grouping by day in the user's zone, ordering, descriptions, another account left out, a revoked key keeping its name, in tests/Unit/ViewModels/Settings/Account/Security/McpCallsViewModelTest.php
- [X] T029 [P] [US2] Test the web controller: renders with the copy, answers not found with the flag off, refuses an unknown filter, in tests/Feature/Controllers/App/Settings/Account/Security/McpCallControllerTest.php
- [X] T030 [P] [US2] Test the API controller: `$jsonStructure`, another account's rows left out, `show` 404 on a foreign uuid, in tests/Feature/Controllers/Api/McpCallControllerTest.php

## Phase 5: User Story 3, filters (P2)

Independent test: quickstart row 13.

- [X] T031 [US3] Apply `period` (`day`, `week`, `month`, `all`), `connection` (`api_key:{id}` or `oauth_client:{id}`), `result` and `tool` in `McpCallsViewModel`, with the connection and tool choices read from the account's own rows, in app/ViewModels/Settings/Account/Security/McpCallsViewModel.php
- [X] T032 [US3] Add the GET filter form (four labelled `x-select`, submit button) and the no match state linking to the unfiltered list, in resources/views/app/settings/account/security/mcp-calls.blade.php
- [X] T033 [US3] Accept the same filters, 422 on an unknown value, in app/Http/Controllers/Api/McpCallController.php
- [X] T034 [P] [US3] Test each filter, their combination, survival across pages and the no match state, in tests/Unit/ViewModels/Settings/Account/Security/McpCallsViewModelTest.php

## Phase 6: User Story 4, secrets out and old calls purged (P2)

Independent test: quickstart rows 8 to 10.

- [X] T035 [P] [US4] Test `McpPayload`: nested secret keys, sensitive and unknown field ids masked, non sensitive kept, the rule skipped for `replace-life-event-type-fields`, cut at 16 384 bytes on a character boundary, in tests/Unit/Helpers/McpPayloadTest.php
- [X] T036 [P] [US4] Test `prunable()` with rows at 91 and 89 days and a retention of 0 treated as 1, in tests/Unit/Models/McpCallTest.php
- [X] T037 [US4] Test quickstart rows 8 and 9 end to end over `POST /mcp`, in tests/Feature/Mcp/McpCallRecordingTest.php

## Phase 7: Polish

- [X] T038 Run tests/Feature/RouteProtectionTest.php: the new routes must ask who is signed in; `{mcpCall}` is a string looked up inside the account, not a bound model, so no trait is needed
- [X] T039 Write the API reference with the `api-docs-writer` skill, in resources/docs/api/24-mcp-calls.php
- [X] T040 Run `php artisan monica:localize` and translate every reported string in every locale of lang/*.json, French typography per .claude/rules/translations.md, until `--check` passes
- [X] T041 Record the feature with the `feature-documentator` skill
- [X] T042 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress` and the whole suite
- [ ] T043 Check the screen with the keyboard alone, VoiceOver, 200% zoom and a phone width, per quickstart.md

## Dependencies

- T001 to T005, then T006 to T011, block every story. T010 and T011 block T013.
- US1 (T012 to T018) blocks nothing on screen, but US2 tests need rows only the factory provides, so US2 can start after Phase 2.
- US3 extends the files US2 creates (T019, T022, T026). US4's tests need T010, T013 and T007.
- Parallel: T002 to T005; T009 with T010 and T011; T012 with T016; T025 with T019 to T024; T028 to T030; T035 with T036.
- MVP: Phases 1 to 3 record faithfully; Phase 4 makes it visible.
