# Implementation Plan: Family tree panel

**Branch**: `2026-09-13-family-tree-panel` | **Date**: 2026-09-13 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/032-family-tree-panel/spec.md`

## Summary

A panel beside the relationship groups draws one contact's line: recorded parents above, the contact
and whoever they are with now in the middle, recorded children below. It reads relationships that
already exist and writes nothing. Three tiers of flex cells joined by pseudo-element borders, all
centred on the contact, inside a fixed-size box that scrolls both ways. One new model scope supplies
the tier the model cannot yet select; the other two exist.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 12

**Primary Dependencies**: Blade, Tailwind CSS v4, Alpine.js (not needed here, the panel holds no state)

**Storage**: the existing `relationships`, `relationship_types` and `contacts` tables. No migration.

**Testing**: PHPUnit. Unit tests for the scope and the view model, feature tests for the view.

**Target Platform**: server-rendered web, desktop through phone

**Project Type**: web application, `app/` plus `resources/views`

**Performance Goals**: three queries per reading, one per tier, constant in the size of the family (FR-020, SC-004)

**Constraints**: WCAG 2.2 AA; fixed panel width and height; suite green on SQLite, MySQL and
PostgreSQL; PHPStan level 5

**Scale/Scope**: one panel on one existing page. One model scope, one view model, one partial, one
edit to an existing partial.

## Constitution Check

*GATE: passed before Phase 0, re-checked after Phase 1.*

| Principle | How this satisfies it |
| --- | --- |
| I. Actions hold the logic | Nothing is written, so no action. A view model reads and is consumed only by Blade. |
| II. Simplicity | No new component: `x-panel` already carries `subtitle` and `footer`. No geometry in PHP (research D1). |
| III. Tested before merged | Scope, view model and view each get tests, including both compartments and the empty line. |
| IV. Laravel conventions | Scope on the model beside its two siblings; view model under `ViewModels/Vaults`; kebab-case partial. |
| V. Localised and safe | Every string through `__()`, `monica:localize` run, all locales translated. Nothing destructive. |
| VI. Nothing changes underfoot | Reads only. |
| VII. Stable core | Relationship is one of the nine fixed concepts and is not extended. No new way to attach data. |
| VIII. Nothing disappears | Reads only. A withdrawn relationship type leaves the drawing unchanged (FR-008). |
| IX. Accessible | Focusable scroll region, tier names for a screen reader, no meaning in colour alone (research D4). |
| X. Everything through the API | No endpoint ships. Justified in research D7: no new capability, and the selection sits on the model. |
| XI. The record is short | No `data-model.md`, no `contracts/`: spec.md's Key Entities and requirements already carry both. |

**Result**: pass, before and after design. No entry in Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/032-family-tree-panel/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── quickstart.md        # Phase 1 output
└── tasks.md             # Phase 2 output (/speckit-tasks, not created here)
```

`data-model.md` and `contracts/` are deliberately absent. The feature adds no entity and no external
interface, and principle XI requires an artifact whose content another already carries to be omitted.

### Source code

```text
app/Models/Contact.php                                  # + scopeParentsOf(), beside its two siblings
app/ViewModels/Vaults/FamilyTreeBlock.php               # new: the three tiers and the empty line
app/ViewModels/Vaults/ContactViewModel.php              # + familyTree()
resources/views/app/vaults/contacts/_family-tree.blade.php   # new: the panel and the drawing
resources/views/app/vaults/contacts/_relationships.blade.php # two columns instead of one

tests/Unit/Models/ContactTest.php                       # the new scope
tests/Unit/ViewModels/Vaults/FamilyTreeBlockTest.php    # new
tests/Feature/Views/…                                   # the panel on the page, per testing.md
```

**Structure Decision**: the existing layout, unchanged. The panel is a partial included by
`_relationships.blade.php`, which becomes a grid of the groups and the aside, stacking below `xl`.
`FamilyTreeBlock` sits beside `ImmediateFamilyBlock` and `RelationshipsBlock`, which it resembles and
does not replace (FR-003).

## Complexity Tracking

No violations. The table is omitted.
