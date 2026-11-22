# Implementation Plan: A year index for the dashboard timeline

**Branch**: `2026-11-22-dashboard-timeline` | **Date**: 2026-11-22 | **Spec**: [spec.md](spec.md)

## Summary

The dashboard's timeline becomes the whole vault's, drawn as design 1c: an index of slices ("Upcoming",
then each year) beside one slice grouped by month, filtered by a person and a life event type, with the
counts following the filters. One reader, `App\Helpers\LifeEventTimeline`, counts every slice in a single
aggregate and returns a slice's query; the dashboard's new `DashboardTimelineBlock` and the API's list
and new counts endpoint both use it. A change of slice or filter swaps only `#dashboard-timeline`
through Alpine Ajax, and the controller renders that fragment alone. The timeline area takes at most six
reads whatever the vault holds. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, current Laravel, Blade, Tailwind CSS, Alpine.js and Alpine Ajax

**Primary Dependencies**: none added

**Storage**: no schema change (R8). SQLite, MySQL, MariaDB and PostgreSQL, so the one driver dependent
expression, the year of a date, is matched on the driver (R2).

**Testing**: PHPUnit. A `benchmark` group, excluded from the ordinary run, holds the timing test (quickstart).

**Target Platform**: the web application, its JSON API and its MCP server

**Project Type**: web application

**Performance Goals**: at most 6 reads for the timeline area, constant in the size of the vault
(FR-014); 100 ms of server time for 20,000 life events over 30 years and 2,000 people (FR-016)

**Constraints**: WCAG 2.2 AA; right to left; the contact page's timeline must behave exactly as before

**Scale/Scope**: 1 helper, 1 view model block, 3 controllers changed and 2 added, 2 routes, 1 MCP tool
added and 1 changed, 3 views, 1 script

## Constitution Check

| Principle | Result |
| --- | --- |
| I. Actions hold the logic | Pass. Nothing is written. The shared read lives in a helper, since a view model may not be consumed by the API (R1). |
| II. Simplicity | Pass. One reader for three callers (R1); one driver `match` rather than a stored column (R2); no index until the benchmark asks for one (R8). |
| III. Tested | Pass, see Tests. The read ceiling is a test, not a promise (FR-014). |
| IV. Laravel conventions | Pass. Query parameters for filters, justified under the code style's "unless you have a good reason" (R6). Inline validation. |
| V. Localised | New copy through `monica:localize`, counts through `trans_choice()` with `:count` in every segment. Right to left checked by hand (quickstart). |
| VI. Nothing changes under anybody's feet | Pass. Nothing is written; type names read as stored. |
| VII. Stable core | Pass. Life events and types keep their nature. |
| VIII. Nothing disappears silently | The sidebar's "Recent life events" and "View full timeline" go, replaced by the timeline that shows the same entries first (R11). A changelog entry says so. |
| IX. Accessible | The index is a `nav` marking the current slice; month headings are disclosure buttons; the person search is the panel's listbox pattern; a polite live region announces the slice after a swap; the layout stacks on a narrow screen (FR-017, FR-018). Reviewed by the `accessibility-reviewer` agent. |
| X. Through the API | Pass. The list gains `slice` and `life_event_type`, a counts endpoint and its MCP tool are added (R12). |
| XI. Short written record | Pass. |

Gate passes. Re-checked after design: unchanged.

## Changes

