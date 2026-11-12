# Implementation Plan: Possession Detail

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-26 | **Spec**: [spec.md](spec.md)

## Summary

Each possession gets a page: header, a list of ten sections with only Overview available, and the
Overview itself. The Edit split button's main part is unavailable; its menu features, unfeatures or
deletes. One view model reads the possession once. Featuring gets a one column action; deleting
reuses `DestroyPossession`. Listing rows become links. Decisions in [research.md](research.md),
checks in [quickstart.md](quickstart.md). No data model and no contract file: nothing is stored, and
the API already reads, updates and deletes a possession.

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js |
| Storage | None changed. One possession read with its relations, one location tree read (R6) |
| Testing | PHPUnit: action, view model, formatter, web controllers, split button component |
| Scale | Up to 50 field values on one page |
| Constraints | Nothing across vaults or accounts; every wording translated; WCAG 2.2 AA; no new JavaScript file |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. `UpdatePossessionFeatured` new (R2); deleting through `DestroyPossession` |
| II Simplicity | Pass. No new helper (R4), no policy, no flag, no component extracted (one file) |
| III Tested | Pass. Each layer proves its own rules (quickstart) |
| IV Laravel | Pass. Bound model scoped by `ResolvesModelInVault` (R1); one controller per extra action |
| V Localized | Pass. Dates, numbers and money in the user's formats; ratings through `trans_choice()` |
| VI Nothing changes | Pass. Nothing changes until somebody confirms it |
| VIII Nothing disappears | Pass. Featuring touches one column (R2); deleting warns that it is final |
| IX Accessible | Pass. Nav landmark with `aria-current`; unavailable items keep their reason (R5); menu by keyboard |
| X API | Pass. Featuring and deleting already in the API and the assistant |
| XI Short record | Pass. spec 114/120, plan, research and quickstart within caps |

Re-checked after design: unchanged.

## Project Structure

```text
routes/web.php                                              possessions.show, .destroy, .featured.update
app/Actions/UpdatePossessionFeatured.php                    new
app/Http/Controllers/App/Vaults/PossessionController.php    show, destroy
app/Http/Controllers/App/Vaults/PossessionFeaturedController.php  new, update
app/ViewModels/Vaults/PossessionViewModel.php               new
app/ViewModels/Vaults/PossessionsViewModel.php              row gains its url
app/Helpers/FieldValueFormatter.php                         formatPossessionValue (R4)
resources/views/components/button/split.blade.php          unavailable prop (R5)
resources/views/app/vaults/possessions/show.blade.php       new
resources/views/app/vaults/possessions/index.blade.php      rows link to the page
docs/portal/en/9-templates-and-customisation/16-possessions.md; docs/FEATURES.md
lang/*.json                                                 via `php artisan monica:localize`
tests/...                                                   as quickstart lists
```

## Order of work

1. `UpdatePossessionFeatured` and its test.
2. `FieldValueFormatter::formatPossessionValue` and its tests, one per kind.
3. `PossessionViewModel` and its test: header, place, groups, facts, value, edge cases of the spec.
4. `x-button.split` `unavailable` prop and its component test; whole suite, since it is shared.
5. Routes, controllers, `show.blade.php`, listing links, controller tests; add the three routes to
   `RouteProtectionTest` expectations if it lists them.
6. Guide, FEATURES.md, translations, PHPStan, Pint, whole suite, a look at the page in light, dark,
   `fr_FR`, at 375 px and at 200%.

## Complexity Tracking

None.
