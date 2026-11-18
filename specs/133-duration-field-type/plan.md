# Implementation Plan: Duration field type, and a quieter field type picker

**Branch**: `2026-11-18-public-changelog` | **Date**: 2026-11-18 | **Spec**: [spec.md](spec.md)

## Summary

Add `Duration` to `FieldTypeEnum`, stored in the existing `value_number` column as whole minutes,
validated 1 to 100,000, entered in one number box and shown as ":count minutes", exactly as life
events do. Remove the family sentence from the field type picker. Decisions and their reasons are
in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: Blade, Alpine.js, Tailwind CSS

**Storage**: `field_values.value_number`, already present; no migration (R1)

**Testing**: PHPUnit, `composer test:parallel` for the full suite

**Project Type**: web application with a JSON API and an MCP server

**Constraints**: every string through `monica:localize`; WCAG 2.2 AA on the touched screens

**Scale/Scope**: one enum case, six branch points, one Blade line, nine catalogue entries

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the logic | Pass. `SetFieldValue` already writes `value_number`; no new action |
| II. Simplicity | Pass. Reuses the life event wording and input; no new component |
| III. Tested | Pass. Rules, formatter, catalogue and one API write are tested (below) |
| VI. Nothing changes underfoot | Pass. A new type touches no published version |
| VII. One personalisation mechanism | Pass. A field type, stored as data in an existing column |
| VIII. Nothing disappears silently | Pass. Removing sentences removes copy, not data |
| IX. Accessible | Pass. The box is the existing `x-input` with its label; no new widget |
| X. Through the API | Pass. API and MCP take the type through `Rule::enum` and the values through `FieldValueRules` (R4) |
| XI. Short record | Pass. `data-model.md`, `contracts/` and `quickstart.md` omitted: this plan carries them |

Re-checked after design: unchanged.

## Changes

| File | Change |
| --- | --- |
| `app/Enums/FieldTypeEnum.php` | `case Duration = 'duration'`; `valueColumn()` returns `value_number`; listed in `hasAnInput()` |
| `app/Helpers/FieldValueRules.php` | `Duration => ['integer', 'min:1', 'max:100000']` (R3) |
| `app/Helpers/FieldValueFormatter.php` | `Duration => trans_choice(':count minute|:count minutes', (int) ...)` (R2) |
| `resources/views/components/field-input.blade.php` | `type="number"`, `min="1"`, value `(int)` the stored minutes (R5) |
| `app/Helpers/ContactListCatalogue.php` | add `Duration` to the types a column may show |
| `app/ViewModels/Settings/Account/Templates/FieldCatalogueViewModel.php` | drop every `blurb`, and from the `@return` shapes (R6) |
| `resources/views/app/settings/account/field-type.blade.php` | drop the `<p>` holding the blurb |
| `lang/*.json` | `monica:localize` removes the nine sentences; no string is added |
| `resources/views/app/docs/**` or help centre | name Duration where field types are listed, if anywhere (none found so far) |
| What's new | one entry through the `changelog` skill |

The `FieldCatalogueViewModel` entry for `duration` stays as it is: its description, example
("How long the walk usually takes") and preview ("45 minutes") already fit.

## Tests

| Test | Proves |
| --- | --- |
| `tests/Unit/Helpers/FieldValueRulesTest.php` | 90 passes; 0, 2.5, "abc", 100001 fail |
| `tests/Unit/Helpers/FieldValueFormatterTest.php` | 1 reads "1 minute", 90 reads "90 minutes", empty reads null |
| `tests/Unit/Actions/SetFieldValueTest.php` | 90 lands in `value_number` |
| `FieldCatalogueViewModelTest` | already proves Duration turns available once the enum ships it; unchanged |
| `tests/Feature/Controllers/Api/ContactSectionControllerTest.php` | one write of 90 through the API is stored |

The export and import read `valueColumn()` generically; their existing tests cover the path.

## Validation

1. `composer test:parallel` green.
2. Open a template, add a field: Duration is selectable, no family shows a sentence.
3. Fill 90 on a contact: it reads "90 minutes"; 0 and 100001 are refused with a message.
4. `php artisan monica:localize --check`, Pint, PHPStan clean.