| File | Change |
| --- | --- |
| `app/Helpers/LifeEventTimeline.php` | New. `slices()`, `total()`, `defaultSlice()`, `query($slice)`, `types()` (R1 to R4) |
| `app/ViewModels/Vaults/DashboardTimelineBlock.php` | New. Everything the timeline area shows (data-model) |
| `app/ViewModels/Vaults/VaultDashboardViewModel.php` | `timeline()` returns the block, built from the request's parameters; `timelineCount()` reads `total()`; `lifeEvents()`, `fullTimelineUrl()`, `LIFE_EVENTS_SHOWN`, `TIMELINE_SHOWN` removed (R11) |
| `app/ViewModels/Vaults/ContactTimelineBlock.php` | `isReadOnly` parameter removed; `perPage` stays, its own paging tests use it (R11) |
| `app/Http/Controllers/App/Vaults/VaultController.php` | `show` validates nothing (unknown values are ignored by the block), passes `slice`, `person`, `type`, `page`; renders `app.vaults._timeline` alone for a `dashboard-timeline` target and skips `RecordVaultVisit` then (R5) |
| `app/Http/Controllers/App/Vaults/VaultTimelinePersonController.php` | New. `index` (R7) |
| `app/Http/Controllers/App/Vaults/VaultLifeEventController.php` | `create` redirects with `slice` (R10) |
| `app/Http/Controllers/Api/LifeEventController.php` | `index` validates and applies `slice` and `life_event_type` through the helper (R12) |
| `app/Http/Controllers/Api/LifeEventSliceController.php` | New. `index` (R12) |
| `app/Mcp/Tools/ListLifeEvents.php` | Two parameters |
| `app/Mcp/Tools/ListLifeEventSlices.php` | New, registered in `MonicaServer` |
| `routes/web.php` | `vaults.timelinePeople.index` |
| `routes/api.php` | `api.lifeEventSlices` |
| `resources/views/app/vaults/show.blade.php` | The switch says "Timeline"; its panel includes `_timeline`; the "Add a life event" panel stays above it; the sidebar's "Recent life events" removed |
| `resources/views/app/vaults/_timeline.blade.php` | New. The section of design 1c: index, filters, title and count, months, entries, empty states, newer and older, next page, live region. Tailwind utilities on the tokens `resources/css/app.css` already defines (`hairline-soft`, `shadow-nav-active`, `identity-*`), and existing components where `.claude/rules/components.md` lists one; the entry reuses the contact timeline's icon tile and "With" line |
| `resources/views/app/vaults/contacts/_timeline.blade.php` | Read only branches removed (R11) |
| `resources/js/timeline-person-search.js` | New, unless the life event panel's search can be extracted for both; decided at implementation by reading `life-event-panel.js`, following the contract rule of `.claude/rules/components.md` |
| `resources/docs/api/22-life-events.php`, `resources/docs/api-changelog/2026-11-22.php` | The two parameters and the counts endpoint |
| `phpunit.xml` | Exclude the `benchmark` group |
| `lang/*.json` | Through `monica:localize` only |
| What's new | An entry, through the `changelog` skill |

## Tests

| Test file | Proves |
| --- | --- |
| `tests/Unit/Helpers/LifeEventTimelineTest.php` | Counts per slice with and without each filter and both; a year kept at zero; the split at today in the reader's time zone; deleted life events and other vaults left out; `defaultSlice()` in its three cases; slice bounds and order |
| `tests/Unit/Helpers/LifeEventTimelineBenchmarkTest.php` | `benchmark` group: FR-016 on 20,000 life events |
| `tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php` | The read count is equal on a small and a larger vault and at most six; month grouping and headings; "Sometime in"; relative labels at 0, 1, 56 and 57 days; partial contacts unlinked; `editUrl` on the first non partial participant or null; next page at 101; newer and older at the ends; unknown parameters ignored; the just added entry marked |
| `tests/Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php` | Tests of the removed methods removed; `timelineCount()` counts the vault |
| `tests/Unit/ViewModels/Vaults/ContactTimelineBlockTest.php` | Read only tests removed |
| `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php` | The dashboard renders "Upcoming"; a `dashboard-timeline` request renders the section and not the attention panel |
| `tests/Feature/Controllers/App/Vaults/VaultTimelinePersonControllerTest.php` | Matches by name, only people with a life event, ten at most, another vault left out |
| `tests/Feature/Controllers/App/Vaults/VaultLifeEventControllerTest.php` | The redirect carries the slice |
| `tests/Feature/Controllers/Api/LifeEventControllerTest.php` | `slice` and `life_event_type` filter and order; an invalid slice is 422; a foreign type is 404 |
| `tests/Feature/Controllers/Api/LifeEventSliceControllerTest.php` | Shape, counts under filters, 404 for a foreign contact or type |

`RouteProtectionTest` and `McpCatalogueTest` cover the new routes and tool on their own.

## Project Structure

### Documentation (this feature)

```text
specs/149-dashboard-timeline/
├── spec.md
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── web.md
│   └── api.md
└── checklists/requirements.md
```

### Source Code

No new folder. Files are listed under Changes.

## Complexity Tracking

| Choice | Why | Simpler alternative rejected because |
| --- | --- | --- |
| A driver `match` for the year of a date | Four supported databases, one aggregate (R2) | One count per year breaks FR-015; a stored year column is a migration and a backfill |
| The fragment rendered alone by `VaultController@show` | A change must not recompute the dashboard (US3) | Letting Alpine Ajax cut the target out of the whole page runs every block of the dashboard |
| A `benchmark` test group excluded by default | FR-016 needs 20,000 rows, seconds of inserts | Timing in the ordinary run slows every run; timing by hand is not repeatable |
