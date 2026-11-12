# Implementation Plan: Life events through the API and the MCP server

**Branch**: `2026-10-11-life-events-api` | **Date**: 2026-10-11 | **Spec**: [spec.md](spec.md)

## Summary

Expose the life events of a vault through six JSON endpoints and six MCP tools, all calling the
actions the contact page already uses, and document both. Two small action changes: `CreateLifeEvent`
records where it was written from, and `UpdateLifeEvent` can leave custom field values alone.
Decisions are in [research.md](research.md); shapes in [contracts/](contracts/).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Laravel MCP, Sanctum |
| Storage | No migration. `life_events`, `life_event_participants`, `field_values` unchanged |
| Testing | PHPUnit, SQLite, MySQL and PostgreSQL in CI |
| Scale | One vault's life events, paginated like every list endpoint |
| Unknowns | None left: see research R1 to R8 |

## Constitution Check

| Principle | How it is met |
| --- | --- |
| I. Actions | Create, update, delete and restore reuse their actions; controllers and tools only translate input |
| II. Simplicity | No new helper class; `FieldValueRules` and `FieldValueFormatter` are reused |
| III. Tests | Controller, MCP tool and action tests listed below |
| IV. Laravel | Routes one per line, camelCase names, inline validation, API resource |
| V. Localized | MCP refusals and the new 422 messages go through `__()` and `monica:localize`. Docs portal copy stays English, as every docs page does |
| VI. Nothing changes | An update keeps the life event on its structure version unless its type changes (existing action behaviour) |
| VIII. Nothing disappears | Delete is the existing soft delete; restore is exposed |
| IX. Accessible | No screen changes |
| X. API | This feature; MCP calls the actions and holds no rule of its own |
| XI. Short | spec 110, plan, research, two contracts and quickstart under their caps; no data-model.md (no schema change; the resource shape is in contracts/api.md) |

Gate: pass, before and after design.

## Project Structure

```text
app/Actions/CreateLifeEvent.php                       + createdFrom parameter (R8)
app/Actions/UpdateLifeEvent.php                       values becomes ?array, null skips SetFieldValues (R5)
app/Http/Controllers/Api/LifeEventController.php      index, show, create, update, destroy
app/Http/Controllers/Api/LifeEventRestorationController.php  update
app/Http/Resources/LifeEventResource.php              withSensitive property, as SearchResultResource
app/Mcp/Tools/{List,Get,Create,Update,Delete,Restore}LifeEvent(s).php
app/Mcp/Tools/MirrorsAnApiCapability.php              + lifeEvent()
app/Mcp/Servers/MonicaServer.php                       register six tools, one instructions paragraph
app/ViewModels/Docs/McpDocsViewModel.php              two tool groups, summary corrected
resources/docs/api/22-life-events.php                 six sections, in api-docs-writer order
routes/api.php                                        six routes (contracts/api.md)
lang/*.json                                           via monica:localize

tests/Feature/Controllers/Api/LifeEventControllerTest.php
tests/Feature/Controllers/Api/LifeEventRestorationControllerTest.php
tests/Unit/Mcp/Tools/{List,Get,Create,Update,Delete,Restore}LifeEvent(s)Test.php
tests/Unit/Actions/CreateLifeEventTest.php            + records the source it is given
tests/Unit/Actions/UpdateLifeEventTest.php            + null values keep every value
tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php   adjust if it counts tools
tests/Feature/Controllers/Marketing/Docs/ApiDocsControllerTest.php  + assertSee "Life events"
```

**Structure decision**: existing folders only. The API controller looks records up with `string`
parameters and `addressedBy()`, as `ContactController` and `ContactReminderController` do.

## What each test file proves

- **LifeEventControllerTest**: list ordered and paginated, narrowed by contact, another account's
  left out; show with participants and fields; sensitive value hidden without the scope and shown
  with it; create 201 with `created_from` `api`; validation 422 (date, participants, file field);
  one action refusal (inactive type) as 422; update 200 keeping values when `fields` is omitted;
  first participant changed refused 422; delete 204 and `assertSoftDeleted`; a life event of
  another vault of the same account 404.
- **LifeEventRestorationControllerTest**: restore 200; a life event that is not deleted 404.
- **MCP tool tests**: each tool's happy path, its refusal for a record out of reach, the source
  `mcp` on create, update keeping what is left out, and no sensitive value ever returned.
- Account scoping, journal entries, counts and timelines stay proven once, in the action tests.

## Order of work

1. Action changes with their tests (R5, R8).
2. Resource, controllers, routes, controller tests.
3. Docs page `22-life-events.php`, docs tests.
4. MCP tools, trait method, server registration and instructions, tool tests.
5. MCP docs view model, `monica:localize`, PHPStan, Pint, whole suite.

## Risks

- Branch `2026-10-11-dashboard-life-events` adds the same `createdFrom` parameter; merge it first or resolve the one line conflict.
- `api-writer` still lists role based tests ("owners and editors"); the constitution has no roles, so those cases become cross-vault and cross-account 404 tests.

## Complexity Tracking

None.
