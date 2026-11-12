# Implementation Plan: Possession Insurance

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-27 | **Spec**: [spec.md](spec.md)

## Summary

A `possession_insurance_records` table, three actions, one section screen moved under Transactions,
an "Insured" fact on the Overview, API endpoints and assistant tools. The current record, the gap
against the declared value and the renewal are derived. Built the way 099 built transactions.
Decisions in [research.md](research.md), shapes in [data-model.md](data-model.md), the API in
[contracts/api.md](contracts/api.md), checks in [quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js, Alpine Ajax |
| Storage | One new table; nothing existing changes |
| Testing | PHPUnit: enum, model, actions, view models, web and API controllers, MCP tools |
| Scale | 100 records on one possession |
| Constraints | SQLite, MySQL, PostgreSQL; no new JavaScript file; WCAG 2.2 AA; every wording translated |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Create, update and destroy are actions; API, MCP and web share them |
| II Simplicity | Pass. Derived figures are one model method and the view model (R4); no policy, no flag |
| III Tested | Pass. Every layer in quickstart |
| IV Laravel | Pass. New migration only, `up()` only, `foreignIdFor()->constrained()` |
| V Localized | Pass. Money and dates in the user's formats; renewal through `trans_choice()` (R5); delete warns |
| VI Nothing changes | Pass. Nothing existing is rewritten; the sections list only reorders |
| VII Stable core | **Amendment**. Possession insurance record becomes a fixed concept (R10) |
| VIII Nothing disappears | Pass. Deleting warns that it is final; no documents to keep |
| IX Accessible | Pass. Status is a labelled radio group; colour never alone, badges carry words |
| X API | Pass. Endpoints and tools in the contract |
| XI Short record | Pass. spec 119/120, plan, research and the others within caps |
| Invariant: imprecision | Pass. Undated records allowed, never invented |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md; spec/models/062-possession-in-progress.md; spec/models/067-possession-insurance-record-in-progress.md
database/migrations/2026_10_27_000004_create_possession_insurance_records_table.php
database/factories/PossessionInsuranceRecordFactory.php
app/Enums/PossessionInsuranceStatusEnum.php                 new
app/Enums/UserActionEnum.php                                three cases
app/Models/PossessionInsuranceRecord.php                    new, ResolvesModelInVault
app/Models/Possession.php                                   insuranceRecords(), currentInsuranceRecord()
app/Actions/{Create,Update,Destroy}PossessionInsuranceRecord.php       new
app/Helpers/PossessionInsuranceRecordAttributes.php         new, the rules create and update share
app/Http/Controllers/App/Vaults/PossessionInsuranceRecordController.php  new
app/Http/Controllers/Api/Possessions/PossessionInsuranceRecordController.php  new
app/Http/Resources/PossessionInsuranceRecordResource.php    new
app/Mcp/Tools/{List,Get,Create,Update,Delete}PossessionInsuranceRecord(s).php  new; registered on the server
app/ViewModels/Vaults/PossessionInsuranceViewModel.php      new; PossessionViewModel sections and facts
resources/views/app/vaults/possessions/insurance.blade.php, _insurance-form.blade.php  new
routes/web.php, routes/api.php; resources/docs/api/31-possession-insurance-records.php
docs/portal/en/9-templates-and-customisation/16-possessions.md, 18-possession-insurance.md (new); docs/FEATURES.md; lang/*.json
```

## Order of work

1. Constitution amendment, `spec/models` 062 and 067.
2. Status enum, journal cases and their tests.
3. Migration, model and factory, `Possession` methods; model test.
4. The three actions and their tests.
5. View models, web routes, controller, `insurance.blade.php`; sections list order, URL and count; facts.
6. API controller, resource, routes and reference; MCP tools; their tests.
7. Guide, FEATURES.md, translations, PHPStan, Pint, whole suite; the page by hand as quickstart says.

## Complexity Tracking

| Item | Why |
| --- | --- |
| `account_id` and `vault_id` copied onto each record | `ResolvesModelInVault` and `RouteProtectionTest` need them (R1) |
