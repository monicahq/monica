# Implementation Plan: Possession timeline

**Branch**: `2026-11-19-possession-timeline`, stacked on `2026-11-19-relation-links` | **Date**: 2026-11-19 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/140-possession-timeline/spec.md`

## Summary

Open the Timeline section of a possession, which is listed today and marked for later. A helper reads
the seven sections of one possession and returns ordered, deduplicated entries; a view model words them
for the screen, grouped by year with section chips; an API endpoint and an MCP tool return the same
entries as values. Nothing is stored. The design `Possession Timeline.dc.html` is followed with the
possession pages' existing parts, without milestones.

## Technical Context

**Language/Version**: PHP 8.5, Laravel (installed major)

**Primary Dependencies**: Blade, Alpine.js, Tailwind CSS; no new dependency

**Storage**: None new. Reads `possession_transactions`, `possession_insurance_records`,
`possession_valuations`, `possession_provenance_events`, `possession_maintenance_records`,
`possession_loans`, `possession_location_stays`.

**Testing**: PHPUnit; `php artisan monica:localize --check`

**Target Platform**: Web application, JSON API, MCP server

**Project Type**: Laravel web application

**Performance Goals**: The timeline of a possession with 300 records opens as quickly as its overview (SC-004)

**Constraints**: No query string for the filter (routing rule); WCAG 2.2 AA; one word for one meaning with the sections' existing copy

**Scale/Scope**: One screen, one helper with its entry object, one view model, one web route, one API endpoint, one MCP tool

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Check | Result |
| --- | --- | --- |
| I. Actions hold the business logic | Nothing is changed, so no action. What makes an entry is read logic in one helper (R2), shared by the screen and the API. | Pass |
| II. Simplicity | One helper, one entry object, one view model, one partial for the list. No component until a third screen draws a timeline like this. | Pass |
| III. Tested before merged | Helper, view model, web controller, API controller tests, listed below. | Pass |
| IV. Laravel conventions | Route with a constrained optional segment, resource-free JSON as sibling possession endpoints. | Pass |
| V. Localized | New strings translated by Sonnet subagents; counted phrase through `trans_choice`. | Pass |
| VI. Nothing changes under anybody's feet | Read only. | Pass |
| VII. Stable core | Possessions are the one place besides templates where fields live; no new mechanism. | Pass |
| VIII. Nothing disappears silently | Read only. | Pass |
| IX. Accessible | Ordered list, entries named by their title, chips as links with `aria-current`, decorative rail and icons. | Pass |
| X. Through the API | Endpoint and MCP tool (R7). | Pass |
| XI. Short written record | One changelog entry. | Pass |

Re-checked after Phase 1: no change.

## Project Structure

### Documentation (this feature)

```text
specs/140-possession-timeline/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── timeline-screen.md
│   └── timeline-api.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
app/
├── Helpers/
│   ├── PossessionTimeline.php              # new: reads the seven sections, orders, deduplicates
│   └── PossessionTimelineEntry.php         # new: one entry, values only
├── Enums/PossessionTimelineSectionEnum.php # new: the seven section keys, their icon, colour and route
├── ViewModels/Vaults/
│   ├── PossessionTimelineViewModel.php     # new: words, groups and chips for the screen
│   └── PossessionViewModel.php             # Timeline's URL and count in sections()
├── Http/Controllers/App/Vaults/PossessionTimelineController.php   # new: index
├── Http/Controllers/Api/Possessions/PossessionTimelineController.php          # new: index
└── Mcp/Tools/ListPossessionTimeline.php    # new, registered in MonicaServer

routes/web.php                              # possessions.timeline.index, {section?}
routes/api.php                              # api.v1.possessions.timeline

resources/views/app/vaults/possessions/
├── timeline.blade.php                      # new: the screen
└── _timeline-entry.blade.php               # new: one entry

resources/docs/api/…, resources/docs/api-changelog/2026-11-19.php
docs/portal/en/…possessions… (the possession guide), changelog/…, lang/*.json

tests/Unit/Helpers/PossessionTimelineTest.php                       # entries, order, dedup, undated
tests/Unit/ViewModels/Vaults/PossessionTimelineViewModelTest.php    # wording, badges, groups, chips
tests/Unit/ViewModels/Vaults/PossessionViewModelTest.php            # Timeline link and count
tests/Feature/Controllers/App/Vaults/PossessionTimelineControllerTest.php
tests/Feature/Controllers/Api/Possessions/PossessionTimelineControllerTest.php
tests/Feature/Controllers/App/Docs/* (section count, return fields)
```

**Structure Decision**: The existing layout. The helper goes in `app/Helpers`, next to
`PossessionValueFormatter`, because the screen and the API both read it.

## Complexity Tracking

No violation to justify.
