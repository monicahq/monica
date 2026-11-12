# Implementation Plan: Possession Valuations

**Branch**: `2026-10-25-location-management` | **Date**: 2026-11-12 | **Spec**: [spec.md](spec.md)

## Summary

A `possession_valuations` table and its document pivot, four actions (create, destroy, attach,
detach), the Valuations section screen, the current value read by the Overview and Insurance with the
declared value as fallback, API endpoints, assistant tools and documentation. A valuation is never
edited. Built the way 099 built transactions and 102 built insurance. Decisions in
[research.md](research.md), shapes in [data-model.md](data-model.md), the API in
[contracts/api.md](contracts/api.md), checks in [quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js, Alpine Ajax |
| Storage | Two new tables; nothing existing changes |
| Testing | PHPUnit: enums, model, actions, view models, web and API controllers, MCP tools |
| Scale | 100 valuations on one possession |
| Constraints | SQLite, MySQL, PostgreSQL; no new JavaScript file; WCAG 2.2 AA; every wording translated |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Create, destroy, attach and detach are actions; API, MCP and web share them |
| II Simplicity | Pass. One model method for the current value (R4); no policy, no new flag |
| III Tested | Pass. Every layer in quickstart |
| IV Laravel | Pass. New migrations only, `up()` only, `foreignIdFor()->constrained()` |
| V Localized | Pass. Money and dates in the user's formats; changes as sentences, no count comparison; delete warns |
| VI Nothing changes | Pass. The Overview and Insurance show a new figure only once somebody records a valuation; without one they read as today |
| VII Stable core | **Amendment**. Possession valuation becomes a fixed concept (R13) |
| VIII Nothing disappears | Pass. Deleting warns; its documents are detached and wait out the grace period (R7) |
| IX Accessible | Pass. The under insured note carries words and an icon, not colour alone; preview line is `aria-live="polite"` |
| X API | Pass. Endpoints and tools in the contract; no update anywhere, by design (R6) |
| XI Short record | Pass. spec 117/120, plan, research and the others within caps |
| Invariant: imprecision | Pass. Confidence records how sure the figure is; Unknown is the default |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md; spec/models/062-possession-in-progress.md; spec/models/074-possession-valuation-in-progress.md (new)
database/migrations/2026_11_12_000000_create_possession_valuations_table.php
database/migrations/2026_11_12_000001_create_possession_valuation_file_table.php
database/factories/PossessionValuationFactory.php
app/Enums/PossessionValuationTypeEnum.php, PossessionValuationConfidenceEnum.php   new
app/Enums/UserActionEnum.php                                four cases
app/Models/PossessionValuation.php                          new, ResolvesModelInVault
app/Models/Possession.php                                   valuations(), currentValuation(), currentValue()
app/Actions/{Create,Destroy}PossessionValuation.php         new
app/Actions/{Attach,Detach}PossessionValuationDocument.php  new
app/Http/Controllers/App/Vaults/PossessionValuationController.php      new (index, create, destroy)
app/Http/Controllers/Api/Possessions/PossessionValuation{,Document}Controller.php  new
app/Http/Resources/PossessionValuationResource.php          new
app/Mcp/Tools/{List,Get,Create,Delete}PossessionValuation(s).php  new; registered on the server
app/ViewModels/Vaults/PossessionValuationViewModel.php      new
app/ViewModels/Vaults/PossessionViewModel.php               sections URL and count; value panel
app/ViewModels/Vaults/PossessionInsuranceViewModel.php      reads currentValue() (R5)
resources/views/app/vaults/possessions/valuations.blade.php, _valuation-form.blade.php  new
resources/views/app/vaults/possessions/show.blade.php, _header.blade.php   value panel; menu item
routes/web.php, routes/api.php; resources/docs/api/36-possession-valuations.php
docs/portal/en/9-templates-and-customisation/20-possession-valuations.md (new), 16-possessions.md, 18-possession-insurance.md
docs/FEATURES.md; lang/*.json
```

## Order of work

1. Constitution amendment, `spec/models` 062 and 074.
2. Enums, journal cases and their tests.
3. Migrations, model and factory, `Possession` methods; model test.
4. The four actions and their tests.
5. Valuation view model, web routes, controller, `valuations.blade.php` and the form; sections list.
6. Overview value panel, header menu item, Insurance view model and its wording.
7. API controllers, resource, routes and reference; MCP tools; their tests.
8. Portal pages, FEATURES.md, translations, PHPStan, Pint, whole suite; the page by hand as quickstart says.

## Complexity Tracking

| Item | Why |
| --- | --- |
| `account_id` and `vault_id` copied onto each valuation | `ResolvesModelInVault` and `RouteProtectionTest` need them (R1) |
| Two Insurance wordings for the comparison | Keeps shipped copy for possessions without a valuation (R5) |
