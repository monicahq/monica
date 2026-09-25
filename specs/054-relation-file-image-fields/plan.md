# Implementation Plan: Relation, file and image fields in the template builder

**Branch**: `2026-09-25-template-relation-file-image-fields` | **Date**: 2026-09-25 | **Spec**: [spec.md](spec.md)

## Summary

A relation joins the fields a screen can fill in (R1), valued by the uuid of the record it points at,
checked against the vault at validation (R2), through a new searchable `x-record-picker` backed by one
route (R3, R4). The builder asks for a relation's target when the field is added (R5), adds fields
inside a repeater (R6), and edits accepted file kinds without wiping the settings it does not show
(R7, R8). No schema change, so no `data-model.md`; no new API endpoint, so no contract; the tests
table below is the validation guide, so no `quickstart.md`.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (latest)
**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js, Alpine Ajax
**Storage**: writes `field_values.related_contact_id` / `related_entity_id`, `fields.options`,
`fields.relation_target`, `fields.relation_entity_type_id`, `fields.parent_field_id`; all exist
**Testing**: PHPUnit: actions, helpers, view models, web and API controllers, component tests
**Target Platform**: the builder, the field type screen, every screen drawing `x-field-input`
**Project Type**: server rendered web application with a JSON API
**Performance Goals**: a search answers with at most 25 candidates from one indexed query
**Constraints**: WCAG 2.2 AA; every string in every locale; published versions stay frozen
**Scale/Scope**: 1 component, 1 script, 2 controllers, 1 view model, 3 web routes, 1 exception, 1 scope, about 15 strings

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.* Re-checked after Phase 1: unchanged.

| Principle | Status | How |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | `CreateField`, `UpdateField`, `SetFieldValues` carry the rules |
| II. Simplicity over abstraction | Pass | One allow-list change (R1); one component; parsing shared, not duplicated |
| III. Tested before merged | Pass | Tests table below |
| IV. Laravel conventions | Pass | Inline validation, one named route per line, `Rule::exists` |
| V. Localized and safe | Pass | New strings through `monica:localize`; nothing destructive added |
| VI. Nothing changes under anybody’s feet | Pass | Draft only; the target is fixed once added |
| VII. Stable core | Pass | Uses Field as it is; no second mechanism |
| VIII. Nothing disappears silently | Pass | Fixes the inspector wiping settings (R7); an inactive target says so |
| IX. Accessible to everybody | Pass | The picker is keyboard operable, announces results and the choice |
| X. Everything goes through the API | Pass | Field API already takes target, parent, options; entity API takes relation uuids |
| XI. The written record is short | Pass | spec 120, plan under 100, research under 80 |

## Project Structure

```text
app/Enums/FieldTypeEnum.php                                         # hasAnInput (R1)
app/Helpers/FieldValueRules.php                                     # vault, relation rule (R2)
app/Actions/SetFieldValues.php                                      # relation uuid (R2)
app/Models/Entity.php                                               # scopeMatchingSearch (R3)
app/Models/Field.php, app/Helpers/StructureFileReader.php           # mime pattern and limit (R8)
app/Helpers/FieldSettingsCatalogue.php, app/Actions/ReplaceLifeEventTypeFields.php  # target parsing (R5)
app/Actions/CreateField.php, UpdateField.php                        # kinds (R8)
app/Exceptions/AcceptedFileKindIsNotValid.php                       # new (R8)
app/Http/Controllers/App/Vaults/FieldChoiceController.php, app/ViewModels/Vaults/FieldChoicesViewModel.php  # new (R3)
app/Http/Controllers/App/Settings/Account/Templates/FieldController.php       # target, update (R5, R7)
app/Http/Controllers/App/Settings/Account/Templates/ChildFieldController.php  # new (R6)
app/Http/Controllers/Api/FieldController.php                        # catch (R8)
app/Http/Controllers/App/Vaults/EntityController.php, Api/EntityController.php, ContactSectionController.php  # vault to rules
app/Http/Requests/SaveFieldValuesRequest.php                        # vault to rules
app/ViewModels/Settings/Account/Templates/TemplateBuilderViewModel.php  # inspector data (R6, R7)
app/ViewModels/Settings/Account/Templates/FieldCatalogueViewModel.php   # relation targets, no repeater (R5, R6)
routes/web.php                                                      # fields.choices.index, settings.childFields.new/create
resources/js/record-picker.js, resources/js/app.js                  # new (R3)
resources/views/components/record-picker.blade.php                  # new (R3)
resources/views/components/field-input, field-repetitions; contacts/_section, _life-event-fields, entities/form  # vault (R4)
resources/views/app/settings/account/field-type.blade.php           # target select, parent (R5, R6)
resources/views/app/settings/account/templates/_inspector.blade.php, _field.blade.php  # (R6, R7)
resources/docs/api/*                                                # relation value and field settings
lang/*.json                                                         # through monica:localize
```

**Structure Decision**: the existing layout; the picker follows `relationships/_candidates`.

## Tests

| Layer | Proves |
| --- | --- |
| `FieldValueRules` | relation now has an input, avatar still not; a relation uuid from another vault, another entity type or nowhere is refused |
| `SetFieldValues` | a contact and an entity are written by uuid, top level and in a repeater row; `distinct_in_list` refuses a repeat |
| `CreateField`, `UpdateField` | refuse a malformed kind, more than 20, a non image kind on an image field |
| `FieldSettingsCatalogue` | parses `contact` and `entity:ID`, refuses anything else |
| `FieldChoicesViewModel` | vault only, target kind only, search by name, 25 at most, empty line |
| `TemplateBuilderViewModel` | inspector exposes target (and inactive), kinds, the add-inside link for a repeater |
| `FieldCatalogueViewModel` | active entity types only; no repeater when adding inside one |
| Web `FieldController` | relation created with its target; inactive type refused; save keeps `distinct_in_list`, defaults and flags; kinds saved |
| Web `ChildFieldController` | child created under the repeater, also after a published version is copied; not a repeater parent is 404 |
| Web `FieldChoiceController` | answers the partial; 404 for another account's field or a non relation |
| Web `EntityController` | an animal with two caregivers is created; a refused upload kind reaches the form |
| API | entity create takes a relation uuid, a foreign one is 422; a malformed kind on a field is 422 |
| `RecordPickerTest` | combobox contract: label, live region, `aria-pressed`, hidden input name |
| `EntityTypePolicy` | addEntity no longer refused for a required relation in a repeater (053 test rewritten) |

## Complexity Tracking

| Deviation | Why | Simpler alternative rejected |
| --- | --- | --- |
| The relationship form keeps its own candidate list beside `x-record-picker` | Its list is one mode of a three mode form sharing Alpine state | Moving it now doubles the change surface of this feature |
