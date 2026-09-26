# Implementation Plan: MCP tools for entity types

**Branch**: `2026-09-25-mcp-stuff-2` | **Date**: 2026-09-25 | **Spec**: [spec.md](spec.md)

## Summary

Six new tools on `EricaServer`, one class each under `app/Mcp/Tools`, each mirroring an existing
`api.v1.entityTypes.*` route by calling the same action and returning `EntityTypeResource`
([contracts/mcp-tools.md](contracts/mcp-tools.md)). The API already covers everything
([R1](research.md#r1-every-capability-already-exists-in-the-api)), so no route or controller changes.
`/docs/mcp` and its Markdown gain the tools and one paragraph on entity types; the server
instructions gain one sentence.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (current major)

**Primary Dependencies**: `laravel/mcp` (`Tool`, `Response`, `IsReadOnly`, `IsDestructive`)

**Storage**: existing `entity_types` table; no migration

**Testing**: PHPUnit. One unit test per tool under `tests/Unit/Mcp/Tools`, one case each added to
`McpCompartmentTest` and `McpChangelogTest`, `McpDocsViewModelTest` updated

**Target Platform**: the MCP endpoint at `/api/mcp`

**Project Type**: server rendered web application with a JSON API and an MCP server

**Constraints**: no rule of its own in a tool (principle X); refusals relayed word for word; no new
dependency or folder

**Scale/Scope**: 6 tools, 1 trait lookup, 1 view model, 1 Blade view, server instructions

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Tools call the five existing actions only |
| II. Simplicity | Pass. One lookup added to the existing trait; tools copy the shape of their template siblings |
| III. Tested | Pass. Every tool, the account boundary, the journal source and the docs listing are tested |
| IV. Laravel conventions | Pass. `$request->validate()`, the API's rules, no new route |
| V. Localized | Pass. New sentences (`That entity type does not exist.`, `The entity type is deleted.`) go through `__()` and `monica:localize`; the delete refusal is the policy's translated sentence |
| VI. Nothing changes under anybody's feet | Pass. Switching a type off keeps its records; the slug stays fixed |
| VIII. Nothing disappears silently | Pass. Delete goes through the policy, refuses a type holding records, and is marked destructive |
| IX. Accessible | Pass. The docs page reuses its existing table and prose markup |
| X. Through the API | Pass. Every tool names a route that exists (`McpCatalogueTest`) |
| XI. Short record | Pass. `data-model.md` omitted: nothing is stored or reshaped |

One deliberate difference from the API, justified in
[R2](research.md#r2-update-keeps-what-is-left-out): `update-entity-type` keeps what is left out, as
the other MCP update tools do, instead of requiring every attribute.

Re-checked after design: no change.

## Project Structure

### Documentation (this feature)

```text
specs/058-mcp-entity-type-tools/
├── plan.md
├── research.md
├── quickstart.md
├── contracts/mcp-tools.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/Mcp/Tools/MirrorsAnApiCapability.php      # + entityType() lookup
app/Mcp/Tools/{GetEntityType,CreateEntityType,UpdateEntityType,DeleteEntityType,
  SetEntityTypeActivation,SetEntityTypePosition}.php
app/Mcp/Servers/EricaServer.php               # register 6 tools; one sentence of instructions
app/ViewModels/Docs/McpDocsViewModel.php      # tool groups, ENTITY_TYPES paragraph, markdown
resources/views/app/docs/mcp.blade.php        # draw the paragraph under its heading
lang/*.json                                   # new sentences via monica:localize
docs/FEATURES.md                              # feature entry

tests/Unit/Mcp/Tools/{GetEntityType,CreateEntityType,UpdateEntityType,DeleteEntityType,
  SetEntityTypeActivation,SetEntityTypePosition}Test.php
tests/Feature/Mcp/McpCompartmentTest.php      # another account's type is not found, not changed
tests/Feature/Mcp/McpChangelogTest.php        # an entity type change arrives over MCP
tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php  # new group name in the changing list
```

**Structure Decision**: the existing MCP layout. Tool classes are named after the tool
(`CreateEntityType`), which collides with the action of the same name; import the action with an
alias (`CreateEntityType as CreateEntityTypeAction`), as `UpdateTemplate` already does.

## Complexity Tracking

None.
