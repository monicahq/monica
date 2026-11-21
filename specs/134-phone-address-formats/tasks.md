# Tasks: Phone numbers and addresses shown the way their country writes them

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md)

**Tests**: included, since principle III makes them a delivery condition. Work stays on `claude/location-specific-phone-address-364373`.

## Phase 1: Setup

- [X] T001 Run `composer require commerceguys/addressing giggsey/libphonenumber-for-php-lite` (approved 2026-11-19), updating composer.json and composer.lock
- [X] T002 Confirm in `php artisan tinker` the package behaviour R5 and R6 rely on: a third address line, plain text output, a format whose country line can be dropped, unknown subdivision text passed through, `parse('+33612345678', null)` formatting as `+33 6 12 34 56 78`; record any difference in specs/134-phone-address-formats/research.md before going on

## Phase 2: Foundational

- [X] T003 Write `database/migrations/2026_11_19_000001_add_country_code_to_value_list_items_table.php`: `up()` only, nullable `string('country_code', 2)` after `key`, per the `migrations` skill
- [X] T004 [P] Add `country_code` to the `#[Fillable]` list and the `@property string|null $country_code` docblock in app/Models/ValueListItem.php
- [X] T005 Create app/Helpers/CountryCodes.php: `codeFor(int $accountId, string $country): ?string`, trying in order the account's Countries list items by current label (code `country_code ?? key`), the ten `CountryEnum` names translated in every configured locale (only when the first misses), then two letters the package knows; matching ignores case and surrounding spaces; one map per account held in the instance (R2)
- [X] T006 Register `CountryCodes` with `$this->app->scoped()` in app/Providers/AppServiceProvider.php (R4)
- [X] T007 [P] Test an added country with a code matches, a renamed supplied country matches both its old label and its French name, `FR` matches, `Narnia` gives null, and another account's list is ignored, in tests/Unit/Helpers/CountryCodesTest.php

## Phase 3: User Story 1, an address reads as it would on an envelope (P1)

Independent test: store the same seven parts under France and the United States and read two layouts.

- [X] T008 [US1] Add `addressLines(?array $parts, int $accountId): list<string>` to app/Helpers/FieldValueFormatter.php: street to address line 1, extended to line 2, post office box to line 3, city, region and postcode to their tokens, the stored country text as the last line, a filled part without a token appended on its own line, today's order when `CountryCodes` gives null (R5, FR-001 to FR-003)
- [X] T009 [US1] Make `addressLine()` take the account id and join `addressLines()` with `, `, then update its callers `labelled()` in app/Helpers/FieldValueFormatter.php and `$line` in resources/views/components/field-value.blade.php
- [X] T010 [US1] Render an address as one line per element in reading order, keeping the badge, in the `usesALabelList()` branch of resources/views/components/field-value.blade.php (FR-011, FR-013)
- [X] T011 [P] [US1] Hand an address field to the view as `lines` beside `value` in app/ViewModels/Shared/SharedContactViewModel.php, and print the lines one per element in resources/views/shared/contact.blade.php (FR-013)
- [X] T012 [P] [US1] Test France puts `75002 Paris` on the line after the street, the United States puts `Scranton, PA 18505` on one line, no country and `Narnia` keep today's order, a region in France is still shown, and `addressLine()` reads the lines in the same order, in tests/Unit/Helpers/FieldValueFormatterTest.php

## Phase 4: User Story 2, a phone number reads as its country groups it (P2)

Independent test: store `+33612345678`, `+15705550123`, `06 12 34 56 78` and `ask Pam`, and read each.

