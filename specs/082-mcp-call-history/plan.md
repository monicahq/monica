# Implementation Plan: MCP Call History

**Branch**: `2026-10-23-mcp-call-history` | **Date**: 2026-10-23 | **Spec**: [spec.md](spec.md)

## Summary

`MonicaServer` replaces the package's `tools/call` handler with one that writes a row before the tool
runs and closes it with a result read from the exception or the response, never the HTTP status
([research R1, R2](research.md)). The settings screen and a read only API list the rows. A daily prune
removes them after 90 days.

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel 13, `laravel/mcp` v1.0.1, Sanctum and Passport |
| Storage | One table, `mcp_calls` ([data-model.md](data-model.md)); SQLite, MySQL and PostgreSQL |
| Front end | Blade, Tailwind, native `<details>`; no new JavaScript |
| Testing | PHPUnit; recording over `POST /mcp` ([quickstart.md](quickstart.md)) |
| Cost per call | Two writes (insert, then update) and one select for the `fields` mask |
| Scale | 20 rows a page; indexes on account plus each filter |

No open question remains.

## Constitution Check

| Principle | How it holds |
| --- | --- |
| I. Actions | Nothing here is a user operation: recording is server plumbing, reading is a view model. No action is added. |
| II. Simplicity | One handler class, one helper for masking, no event or listener |
| III. Tested | Every scenario in quickstart.md; enums, model scope, helper and view model tested in their layer |
| IV. Laravel | `MassPrunable`, `model:prune` already scheduled, `PageSize`, `up()` only |
| V. Localized | Copy through `__()` and `trans_choice()`; dates through `x-timestamp` in the user's zone |
| VI. Nothing changes underfoot | A row is closed once and never edited |
| VIII. Nothing disappears silently | Removal only by the stated retention, which the screen announces |
| IX. Accessible | `<details>`/`<summary>` rows, result as text beside the badge, labelled filters |
| X. API | `GET api/v1/mcp-calls` and `.../{uuid}` ([contracts/api.md](contracts/api.md)) |
| XI. Short record | spec 120, plan, research 79, data model and contract under 60 |
| Invariants | Scoped by account, then vault. Not a share, not the changelog: a third record, of access |

Gate: passes. No amendment: every file goes into an existing folder.

## Project Structure

```text
database/migrations/2026_10_23_000000_create_mcp_calls_table.php
app/Models/McpCall.php                                   + Account::mcpCalls()
app/Enums/McpCallResultEnum.php, McpOperationEnum.php, McpConnectionTypeEnum.php
app/Mcp/Servers/RecordedCallTool.php                     tools/call handler (R1, R2, R3)
app/Mcp/Servers/MonicaServer.php                          $methods points tools/call at it
app/Mcp/Tools/MirrorsAnApiCapability.php                 sets mcp_vault, mcp_subject (R4)
app/Http/Middleware/SetMcpLogSource.php                  also sets connection kind and id (R6)
app/Helpers/McpPayload.php                               mask and cap (R5)
config/monica.php                                        mcp_call_retention_in_days (R7)
app/ViewModels/Settings/Account/Security/McpCallsViewModel.php
app/Http/Controllers/App/Settings/Account/Security/McpCallController.php   index
resources/views/app/settings/account/security/mcp-calls.blade.php, _mcp-call-row.blade.php
resources/views/app/settings/account/security/_connected-assistants.blade.php   link to it
routes/web.php            settings/account/security/mcp-history, settings.mcpCalls.index, flag:mcp-server
app/Http/Controllers/Api/McpCallController.php, app/Http/Resources/McpCallResource.php
routes/api.php            mcp-calls, mcp-calls/{mcpCall}, inside flag:mcp-server
resources/docs/api/24-mcp-calls.php
tests/Feature/Mcp/McpCallRecordingTest.php               quickstart 1 to 9
tests/Fixtures/Mcp/...                                    the fixture tool and server for scenario 6
tests/Unit/Helpers/McpPayloadTest.php, tests/Unit/Models/McpCallTest.php (prune)
tests/Unit/ViewModels/Settings/Account/Security/McpCallsViewModelTest.php
tests/Feature/Controllers/App/Settings/Account/Security/McpCallControllerTest.php
tests/Feature/Controllers/Api/McpCallControllerTest.php
```

## Screen

`x-settings.page` with Security and access current. `x-page-header` "MCP history" and a line saying
what lands here. A filter form (four `x-select`, GET, submitted with a button), then one panel per
day in the user's zone. A row's `<summary>` holds time, short description, connection, tool,
operation and vault, duration, and a badge with the result in words. Its body holds an `x-notice`
for the reason, an `x-detail-list` of the facts, and two `<pre>` blocks, each with a "Truncated" note
when cut. Under the list: "Sensitive information is masked." and the retention sentence, with the
day count from configuration through `trans_choice()`. Empty and no match states use
`x-empty-state`; the second links to the unfiltered list.

Short description (FR-011): sixteen wordings, operation × succeeded or not × object known or not,
such as "Read :subject" and "Tried to delete :subject". Built in the view model, never in Blade.

## Risks

- `RecordedCallTool` copies twenty lines of `CallTool` and `ToolInvoker`; scenario 1 fails on drift.
- The row is written outside any transaction an action opens, so a rolled back change still leaves
  its call recorded, as `error`. That is the intended reading.
- A call running right now shows as interrupted until it returns. Accepted: it is seconds.

## Complexity Tracking

None.
