# Implementation Plan: Composite Email and Phone Fields

**Input**: Feature specification from `specs/040-composite-email-phone/spec.md`

## Summary

An email or phone value becomes two parts: the address or number, and the label saying what it is
for. The address or number stays in `value_string`, so duplicate detection, uniqueness and the
filter index keep working untouched. The label reuses the machinery choice fields already have:
`fields.value_list_id` names the list, `field_values.value_list_item_id` holds the chosen item. No
new column and no new table. The shape and the rules are in [data-model.md](data-model.md), the
decisions and what was rejected in [research.md](research.md), the card mapping in
[contracts/vcard-mapping.md](contracts/vcard-mapping.md).

**Language/Version**: PHP 8.4, current major Laravel

**Primary Dependencies**: Blade, Tailwind, Alpine, Alpine Ajax, `sabre/vobject` for cards

**Storage**: existing `fields` and `field_values` tables. No migration.

**Testing**: PHPUnit, `RefreshDatabase` where the database is touched

**Target Platform**: server rendered web application, desktop, tablet and phone

**Project Type**: web application

**Constraints**: suite passes on SQLite, MySQL and PostgreSQL. PHPStan level 5. WCAG 2.2 AA.

**Scale/Scope**: two field types, around 18 files, no data migration owed

## Constitution Check

| Principle | How this satisfies it |
| --- | --- |
| I. Actions hold the logic | `SetFieldValue` and `SetFieldValues` gain the label. Controllers pass it through and hold no rule. |
| II. Simplicity over abstraction | No new column, no new table, no new value object. The label is a value list item, which is a thing the product already has. |
| III. Tested before merged | Every file below arrives with its test. The three ways an ability is proven do not apply, since no policy is added. |
| IV. Laravel conventions | Validation stays inline in `FieldValueRules`. One class per file. No `down()` because there is no migration. |
| V. Localized and safe | `monica:localize` is run and every locale translated. Nothing destructive is added. |
| VI. Nothing changes under feet | Settled by clarification: every field gains the part, and no stored value is rewritten. |
| VII. Stable core | Email and phone stay field types of the one personalisation mechanism. Nothing becomes a column. |
| VIII. Nothing disappears silently | A label whose list item is deactivated keeps reading, exactly as a choice value does today. |
| IX. Accessible to everybody | The label select is labelled and grouped with its input, so the pair is announced as one thing. Built once in `x-field-input`. |
| X. Everything through the API | **Not met. See Deviations.** |
| XI. The written record is short | This file is 93 lines, inside the 100 line cap. Each artifact holds its fact once. |

## Project Structure

```text
specs/040-composite-email-phone/
├── plan.md
├── research.md
├── data-model.md
├── contracts/vcard-mapping.md
└── quickstart.md
```

### Source Code (repository root)

```text
app/Enums/FieldTypeEnum.php            # usesALabelList(), catalogue family
app/Enums/DefaultFieldEnum.php         # Email and Phone name their supplied label list
app/Actions/SetFieldValue.php          # write value_list_item_id beside value_string
app/Actions/SetFieldValues.php         # carry labels, keyed by field id and inside a repetition
app/Actions/CreateField.php            # keep value_list_id for a label list type
app/Actions/UpdateField.php            # same
app/Actions/ImportVCard.php            # read the card's type into a label
app/Helpers/FieldValueRules.php        # a rule for labels.{id}
app/Helpers/FieldValueFormatter.php    # the label reads beside the value
app/Helpers/FieldSettingsCatalogue.php # the label list is a setting of the field
app/Http/Requests/SaveFieldValuesRequest.php
app/Http/Controllers/App/Vaults/ContactSectionController.php
app/Http/Controllers/Api/FieldController.php
app/Http/Dav/VCardMerger.php           # the value's label becomes the card's type
app/ViewModels/Settings/Account/Templates/FieldCatalogueViewModel.php
resources/views/components/field-input.blade.php
resources/views/components/field-value.blade.php
spec/spec.md, spec/models/017-field-done.md
lang/*.json                            # through php artisan monica:localize
```

## Deviations

**Principle X, everything through the API.** FR-013 asks the API to read and write both parts of a
value in one call. There is no endpoint that reads or writes a contact's field values of any type:
`routes/api.php` exposes contacts, names, avatars and templates, and nothing under them. This
feature therefore cannot satisfy FR-013 without first building the field values API, which is a
capability of its own and belongs in its own specification.

What ships instead: the logic stays in `SetFieldValue`, so adding the endpoint later is transport
and nothing more, and `GET vaults/{vault}/contacts/{contact}/vcard` already returns both parts,
since it renders through `VCardMerger`. FR-013 is met for reading and unmet for writing. The pull
request must say so.
