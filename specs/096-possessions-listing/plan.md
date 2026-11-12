# Implementation Plan: Possessions Listing

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-26 | **Spec**: [spec.md](spec.md)

## Summary

All possessions gains its listing for a vault that holds possessions: a location tree, a panel for
the selection with a split bar, "Featured here" and the remaining list. Each location has its own
route. One read of the vault's possessions and one of the account's locations feed every figure.
Money is formatted by a new helper, totals stay per currency. The sidebar counts possessions, and the
API and the `list-possessions` tool learn a `location` filter. Decisions in
[research.md](research.md), shapes in [contracts/screen-and-api.md](contracts/screen-and-api.md),
checks in [quickstart.md](quickstart.md). No data model file: nothing is stored.

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js, intl |
| Storage | None changed; two reads per screen (possessions of the vault, locations of the account) |
| Testing | PHPUnit: helper, view model and block, web and API controllers, MCP tool, sidebar view |
| Scale | Up to 500 possessions and 50 locations per screen, filtered in memory (R2) |
| Constraints | Nothing across vaults or accounts; every wording translated; WCAG 2.2 AA; no new JavaScript |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Read only screen: view model, block and helper; no action needed |
| II Simplicity | Pass. One block object, one helper; no component extracted (R9), no policy, no flag |
| III Tested | Pass. Each layer proves its own rules (quickstart) |
| IV Laravel | Pass. Route segment instead of query string (R1); bound model scoped by its trait |
| V Localized | Pass. Currency through intl and the user's separators (R3); counts through `trans_choice()` |
| VI Nothing changes | Pass. Nothing written; the API parameter is additive |
| VII Stable core | Pass. Locations gain no colour; it is derived (R5) |
| VIII Nothing disappears | Pass. Every status still listed |
| IX Accessible | Pass. Nav landmark with `aria-current`; bar has a text alternative and a text legend |
| X API | Pass. `location` filter on the endpoint and the tool (R8), documented |
| XI Short record | Pass. spec 118/120, plan, research, contracts and quickstart within caps |

Re-checked after design: unchanged.

## Project Structure

```text
routes/web.php                                         possessions.locations.show
app/Http/Controllers/App/Vaults/PossessionLocationController.php   new, show
app/ViewModels/Vaults/PossessionsViewModel.php         listing methods, optional location
app/ViewModels/Vaults/PossessionLocationTreeBlock.php  new
app/Helpers/PossessionValueFormatter.php               new
app/View/Components/Vault/Sidebar.php                  possessionCount
resources/views/components/vault/sidebar.blade.php     badge
resources/views/app/vaults/possessions/index.blade.php the listing replaces the placeholder
app/Http/Controllers/Api/Possessions/PossessionController.php      location filter
app/Mcp/Tools/ListPossessions.php                      location argument
resources/docs/api/27-possessions.php                  the parameter
docs/portal/en/9-templates-and-customisation/16-possessions.md; docs/FEATURES.md
lang/*.json                                            via `php artisan monica:localize`
tests/...                                              as quickstart lists
```

## Order of work

1. `PossessionValueFormatter` and its test.
2. `PossessionLocationTreeBlock`, the view model methods, their tests.
3. Route, `PossessionLocationController`, index view, controller tests; update the placeholder test.
4. Sidebar count and its view test.
5. API filter, tool argument, API reference, their tests.
6. Guide, FEATURES.md, translations, PHPStan, Pint, whole suite, a look at the screen in light, dark,
   `fr_FR` and at 200%.

## Spec amendments made while planning

- Cut from 190 to 118 lines (principle XI); Key Entities removed, nothing is stored.
- FR-015 added: principle X asks for the location filter on the API and the tool.

## Complexity Tracking

None.
