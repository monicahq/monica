# Implementation Plan: Relatives without a record and possessions through a connected assistant

**Branch**: `2026-11-13-mcp-relatives-possessions` | **Date**: 2026-11-13 | **Spec**: [spec.md](spec.md)

## Summary

Both capabilities exist; a connected assistant does not find them. The MCP server returns its whole
catalogue in one page instead of fifteen, its instructions describe adding a relative without a
record and recording a possession in the words a person uses, and two tool descriptions open with
those words. No action, model, migration, API endpoint or screen changes. Causes and choices are in
[research.md](research.md); exact wording in [contracts/mcp-guidance.md](contracts/mcp-guidance.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (latest)

**Primary Dependencies**: `laravel/mcp` (server paging read from `Server::$defaultPaginationLength`
and `Server::$maxPaginationLength`)

**Storage**: no change

**Testing**: PHPUnit, `tests/Feature/Mcp` and `tests/Unit/Mcp/Tools`

**Target Platform**: Linux server, the `/mcp` route

**Project Type**: web service (Laravel monolith)

**Performance Goals**: one `tools/list` answer of about 183 KB for 222 tools, served once per session

**Constraints**: the MCP server mirrors the API and holds no rule of its own (constitution X)

**Scale/Scope**: three files changed, two tests added

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. No logic added; tools still delegate to `CreateRelationship` and `CreatePossession`. |
| II. Simplicity | Pass. Two property overrides and text. No new tool. |
| III. Tested before merged | Pass. Catalogue paging and the tool's default are tested (research R5). |
| IV. Laravel conventions | Pass. Uses the package's documented properties. |
| V. Localized | Pass. MCP descriptions and instructions are English like every other tool; no `__()` string added. |
| VI to IX | Not touched: no structure, personalisation, deletion or screen. |
| X. Everything through the API | Pass. No capability added; parameters and answers stay identical to the API. |
| XI. Written record short | Pass. spec 120, plan under 100, research under 80; data-model omitted (no data change). |

Re-check after design: unchanged, no violation.

## Project Structure

### Documentation (this feature)

```text
specs/124-mcp-relatives-possessions/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
├── contracts/mcp-guidance.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/Mcp/Servers/MonicaServer.php              # paging properties, two instruction paragraphs
app/Mcp/Tools/CreateContactRelationship.php   # first sentence of the description
app/Mcp/Tools/CreatePossession.php            # first sentence of the description
tests/Feature/Mcp/McpCatalogueTest.php        # whole catalogue on one page, no cursor
tests/Unit/Mcp/Tools/CreateContactRelationshipTest.php  # named person kept without a record
app/ViewModels/Docs/McpDocsViewModel.php      # MCP guide, two tool descriptions
docs/portal/en/4-relationships-and-life-events/2-link-two-people.md  # section on assistants
```

**Structure Decision**: the existing MCP server and tool files; nothing new under `app/`.

## Done when

- Tests above pass, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent` are clean.
- `php artisan monica:localize --check` passes (expected untouched).
- The feature list is updated with the `feature-documentator` skill.
- The by-hand replay in [quickstart.md](quickstart.md) is run against a deployment by the user.
