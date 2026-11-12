# Implementation Plan: Possession Types

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-26 | **Spec**: [spec.md](spec.md)

## Summary

Three models under the account (`PossessionType`, `PossessionFieldGroup`, `PossessionField`), thirteen
actions, one file format with its reader, the type screens built with the project's components
in the account settings, then fifteen API endpoints, fifteen MCP tools and their documentation.
Principle VII is amended in the same pull request. Decisions in [research.md](research.md), tables and
actions in [data-model.md](data-model.md), routes in [contracts/web-routes.md](contracts/web-routes.md)
and [contracts/api-and-mcp.md](contracts/api-and-mcp.md), checks in [quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine, Alpine Ajax, Sanctum, Laravel MCP |
| Storage | Three new tables; SQLite, MySQL and PostgreSQL |
| Testing | PHPUnit: one test per action and helper, view models, one controller test per web and API controller, one per MCP tool |
| Scale | Tens of types, hundreds of fields per account at most; no pagination on the screens |
| Constraints | WCAG 2.2 AA, every string in every locale, nothing across accounts, new migrations only |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Thirteen actions hold every write; controllers, API and tools call them |
| II Simplicity | Pass. No policy, no flag, the existing position idiom (R6, R10) |
| III Tested | Pass. Every action, the reader, every controller and tool |
| IV Laravel | Pass. `up()` only, `foreignIdFor()->constrained()`, inline validation, default keywords |
| V Localized | Pass. Default names and every refusal translated; counted phrases through `trans_choice()` |
| VI Nothing changes | Not touched: nothing records a value yet |
| VII Stable core | **Violation, amended.** Possession type joins the fixed concepts (R1) |
| VIII Nothing disappears | Pass for now: no value exists to lose. Deleting a field holding values is the possessions feature's decision (spec Assumptions) |
| IX Accessible | Pass. Named swatches, labelled reorder buttons, saves announced in a live region (R3, R8) |
| X API | Pass. Every screen capability has an endpoint and a tool (contracts) |
| XI Short record | Pass. Every artifact under its cap |
| Invariant: vault is a compartment | Pass. Types are account level, every lookup scoped to the account |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md                 VII gains Possession type, Sync Impact Report, 2.7.0
spec/spec.md, spec/models/059-061               Possession type named; three model files
app/Enums/PossessionFieldKindEnum.php, UserActionEnum.php (ten cases)
app/Models/PossessionType.php, PossessionFieldGroup.php, PossessionField.php; Account gains a relation
app/Actions/                                    the thirteen actions of data-model.md
app/Helpers/PossessionTypeFile.php, PossessionTypeFileReader.php
app/Exceptions/PossessionTypeFileIsNotReadable.php
app/Http/Controllers/App/Settings/Account/PossessionTypes/   eight controllers (web-routes.md)
app/ViewModels/Settings/Account/PossessionTypes/ index, edit, export, import view models; SettingsViewModel row
resources/views/app/settings/account/possession-types/       index, edit, _group, _field, export, import
resources/views/components/settings/sidebar.blade.php        entry in Possessions
app/Http/Resources/PossessionTypeResource.php
app/Http/Controllers/Api/PossessionTypes/       type, export, import, group, group position, field, field position
app/Mcp/Tools/                                  fifteen tools; MirrorsAnApiCapability lookups; MonicaServer
app/ViewModels/Docs/McpDocsViewModel.php        "Possession types" group
resources/docs/api/26-possession-types.php; docs/portal/en/9-templates-and-customisation/; docs/FEATURES.md
database/migrations/, database/factories/       one migration, three factories
lang/*.json                                     via `php artisan monica:localize`
tests/Unit/{Actions,Helpers,ViewModels,Mcp/Tools}/, tests/Feature/Controllers/{App/Settings/Account/PossessionTypes,Api}/
```

No new component: `x-settings.page`, `x-page-header`, `x-page-section`, `x-list-panel`,
`x-actions-menu`, `x-delete-action`, `x-copy-button`, `x-code-value`, `x-empty-state`, `x-input`,
`x-select`, `x-button`.

## Order of work

1. Amendment, spec files, enums, migration, models, factories.
2. Type, group and field actions with tests; file, reader, export and import actions with tests.
3. View models, web controllers, routes, views, sidebar and overview, controller tests.
4. Resource, API controllers, routes, tests; MCP tools, registration, docs group, tests.
5. API reference, guide page, FEATURES.md, translations, PHPStan, Pint, whole suite, quickstart by hand.

## Spec amendments made while planning

- Assumptions: the palette is `VaultColourEnum`; an import without a colour takes Slate (R3).
- US1.2: a new type, group or field is named "Untitled …" in the user's language (R2).

## Complexity Tracking

| Departure | Why | Simpler alternative rejected because |
| --- | --- | --- |
| Principle VII amended | The user chose dedicated tables | A structure owned by the type was declined |
| A second file format and reader | Different shape from `StructureFile` | Stretching `StructureFileReader` mixes two formats |
| Fifteen tools | One per API capability (087 convention) | Fewer, broader tools break the mirror rule |
