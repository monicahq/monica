# Implementation Plan: Possessions Empty State

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-26 | **Spec**: [spec.md](spec.md)

## Summary

All possessions gets a view model and, for an empty vault, the designed empty state: an intro with
two actions, up to eight type tiles opening the add form with the type chosen, three "How it works"
cards, and a line about the account's locations. Possession types gain an optional icon from the
section catalogue, added to the branch migration, set on the 25 defaults, editable on the type edit
screen, and carried by the API, the MCP tools, export and import. Principle VII is amended.
Decisions in [research.md](research.md), schema and view models in [data-model.md](data-model.md),
routes, screens, API and tools in [contracts/screen-api-and-mcp.md](contracts/screen-api-and-mcp.md),
checks in [quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js, Alpine Ajax, Sanctum, Laravel MCP |
| Storage | One nullable column in a branch migration; SQLite, MySQL and PostgreSQL |
| Testing | PHPUnit: enum, actions, file reader, view models, web and API controllers, tools |
| Scale | Eight types and three locations read per screen; one existence check on possessions |
| Constraints | Nothing across accounts; every wording translated; WCAG 2.2 AA; no new JavaScript |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. The two type actions own the icon rule; web, API, tools and import call them |
| II Simplicity | Pass. One view model, no component, no policy, no flag; the condition's icon rule copied |
| III Tested | Pass. Each layer proves its own rules (quickstart) |
| IV Laravel | Pass. Branch migration edited (R1); optional bound route parameter; inline validation |
| V Localized | Pass. Counts through `trans_choice()`, names joined with the existing `:names and :last` |
| VI Nothing changes | Pass. Existing types gain an empty icon; old export files still import; `PUT` full replace documented |
| VII Stable core | **Amended.** A Possession type is described with its name, colour and optional icon (R2) |
| VIII Nothing disappears | Pass. Nothing is deleted |
| IX Accessible | Pass. Tiles are links with text; glyph buttons labelled and `aria-pressed`; illustrations hidden |
| X API | Pass. The icon on endpoints, tools and the reference |
| XI Short record | Pass. spec 120/120, plan, research, data model, contracts and quickstart within caps |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md                 VII, Sync Impact Report, 2.12.0
spec/models/059-possession-type-in-progress.md  the icon column and its rule
database/migrations/2026_10_26_000000_create_possession_types_table.php   icon column
app/Enums/PossessionTypeEnum.php                icon()
app/Models/PossessionType.php                   fillable, docblock
app/Actions/CreatePossessionType.php, UpdatePossessionType.php, CreateDefaultPossessionTypes.php,
  ImportPossessionType.php, ExportPossessionType.php
app/Helpers/PossessionTypeFileReader.php        icon (R6)
app/Http/Controllers/App/Settings/Account/PossessionTypes/PossessionTypeController.php   update
app/ViewModels/Settings/Account/PossessionTypes/PossessionTypeViewModel.php, PossessionTypesViewModel.php
resources/views/app/settings/account/possession-types/edit.blade.php, index.blade.php
app/Http/Controllers/Api/PossessionTypes/PossessionTypeController.php
app/Http/Resources/PossessionTypeResource.php, PossessionResource.php
app/Mcp/Tools/CreatePossessionType.php, UpdatePossessionType.php, Get/List/ExportPossessionType.php (descriptions)
resources/docs/api/26-possession-types.php, 27-possessions.php
app/ViewModels/Vaults/PossessionsViewModel.php (new), PossessionFormViewModel.php
app/Http/Controllers/App/Vaults/PossessionController.php   index, new
routes/web.php                                  possessions.new optional type
resources/views/app/vaults/possessions/index.blade.php
docs/portal/en/9-templates-and-customisation/12-possession-types.md, 16-possessions.md; docs/FEATURES.md
lang/*.json                                     via `php artisan monica:localize`
tests/...                                       as quickstart lists
```

## Order of work

1. Amendment, model doc; migration, model, factory, enum `icon()`.
2. The five actions and the file reader, with their tests.
3. API, resources, tools, API reference, with tests.
4. Type settings: controller, view models, edit and index views, with tests.
5. Route, `PossessionFormViewModel`, `PossessionsViewModel`, controller, index view, with tests.
6. Guides, FEATURES.md, translations, PHPStan, Pint, whole suite, a look at the screen.

## Spec amendments made while planning

- Cut to the 120 line cap of principle XI (it was 210); Key Entities removed, carried by data-model.
- FR-013 names the edit screen only: the web creates a blank type, then edits it.
- Types are listed by name, the order the add form uses.

## Complexity Tracking

| Departure | Why | Simpler alternative rejected because |
| --- | --- | --- |
| Principle VII amended | The described shape of a type grows | Leaving it stale breaks the governance rule on one description |
| `UpdatePossessionType` icon is a required argument | Each caller has a different "left out" meaning (R5) | A default of null would clear the icon on a caller that forgot it |
