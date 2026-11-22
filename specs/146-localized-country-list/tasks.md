# Tasks: Every country, named in the reader's language

**Tests**: included, since principle III makes them a delivery condition. Work stays on `claude/localized-countries-list-f1be08`. Decisions referenced as R1 to R8 are in [research.md](research.md), the API in [contracts/countries-api.md](contracts/countries-api.md).

## Phase 1: Setup

- [X] T001 Confirm in `php artisan tinker` what R1 relies on: `new CountryRepository()->getList('fr_FR')` holds 256 codes including XK, names are French, `nb_NO` and `pt_BR` resolve; record any difference in specs/146-localized-country-list/research.md before going on

## Phase 2: Foundational (blocks every story)

- [X] T002 Add to app/Helpers/CountryCodes.php: static `all(string $locale): array<string, string>` (code to name from `CountryRepository::getList($locale)`, sorted with `LocaleCollation::getCollator($locale)`, memoised per locale with `once()` or a static array like `TimezoneCatalogue`), and static `nameOf(string $code, string $locale): ?string` (null for an unknown code) (R1, R6, FR-002, FR-003)
- [X] T003 Add `countryCode(): ?string` to app/Models/ValueListItem.php returning `mb_strtoupper(country_code ?? key)` when it is in `CountryCodes::known()`, null otherwise, with its docblock; use it in `CountryCodes::accountCodes()` instead of the inline expression (R3)
- [X] T004 [P] Test `all('fr_FR')` holds 256 entries with "Allemagne" for DE and "Égypte" sorted among the E, and `nameOf('DE', 'fr_FR')` is "Allemagne", `nameOf('QQ', 'en')` null, in tests/Unit/Helpers/CountryCodesTest.php
- [X] T005 [P] Test `countryCode()` is `FR` for the supplied France (code in `key`), `JP` for an item with `country_code` `jp`, null for a typed item with no code, in tests/Unit/Models/ValueListItemTest.php

## Phase 3: User Story 1, pick any country without leaving the address (P1, MVP)

Independent test: on an account holding the ten supplied countries, edit an address, type "jap", pick Japan, save, read Japan on the contact.

- [X] T006 [US1] In `addressFor()` of app/Actions/SetFieldValues.php: accept a value matching `/^[A-Za-z]{2}$/` in `CountryCodes::known()`; when an active item of the account's Countries list has that `countryCode()`, continue as if its id was given (FR-006); otherwise save `country` as `CountryCodes::nameOf($code, app()->getLocale())` and `country_code` as the code. For a favourite, save `country_code` as `$item->countryCode()` only when `$item->getRawOriginal('label') === null`, else null. Always set `country_code` explicitly so a caller's value is overwritten; `keep` keeps both the stored text and the stored `country_code` (R2, R3)
- [X] T007 [US1] In `address()` of app/Actions/SetFieldValue.php keep a `country_code` key when its upper cased value is in `CountryCodes::known()`, drop it otherwise (R2)
- [X] T008 [P] [US1] Accept a two letter code in `CountryCodes::known()` (any case) in the country rule of `forAddressParts()` in app/Helpers/FieldValueRules.php, beside `keep` and an active favourite id (R3)
- [X] T009 [US1] Create resources/js/country-picker.js exporting the Alpine component `countryPicker({ favourites, countries, value, kept })`: `query`, `open`, `active`, `value`; `results` filtering both groups by a substring match on the name folded with `toLowerCase()`, `normalize('NFD')` and `/\p{Diacritic}/gu`; arrow keys move `active`, Home and End jump, Enter picks, Escape closes and restores the chosen name, Tab closes; a `count` for the live region. Register it in resources/js/app.js as `Alpine.data('countryPicker', countryPicker)` beside `timezonePicker` (R5)
- [X] T010 [US1] Create resources/views/components/country-picker.blade.php following the props and markup conventions of resources/views/components/currency-picker.blade.php: first line comment and `@var` block per `code-style.md`; props `id`, `name`, `label`, `favourites` (list of `{value, label}`), `countries` (list of `{value, label}`), `value`, `kept`, `error`. An `input` with `role="combobox"`, `aria-autocomplete="list"`, `aria-expanded`, `aria-controls`, `aria-activedescendant`; a `role="listbox"` popup holding two `role="group"` blocks with `aria-labelledby` naming "Favourites" and "All countries", each option `role="option"` with `aria-selected`; a polite `aria-live` region with the count and "No country matches" when empty; a hidden input carrying `name` rendered inside `<template x-if>`; a `<noscript>` native select with two `<optgroup>` and the `keep` option (R5, FR-004)
- [X] T011 [US1] Replace the country `x-select` in resources/views/components/field-input.blade.php with `x-country-picker`: favourites are the active items of the Countries list by position (`value` = id, `label` = accessor), countries are `CountryCodes::all(app()->getLocale())` (`value` = code); the chosen value is the active favourite whose `countryCode()` matches a stored `country_code` and whose raw label is null, else the stored `country_code`, else `keep` with the stored text when there is one (R2, FR-001, FR-008)
- [X] T012 [P] [US1] Test in tests/Unit/Actions/SetFieldValuesTest.php: `JP` saves `country` "Japan" and `country_code` `JP`; `fr` saves as the supplied France with `country_code` `FR`; a favourite with a written label saves its label and a null code; `keep` keeps `country_code`; a `country_code` given beside the country is overwritten; `QQ` is refused
- [X] T013 [P] [US1] Test the component contract in tests/Feature/Views/Components/CountryPickerTest.php, rendered with `$this->blade()`: `role="combobox"` with `aria-controls` naming the listbox id, both groups labelled "Favourites" and "All countries", the `aria-live` region, `x-data="countryPicker(`, and the `noscript` select carrying the field `name` and the `keep` option when `kept` is given (no class strings, per `testing.md`)

