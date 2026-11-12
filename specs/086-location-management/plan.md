# Implementation Plan: Location Management

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-25 | **Spec**: [spec.md](spec.md)

## Summary

One `Location` model under the account, with an optional parent, emoji and address. Three actions, one
policy refusing to delete a location that holds others, one emoji rule, one view model building the
tree and the paths in memory, and one screen in the account settings that opens its form in place,
with no flag. Decisions in [research.md](research.md), table and actions in
[data-model.md](data-model.md), routes and form in [contracts/web-routes.md](contracts/web-routes.md),
checks in [quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine, Alpine Ajax |
| Storage | One new table, `locations`; SQLite, MySQL and PostgreSQL |
| Testing | PHPUnit: action, policy, rule, view model and controller tests per `.claude/rules/testing.md` |
| Scale | A household: tens of locations per account, a handful of levels; no pagination |
| Constraints | WCAG 2.2 AA, every string in every locale, nothing reachable across accounts, new migrations only |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Three actions hold every write; the view model only reads |
| II Simplicity | Pass. No closure table, no recursion in SQL (R5, R7) |
| III Tested | Pass. One test per action, policy, rule and view model, one controller test |
| IV Laravel | Pass. `up()` only, `foreignIdFor()->constrained()`, inline validation, default controller keywords |
| V Localized | Pass. Counted phrases through `trans_choice()`; delete confirms first |
| VI Nothing changes | Not touched |
| VII Stable core | **Violation, amended.** Location joins the fixed concepts in the same pull request (R1) |
| VIII Nothing disappears | Pass. Only an empty location can be deleted; the changelog keeps its name (R9) |
| IX Accessible | Pass with nested lists rather than an ARIA tree, and a refused delete that says why (R4, R8) |
| X API | **Deferred**, stated in the pull request (R10) |
| XI Short record | Pass. Every artifact under its cap |
| Invariant: vault is a compartment | Pass. Locations hold no vault data; the parent is checked against the account in the controller and the actions |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md                 principle VII gains Location, Sync Impact Report, MINOR
spec/spec.md, spec/models/                      Location among the fixed concepts, one model file
app/Enums/UserActionEnum.php                    LocationCreation, LocationUpdate, LocationDeletion
app/Models/Location.php                         Account gains locations()
app/Actions/                                    CreateLocation, UpdateLocation, DestroyLocation
app/Policies/LocationPolicy.php                 delete
app/Rules/SingleEmoji.php
app/Http/Controllers/App/Settings/Account/Locations/  LocationController, ChildLocationController
app/ViewModels/Settings/Account/Locations/      LocationsViewModel; SettingsViewModel gains a row
database/migrations/, database/factories/       create_locations_table, LocationFactory
resources/views/app/settings/account/locations/ index, _form, _row
resources/views/components/settings/sidebar.blade.php  a Locations entry
routes/web.php                                  contracts/web-routes.md
lang/*.json                                     via `php artisan monica:localize`
tests/Unit/Actions/                             three action tests
tests/Unit/Policies/LocationPolicyTest.php      permitted, refused, nobody_is_signed_in
tests/Unit/Rules/SingleEmojiTest.php
tests/Unit/ViewModels/Settings/Account/Locations/LocationsViewModelTest.php
tests/Feature/Controllers/App/Settings/Account/Locations/LocationControllerTest.php
```

No new component. The screen uses `x-settings.page`,
`x-page-header`, `x-panel`, `x-input`, `x-select`, `x-textarea`, `x-button`, `x-delete-action`,
`x-empty-state` and `x-icon` (`place`, `layers`, `plus`). The emoji choice follows the colour
group of the vault settings screen.

## Order of work

1. Amendment and spec files, enum cases, migration, model, factory.
2. Rule, policy, then the three actions, each with its test.
3. View model and its test, then controllers, routes, views, the settings link, controller test.
4. Translations, PHPStan, Pint, the whole suite, then quickstart by hand.

## Complexity Tracking

| Departure | Why | Simpler alternative rejected because |
| --- | --- | --- |
| Principle VII amended | The user asked for one model with a parent column | Templates have no parent or cycle rule |
| No API endpoint | The user asked for a simple first version; actions keep endpoints transport only | Doubles the change for a first version |
| Delete refused, unlike the design | The user chose the brief's rule (spec Clarifications) | Moving contents up changes data with one click |
| Nested lists, unlike the design's `role="tree"` | An ARIA tree needs arrow key navigation the design omits | Shipping a tree role that does not behave as one |
