# Implementation Plan: Possessions

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-26 | **Spec**: [spec.md](spec.md)

## Summary

Two tables (`possessions`, `possession_field_values`), five new actions and two changed ones, seven
API endpoints under a vault, seven MCP tools, and their documentation. No screen. Principle VII is
amended to name Possession. Decisions in [research.md](research.md), tables and actions in
[data-model.md](data-model.md), endpoints and tools in [contracts/api-and-mcp.md](contracts/api-and-mcp.md),
checks in [quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Sanctum, Laravel MCP |
| Storage | Two new tables, one migration; SQLite, MySQL and PostgreSQL |
| Testing | PHPUnit: every action, every API controller, every tool, the enum |
| Scale | Hundreds of possessions per vault, tens of values each; the list is paginated |
| Constraints | Nothing across accounts; every refusal translated; new migration only |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Every write in an action; API and tools call the same ones |
| II Simplicity | Pass. One value column, database cascades, no policy, no flag |
| III Tested | Pass. Actions, controllers, tools (quickstart) |
| IV Laravel | Pass. `up()` only, `foreignIdFor()->constrained()`, inline validation |
| V Localized | Pass. Status labels and refusals through `monica:localize` |
| VI Nothing changes | Pass. The type deletion API keeps its 204 (R3) |
| VII Stable core | **Amended.** Possession joins the fixed concepts (R1) |
| VIII Nothing disappears | **Departure at the user's request**: a type takes its possessions, a field change or removal takes its values; the count is logged and told by the assistant (R3) |
| IX Accessible | Not touched: no screen |
| X API | Pass. Everything is API and tool, nothing else |
| XI Short record | Pass |
| Invariant: vault is a compartment | Pass. Every possession lives in one vault, looked up through it |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md                 VII names Possession, Sync Impact Report, 2.8.0
spec/spec.md, spec/models/062-063               Possession named; two model files
app/Enums/PossessionStatusEnum.php; UserActionEnum.php (three cases)
database/migrations/2026_10_26_000002_create_possessions_table.php
database/factories/PossessionFactory.php, PossessionFieldValueFactory.php
app/Models/Possession.php, PossessionFieldValue.php; Vault, PossessionType, PossessionField gain relations
app/Actions/CreatePossession.php, UpdatePossession.php, DestroyPossession.php,
            UpdatePossessionPosition.php, UpdatePossessionFieldValues.php
app/Actions/DestroyPossessionType.php, UpdatePossessionField.php    changed (R3)
app/Http/Resources/PossessionResource.php
app/Http/Controllers/Api/Possessions/           possession, position, values
routes/api.php                                  seven routes
app/Mcp/Tools/                                  seven tools; MirrorsAnApiCapability::possession(); MonicaServer;
                                                DeletePossessionType answers with the count
app/ViewModels/Docs/McpDocsViewModel.php        "Possessions" group
resources/docs/api/27-possessions.php
docs/portal/en/9-templates-and-customisation/12-possession-types.md   deleting a type deletes its possessions
docs/FEATURES.md
lang/*.json                                     via `php artisan monica:localize`
tests/Unit/Actions, tests/Unit/Enums, tests/Unit/Mcp/Tools, tests/Feature/Controllers/Api/Possessions
```

## Order of work

1. Amendment and model files; enum, migration, models, factories.
2. The five actions with their tests; the two changed actions and their new tests.
3. Resource, API controllers, routes, controller tests.
4. Tools, registration, docs group, tool tests.
5. API reference, guide line, FEATURES.md, translations, PHPStan, Pint, whole suite.

## Spec amendments made while planning

- FR-009: the count of deleted possessions is in the log and the assistant's answer, not the API's
  204 (R3).
- FR-010, SC-002, US4.2: a key reaches its whole account; keys have no vault restriction (R6).

## Complexity Tracking

| Departure | Why | Simpler alternative rejected because |
| --- | --- | --- |
| Principle VII amended | Possessions are the records of a fixed concept already admitted | Building them on templates was declined in 088 |
| Principle VIII departed from | The user chose cascade | Refusing deletion was declined |
