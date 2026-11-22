# Implementation Plan: Every country, named in the reader's language

**Branch**: `claude/localized-countries-list-f1be08` | **Date**: 2026-11-21 | **Spec**: [spec.md](spec.md)

## Summary

The country of an address becomes a combo box offering the account's favourites (the Countries value
list) first, then the 256 countries of the standard, named and sorted in the reader's language and
filtered as somebody types. An address records the country's code beside its text when no label of the
account's own applies, so it reads in each reader's language and keeps its layout; every address saved
before keeps its text. In the settings, a favourite is picked from the same list, or typed as free
text. The API lists the countries and accepts a code. Decisions and reasons are in
[research.md](research.md), the API in [contracts/countries-api.md](contracts/countries-api.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major), Alpine.js

**Primary Dependencies**: `commerceguys/addressing` (installed, R1). No new dependency

**Storage**: no migration. An address value gains an optional `country_code` key in its JSON (R2); a
favourite picked from the list is a `value_list_items` row with a null `label` and a `country_code` (R4)

**Testing**: PHPUnit, `composer test:parallel` for the full suite (a shared component changes)

**Project Type**: web application with a JSON API and an MCP server

**Performance Goals**: the list is built once per request and locale; filtering is client side over
256 entries (R7)

**Constraints**: WCAG 2.2 AA for the combo box (R5); every new string through `monica:localize`

**Scale/Scope**: one helper extended, one action changed, two actions touched, one new component and
its script, one API endpoint and its MCP tool, two settings forms

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the logic | Pass. Reading the country is `SetFieldValues`; the duplicate refusal is `CreateValueListItem` and `UpdateValueListItem` |
| II. Simplicity | Pass. One helper (`CountryCodes`) gains the list and the names; no new model, no migration |
| III. Tested | Pass. See Tests |
| V. Localized | Pass. Country names come from CLDR in every shipped locale; new copy through `monica:localize` |
| VI. Nothing changes underfoot | Pass. Stored addresses are untouched; a written label is never translated (R2) |
| VIII. Nothing disappears silently | Pass. A stored country matching nothing is kept through `keep`; a deleted favourite leaves its addresses as they are |
| IX. Accessible | Pass. The combo box contract in R5, reviewed by the `accessibility-reviewer` agent |
| X. Through the API | Exception, recorded in Complexity Tracking and in the pull request. Listing and saving a country are in the API and MCP (saving through a new `update-contact-section` tool); adding a favourite is not |
| XI. Short record | Caps exceeded, see Complexity Tracking. `data-model.md` and `quickstart.md` omitted: the Storage line, R2 and R4 are the data model, and Validation below is the run guide |

Re-checked after design: unchanged.

## Changes

| File | Change |
| --- | --- |
| `app/Helpers/CountryCodes.php` | `all(string $locale)`: code to name, sorted with `LocaleCollation` (R1, R6). `nameOf(string $code, string $locale)`. `codeFor()` unchanged |
| `app/Models/ValueListItem.php` | the `label` accessor falls back to the country name when there is no label and no translation key (R4); docblock |
| `app/Actions/SetFieldValues.php` | `addressFor()` takes a two letter code, maps it to a favourite carrying it, writes `country_code` per R2; `keep` keeps the code |
| `app/Actions/SetFieldValue.php` | `address()` keeps only `ADDRESS_PARTS` today, which would drop the code: keep `country_code` when it is in `CountryCodes::known()` |
| `app/Helpers/FieldValueRules.php` | the country rule accepts a code of `CountryCodes::known()` (R3) |
| `app/Helpers/FieldValueFormatter.php` | `addressLines()`: the code is `country_code ?? codeFor(text)`, the last line is `nameOf(country_code)` when there is one |
| `app/Actions/CreateValueListItem.php`, `UpdateValueListItem.php` | `label` becomes nullable when a code is given on the Countries list; refuse a code an active item of the list already carries |
| `app/Http/Controllers/App/Settings/Account/ValueLists/ValueListItemController.php` | `label` required without `country_code`, `country_code` required without `label` |
| `resources/views/components/country-picker.blade.php` | new: the combo box of R5 |
| `resources/js/country-picker.js`, `resources/js/app.js` | new Alpine component `countryPicker`, registered |
| `resources/views/components/field-input.blade.php` | the address country becomes `x-country-picker`: chosen by `country_code` when there is one, otherwise the stored text as `keep` (R2) |
| `app/Helpers/ValueListSnapshot.php` | copies `country_code`; a snapshot item with neither label nor translation key reads as its country (R4) |
| `resources/views/app/settings/account/value-lists/_list.blade.php`, `_value.blade.php` | on the Countries list, the picker and an optional free text name replace the code input |
| `app/ViewModels/Settings/Account/ValueLists/ValueListsViewModel.php` | what the picker needs for the Countries list |
| `app/Http/Controllers/Api/CountryController.php`, `routes/api.php` | `index` per the contract |
| `app/Mcp/Tools/ListCountries.php`, `UpdateContactSection.php`, their registration | mirror `api.v1.countries` and `api.v1.contacts.sections.update` |
| `resources/views/app/docs/**` | the countries endpoint, and `country` and `country_code` on an address (`api-docs-writer` skill) |
| `docs/portal/en/9-templates-and-customisation/10-value-lists.md` | "Give a country its code" becomes picking a country (`writer` skill) |
| `lang/*.json` | `monica:localize`: "Favourites", "All countries", "No country matches", the result count, the duplicate refusal, the picker's label and placeholder |
| What's new | one entry through the `changelog` skill |

