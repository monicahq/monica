# Implementation Plan: Field Type Picker

**Branch**: `2026-09-02-new-way-of-displaing-fields` | **Date**: 2026-09-02 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/010-field-type-picker/spec.md`

## Summary

Rebuild `settings.fields.new` as a catalogue of compact cards beside a panel that draws the field the selected type produces. The catalogue keeps its families and its radio group, gains a client side filter with a count and a mark on every card, and loses the technical key from the cards. The label input and the value list question stay where they are. Nothing about what is submitted or stored changes: the same route, the same payload, the same `CreateField`.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13

**Primary Dependencies**: Blade, Tailwind CSS 4, Alpine.js. No new dependency.

**Storage**: none. No migration, no model, no column.

**Testing**: PHPUnit, through `php artisan test --compact`

**Target Platform**: server rendered web application, phone to desktop

**Project Type**: web application, one screen and the catalogue behind it

**Performance Goals**: the filter answers a keystroke without a request; the screen is one response as it is today

**Constraints**: WCAG 2.2 AA, nine locales, readable at 200% zoom, no motion when the system asks for none, PHPStan level 5 clean, Pint clean

**Scale/Scope**: one Blade screen, one new partial, one view model, twelve new glyphs, thirty one catalogue entries

## Constitution Check

*GATE: passed before Phase 0, re-checked after Phase 1. No violations, so Complexity Tracking is empty and omitted.*

| Principle | How this change satisfies it |
| --- | --- |
| I Actions hold the logic | No action is touched. `FieldCatalogueViewModel` only reads copy, and the controller keeps delegating to `CreateField`. |
| II Simplicity | One new partial and one new set of glyphs. The preview shape is data on the catalogue entry, not a class per type. |
| III Tested before merged | Three test files, named under Phase 1. |
| IV Laravel conventions | Kebab-case view files, no new class, no migration, no `env()`. |
| V Localized and safe | Every string through `__()` or `trans_choice()`, `php artisan monica:localize` run and the nine locales translated. No destructive action on this screen. |
| VI Nothing changes underfoot | The screen still writes through `MakeSectionVersionEditable`, so a published section is still copied rather than edited. |
| VII Stable core | The catalogue stays the one way a field is added. No new mechanism. |
| VIII Nothing disappears | Nothing is deactivated or deleted here. |
| IX Accessible to everybody | Native radio group kept, focus visible, the preview announced through one polite live region, the "Later" state carried by a badge and not by colour, two columns collapse to one, motion answered by the tokens. |
| X Everything goes through the API | No API surface added. The types the API accepts come from `FieldTypeEnum`, which this change does not touch. |
| XI The written record is short | `spec.md` 112 lines, `research.md` 75, this file inside its cap, three artifacts omitted with the reason given in `research.md`. |

## Project Structure

### Documentation (this feature)

```text
specs/010-field-type-picker/
├── spec.md
├── plan.md              # this file
├── research.md          # the eleven decisions
├── checklists/
│   └── requirements.md
└── tasks.md             # /speckit-tasks output, not created here
```

### Source Code (repository root)

```text
app/ViewModels/Settings/Account/Templates/
└── FieldCatalogueViewModel.php          # + mark and preview per type, searchIndex(), countPhrases(), sentences()

resources/views/app/settings/account/templates/
├── field-type.blade.php                 # rewritten: filter, compact cards, panel, footer
└── _field-type-preview.blade.php        # new: draws one illustration from a preview shape

resources/views/components/
└── icon.blade.php                       # + 12 glyphs

lang/
└── *.json                               # written by php artisan monica:localize

tests/Unit/ViewModels/Settings/Account/Templates/
└── FieldCatalogueViewModelTest.php      # + the four new methods

tests/Feature/Controllers/App/Settings/Account/Templates/
└── FieldControllerTest.php              # + the panel, the filter data, the count, the Later marking

tests/Feature/Views/Components/
└── IconTest.php                         # + the 12 glyphs answer to their names
```

**Structure Decision**: the screen is a Blade view with its view model, which is where every other settings screen lives. `FieldControllerTest` covers the rendered view because the screen has no component of its own; the preview partial is reached through it. No controller change: `new()` already builds the view model and the value lists.

## Validation

```bash
php artisan test --compact tests/Feature/Controllers/App/Settings/Account/Templates/FieldControllerTest.php
php artisan test --compact tests/Unit/ViewModels/Settings/Account/Templates/FieldCatalogueViewModelTest.php tests/Feature/Views/Components/IconTest.php
php artisan monica:localize --check && vendor/bin/pint --dirty --format agent && vendor/bin/phpstan analyse
```
