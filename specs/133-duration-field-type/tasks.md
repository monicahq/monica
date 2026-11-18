# Tasks: Duration field type, and a quieter field type picker

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md)

**Tests**: included, since principle III makes them a delivery condition. Work stays on `2026-11-18-public-changelog`.

## Phase 1: Setup

None. No dependency, no migration (research R1).

## Phase 2: Foundational

- [X] T001 Add `case Duration = 'duration'` after `RecurringDate`, map it to `'value_number'` in `valueColumn()`, and list it in `hasAnInput()`, in app/Enums/FieldTypeEnum.php

## Phase 3: User Story 1, add a duration field and fill it (P1)

Independent test: add a Duration field to a template, enter 90 on a contact, reload, read "90 minutes"; 0 and 100001 are refused.

- [X] T002 [P] [US1] Add `FieldTypeEnum::Duration => ['integer', 'min:1', 'max:100000']` to `forOne()` in app/Helpers/FieldValueRules.php
- [X] T003 [P] [US1] Add `FieldTypeEnum::Duration` to `format()`, returning null when `value_number` is null, else `trans_choice(':count minute|:count minutes', (int) $value->value_number)`, in app/Helpers/FieldValueFormatter.php
- [X] T004 [P] [US1] In the generic input branch, give Duration `type="number"`, `min="1"`, and the value `(int) $value->value_number` when not null, in resources/views/components/field-input.blade.php
- [X] T005 [P] [US1] Add `FieldTypeEnum::Duration` after `Number` in the list of types a column may show, in app/Helpers/ContactListCatalogue.php
- [X] T006 [P] [US1] Test 90 passes and 0, 2.5, "abc", 100001 fail for a Duration field in tests/Unit/Helpers/FieldValueRulesTest.php
- [X] T007 [P] [US1] Test 1 reads "1 minute", 90 reads "90 minutes", no value reads null in tests/Unit/Helpers/FieldValueFormatterTest.php
- [X] T008 [P] [US1] Test a Duration value of 90 is stored in `value_number` in tests/Unit/Actions/SetFieldValueTest.php
- [X] T009 [US1] Run `php artisan test --compact` on FieldValueRulesTest, FieldValueFormatterTest, SetFieldValueTest and tests/Unit/ViewModels/Settings/Account/Templates/FieldCatalogueViewModelTest.php

## Phase 4: User Story 2, the same duration through the API and the export (P2)

Independent test: write 90 through the contact section API, find 90 minutes stored.

- [X] T010 [US2] Skipped: the API writes through the same `FieldValueRules` and `SetFieldValue` that T006 and T008 test, and `.claude/rules/testing.md` forbids testing a rule twice. Was: Test that writing 90 to a Duration field through the contact section endpoint stores 90 in `value_number`, and that 0 answers 422, in tests/Feature/Controllers/Api/ContactSectionControllerTest.php
- [X] T011 [US2] Confirm the MCP `create-template-field` and `replace-life-event-type-fields` schemas list `duration` (they read `FieldTypeEnum::cases()`), and grep `resources/views/app/docs` and the help centre for a list of field types to add Duration to

## Phase 5: User Story 3, a picker without the family sentences (P3)

Independent test: open the field type picker, no family shows a sentence.

- [X] T012 [P] [US3] Delete the `blurb` entry from all nine families and from the three `@return` array shapes in app/ViewModels/Settings/Account/Templates/FieldCatalogueViewModel.php
- [X] T013 [P] [US3] Delete the `<p>` printing `$family['blurb']` at line 153 of resources/views/app/settings/account/field-type.blade.php

## Phase 6: Polish

- [X] T014 Run `php artisan monica:localize`, confirm the nine sentences leave every lang/*.json and nothing new needs translating, then `php artisan monica:localize --check`
- [X] T015 Write the What's new entry through the `changelog` skill
- [X] T016 Run `vendor/bin/pint --dirty --format agent`, PHPStan, and `composer test:parallel`
- [X] T017 Run the `definition-of-done` skill

## Dependencies

T001 blocks every US1 and US2 task. US3 (T012, T013) depends on nothing and can run at any time. T014 to T017 run last.

## Parallel execution

- After T001: T002 to T008 touch seven different files and run together.
- T012 and T013 run together, alongside any of the above.

## Implementation strategy

MVP is T001 to T009: a usable Duration field. US2 adds only a test, since the API and export already go through the code US1 changes. US3 ships alone if wanted.
