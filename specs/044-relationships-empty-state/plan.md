# Implementation Plan: Relationships empty state

**Branch**: `2026-09-23-relationships-empty-state` | **Date**: 2026-09-23 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/044-relationships-empty-state/spec.md`

## Summary

When a contact has no relationship, the Relationships tab draws one row per category the account
offers (design 1c), each naming up to four of its types and carrying an Add link that opens the add
form with the category's first type chosen. The categories come from `RelationshipTypeChoices`, the
same source the add form's picker reads, so the empty tab and the picker can never disagree. The add
form learns to accept a `type` in its URL and ignores one it does not offer.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (latest)

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js, Alpine Ajax

**Storage**: MySQL/SQLite through Eloquent; no schema change

**Testing**: PHPUnit (unit tests for view models, feature tests for the controller and the screen)

**Target Platform**: web application, the logged in vault screens

**Project Type**: web application (server rendered)

**Performance Goals**: the empty tab adds one query (categories with their active types, eager
loaded), already cached with `once()` inside `RelationshipTypeChoices`

**Constraints**: WCAG 2.2 AA; every string translated in every configured locale; right to left safe

**Scale/Scope**: one partial, two view models, one controller method, their tests

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | How |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | Nothing is written. Two view models read; no action changes |
| II. Simplicity over abstraction | Pass | Reuses `RelationshipTypeChoices` and `x-row`; no new class, no new component |
| III. Tested before merged | Pass | View model tests for the rows and the preselected type; one controller test for the URL; one screen test |
| IV. Laravel conventions | Pass, one justified query string | `?type=` on the add form, like its existing `?query=`. A route segment would make an optional preset a second URL for one screen |
| V. Localized and safe | Pass | New strings through `__()`; list separator through `__(', ')` as `ReminderDelivery` does; nothing destructive |
| VI. Nothing changes under anybody's feet | Pass | Reads the account's current choices; writes nothing |
| Accessibility (WCAG 2.2 AA) | Pass | Each Add names its category for assistive technology; rows in reading order |

Re-check after Phase 1: unchanged, all pass.

## Project Structure

### Documentation (this feature)

```text
specs/044-relationships-empty-state/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── relationships-tab.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
app/ViewModels/Vaults/
├── RelationshipTypeChoices.php      # groups carry the category key
├── RelationshipsBlock.php           # emptyCategories(), settingsUrl()
└── RelationshipFormViewModel.php    # accepts a preselected type id
app/Http/Controllers/App/Vaults/
└── ContactRelationshipController.php  # new() reads `type`
resources/views/app/vaults/contacts/
└── _relationships.blade.php         # the empty branch becomes design 1c
tests/Unit/ViewModels/Vaults/
├── RelationshipsBlockTest.php
├── RelationshipTypeChoicesTest.php
└── RelationshipFormViewModelTest.php
tests/Feature/Controllers/App/Vaults/
└── ContactRelationshipControllerTest.php
lang/*.json                          # through monica:localize
```

**Structure Decision**: the existing Laravel layout. The empty state is a branch of the tab partial it
already lives in, and its data is a method of the block that already feeds that partial.

## Complexity Tracking

No violation to justify.
