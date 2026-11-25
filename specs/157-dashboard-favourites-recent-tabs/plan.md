# Implementation Plan: Favourite people and Recently viewed as dashboard tabs

**Branch**: `2026-11-24-dashboard-favourites-recent` | **Date**: 2026-11-24 | **Spec**: [spec.md](spec.md)

## Summary

The two people panels above the timeline become the third and fourth tabs of the dashboard's tab bar, drawn with `x-tabs`. The timeline gains a "Past" slice, which is now the default. `LifeEventTimeline` is the one place slices are defined, so the API and the MCP tools gain `past` along with the dashboard. All decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (latest), Blade, Alpine.js with Alpine Ajax, Tailwind CSS 4
**Primary Dependencies**: none added
**Storage**: no schema change. One aggregate query in `LifeEventTimeline` gains no new statement.
**Testing**: PHPUnit, run with `composer test:parallel`
**Target Platform**: web application, plus the JSON API and the MCP server
**Project Type**: Laravel monolith
**Performance Goals**: no extra query on the dashboard (favourites are memoised, Past comes from the existing aggregate)
**Constraints**: WCAG 2.2 AA; every new string in every shipped locale
**Scale/Scope**: one screen, one helper, one API endpoint pair, documentation

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold business logic | Pass. Nothing is written. The change is in a helper, view models and a view. |
| II. Simplicity | Pass. No new component or JS module. Tab keyboard logic is a small inline `x-data` (R2). |
| III. Tested | Pass. Tests for the helper, view models and API controller, plus one render test of the dashboard. |
| IV. Laravel conventions | Pass. |
| V. Localized | Pass. Five new strings, none counted (R8). |
| VI. Nothing changes under anybody's feet | Pass. Opening on Past changes a default view, not a record or a setting. |
| VII. Stable core | Pass. No new concept. |
| VIII. Nothing disappears silently | Pass. The years stay. The favourites and recent lists move and are not removed. |
| IX. Accessible | Pass if the tabs pattern in spec User Story 3 is kept. `accessibility-reviewer` runs before the commit. |
| X. Through the API | Pass. `past` reaches the slices and list endpoints, their docs and the MCP descriptions (R7). |
| XI. Short record | Pass. Spec 114, plan under 100, research 53. `data-model.md` and `contracts/` are omitted, since nothing about the data changes and the API change is two lines in R7. |

Re-check after design: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/157-dashboard-favourites-recent-tabs/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/Helpers/LifeEventTimeline.php                    # PAST slice, query, default (R5)
app/ViewModels/Vaults/DashboardTimelineBlock.php     # label, month headings, Newer/Older (R6)
app/ViewModels/Vaults/VaultDashboardViewModel.php    # favouritesCount(), tabs() (R4)
app/Http/Controllers/Api/LifeEventController.php     # slice regex (R7)
app/Mcp/Tools/ListLifeEvents.php                     # description (R7)
app/Mcp/Tools/ListLifeEventSlices.php                # description (R7)
resources/views/app/vaults/show.blade.php            # panels removed, four tabs, two people panels (R1–R3)
resources/views/app/vaults/_timeline.blade.php       # Upcoming and Past outside "Years" (R6)
resources/docs/api/22-life-events.php                # past documented (R7)
resources/docs/api-changelog/2026-11-24.php          # entry (R7)
lang/*.json                                          # five strings (R8)

tests/Unit/Helpers/LifeEventTimelineTest.php
tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php
tests/Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php
tests/Feature/Controllers/Api/LifeEventControllerTest.php
tests/Feature/Controllers/Api/LifeEventSliceControllerTest.php
tests/Feature/Controllers/App/Vaults/VaultControllerTest.php
```

**Structure Decision**: the existing Laravel layout. No new file except a changelog entry, if `resources/docs/api-changelog/2026-11-24.php` turns out to hold another change already (append to it otherwise).

## Test layers

| Layer | Proves |
| --- | --- |
| `LifeEventTimelineTest` | Past count and total, filtered count, order of `slices()`, `query('past')` bounds and order, default is Past, default falls back to Upcoming when Past is empty or filtered out |
| `DashboardTimelineBlockTest` | label "Past", month headings with the year, undated group placement across years, Newer/Older from Past and from Upcoming |
| `VaultDashboardViewModelTest` | `tabs()` order, labels and counts, favourites count beyond the cap, unlisted favourites left out |
| API controller tests | `slice=past` accepted and bounded, the slices list carries `past` second, an unknown slice still answers 422 |
| `VaultControllerTest` | the dashboard renders the four tabs with their tab and panel ARIA contract, and the people lists sit in panels. The same holds for a vault without a Me contact |

## Delivery

The changelog skill writes the What's new entry. The `definition-of-done` skill runs before the commit, with `accessibility-reviewer` and `rules-reviewer` on the diff.

## Complexity Tracking

None.