## Tests

| Test | Proves |
| --- | --- |
| `tests/Unit/Helpers/CountryCodesTest.php` | `all('fr_FR')` holds 256 countries in French alphabetical order ("Égypte" among the E); `nameOf('DE', 'fr_FR')` is "Allemagne" |
| `tests/Unit/Actions/SetFieldValuesTest.php` | a code outside the favourites saves `country` and `country_code`; `FR` saves as the supplied France (code in `key`); a favourite with a written label saves no code; `keep` keeps the code; a `country_code` sent by the caller is ignored; an unknown code is refused |
| `tests/Unit/Helpers/FieldValueFormatterTest.php` | an address with `country_code` reads its country in the current locale and takes its layout; one without reads its text, as before |
| `tests/Unit/Models/ValueListItemTest.php` | an item with no label and a code reads as the country in the current locale |
| `tests/Unit/Actions/CreateValueListItemTest.php`, `UpdateValueListItemTest.php` | a picked country is stored with a null label; a code already on the list is refused, including `FR` against the supplied France |
| `tests/Unit/Helpers/ValueListSnapshotTest.php` | a picked country keeps its code in the snapshot |
| `tests/Feature/Controllers/App/Settings/Account/ValueLists/ValueListItemControllerTest.php` | neither a label nor a code is refused on the form |
| `tests/Feature/Views/Components/CountryPickerTest.php` | the combobox contract: `role="combobox"`, `aria-controls`, the two labelled groups, the live region, the hidden input's `name` and `value`, the `noscript` select |
| `tests/Feature/Controllers/Api/CountryControllerTest.php` | the structure, the French names under `Accept-Language: fr`, favourites first with their id, another account's favourites left out |
| `tests/Feature/Controllers/Api/ContactSectionControllerTest.php` | an address saved with `"country": "JP"` is accepted (the stored code is the action's test) |

The MCP tool adds nothing beyond `answerThroughApi()`, so it gets no test file (`mcp.md`);
`McpCatalogueTest` covers its route.

## Validation

1. `composer test:parallel` green, `vendor/bin/pint --dirty`, PHPStan, `php artisan monica:localize --check` clean.
2. In English, edit an address, type "jap", pick Japan, save: the contact shows "Japan" on the last line in the Japanese layout.
3. Switch the interface to French: the same contact shows "Japon"; the dropdown lists "Allemagne" under A, favourites first.
4. Type "perou": "Pérou" is offered. Type "zzz": "No country matches" is announced.
5. In the Countries settings, pick Lithuania: it is added and shows as "Lituanie" in French. Pick it again: refused. Type "Kurdistan": added as written.
6. An address saved before the change reads exactly as before.
7. `curl -H 'Accept-Language: fr' /api/v1/countries` with a key: French names, favourites first.
8. The `accessibility-reviewer` agent finds nothing on the picker.

## Complexity Tracking

| Gap | Why | Simpler alternative rejected because |
| --- | --- | --- |
| Principle X, an exception: adding a favourite by picking a country is interface only | The API still has no endpoint creating or editing a value list item, for any list | Item write endpoints and their MCP tools are a feature of their own; the same gap was accepted for spec 134 on 2026-11-19. The pull request states the exception |
| Principle XI: spec.md (226 lines, cap 120), plan.md (110, cap 100), research.md (134, cap 80) | Four user stories with acceptance scenarios, and eight decisions each touching stored data | Cutting them would drop decisions implementation needs (R2 to R4). The pull request names the three caps |
