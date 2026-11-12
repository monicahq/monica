# Implementation Plan: Possession Conditions

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-26 | **Spec**: [spec.md](spec.md)

## Summary

One table (`possession_conditions`) with a position, one enum of six defaults written to every new
account and backfilled to existing ones, five actions, one settings screen edited in place and
reordered by dragging or from the keyboard, six API endpoints, six MCP tools, and their
documentation. Conditions are not put on possessions yet. Principle VII is amended to name
Possession condition. Decisions in [research.md](research.md), the table, actions and view model in
[data-model.md](data-model.md), routes, screen, endpoints and tools in
[contracts/screen-api-and-mcp.md](contracts/screen-api-and-mcp.md), checks in
[quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js, Alpine Ajax, Sanctum, Laravel MCP |
| Storage | One new table, a create migration and a backfill migration; SQLite, MySQL and PostgreSQL |
| Testing | PHPUnit: every action, the job, the view model, the settings and API controllers, every tool, the screen's contract |
| Scale | Six to a few dozen conditions per account; the list is loaded whole |
| Constraints | Nothing across accounts; every wording translated, the default names included; WCAG 2.2 AA; new migrations only |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Every write in an action; screen, API and tools call the same five |
| II Simplicity | Pass. No index for uniqueness, no policy, no flag, no new component (R4, R5, R11) |
| III Tested | Pass. Each layer proves its own rules (quickstart) |
| IV Laravel | Pass. `up()` only, `foreignIdFor()->constrained()`, inline validation, one named route per line |
| V Localized | Pass. The defaults are written in the user's locale; the count line through `trans_choice()`; "New" re-translated to its condition sense (R3) |
| VI Nothing changes | Pass. Nothing existing changes shape; `CreateAccount` gains one job in its chain |
| VII Stable core | **Amended.** Possession condition joins the fixed concepts (R1) |
| VIII Nothing disappears | Pass. A deletion is confirmed and journaled; nothing else holds a condition yet |
| IX Accessible | Pass. Labelled pickers, keyboard reorder with focus kept and the move announced, colour never alone (R5, R6) |
| X API | Pass. Every operation of the screen is an endpoint and a tool |
| XI Short record | Pass |
| Invariant: vault is a compartment | Pass. A condition belongs to the account |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md                 VII names Possession condition, Sync Impact Report, 2.10.0
spec/spec.md, spec/models/065-possession-condition-in-progress.md, spec/models/README.md
app/Enums/PossessionConditionEnum.php           the six defaults
app/Enums/UserActionEnum.php                    five cases
database/migrations/2026_10_26_000004_create_possession_conditions_table.php
database/migrations/2026_10_26_000005_backfill_possession_conditions.php
database/factories/PossessionConditionFactory.php
app/Models/PossessionCondition.php; Account gains possessionConditions()
app/Actions/CreatePossessionCondition.php, UpdatePossessionCondition.php,
  UpdatePossessionConditionPosition.php, DestroyPossessionCondition.php,
  CreateDefaultPossessionConditions.php
app/Jobs/SeedPossessionConditions.php; app/Actions/CreateAccount.php chains it
app/ViewModels/Settings/Account/PossessionConditions/PossessionConditionsViewModel.php
app/Http/Controllers/App/Settings/Account/PossessionConditions/PossessionConditionController.php,
  PossessionConditionPositionController.php
resources/views/app/settings/account/possession-conditions/index.blade.php, _row.blade.php
resources/views/components/settings/sidebar.blade.php   the entry, after Possession tags (R8)
routes/web.php                                  five routes
app/Http/Resources/PossessionConditionResource.php
app/Http/Controllers/Api/PossessionConditions/PossessionConditionController.php,
  PossessionConditionPositionController.php
routes/api.php                                  six routes
app/Mcp/Tools/                                  six tools; MirrorsAnApiCapability::possessionCondition(); MonicaServer
app/ViewModels/Docs/McpDocsViewModel.php        two groups
resources/docs/api/29-possession-conditions.php
docs/portal/en/9-templates-and-customisation/15-possession-conditions.md
docs/FEATURES.md
lang/*.json                                     via `php artisan monica:localize`
.claude/rules/translations.md                   glossary row "condition"
tests/...                                       as quickstart lists
```

## Order of work

1. Amendment and model file; enum; migrations; model, factory, `Account::possessionConditions()`.
2. The five actions and the journal cases, with their tests; the job and the chain.
3. View model, the two settings controllers, routes, the two views, the sidebar entry, with tests.
4. Resource, the two API controllers, routes, tests.
5. Tools, registration, docs groups, tool tests.
6. API reference, guide page, FEATURES.md, translations and glossary, PHPStan, Pint, whole suite,
   a look at the screen in light and dark.

## Spec amendments made while planning

None. The spec's assumptions hold: the defaults have no icon, the order is the account's, and the
screen follows the tags screen.

## Complexity Tracking

| Departure | Why | Simpler alternative rejected because |
| --- | --- | --- |
| Principle VII amended | A condition is a record of the account with a fixed shape | A value list belongs to templates, which possessions do not use |
| The translation of the existing key "New" changes | Keys are English strings; a condition needs its own word | Renaming the default "Brand new" changes the name the request gave (R3) |
