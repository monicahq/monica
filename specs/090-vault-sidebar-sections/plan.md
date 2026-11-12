# Implementation Plan: Vault Sidebar Sections

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-26 | **Spec**: [spec.md](spec.md)

## Summary

The vault column is regrouped into the sections of variant 1a, built the way the settings column
already builds its groups. Custom types are listed through a new, stricter `configuredInVault` scope,
while `visibleInVault` keeps guarding access. All possessions leads to a new placeholder screen and
is offered only when the account has a possession type. No schema, API or MCP change.
Decisions in [research.md](research.md), checks in [quickstart.md](quickstart.md). No data model
and no contract: nothing is stored and nothing is exposed.

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine |
| Storage | None new; reads `entity_types`, `vault_default_templates`, `possession_types` |
| Testing | PHPUnit: the scope, the sidebar component, the new screen |
| Scale | One extra `exists()` query per vault screen |
| Constraints | WCAG 2.2 AA; every label in every locale |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Nothing is written, so no action |
| II Simplicity | Pass. No view model for an empty screen (R2), no new component (R4) |
| III Tested | Pass. Scope, column, screen (quickstart) |
| IV Laravel | Pass. One named route, a scope, an anonymous view |
| V Localized | Pass. Four strings through `monica:localize` (R5) |
| VI Nothing changes | Pass with note: entries move into sections; the guide and screenshot follow (R6) |
| VII Stable core | Not touched |
| VIII Nothing disappears | Pass. A type's entry leaves the column, its records stay reachable (R1) |
| IX Accessible | Pass. Section labels are headings, not links; current page unchanged |
| X API | Pass. The screen shows no data; nothing to expose |
| XI Short record | Pass |

Departure from `.claude/rules/flags.md` (no flag for an unbuilt capability) at the user's request (R3).

Re-checked after design: unchanged.

## Project Structure

```text
app/Models/EntityType.php                                  scopeConfiguredInVault; visibleInVault uses it (R1)
app/View/Components/Vault/Sidebar.php                      configuredInVault; hasPossessionTypes (R4)
resources/views/components/vault/sidebar.blade.php         groups, foot entry (R4)
app/Http/Controllers/App/Vaults/PossessionController.php   index (R2)
resources/views/app/vaults/possessions/index.blade.php     placeholder screen
routes/web.php                                             vaults/{vault}/possessions, possessions.index
docs/portal/en/3-people/6-profiles.md                      under Your relationships (R6)
docs/FEATURES.md                                           one line
lang/*.json                                                via `php artisan monica:localize`
tests/Unit/Models/EntityTypeTest.php                       configuredInVault
tests/Feature/Views/Components/SidebarTest.php             sections, types, possessions
tests/Feature/Controllers/App/Vaults/PossessionControllerTest.php
```

## Order of work

1. Scope and its tests, `visibleInVault` rewritten on top of it; existing scope tests stay green.
2. Sidebar component and view; rewrite the sidebar tests that assumed the flat list.
3. Route, controller, screen, controller test; `RouteProtectionTest` picks the route up on its own.
4. Translations, guide, FEATURES.md, screenshot.
5. PHPStan, Pint, whole suite (a shared component changes every vault screen).
