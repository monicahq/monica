# Implementation Plan: Possession Provenance

**Branch**: `2026-10-25-location-management` | **Date**: 2026-11-12 | **Spec**: [spec.md](spec.md)

## Summary

A `possession_provenance_events` table, three actions (create, update, destroy), the Provenance
section screen with its summary, filters and edit screen, API endpoints, assistant tools and
documentation. An event holds no money and may point at one transaction of the same possession.
Built the way 099 built transactions and 115 built valuations. Decisions in
[research.md](research.md), shapes in [data-model.md](data-model.md), the API in
[contracts/api.md](contracts/api.md), checks in [quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js, Alpine Ajax |
| Storage | One new table; nothing existing changes |
| Testing | PHPUnit: enums, actions, view models, web and API controllers, MCP tools |
| Scale | 100 events on one possession |
| Constraints | SQLite, MySQL, PostgreSQL; no new JavaScript file; WCAG 2.2 AA; every wording translated |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Create, update and destroy are actions; API, MCP and web share them |
| II Simplicity | Pass. One table, no pivot, no policy, no new flag; the order is a PHP sort (R5) |
| III Tested | Pass. Every layer in quickstart |
| IV Laravel | Pass. New migration only, `up()` only, `foreignIdFor()->constrained()` |
| V Localized | Pass. Dates in the user's format by precision; counts through `trans_choice()`; delete warns |
| VI Nothing changes | Pass. A section that was unavailable becomes available; no existing screen changes its figures |
| VII Stable core | **Amendment**. Possession provenance event becomes a fixed concept (R13) |
| VIII Nothing disappears | Pass. Deleting warns; deleting a transaction keeps the event (R7) |
| IX Accessible | Pass. Verified in words; filters are `aria-pressed` chips; the date fieldset keeps its label per precision |
| X API | Pass. Endpoints and tools in the contract |
| XI Short record | Pass. Every artifact within its cap |
| Invariant: imprecision | Pass. The date precision records how much of a date is known; Unknown is allowed |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md; spec/models/062-possession-in-progress.md; spec/models/075-possession-provenance-event-in-progress.md (new)
database/migrations/2026_11_12_000002_create_possession_provenance_events_table.php
database/factories/PossessionProvenanceEventFactory.php
app/Enums/PossessionProvenanceEventTypeEnum.php, PossessionProvenanceDatePrecisionEnum.php   new
app/Enums/UserActionEnum.php                                three cases
app/Models/PossessionProvenanceEvent.php                    new, ResolvesModelInVault
app/Models/Possession.php                                   provenanceEvents()
app/Actions/{Create,Update,Destroy}PossessionProvenanceEvent.php   new
app/Http/Controllers/App/Vaults/PossessionProvenanceEventController.php   new (index, create, edit, update, destroy)
app/Http/Controllers/Api/Possessions/PossessionProvenanceEventController.php   new
app/Http/Resources/PossessionProvenanceEventResource.php    new
app/Mcp/Tools/{List,Get,Create,Update,Delete}PossessionProvenanceEvent(s).php   new; registered on the server
app/ViewModels/Vaults/PossessionProvenanceViewModel.php     new
app/ViewModels/Vaults/PossessionViewModel.php               sections URL and count
resources/views/app/vaults/possessions/provenance.blade.php, _provenance-form.blade.php   new
routes/web.php, routes/api.php; resources/docs/api/37-possession-provenance.php
docs/portal/en/9-templates-and-customisation/21-possession-provenance.md (new), 16-possessions.md, 17-possession-transactions.md
docs/FEATURES.md; lang/*.json
```

## Order of work

1. Constitution amendment, `spec/models` 062 and 075.
2. Enums, journal cases and their tests.
3. Migration, model and factory, `Possession::provenanceEvents()`.
4. The three actions and their tests, and the transaction deletion case.
5. Provenance view model, web routes, controller, `provenance.blade.php` and the form; sections list.
6. API controller, resource, routes and reference; MCP tools; their tests.
7. Portal pages, FEATURES.md, translations, PHPStan, Pint, whole suite; the page by hand as quickstart says.

## Complexity Tracking

| Item | Why |
| --- | --- |
| `account_id` and `vault_id` copied onto each event | `ResolvesModelInVault` and `RouteProtectionTest` need them (R1) |
| A precision column beside the date | The design records imprecise dates; one date column cannot say "about 1975" (R3) |