## Phase 4: User Story 2, country names in the reader's language (P1)

Independent test: save an address with Japan from the full list, switch the interface to French, read "Japon" on the contact; an address saved before reads as before.

- [X] T014 [US2] In `addressLines()` of app/Helpers/FieldValueFormatter.php take the layout code from `country_code` when present, else `codeFor()` as today; make the last line `CountryCodes::nameOf($country_code, app()->getLocale())` when there is a code, the stored text otherwise (R2, FR-005)
- [X] T015 [US2] Make the `label` accessor of app/Models/ValueListItem.php fall back, when the raw label and the translation key are both null, to `CountryCodes::nameOf($this->countryCode(), app()->getLocale()) ?? ''`; update its docblock (R4, FR-007)
- [X] T016 [P] [US2] Copy `country_code` into each item of app/Helpers/ValueListSnapshot.php, and find the readers of a snapshot item's `label` (`grep -rn "label_translation_key" app`) to make them fall back to `CountryCodes::nameOf()` when both are null (R4)
- [X] T017 [P] [US2] Test in tests/Unit/Helpers/FieldValueFormatterTest.php: an address with `country_code` `JP` reads "Japon" on its last line under `fr_FR` and takes the Japanese layout; an address with only the text "Japan" reads "Japan" under `fr_FR`, as before
- [X] T018 [P] [US2] Test in tests/Unit/Models/ValueListItemTest.php that an item with no label, no translation key and `country_code` `LT` reads "Lituanie" under `fr_FR`, and that an item with a written label reads it as written
- [X] T019 [P] [US2] Test in tests/Unit/Helpers/ValueListSnapshotTest.php that a picked country keeps its `country_code` in the snapshot

## Phase 5: User Story 3, choose favourites from the same list (P2)

Independent test: in the Countries settings pick Lithuania, find it among the address favourites; pick it again and be refused; type "Kurdistan" and find it as written.

- [X] T020 [US3] Make `label` nullable in app/Actions/CreateValueListItem.php and app/Actions/UpdateValueListItem.php: allowed null only with a country code on the Countries list, stored as null so the accessor names the country; refuse with an `InvalidArgumentException` when neither is given. When both are given, both are stored: a written label beside a country, as under spec 134, so renaming a supplied country keeps working (FR-007); a code alone stores a null label; a typed name with no code stores the label and a null `country_code`. Refuse a code that another active item of the list already carries through `countryCode()`, with a translated message ("This country is already on the list."); keep the existing log entries, logging the accessor label (R4, FR-009, FR-010)
- [X] T021 [US3] In app/Http/Controllers/App/Settings/Account/ValueLists/ValueListItemController.php validate `label` as `required_without:country_code` and `country_code` as `required_without:label`, keep the existing upper casing and `Rule::in(CountryCodes::known())`; catch the duplicate refusal and return it on the form's error bag under `country_code`
- [X] T022 [US3] Expose what the picker needs for the Countries list from app/ViewModels/Settings/Account/ValueLists/ValueListsViewModel.php: the full list for the current locale and, per item, its `countryCode()` and whether its label was written
- [X] T023 [P] [US3] Test in tests/Unit/ViewModels/Settings/Account/ValueLists/ValueListsViewModelTest.php that «A6» exposes the full list in the current locale ("Lituanie" under `fr_FR`) and each item's `countryCode()` and whether its label was written (principle III)
- [X] T024 [US3] On the Countries list in resources/views/app/settings/account/value-lists/_list.blade.php and _value.blade.php, replace the "Country code" text input with `x-country-picker` (name `country_code`, no favourites group) and make the label input optional with the hint "Or type a name for a place not in the list"; picking a country clears the label on save. Keep the other lists unchanged
- [X] T025 [P] [US3] Test in tests/Unit/Actions/CreateValueListItemTest.php and tests/Unit/Actions/UpdateValueListItemTest.php: a picked `LT` is stored with a null label; `FR` is refused against the supplied France; `JP` is refused when an active item carries it; neither label nor code is refused; a code on another list is still refused; updating an item to the code `LV` with no label stores a null label and `LV`, and with a label keeps that label
- [X] T026 [P] [US3] Test in tests/Feature/Controllers/App/Settings/Account/ValueLists/ValueListItemControllerTest.php that a form with neither a label nor a country code is refused, and that a duplicate country comes back as an error on `country_code`

