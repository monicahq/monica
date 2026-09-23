# Implementation Plan: Editable Address Field

**Branch**: `2026-09-22-fix-address-edit` | **Date**: 2026-09-22 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/043-fix-address-edit/spec.md`

## Summary

The address joins the two composite types that already have a control. `FieldTypeEnum` answers
`hasAnInput()` and `usesALabelList()` with yes for it, and that alone switches on the form, the
validation, the save, the label list on the field type screen, and the card label on both sides. What
is written new is the control itself, seven boxes and a country pick, the rules for seven nested keys,
the array handling in the two save actions, and the address line beside its label on the record. No
migration. The shape is in [data-model.md](data-model.md), the decisions in [research.md](research.md),
the form field names in [contracts/section-form.md](contracts/section-form.md) and the card mapping
in [contracts/vcard-mapping.md](contracts/vcard-mapping.md).

## Technical Context

**Language/Version**: PHP 8.4, current major Laravel

**Primary Dependencies**: Blade, Tailwind, Alpine, `sabre/vobject` for cards

**Storage**: existing `fields` and `field_values` tables. No migration.

**Testing**: PHPUnit, `RefreshDatabase` where the database is touched

**Target Platform**: server rendered web application, desktop, tablet and phone

**Project Type**: web application

**Constraints**: suite passes on SQLite, MySQL and PostgreSQL. PHPStan level 5. WCAG 2.2 AA.

**Scale/Scope**: one field type, around 20 files, no data migration owed

## Constitution Check

| Principle | How this satisfies it |
| --- | --- |
| I. Actions hold the logic | `SetFieldValues` assembles the address and resolves the country pick, `SetFieldValue` sanitises and writes it. `ContactSectionController` passes `fields` through as it does today and gains no rule. |
| II. Simplicity over abstraction | No new column, table, class or value object. The address stays an array in `value_json`, the label a value list item, the country a string, which is how a card already writes them. |
| III. Tested before merged | Every file below arrives with its test, and `FieldTypeEnumTest` flips the assertion that said an address has no input. No policy is added, so the three ways an ability is proven do not apply. |
| IV. Laravel conventions | Validation stays inline in `FieldValueRules` through nested array keys. One class per file. No migration, so no `down()`. |
| V. Localized and safe | `monica:localize` is run and every locale translated: the seven part names, the wording of a country kept as it arrived, the catalogue copy. Nothing destructive is added. |
| VI. Nothing changes under feet | No stored value is rewritten. An address a device wrote is read as it stands and written back with every key it had. |
| VII. Stable core | The address stays a field type of the one personalisation mechanism. Nothing becomes a column. |
| VIII. Nothing disappears silently | A country or label whose item is deactivated keeps reading. A country a device wrote that names no item is offered back as it arrived rather than dropped on save. |
| IX. Accessible to everybody | One fieldset per address with the field's name as its legend, every box under a visible label, the country a native select, the label the existing combo box. Built once in `x-field-input`, so every template renders it accessibly. |
| X. Everything through the API | **Not met. See Deviations.** |
| XI. The written record is short | This file is 99 lines, inside the 100 line cap. Each artifact holds its fact once. |

## Project Structure

```text
specs/043-fix-address-edit/
├── plan.md
├── research.md
├── data-model.md
├── contracts/section-form.md
├── contracts/vcard-mapping.md
└── quickstart.md
```

### Source Code (repository root)

```text
app/Enums/FieldTypeEnum.php                    # hasAnInput() and usesALabelList() say yes; suppliedLabelList()
app/Enums/DefaultFieldEnum.php                 # the supplied Address field names AddressLabels
app/Actions/SetFieldValue.php                  # sanitise the seven parts before writing value_json
app/Actions/SetFieldValues.php                 # an all blank array is blank; the country pick becomes text
app/Helpers/FieldValueRules.php                # rules for the seven nested keys and the country
app/Helpers/FieldValueFormatter.php            # addressLine(), and the label read beside it
app/Http/Requests/SaveFieldValuesRequest.php   # attribute names for the parts
app/Http/Dav/VCardMerger.php                   # ADR carries home and work as standard types
app/ViewModels/Settings/Account/Templates/FieldCatalogueViewModel.php   # address copy and preview
resources/views/components/field-input.blade.php          # the address case
resources/views/components/field-value.blade.php          # the address line and its badge
app/Http/Controllers/App/Settings/Account/Templates/FieldController.php   # hands the supplied list per type to the screen
resources/views/app/settings/account/field-type.blade.php # preselect the supplied label list
resources/views/app/settings/account/templates/_field-type-preview.blade.php   # a label above the rows
spec/spec.md, spec/models/017-field-done.md, spec/models/022-field-value-done.md
lang/*.json                                    # through php artisan monica:localize
tests/Unit/Enums/FieldTypeEnumTest.php, tests/Unit/Enums/DefaultFieldEnumTest.php
tests/Unit/Actions/SetFieldValueTest.php, tests/Unit/Actions/SetFieldValuesTest.php
tests/Unit/Helpers/FieldValueRulesTest.php, tests/Unit/Helpers/FieldValueFormatterTest.php
tests/Unit/Http/Dav/VCardMergerTest.php, tests/Unit/Actions/ImportVCardTest.php
tests/Unit/Jobs/CreateDefaultTemplateTest.php
tests/Unit/ViewModels/Settings/Account/Templates/FieldCatalogueViewModelTest.php
tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php
tests/Feature/Views/Components/FieldInputTest.php, tests/Feature/Views/Components/FieldValueTest.php
```

## Deviations

**Principle X, everything through the API.** No endpoint reads or writes a contact's field values,
for any type, and the spec records that as an assumption rather than a requirement. The logic lands in
`SetFieldValues` and `SetFieldValue`, so the endpoint, when it comes, is transport and nothing more.
`GET vaults/{vault}/contacts/{contact}/vcard` already returns an address with its label, since it
renders through `VCardMerger`. The pull request must say so.
