# Implementation Plan: Empty list of an invented type

**Branch**: `2026-11-19-changelog-panda-redesign` | **Date**: 2026-11-19 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/138-entity-empty-state/spec.md`

## Summary

Replace the empty slot of the entities list with design 1b: three fading placeholder rows marked with the
type's icon on a tint of its colour, then `x-empty-state` holding a title, a sentence (the type's
description, or a generic one) and a secondary add button that is refused the same way the header button
is. Presentation only: no action, no API, no migration. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel, Blade, Tailwind CSS v4, Alpine.js

**Primary Dependencies**: existing components `x-list-panel`, `x-empty-state`, `x-button`, `x-entity-icon`

**Storage**: N/A (reads `entity_types.description`, `icon`, `colour`, already stored)

**Testing**: PHPUnit: `EntitiesViewModelTest` (copy and fallbacks), `EntityControllerTest` (screen renders)

**Target Platform**: web, light and dark themes, down to 320px

**Project Type**: web application (Laravel monolith)

**Performance Goals**: no extra query; the type is already loaded

**Constraints**: WCAG 2.2 AA; rows `aria-hidden`; every new string in every locale

**Scale/Scope**: one screen, one view model, two test files, translations, one changelog entry

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. No write; copy lives in `EntitiesViewModel` |
| II. Simplicity | Pass. Rows inline in the one view that uses them; no new component (one file, under the bar) |
| III. Tested | Pass. View model methods and the rendered screen are tested |
| V. Localized | Pass. Three new strings through `__()`, translated by Sonnet subagents |
| VI, VII, VIII | Not touched |
| IX. Accessible | Pass. Decorative rows hidden; refused button stays with `aria-disabled` and `aria-describedby` |
| X. API | Pass. No new capability; the API already lists records and exposes the description |
| XI. Short record | `data-model.md` and `contracts/` omitted: no data and no interface change |

Re-checked after design: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/138-entity-empty-state/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── tasks.md
```

### Source Code (repository root)

```text
app/ViewModels/Vaults/EntitiesViewModel.php        # emptyTitle(), emptyDescription(), firstAddLabel(), icon(), colour()
resources/views/app/vaults/entities/index.blade.php # the empty slot
tests/Unit/ViewModels/Vaults/EntitiesViewModelTest.php
tests/Feature/Controllers/App/Vaults/EntityControllerTest.php
lang/*.json                                        # through monica:localize
changelog/2026-11-19-*-empty-list-preview.md
```

**Structure Decision**: existing Laravel layout; the change stays in the screen and its view model.

## Complexity Tracking

None.
