# Implementation Plan: MCP tools for reading the account logs

**Branch**: `2026-09-25-mcp-account-logs` (based on `main`) | **Date**: 2026-09-25 |
**Spec**: [spec.md](spec.md)

## Summary

Four read-only tools on `EricaServer`, one class each under `app/Mcp/Tools`: `list-logs`, `get-log`,
`list-emails-sent` and `get-email-sent`. Each mirrors an existing `api.v1.logs*` or `api.v1.emailsSent*`
route by running the same query and returning the same resource
([contracts/mcp-tools.md](contracts/mcp-tools.md)). The two lists are the first paginated MCP tools
([R2](research.md#r2-the-list-tools-are-paginated-unlike-every-existing-mcp-list-tool)).
`EmailSentResource` takes a `withBody` argument instead of reading the route name, so the body reaches
MCP ([R3](research.md#r3-the-email-body-reaches-the-mcp-tool)). `CreateEmailSent` removes web addresses
from the body it stores, which closes the sign-in link leak for MCP and the API alike
([R4](research.md#r4-web-addresses-are-removed-from-the-body-when-it-is-recorded)). `/docs/mcp`, its
Markdown, the server instructions and the API reference's `body` field are updated.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (current major)

**Primary Dependencies**: `laravel/mcp` (`Tool`, `Response`, `IsReadOnly`), `stevebauman/purify`
(already used by `CreateEmailSent`)

**Storage**: existing `logs` and `emails_sent` tables; no migration

**Testing**: PHPUnit. One unit test per tool under `tests/Unit/Mcp/Tools`; cases added to
`CreateEmailSentTest`; docs tests updated;
`EmailSentControllerTest` run unchanged to guard the resource change

**Target Platform**: the MCP endpoint at `/api/mcp`

**Project Type**: server rendered web application with a JSON API and an MCP server

**Performance Goals**: a list call reads one page (25 by default, 100 at most), never the whole table

**Constraints**: no rule of its own in a tool (principle X); refusals translated; no new dependency or
folder

**Scale/Scope**: 4 tools, 2 trait lookups, 1 resource argument, 1 line in 1 action, 1 view model,
1 Blade view, server instructions, 1 API reference field, 2 new translated sentences

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. The tools read, as the API controllers do, and call no action. Removing addresses sits in `CreateEmailSent`, the action that already sanitizes the body |
| II. Simplicity | Pass. The page clamp is repeated inline in two tools, as in the two controllers. The address removal is one expression |
| III. Tested | Pass. Every tool, the account boundary, the address removal on a real notification, the unchanged API shape and the docs listing are tested |
| IV. Laravel conventions | Pass. `$request->validate()`, `paginate()` with an explicit page, no new route |
| V. Localized and safe | Pass. Two new not-found sentences through `__()` and `monica:localize`. The address removal makes the stored body safe to read back |
| VI. Nothing changes under anybody's feet | Pass. Nothing is written. Stored bodies are not rewritten |
| VIII. Nothing disappears silently | Pass. The docs page and the API reference say links and addresses are removed from the kept copy |
| IX. Accessible | Pass. The docs section reuses the existing section markup |
| X. Through the API | Pass. Every tool names a route that exists (`McpCatalogueTest`). The one deviation, refusing a non-numeric page size the API would cast, is recorded in R2 |
| XI. Short record | Pass. `data-model.md` omitted: no table or shape changes |

Re-checked after design: no change.

## Project Structure

### Documentation (this feature)

```text
specs/061-mcp-account-log-tools/
├── plan.md
├── research.md
├── quickstart.md
├── contracts/mcp-tools.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/Mcp/Tools/{ListLogs,GetLog,ListEmailsSent,GetEmailSent}.php
app/Mcp/Tools/MirrorsAnApiCapability.php       # + log(), emailSent()
app/Mcp/Servers/EricaServer.php                # register 4 tools; one paragraph of instructions
app/Http/Resources/EmailSentResource.php       # withBody constructor argument
app/Http/Controllers/Api/EmailSentController.php  # show passes withBody: true
app/Actions/CreateEmailSent.php                # remove web addresses after the Purify pass
app/ViewModels/Docs/McpDocsViewModel.php       # Account logs group, LOGS paragraphs, section, markdown
resources/views/app/docs/mcp.blade.php         # draw the Read the logs section
resources/docs/api/09-emails-sent.php          # body description says addresses are removed
lang/*.json                                    # two new sentences via monica:localize
docs/FEATURES.md                               # feature entry

tests/Unit/Mcp/Tools/{ListLogs,GetLog,ListEmailsSent,GetEmailSent}Test.php
tests/Unit/Actions/CreateEmailSentTest.php     # addresses removed, copy kept, on a real sign-in email
tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php  # count 55
tests/Feature/Controllers/App/Docs/McpDocs{,Markdown}ControllerTest.php  # new heading
```

**Structure Decision**: the existing MCP layout. The tools read the account relations directly, like
`ListRelationshipTypes` and the API controllers they mirror.

## Complexity Tracking

None.