- [X] T013 [US2] Add static `phone(string $text): string` to app/Helpers/FieldValueFormatter.php: text starting with `+` that parses and is valid is formatted in the international form, anything else is returned untouched; call it from `labelled()` for the Phone type (R6, FR-004 to FR-006)
- [X] T014 [US2] Pass a phone's `$line` through `FieldValueFormatter::phone()` and wrap it in `dir="ltr"` in resources/views/components/field-value.blade.php
- [X] T015 [P] [US2] Keep indexing a phone's stored `value_string` instead of `format()` in app/Models/Concerns/SearchesRecords.php (FR-008)
- [X] T016 [P] [US2] Test `+33612345678` reads `+33 6 12 34 56 78`, `+15705550123` reads `+1 570-555-0123`, and `06 12 34 56 78` and `ask Pam` read as typed, in tests/Unit/Helpers/FieldValueFormatterTest.php
- [X] T017 [P] [US2] Test a contact holding `+33612345678` is still found by `+33612345678`, in tests/Unit/Models/ContactTest.php

## Phase 5: User Story 3, the same reading everywhere (P3)

Independent test: read one address and one number on the record page, a list cell, a shared page and the insurance record.

- [X] T018 [P] [US3] Set `phone` through `FieldValueFormatter::phone()` and leave `tel` reading `contact_phone`, in app/ViewModels/Vaults/PossessionInsuranceViewModel.php (FR-009, FR-010)
- [X] T019 [P] [US3] Test the phone is grouped and the `tel` value is the stored number, in tests/Unit/ViewModels/Vaults/PossessionInsuranceViewModelTest.php
- [X] T020 [P] [US3] Take `?string $countryCode`, store it uppercase, refuse it with `InvalidArgumentException` when the list is not Countries, and record it in the logged changes, in app/Actions/CreateValueListItem.php and app/Actions/UpdateValueListItem.php
- [X] T021 [US3] Validate `country_code` as `['nullable', 'string', 'size:2', Rule::in(...)]` against the package's codes and pass it on, in app/Http/Controllers/App/Settings/Account/ValueLists/ValueListItemController.php
- [X] T022 [US3] Add a Country code input, shown on the Countries list only, to the add form in resources/views/app/settings/account/value-lists/_list.blade.php and the edit form in resources/views/app/settings/account/value-lists/_value.blade.php
- [X] T023 [P] [US3] Return `country_code` on each item (`country_code ?? key` for a Countries item, null otherwise) in app/Http/Controllers/Api/ValueListController.php
- [X] T024 [P] [US3] Test the code is stored uppercase and logged, and refused on the Genders list, in tests/Unit/Actions/CreateValueListItemTest.php and tests/Unit/Actions/UpdateValueListItemTest.php
- [X] T025 [P] [US3] Test `XX1` is refused on the form in tests/Feature/Controllers/App/Settings/Account/ValueLists/ValueListItemControllerTest.php
- [X] T026 [P] [US3] Test a Countries item carries its code in tests/Feature/Controllers/Api/ValueListControllerTest.php

## Phase 6: Polish

- [X] T027 Document `country_code` on the value lists endpoint through the `api-docs-writer` skill, under resources/views/app/docs/api
- [X] T028 Run `php artisan monica:localize`, translate what it reports through the `translations` skill, then `php artisan monica:localize --check`
- [X] T029 Write the What's new entry through the `changelog` skill
- [X] T030 Run `vendor/bin/pint --dirty --format agent`, PHPStan, and `composer test:parallel`
- [ ] T031 Run the `definition-of-done` skill, and name the principle X gap (plan, Complexity Tracking) in the pull request

## Dependencies

T001 blocks T002, which blocks everything that calls a package (T005, T008, T013, T021). T003 to T006 block every story. US1 and US2 both edit `FieldValueFormatter.php` and `field-value.blade.php`, so T008 to T010 run before T013 and T014. US3's value list tasks (T020 to T026) need only T003 and T004. T027 to T031 run last.

## Parallel execution

- After T006: T011, T015, T018 and T020 to T023 touch separate files and run together.
- Every test task marked [P] runs alongside the implementation of another story.

## Implementation strategy

MVP is T001 to T012: every address in its country's layout. US2 adds phone grouping on the same formatter. US3 extends the reading to the insurance record and lets an added country carry its code.
