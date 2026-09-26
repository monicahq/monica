# Implementation Plan: MCP tools for life event types

**Branch**: `2026-09-25-mcp-life-event-types` (on top of `2026-09-25-mcp-relationship-types`) |
**Date**: 2026-09-25 | **Spec**: [spec.md](spec.md)

## Summary

Sixteen new tools on `EricaServer`, one class each under `app/Mcp/Tools`: nine for life event types
(including reading and replacing their fields) and seven for life event categories. Each mirrors an
existing `api.v1.lifeEventTypes.*` or `api.v1.lifeEventCategories.*` route by calling the same action
and returning the same resource ([contracts/mcp-tools.md](contracts/mcp-tools.md)). No route changes.
The API fields controller's rules and input mapping move to a helper shared with the replace tool
([R5](research.md#r5-the-fields-input-is-shared-with-the-api-controller)). `/docs/mcp` and its
Markdown gain the tools and two paragraphs; the server instructions gain one.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (current major)

**Primary Dependencies**: `laravel/mcp` (`Tool`, `Response`, `IsReadOnly`, `IsDestructive`)

**Storage**: existing `life_event_types`, `life_event_categories` and structure tables; no migration

**Testing**: PHPUnit. One unit test per tool under `tests/Unit/Mcp/Tools`, cases added to
`McpCompartmentTest` and `McpChangelogTest`, docs tests updated, the existing
`LifeEventTypeFieldControllerTest` run unchanged

**Target Platform**: the MCP endpoint at `/api/mcp`

**Project Type**: server rendered web application with a JSON API and an MCP server

**Constraints**: no rule of its own in a tool (principle X); refusals in the caller's language where
the application already has one; no new dependency or folder

**Scale/Scope**: 16 tools, 2 trait lookups, 1 helper extracted from 1 API controller, 1 view model,
1 Blade view, server instructions

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Tools call the existing actions only; the helper converts input shape and holds no business rule |
| II. Simplicity | Pass. One extraction, justified by size and reuse (R5); tools copy the shape of the relationship tools |
| III. Tested | Pass. Every tool, the account boundary, the journal source and the docs listing are tested; the API fields test guards the extraction |
| IV. Laravel conventions | Pass. `$request->validate()` with the API's rules, no new route |
| V. Localized | Pass. Type and category refusals are the policies' translated sentences; four new sentences go through `__()` and `monica:localize`. The field refusals stay untranslated English, as on the web screen and in `update-template-field` (R2) |
| VI. Nothing changes under anybody's feet | Pass. Replacing fields publishes a new version; recorded life events keep theirs |
| VIII. Nothing disappears silently | Pass. Deletes refuse what the API refuses; deletes and replace are marked destructive |
| IX. Accessible | Pass. The docs page reuses its table and prose markup |
| X. Through the API | Pass. Every tool names a route that exists (`McpCatalogueTest`) |
| XI. Short record | Pass. `data-model.md` omitted: nothing is stored or reshaped |

One deliberate difference from the API, justified in research: `update-life-event-type` keeps what is
left out ([R3](research.md#r3-update-keeps-what-is-left-out)).

Re-checked after design: no change.

## Project Structure

### Documentation (this feature)

```text
specs/060-mcp-life-event-type-tools/
├── plan.md
├── research.md
├── quickstart.md
├── contracts/mcp-tools.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/Helpers/LifeEventTypeFieldLayout.php      # rules() and the groups/fields mapping, moved from the API controller
app/Http/Controllers/Api/LifeEventTypeFieldController.php  # uses the helper
app/Mcp/Tools/MirrorsAnApiCapability.php      # + lifeEventType(), lifeEventCategory()
app/Mcp/Tools/{ListLifeEventTypes,GetLifeEventType,CreateLifeEventType,UpdateLifeEventType,
  DeleteLifeEventType,SetLifeEventTypeActivation,SetLifeEventTypePosition,
  GetLifeEventTypeFields,ReplaceLifeEventTypeFields}.php
app/Mcp/Tools/{GetLifeEventCategory,ListLifeEventCategoryTypes,CreateLifeEventCategory,
  UpdateLifeEventCategory,DeleteLifeEventCategory,SetLifeEventCategoryActivation,
  SetLifeEventCategoryPosition}.php
app/Mcp/Servers/EricaServer.php               # register 16 tools; one paragraph of instructions
app/ViewModels/Docs/McpDocsViewModel.php      # tool groups, LIFE_EVENT_TYPES paragraphs, markdown
resources/views/app/docs/mcp.blade.php        # draw the paragraphs under their heading
lang/*.json                                   # four new sentences via monica:localize
docs/FEATURES.md                              # feature entries

tests/Unit/Mcp/Tools/<one test per new tool>Test.php
tests/Feature/Mcp/McpCompartmentTest.php      # another account's type and category are not found
tests/Feature/Mcp/McpChangelogTest.php        # a life event type change arrives over MCP
tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php  # group names, tool count
tests/Feature/Controllers/App/Docs/McpDocsControllerTest.php  # new heading
```

**Structure Decision**: the existing MCP layout. Five tool classes share a name with their action;
import the action with an `...Action` alias ([R8](research.md#r8-tool-classes-that-share-a-name-with-an-action)).
The helper goes in `app/Helpers`, next to `FieldValueRules` and `FieldSettingsCatalogue`.

## Complexity Tracking

None.
