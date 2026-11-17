# Implementation Plan: Clearer MCP tool descriptions and a redesigned MCP documentation page

**Branch**: `2026-11-17-mcp-description-audit` | **Date**: 2026-11-17 | **Spec**: [spec.md](spec.md)

## Summary

One update rule for the API and MCP (keep what is left out, null clears), every MCP tool answering
through the endpoint it mirrors, the vocabulary of the audit applied to every description, three new
read tools (two over new endpoints), and `/docs/mcp` rebuilt to the "MCP Docs" design from the
catalogue the server registers. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current), laravel/mcp

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js, Alpine Ajax; no new dependency

**Storage**: MySQL/SQLite through Eloquent; no migration

**Testing**: PHPUnit (`php artisan test --compact`), PHPStan, Pint, `monica:localize --check`

**Target Platform**: hosted and self hosted web application

**Project Type**: web application (Laravel monolith with a JSON API and an MCP server)

**Performance Goals**: the docs page renders the 225 tools in one request without querying the database for them

**Constraints**: API v1 field names unchanged; WCAG 2.2 AA; strings as the docs pages write them today

**Scale/Scope**: 30 API update endpoints, 2 new endpoints, ~225 MCP tools, 1 docs page and its Markdown twin

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass: controllers only decide which value reaches the action; actions keep their signatures |
| II. Simplicity | Pass: one pattern (`GroupController@update`) copied; no new abstraction |
| III. Tested before merged | Pass: each changed endpoint gets a "left out keeps, null clears" controller test |
| VI. Nothing changes under anybody's feet | Pass with notice: no stored data changes; a client clearing by omission is told by the API changelog to send null |
| IX. Accessible | Pass: tabs with `role="tab"`, `aria-expanded` on tool rows, keyboard route, focus rings |
| X. Everything goes through the API | Pass, and improved: tools hold no rule of their own (FR-010a) |
| XI. Short record | Pass: no `data-model.md` (no schema change); contracts in one file |

Re-checked after design: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/128-mcp-descriptions-docs/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
├── contracts/new-endpoints.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/Http/Controllers/Api/            # 30 update methods (R1), VaultReminderController, ValueListController
app/Mcp/Tools/                       # every tool moves to answerThroughApi (R2), 3 new tools, wording (R3)
app/Mcp/Servers/MonicaServer.php     # instructions reworded, 3 tools registered
app/ViewModels/Docs/McpDocsViewModel.php   # catalogue from the server (R4), toolGroups() deleted
app/Http/Controllers/App/Docs/       # McpDocsController gains the family page
resources/views/app/docs/mcp*.blade.php    # rebuilt to the design
resources/docs/api/*.php             # update rule, new endpoints
resources/docs/api-changelog/        # one release entry (R5)
routes/api.php, routes/web.php       # 2 API routes, 1 docs route
.claude/rules/mcp.md                 # "an update replaces" becomes "keeps what is left out"
tests/Feature/Controllers/Api/       # per endpoint: left out keeps, null clears
tests/Unit/Mcp/Tools/                # reduced to the wiring mcp.md lists
tests/Feature/Views/                 # docs page renders, every tool has a family
```

**Structure Decision**: existing Laravel layout; no new base folder.

## Delivery order

1. API update rule (R1) with tests, endpoint by endpoint, then the changelog and reference (R5).
2. New endpoints and their tools (contracts), so the catalogue is complete.
3. Tools through the API and reworded, family by family, following the design's 20 families.
4. Docs page and Markdown twin (R4), last, since it reads what steps 1 to 3 produce.

## Complexity Tracking

None.