## Phase 6: User Story 4, the same capability through the API and an assistant (P2)

Independent test: `GET /api/v1/countries` with `Accept-Language: fr` lists French names, favourites first; saving an address with `"country": "JP"` is accepted.

- [X] T027 [US4] Create app/Http/Controllers/Api/CountryController.php with `index()` returning `data` per contracts/countries-api.md: the account's active Countries items carrying a `countryCode()`, by position, then every entry of `CountryCodes::all(app()->getLocale())`, each `{code, name, favourite_id}`; declare `Route::match(['GET', 'QUERY'], 'countries', [CountryController::class, 'index'])->name('countries')` next to `value-lists` in routes/api.php, inside the `auth:sanctum` group with no scope check (route `api.v1.countries`, R8, FR-011)
- [X] T028 [US4] Create app/Mcp/Tools/ListCountries.php mirroring `api.v1.countries` through `MirrorsAnApiCapability::answerThroughApi()`, read only annotations like app/Mcp/Tools/ListValueLists.php, and register it where `ListValueLists` is registered (FR-012, `mcp.md`)
- [X] T029 [US4] Create app/Mcp/Tools/UpdateContactSection.php mirroring `api.v1.contacts.sections.update` through `answerThroughApi()`, taking `vault`, `contact`, `section` and `fields` as the endpoint does, with the write annotations of app/Mcp/Tools/UpdateContactName.php, and register it in app/Mcp/Servers/MonicaServer.php; describe the two letter `country` of an address in its schema (FR-012, contract)
- [X] T030 [P] [US4] Test in tests/Feature/Controllers/Api/CountryControllerTest.php: the `$jsonStructure`, "Allemagne" under `Accept-Language: fr`, favourites first with their `favourite_id`, another account's favourites left out
- [X] T031 [P] [US4] Test in tests/Feature/Controllers/Api/ContactSectionControllerTest.php that an address sent with `"country": "JP"` is accepted and `"country": "QQ"` answers 422 on its `country` part
- [X] T032 [US4] Document `GET /api/v1/countries` and the two letter `country` of an address in the API docs portal under resources/views/app/docs, with the `api-docs-writer` skill

## Phase 7: Polish and cross-cutting

- [X] T033 Run `php artisan monica:localize` and translate what it reports with subagents on model sonnet, per the `translations` skill: "Favourites", "All countries", "No country matches", the result count as a `trans_choice()` phrase with `:count` in every segment, the picker label and placeholder, the duplicate refusal, the settings hint; then `php artisan monica:localize --check`
- [X] T034 [P] Rewrite "Give a country its code" in docs/portal/en/9-templates-and-customisation/10-value-lists.md as picking a country, or typing a name for a place not in the list, with the `writer` skill
- [X] T035 [P] Add the What's new entry with the `changelog` skill
- [X] T036 Run the `accessibility-reviewer` and `rules-reviewer` agents on the change and fix what they confirm
- [X] T037 Run `vendor/bin/pint --dirty --format agent`, PHPStan and `PAO_DISABLE=1 php artisan test --parallel` (a shared component and `SetFieldValues` change, so the whole suite); rewrite any feature test asserting the old country `x-select` markup to assert behaviour instead
- [ ] T038 Walk through Validation in specs/146-localized-country-list/plan.md in the browser, in English and French, then run the `definition-of-done` skill; the pull request states the principle X exception and the three exceeded caps from plan.md Complexity Tracking

## Dependencies

- Phase 2 blocks every story. T001 blocks T002.
- US1 (T006 to T013) is the MVP. US2 builds on T006 (a stored `country_code`) and T011 (the picker), so it follows US1.
- US3 needs T010 (the component) and T015 (a null label reads as its country). US4 needs T006 to T008 for saving and T002 for listing; it can run beside US3.
- Within a story, an action before its view, a component before its call site, a test beside or after its code.

## Parallel examples

- Phase 2: T004 and T005 together, after T002 and T003.
- US1: T008 beside T006 and T007; T012 and T013 together once T006 and T010 exist.
- US2: T016, T017, T018 and T019 together once T014 and T015 exist.
- After US2: US3 (T020 to T026) and US4 (T027 to T032) in parallel, as they touch different files.
- Polish: T034 and T035 together.

## Implementation strategy

1. Phases 1 and 2, then US1: any country can be picked and is saved with its code. Stop and check it in the browser.
2. US2: the code turns into the reader's language everywhere an address is shown.
3. US3 and US4, in either order.
4. Polish, then the definition of done.
