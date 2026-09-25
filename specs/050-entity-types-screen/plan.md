# Implementation Plan: Contacts and more

**Branch**: `2026-09-25-entity-types-screen` | **Date**: 2026-09-25 | **Spec**: [spec.md](spec.md)

## Summary

`EntityTypesViewModel` gains the three fixed example profiles (R2) and the account's contact count
(R1), loses `summary()`, and words counts as profiles (R4). The index view is rebuilt on the
design's layout: header, example cards, a create band, then a panel holding a pinned Contacts row
above the existing sortable list (R3). Routes, controller, actions, the form and the API are
untouched, so there is no `data-model.md`, no `contracts/` and no `quickstart.md`.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (latest)

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js (the existing `sortableRows`)

**Storage**: none added; reads `vaults.contact_count` and `entity_types`

**Testing**: PHPUnit: the view model, and the controller's screen test

**Target Platform**: settings, desktop and 390px

**Project Type**: server rendered web application

**Performance Goals**: one aggregate query added (the contact sum)

**Constraints**: WCAG 2.2 AA; every string in every configured locale; no string outside this screen

**Scale/Scope**: 1 view model, 2 views, 2 test files, about 20 strings

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | How |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | Read only screen; the view model reads, nothing writes |
| II. Simplicity over abstraction | Pass | No component, no helper; examples are one array |
| III. Tested before merged | Pass | View model test for counts and examples, one screen test |
| IV. Laravel conventions | Pass | `@include` with named variables; existing routes |
| V. Localized and safe | Pass | Strings through `monica:localize`; counted phrases per `code-style.md`; nothing destructive |
| VI. Nothing changes under anybody’s feet | Pass | No structure or record touched |
| VII. Stable core | Pass | Contacts shown as the fixed concept it is, not made an entity type |
| VIII. Nothing disappears silently | Pass | Inactive types stay listed with their marker (FR-005) |
| IX. Accessible to everybody | Pass | Example bars `aria-hidden`, field names in `dl`, "Can’t be deleted" as text, reordering unchanged |
| X. Everything goes through the API | Pass | No capability added |
| XI. The written record is short | Pass | spec 106, plan under 100, research under 80 |

Re-check after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/050-entity-types-screen/
├── spec.md  plan.md  research.md
├── checklists/requirements.md
└── tasks.md                                          # /speckit-tasks
```

### Source Code (repository root)

```text
app/ViewModels/Settings/Account/EntityTypes/EntityTypesViewModel.php  # examples(), contacts row, countLabel, drop summary()
resources/views/app/settings/account/entity-types/index.blade.php     # the design's layout
resources/views/app/settings/account/entity-types/_row.blade.php      # plural name, count, Edit, Not active; no slug
tests/Unit/ViewModels/Settings/Account/EntityTypes/EntityTypesViewModelTest.php
tests/Feature/Controllers/App/Settings/Account/EntityTypes/EntityTypeControllerTest.php  # screen copy
lang/*.json                                                            # through monica:localize
```

**Structure Decision**: the existing layout. The Contacts row is written in `index.blade.php`, its
only caller. Components reused: `x-settings.page`, `x-page-header`, `x-page-section`, `x-panel`,
`x-badge`, `x-avatar-initials`, `x-icon`, `x-entity-icon`, `x-button`, `x-button.ghost`,
`x-sortable-row`.

## View model

| Method | Gives |
| --- | --- |
| `examples()` | three arrays: `icon`, `kind`, `tag`, `name`, `fields` (3 labels), `caption` |
| `contacts()` | `{countLabel}` from the summed vault counter |
| `types()` | as today, `countLabel` in profiles; `slug` kept for the routes |
| `hasTypes()` | whether the drag line shows (FR-006) |
| `countLabel()` | "No profiles yet" at zero, else `:count profile|:count profiles` |

## Tests

| Layer | Proves |
| --- | --- |
| View model | contact count sums every vault of the account and leaves out another account's; `countLabel` at 0, 1, 9; `hasTypes` false then true; `examples()` gives three with three fields each; existing order and scoping tests kept |
| Screen | "Contacts and more", "Create a profile type" linking to the new form, the Contacts row with "Built in" and "Can’t be deleted", a custom type's plural name and Edit link; the drag line absent with no custom type |

Removed: the two `summary()` tests, and the `wine-bottles` slug assertion.
