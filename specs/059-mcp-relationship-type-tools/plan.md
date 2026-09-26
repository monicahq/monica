# Implementation Plan: MCP tools for relationship types

**Branch**: `2026-09-25-mcp-relationship-types` | **Date**: 2026-09-25 | **Spec**: [spec.md](spec.md)

## Summary

Thirteen new tools on `EricaServer`, one class each under `app/Mcp/Tools`: six for relationship types
and seven for relationship categories. Each mirrors an existing `api.v1.relationshipTypes.*` or
`api.v1.relationshipCategories.*` route by calling the same action and returning the same resource
([contracts/mcp-tools.md](contracts/mcp-tools.md)). The API already covers everything
([R1](research.md#r1-every-capability-already-exists-in-the-api)), so no route or controller changes.
`/docs/mcp` and its Markdown gain the tools and two paragraphs; the server instructions gain one
sentence.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (current major)

**Primary Dependencies**: `laravel/mcp` (`Tool`, `Response`, `IsReadOnly`, `IsDestructive`)

**Storage**: existing `relationship_types` and `relationship_categories` tables; no migration

**Testing**: PHPUnit. One unit test per tool under `tests/Unit/Mcp/Tools`, cases added to
`McpCompartmentTest` and `McpChangelogTest`, `McpDocsViewModelTest` updated

**Target Platform**: the MCP endpoint at `/api/mcp`

**Project Type**: server rendered web application with a JSON API and an MCP server

**Constraints**: no rule of its own in a tool (principle X); refusals in the caller's language; no new
dependency or folder

**Scale/Scope**: 13 tools, 2 trait lookups, 1 view model, 1 Blade view, server instructions

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Tools call the existing actions only |
| II. Simplicity | Pass. Two lookups added to the existing trait; tools copy the shape of the entity type tools |
| III. Tested | Pass. Every tool, the account boundary, the journal source and the docs listing are tested |
| IV. Laravel conventions | Pass. `$request->validate()` with the API's rules, no new route |
| V. Localized | Pass. Refusals use the web screen's sentences, already translated ([R2](research.md#r2-refusals-carry-the-web-screens-translated-sentence)); four new sentences go through `__()` and `monica:localize` |
| VI. Nothing changes under anybody's feet | Pass. Switching off keeps existing relationships; a used type keeps its direction |
| VIII. Nothing disappears silently | Pass. Both deletes refuse what the API refuses and are marked destructive |
| IX. Accessible | Pass. The docs page reuses its table and prose markup |
| X. Through the API | Pass. Every tool names a route that exists (`McpCatalogueTest`) |
| XI. Short record | Pass. `data-model.md` omitted: nothing is stored or reshaped |

Two deliberate differences from the API, both justified in research:

- Refusal sentences are the web screen's translated ones, not the actions' English messages
  ([R2](research.md#r2-refusals-carry-the-web-screens-translated-sentence)). Same cases, other
  wording. The spec's assumption is updated to match.
- `update-relationship-type` keeps what is left out, as the other MCP update tools do
  ([R3](research.md#r3-update-keeps-what-is-left-out)).

Re-checked after design: no change.

## Project Structure

### Documentation (this feature)

```text
specs/059-mcp-relationship-type-tools/
├── plan.md
├── research.md
├── quickstart.md
├── contracts/mcp-tools.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/Mcp/Tools/MirrorsAnApiCapability.php      # + relationshipType(), relationshipCategory()
app/Mcp/Tools/{GetRelationshipType,CreateRelationshipType,UpdateRelationshipType,
  DeleteRelationshipType,SetRelationshipTypeActivation,SetRelationshipTypePosition}.php
app/Mcp/Tools/{GetRelationshipCategory,ListRelationshipCategoryTypes,CreateRelationshipCategory,
  UpdateRelationshipCategory,DeleteRelationshipCategory,SetRelationshipCategoryActivation,
  SetRelationshipCategoryPosition}.php
app/Mcp/Servers/EricaServer.php               # register 13 tools; one sentence of instructions
app/ViewModels/Docs/McpDocsViewModel.php      # tool groups, RELATIONSHIP_TYPES paragraphs, markdown
resources/views/app/docs/mcp.blade.php        # draw the paragraphs under their heading
lang/*.json                                   # four new sentences via monica:localize
docs/FEATURES.md                              # feature entry

tests/Unit/Mcp/Tools/<one test per new tool>Test.php
tests/Feature/Mcp/McpCompartmentTest.php      # another account's type and category are not found
tests/Feature/Mcp/McpChangelogTest.php        # a type and a category change arrive over MCP
tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php  # new group name in the changing list
```

**Structure Decision**: the existing MCP layout. Four tool classes share a name with their action;
import the action with an `...Action` alias ([R7](research.md#r7-tool-classes-that-share-a-name-with-an-action)).

## Complexity Tracking

None.
