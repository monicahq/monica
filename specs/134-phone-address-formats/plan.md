# Implementation Plan: Phone numbers and addresses shown the way their country writes them

**Branch**: `claude/location-specific-phone-address-364373` | **Date**: 2026-11-19 | **Spec**: [spec.md](spec.md)

## Summary

Show an address in its country's postal layout, on several lines on a record or shared page and on
one line elsewhere, and a phone number carrying a country prefix grouped in its international form.
The country code comes from the stored country text, matched against the account's Countries list,
where an added country can now be given its code. Nothing stored changes. Decisions and their
reasons are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: `commerceguys/addressing`, `giggsey/libphonenumber-for-php-lite` (new, R1)

**Storage**: one nullable column, `value_list_items.country_code` (R3)

**Testing**: PHPUnit, `composer test:parallel` for the full suite

**Project Type**: web application with a JSON API and an MCP server

**Constraints**: every string through `monica:localize`; WCAG 2.2 AA on the touched screens

**Scale/Scope**: one helper, one formatter rework, one component branch, one column, one form input

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the logic | Pass. The code of an added country is written by `CreateValueListItem` and `UpdateValueListItem` |
| II. Simplicity | Pass. One resolver, the existing formatter, no new component |
| III. Tested | Pass. See Tests |
| V. Localized | Pass. Layout follows the data's country; the new strings go through `monica:localize` |
| VI. Nothing changes underfoot | Pass. Stored values are untouched; only their reading changes |
| VIII. Nothing disappears silently | Pass. Every filled part is shown, and an unmatched address keeps today's order |
| IX. Accessible | Pass. Lines in reading order, phone digits forced left to right |
| X. Through the API | Partial. `country_code` is read through the value lists endpoint and its MCP tool; see below |
| XI. Short record | Pass. `data-model.md`, `contracts/` and `quickstart.md` omitted: this plan carries them |

Re-checked after design: unchanged.

## Changes

| File | Change |
| --- | --- |
| `composer.json`, `composer.lock` | require the two packages (R1) |
| `database/migrations/…_add_country_code_to_value_list_items_table.php` | nullable `string('country_code', 2)` |
| `app/Models/ValueListItem.php` | `country_code` fillable and documented |
| `app/Helpers/CountryCodes.php` | new: the text to code map of one account (R2), scoped in `AppServiceProvider` (R4) |
| `app/Helpers/FieldValueFormatter.php` | `addressLines()`, `addressLine()` built on it, `phone()`; `labelled()` uses both (R5, R6) |
| `resources/views/components/field-value.blade.php` | address as one line per element, phone in `dir="ltr"` |
| `app/ViewModels/Shared/SharedContactViewModel.php`, `resources/views/shared/contact.blade.php` | an address as lines (FR-013) |
| `app/Models/Concerns/SearchesRecords.php` | a phone is indexed as stored, not as read (R7) |
| `app/ViewModels/Vaults/PossessionInsuranceViewModel.php` | `phone` through `FieldValueFormatter::phone()`; `tel` unchanged |
| `app/Actions/CreateValueListItem.php`, `UpdateValueListItem.php` | take `?string $countryCode`; refuse it outside the Countries list; log the change |
| `app/Http/Controllers/App/Settings/Account/ValueLists/ValueListItemController.php` | validate `country_code`: nullable, two letters, a code the package knows |
| `resources/views/app/settings/account/value-lists/_value.blade.php` (and the add form) | a Country code input, on the Countries list only |
| `app/Http/Controllers/Api/ValueListController.php` | each item carries `country_code` (`country_code ?? key` for a country, null otherwise) |
| `resources/views/app/docs/**` | the value lists endpoint documents `country_code` (`api-docs-writer` skill) |
| `lang/*.json` | `monica:localize`; new strings for the input, its hint and the refusal |
| What's new | one entry through the `changelog` skill |

## Tests

| Test | Proves |
| --- | --- |
| `tests/Unit/Helpers/FieldValueFormatterTest.php` | France puts the postcode before the city; the US joins city, region, postcode; no country and an unknown one keep today's order; a part the layout lacks is still shown; one line and several lines agree |
| same | `+33612345678` reads `+33 6 12 34 56 78`; `06 12 34 56 78` and `ask Pam` read as typed |
| `tests/Unit/Helpers/CountryCodesTest.php` | an added country with a code matches; a renamed supplied country still matches its old label and its French name; `FR` matches; another account's list is ignored |
| `tests/Unit/Models/ContactTest.php` | a contact is still found by `+33612345678` |
| `tests/Unit/Actions/CreateValueListItemTest.php`, `UpdateValueListItemTest.php` | the code is stored uppercase and logged; refused on a list other than Countries |
| `tests/Feature/Controllers/App/Settings/Account/ValueLists/ValueListItemControllerTest.php` | `XX1` is refused on the form |
| `tests/Feature/Controllers/Api/ValueListControllerTest.php` | a country item carries its code |
| `tests/Unit/ViewModels/Vaults/PossessionInsuranceViewModelTest.php` | the phone is grouped, the `tel` link is the stored number |

## Validation

1. `composer test:parallel` green.
2. A French and an American address on one contact each read on their own lines, in their own order.
3. Add Japan to the Countries list with `JP`: a Japanese address takes its layout.
4. `php artisan monica:localize --check`, Pint, PHPStan clean.

## Complexity Tracking

| Gap | Why | Simpler alternative rejected because |
| --- | --- | --- |
| Principle X: a country code is written only through the settings screen | The API has no endpoint that creates or edits a value list item today, for any field of it | Item write endpoints and MCP tools are a feature of their own. Accepted by the user on 2026-11-19; the pull request names it |
