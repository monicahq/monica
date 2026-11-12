# Implementation Plan: Edit a Possession

**Branch**: `2026-10-25-location-management` | **Date**: 2026-11-11 | **Spec**: [spec.md](spec.md)

## Summary

The main part of Edit opens the add form of 094, filled with the possession. Saving runs
`UpdatePossession`, which now also writes the field values in the same transaction. No table, no API
change. Decisions in [research.md](research.md), checks in [quickstart.md](quickstart.md).
`data-model.md` and `contracts/` are omitted: nothing in the schema or the API changes.

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js |
| Storage | Nothing new |
| Testing | PHPUnit: helper, actions, form view model, web controller |
| Scale | One possession, its type's fields (tens) |
| Constraints | No new JavaScript file; WCAG 2.2 AA; every wording translated |

No unknown is left.

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. The save is `UpdatePossession`; the controller validates and delegates |
| II Simplicity | Pass. One view model in two modes (R4); one helper for a rule copied twice (R2); no policy, no flag |
| III Tested | Pass. Every layer in quickstart |
| IV Laravel | Pass. One named route per line, inline validation |
| V Localized | Pass. New copy through `__()`, `monica:localize` |
| VI Nothing changes | Pass. Unshown attributes are passed back unchanged (R3) |
| VII Stable core | Pass. No new concept |
| VIII Nothing disappears | Pass. An emptied field is the person's explicit choice on the form |
| IX Accessible | Pass. Same form and components as 094; the type shown as text with its name |
| X API | Pass. The API already updates a possession and its values (091) |
| XI Short record | Pass. All within caps |

Re-checked after design: unchanged.

## Project Structure

```text
app/Helpers/PossessionFieldValues.php                         new (R2)
app/Actions/UpdatePossession.php                              values, one transaction (R1)
app/Actions/CreatePossession.php, UpdatePossessionFieldValues.php   use the helper
app/Http/Controllers/App/Vaults/PossessionController.php      edit(), update() (R3, R6)
app/ViewModels/Vaults/PossessionFormViewModel.php             ?Possession, isEditing (R4)
app/ViewModels/Vaults/PossessionViewModel.php                 editUrl()
resources/views/app/vaults/possessions/form.blade.php         title, trail, method, fixed type, foot bar (R5)
resources/views/app/vaults/possessions/_header.blade.php      Edit links to the form
routes/web.php                                                two routes
docs/portal/en/9-templates-and-customisation/16-possessions.md; docs/FEATURES.md; lang/*.json
tests/Unit/Helpers/PossessionFieldValuesTest.php              new
tests/Unit/Actions/UpdatePossessionTest.php                   values cases
tests/Unit/ViewModels/Vaults/PossessionFormViewModelTest.php  editing cases
tests/Feature/Controllers/App/Vaults/PossessionControllerTest.php   edit, update
```

## Order of work

1. `PossessionFieldValues` and its test; move `CreatePossession` and `UpdatePossessionFieldValues`
   onto it, their tests still green.
2. `UpdatePossession` takes values; tests for written, cleared, refused-writes-nothing.
3. Form view model editing mode and its tests.
4. Routes, `edit()` and `update()`, the form view, the header link; controller tests.
5. Guide, FEATURES.md, `monica:localize`, PHPStan, Pint, whole suite; the screen by hand as quickstart says.

## Complexity Tracking

None.
