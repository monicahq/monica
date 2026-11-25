# Implementation Plan: A one column dashboard for narrow screens

**Branch**: `2026-11-24-dashboard-narrow-layout` | **Date**: 2026-11-24 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/154-dashboard-narrow-layout/spec.md`

## Summary

The vault dashboard becomes one column capped at 960px by leaving the wide shell. The year column becomes a "Period" `<select>` that replaces the hidden `slice` field of the existing filter form. The two people panels move above the tabs, and every timeline entry gets a fixed grid (date, marker, content). Decisions and their reasons are in [research.md](research.md). No data, route, controller, API or MCP change.

## Technical Context

| | |
| --- | --- |
| **Language/Version** | PHP 8.5, Laravel 13, Blade, Tailwind CSS 4, Alpine.js with Alpine Ajax |
| **Storage** | None touched |
| **Testing** | PHPUnit: `DashboardTimelineBlockTest` (view model), `VaultControllerTest` (render) |
| **Target Platform** | Browsers from 320px wide; sidebar drawer under 1024px is the shell's, unchanged |
| **Project Type** | Web application, server rendered |
| **Performance Goals** | No new query. The index is already computed for the year column |
| **Constraints** | WCAG 2.2 AA, right to left, no horizontal scroll from 320px |
| **Scale/Scope** | Two Blade views, one component, one view model method, one string changed, one added |

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass: no logic moves, only markup and one view model array shrinks |
| II. Simplicity | Pass: the Period choice reuses the existing form (R2); no new component |
| III. Tested before merged | Pass: view model test adjusted, one render test added |
| V. Localized | Pass: two strings through `__()`, translated by `monica:localize` and Sonnet subagents; RTL via grid (R6) |
| VI. Nothing changes under anybody's feet | Not concerned: no record or structure changes |
| IX. Accessible | Pass: the select has an accessible name and spoken counts (R3); keyboard route unchanged; `accessibility-reviewer` runs before the commit |
| X. Everything goes through the API | Not concerned: no new capability. The API already takes `slice` (`LifeEventControllerTest`) |
| XI. Short record | Pass: `data-model.md` and `contracts/` omitted (no data, no interface); quickstart below |

Post-design re-check: unchanged.

## Project Structure

```text
specs/154-dashboard-narrow-layout/
├── spec.md
├── plan.md
├── research.md
└── checklists/requirements.md

resources/views/app/vaults/show.blade.php            # no `wide`; people panels above the tabs; one column
resources/views/app/vaults/_timeline.blade.php       # year nav removed; Period select; filter grid; entry grid; copy
resources/views/components/record-picker/index.blade.php  # compact branch: w-56 → w-full
app/ViewModels/Vaults/DashboardTimelineBlock.php     # index(): drop `url` and `startsYears`
tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php
tests/Feature/Controllers/App/Vaults/VaultControllerTest.php
lang/*.json                                          # via monica:localize
```

**Structure Decision**: Existing Laravel layout. Every change sits in the files the dashboard already uses.

## Changes by file

| File | Change |
| --- | --- |
| `show.blade.php` | Remove `wide`. Remove the `lg:grid-cols-[2fr_1fr]` grid. Order: header, paywall prompt, the people grid (R7), then the timeline section or the attention block followed by the timeline |
| `_timeline.blade.php` | Remove the `<nav aria-label="Years">` and the `md:grid-cols` wrapper. Form: Period select first (R2, R3), then the picker and the type select in the filter grid (R5), then a line with `noscript` Filter and "Clear filters". Entry: the three column grid, date column, hairline separators, details at `col-start-3`, the `before:` line removed (R6). New empty slice copy (R8) |
| `record-picker/index.blade.php` | Compact branch only: the field and the chosen button fill their cell |
| `DashboardTimelineBlock::index()` | Return `label`, `count`, `isCurrent` and the slice key the option sends (R4) |

The slice key is the `slice` value of `LifeEventTimeline::slices()` (`upcoming`, `2025` …), so the option's `value` is the same value the hidden input carried.

## Tests

- `DashboardTimelineBlockTest`: the assertions on `startsYears` and on the filters carried by `index()[n]['url']` go. They describe a link column that no longer exists, and the form now carries the filters. Assert the slice key and `isCurrent` instead.
- `VaultControllerTest`: one test that the dashboard renders a `slice` select labelled "Period", with the shown slice selected and an option per slice.
- Full suite through `composer test:parallel`, since a shared component changes (`components.md`).

## Quickstart validation

1. Seed a vault with favourites, a recently viewed person, life events after today and in two past years, then sign in.
2. Open the dashboard at 1180px, 820px and 375px. Check every acceptance scenario of the spec, in particular no horizontal scroll and an entry's title never sharing a line with its date.
3. With a person filter set, pick a year in Period, then reload and go back: the slice and the filter are kept.
4. Repeat 3 with scripts disabled, using the Filter button.
5. Switch the locale to a right to left one and check the entry grid mirrors.
6. Take screenshots with the `take-screenshot` skill at the three widths for the pull request.

## Delivery

The `definition-of-done` skill decides the rest: Pint, PHPStan, translations, and a changelog entry for a change people will notice. There is no API, MCP or documentation change, because no capability is added.

## Complexity Tracking

None.
