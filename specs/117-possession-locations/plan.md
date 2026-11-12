# Implementation Plan: Possession Locations

**Branch**: `2026-10-25-location-management` | **Date**: 2026-11-12 | **Spec**: [spec.md](spec.md)

## Summary

A `possession_location_stays` table, three actions (move, update, destroy), the Locations section
screen with its current block, list of stays and correction screen, API endpoints, assistant tools and
documentation. `possessions.current_location_id` stays and always equals the open stay; the add and
edit forms go through the same rules; a location any stay refers to cannot be deleted. Built the way
116 built provenance. Decisions in [research.md](research.md), shapes in
[data-model.md](data-model.md), the API in [contracts/api.md](contracts/api.md), checks in
[quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js, Alpine Ajax |
| Storage | One new table, backfilled from `current_location_id` (R5); no column changes |
| Testing | PHPUnit: actions, policy, model, view models, web and API controllers, MCP tools |
| Scale | 100 stays on one possession |
| Constraints | SQLite, MySQL, PostgreSQL; no new JavaScript file; WCAG 2.2 AA; every wording translated |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Move, update and destroy are actions; API, MCP, web and the possession forms share them |
| II Simplicity | Pass. One table; the current location stays a column rather than a rewrite of four readers (R2) |
| III Tested | Pass. Every layer in quickstart |
| IV Laravel | Pass. New migration only, `up()` only, `foreignIdFor()->constrained()` |
| V Localized | Pass. Dates in the user's format; lengths and counts through `trans_choice()`; delete warns |
| VI Nothing changes | Pass. Existing possessions keep their location, now as one open stay (R5) |
| VII Stable core | **Amendment**. Possession location stay becomes a fixed concept (R13) |
| VIII Nothing disappears | Pass. A location with stays is refused, not cascaded (R7); deleting a stay warns |
| IX Accessible | Pass. "Here now" in words; the warning and help follow the provenance section |
| X API | Pass. Endpoints and tools in the contract |
| XI Short record | Pass. Every artifact within its cap |
| Invariant: imprecision | Pass. An arrival may be unknown; nothing invents one |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md; spec/models/062-possession-in-progress.md; spec/models/076-possession-location-stay-in-progress.md (new)
database/migrations/2026_11_12_000003_create_possession_location_stays_table.php   new, with backfill
database/factories/PossessionLocationStayFactory.php        new
app/Enums/UserActionEnum.php                                three cases
app/Models/PossessionLocationStay.php                       new, ResolvesModelInVault
app/Models/Possession.php, Location.php                     locationStays(), openLocationStay(); stays()
app/Policies/LocationPolicy.php                             delete() counts stays
app/Actions/MovePossession.php, UpdatePossessionLocationStay.php, DestroyPossessionLocationStay.php   new
app/Actions/CreatePossession.php, UpdatePossession.php      write stays (R4)
app/Http/Controllers/App/Vaults/PossessionLocationStayController.php   new (index, create, edit, update, destroy)
app/Http/Controllers/Api/Possessions/PossessionLocationStayController.php   new
app/Http/Resources/PossessionLocationStayResource.php       new
app/Mcp/Tools/{List,Get,Update,Delete}PossessionLocationStay(s).php, MovePossession.php   new; registered
app/ViewModels/Vaults/PossessionLocationsViewModel.php      new
app/ViewModels/Vaults/PossessionViewModel.php               sections URL and count
resources/views/app/vaults/possessions/locations.blade.php, _location-stay-form.blade.php   new
routes/web.php, routes/api.php; resources/docs/api/38-possession-location-stays.php
docs/portal/en/9-templates-and-customisation/22-possession-locations.md (new), 16-possessions.md
docs/FEATURES.md; lang/*.json
```

## Order of work

1. Constitution amendment, `spec/models` 062 and 076.
2. Migration with backfill, model, factory, relations; journal cases.
3. `LocationPolicy` and its test.
4. The three actions and their tests; then `CreatePossession` and `UpdatePossession`.
5. Locations view model, web routes, controller, `locations.blade.php` and the form; sections list.
6. API controller, resource, routes and reference; MCP tools; their tests.
7. Portal pages, FEATURES.md, translations, PHPStan, Pint, whole suite; the page by hand.

## Complexity Tracking

| Item | Why |
| --- | --- |
| `current_location_id` kept beside the open stay | Four readers depend on it (R2); the actions keep the two equal in one transaction |
| `account_id` and `vault_id` copied onto each stay | `ResolvesModelInVault` and `RouteProtectionTest` need them (R1) |
