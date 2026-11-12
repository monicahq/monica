# Implementation Plan: Possession Maintenance

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-27 | **Spec**: [spec.md](spec.md)

## Summary

A `possession_maintenance_records` table naming two of the account's conditions, three actions, the
Maintenance section given its screen, API endpoints, assistant tools and their documentation. Overdue
is derived. Built the way 102 built insurance. Decisions in [research.md](research.md), shapes in
[data-model.md](data-model.md), the API in [contracts/api.md](contracts/api.md), checks in
[quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js, Alpine Ajax |
| Storage | One new table; nothing existing changes |
| Testing | PHPUnit: enum, model, actions, view models, web and API controllers, MCP tools |
| Scale | 100 records on one possession |
| Constraints | SQLite, MySQL, PostgreSQL; no new JavaScript file; WCAG 2.2 AA; every wording translated |

No unknown is left: the documents question was settled with the user on 2026-10-27 (left out).

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Create, update and destroy are actions; API, MCP and web share them |
| II Simplicity | Pass. Overdue is one model method (R5); no policy (each rule lives once), no flag |
| III Tested | Pass. Every layer in quickstart |
| IV Laravel | Pass. New migration only, `up()` only, `foreignIdFor()->constrained()` |
| V Localized | Pass. Money and dates in the user's formats; the condition count through `trans_choice()`; delete warns |
| VI Nothing changes | Pass. Nothing existing is rewritten; the sections entry only gains a URL and a count |
| VII Stable core | **Amendment**. Possession maintenance record becomes a fixed concept (R10) |
| VIII Nothing disappears | Pass. Deleting a record warns; deleting a used condition says what it empties (R4) |
| IX Accessible | Pass. Type and overdue chips carry words; truncated text whole for assistive technology |
| X API | Pass. Endpoints and tools in the contract |
| XI Short record | Pass. spec 120/120, the others within caps |
| Invariant: imprecision | Pass. Only the date performed is required, never invented |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md; spec/models/062, 065 (updated), 068-possession-maintenance-record-in-progress.md (new)
database/migrations/2026_10_27_000005_create_possession_maintenance_records_table.php
database/factories/PossessionMaintenanceRecordFactory.php
app/Enums/PossessionMaintenanceTypeEnum.php                 new
app/Enums/UserActionEnum.php                                three cases
app/Models/PossessionMaintenanceRecord.php                  new, ResolvesModelInVault
app/Models/Possession.php                                   maintenanceRecords(), overdueMaintenanceRecord()
app/Models/PossessionCondition.php                          docblock only
app/Actions/{Create,Update,Destroy}PossessionMaintenanceRecord.php   new
app/Helpers/PossessionMaintenanceRecordAttributes.php       new, the rules create and update share
app/Http/Controllers/App/Vaults/PossessionMaintenanceRecordController.php   new
app/Http/Controllers/Api/Possessions/PossessionMaintenanceRecordController.php   new
app/Http/Resources/PossessionMaintenanceRecordResource.php  new
app/Mcp/Tools/{List,Get,Create,Update,Delete}PossessionMaintenanceRecord(s).php   new; on MonicaServer
app/ViewModels/Vaults/PossessionMaintenanceViewModel.php    new; PossessionViewModel sections
app/ViewModels/Settings/Account/PossessionConditions/PossessionConditionsViewModel.php   usage count
resources/views/app/vaults/possessions/maintenance.blade.php, _maintenance-form.blade.php   new
resources/views/app/settings/account/possession-conditions/_row.blade.php   warning line
routes/web.php, routes/api.php; resources/docs/api/32-possession-maintenance-records.php
docs/portal/en/9-templates-and-customisation/16-possessions.md, 19-possession-maintenance.md (new); docs/FEATURES.md; lang/*.json
```

## Order of work

1. Constitution amendment, `spec/models` 062, 065 and 068.
2. Type enum, journal cases and their tests.
3. Migration, model and factory, `Possession` methods; model test.
4. The three actions and their tests.
5. View model, web routes, controller, `maintenance.blade.php`; sections URL and count.
6. Condition delete warning count.
7. API controller, resource, routes and reference; MCP tools; their tests.
8. Guide, FEATURES.md, translations, PHPStan, Pint, whole suite; the page by hand as quickstart says.

## Complexity Tracking

| Item | Why |
| --- | --- |
| `account_id` and `vault_id` copied onto each record | `ResolvesModelInVault` and `RouteProtectionTest` need them (R1) |
| Two foreign keys to the same table | before and after are both the account's conditions (R4) |
