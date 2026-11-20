---
description: "Task list for the possession timeline"
---

# Tasks: Possession timeline

**Input**: Design documents from `specs/140-possession-timeline/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: Included. Principle III of the constitution makes tests a merge condition. Follow
`.claude/rules/testing.md`: each rule once, in the layer that owns it; no assertion on classes or
markup; data from The Office (US), the Holiday Armadillo costume.

## Format: `[ID] [P?] [Story] Description`

---

## Phase 1: Setup

- [X] T001 Confirm the branch is `2026-11-19-possession-timeline`, stacked on `2026-11-19-relation-links`, and that `PossessionViewModel::sections()` still lists Timeline with a null URL

---

## Phase 2: Foundational (blocking)

**Purpose**: One place that decides what the timeline holds (research R2 to R4).

- [X] T002 [P] Create `app/Enums/PossessionTimelineSectionEnum.php` with the seven cases of `data-model.md` (`Transactions = 'transactions'`, `Insurance = 'insurance'`, `Valuations = 'valuations'`, `Provenance = 'provenance'`, `Maintenance = 'maintenance'`, `Loans = 'loans'`, `Locations = 'locations'`), each with `icon()`, `colour(): VaultColourEnum` and `label()` matching `PossessionViewModel::sections()`, implementing `Translatable`
- [X] T003 [P] Create `app/Helpers/PossessionTimelineEntry.php`, a readonly value class with the properties of `data-model.md` (`section`, `kind`, `record`, `date`, `precision`, `isCurrent`, `isAcquisition`) promoted in the constructor
- [X] T004 Create `app/Helpers/PossessionTimeline.php`: constructor takes the `Possession`; `entries(?PossessionTimelineSectionEnum $section = null): list<PossessionTimelineEntry>` reads the seven relations (eager loaded once), applies research R3 (skip provenance events referenced by a loan's `departure_provenance_event_id` or `return_provenance_event_id`, or carrying `possession_transaction_id`) and R4 (entries per record, planned loans none, returned loans two), marks `isCurrent` (latest valuation by `valued_on`, as `PossessionValuationViewModel` decides; the stay without `left_on`, as `PossessionLocationsViewModel` decides: read both before writing) and `isAcquisition` (transaction types purchase, gift received, inheritance, exchange), orders by date descending, undated last, ties by `created_at` then `id` descending, then filters by section; `counts(): array<string, int>` per section key, before any filter (depends on T002, T003)
- [X] T005 Create `tests/Unit/Helpers/PossessionTimelineTest.php`: one record per section gives one entry each in date order; a returned loan gives two, a planned loan none; a provenance event written by a loan or tied to a transaction gives none; undated entries come last; two entries on the same day come newest recorded first; `isCurrent` and `isAcquisition` land where research R4 says; the section filter and `counts()` (depends on T004)

**Checkpoint**: T005 passes.

---

## Phase 3: User Story 1, read what happened (P1) 🎯 MVP

**Goal**: The Timeline section opens and shows every entry, worded, grouped by year.

### Tests

- [X] T006 [P] [US1] Create `tests/Unit/ViewModels/Vaults/PossessionTimelineViewModelTest.php`: each kind's title, detail, badge, amount and note as in the spec's table (Purchase from Party City with Acquisition and its total; Own estimate with Current value and "+$25 from the previous" wording taken from the Valuations section; Lent to / Returned by Dwight Schrute; Moved to Storage space with Here now and "Kept in" for the first stay; About 2024 for a circa provenance date); groups by year newest first with "1 entry" / ":count entries" and a last "Date unknown" group; the empty line
- [X] T007 [P] [US1] In `tests/Unit/ViewModels/Vaults/PossessionViewModelTest.php`, assert that `sections()` gives Timeline the timeline URL and the number of entries

### Implementation

- [X] T008 [US1] Create `app/ViewModels/Vaults/PossessionTimelineViewModel.php` (constructor: `User $user`, `Possession $possession`, `?PossessionTimelineSectionEnum $section`), with `page(): PossessionViewModel`, `groups()`, `chips()`, `emptyLine()`; words each entry with the copy the sections already use (research R5), money through `PossessionValueFormatter`, dates through the user's date format, provenance dates through the precision wording of `PossessionProvenanceViewModel`, the group count with `trans_choice(':count entry|:count entries', $count)`; each entry carries the URL of its section page (depends on T004)
- [X] T009 [US1] In `app/ViewModels/Vaults/PossessionViewModel.php`, give the Timeline section its URL (`timelineUrl()`) and its count from `PossessionTimeline::counts()` summed (depends on T004)
- [X] T010 [US1] Add `Route::get('vaults/{vault}/possessions/{possession}/timeline/{section?}', [PossessionTimelineController::class, 'index'])->whereUuid('possession')->whereIn('section', [...the seven keys])->name('possessions.timeline.index')` in `routes/web.php` next to the other possession sections, and create `app/Http/Controllers/App/Vaults/PossessionTimelineController.php` with `index()` building the view model, modelled on `PossessionValuationController::index()` (depends on T008)
- [X] T011 [US1] Create `resources/views/app/vaults/possessions/timeline.blade.php` modelled on `valuations.blade.php` (shell, `_header`, `_sections` with `current` `timeline`, the heading `Timeline` with `x-help` titled "How the timeline works" and the help copy without the milestone sentence, the lead line), the year groups as `h3` with their count, and `resources/views/app/vaults/possessions/_timeline-entry.blade.php` for one `li`: decorative icon on a rail, section noun, date, `x-badge` when any, the title as an `h4` link to its section, amount and note when any, the detail line; `ol` per group; read `.claude/rules/components.md` and reuse components (depends on T010)
- [X] T012 [US1] Create `tests/Feature/Controllers/App/Vaults/PossessionTimelineControllerTest.php`: the page answers and shows "Timeline"; a possession reached through the wrong vault answers 404; an unknown section key answers 404 (depends on T011)

**Checkpoint**: US1 tests pass.

---

## Phase 4: User Story 2, narrow to one section (P2)

- [X] T013 [US2] In `PossessionTimelineViewModel`, `chips()` returns All plus one per section holding entries, in the column's order, each with its count and the URL `possessions.timeline.index` with or without `section`, and `isCurrent`; render them in `timeline.blade.php` as `x-chip` links with `:selected`, inside a `role="group"` named "Which entries to show"; extend `PossessionTimelineViewModelTest` for the chips (counts before filtering, no chip for an empty section, the filtered groups) (depends on T011)

---

## Phase 5: User Story 3, back to the section (P2)

- [X] T014 [US3] Make sure each entry's link points at its section page, with the record's anchor where the section page has one for its rows (read each section view for an `id` on its rows; link to the page alone where there is none); assert the URL per section in `PossessionTimelineViewModelTest` (depends on T008)

---

## Phase 6: API and MCP

- [X] T015 Create `app/Http/Controllers/Api/Possessions/PossessionTimelineController.php` with `index()` answering `{data, meta}` as `contracts/timeline-api.md`, paging like its siblings, `section` validated with `Rule::enum(PossessionTimelineSectionEnum::class)`, the record link per section's API route; add `Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/timeline', …)->name('possessions.timeline')` in `routes/api.php` (depends on T004)
- [X] T016 Create `tests/Feature/Controllers/Api/Possessions/PossessionTimelineControllerTest.php`: structure and order; the `section` filter; 422 on an unknown section; 404 through another account and through the wrong vault (depends on T015)
- [X] T017 Create `app/Mcp/Tools/ListPossessionTimeline.php` (`#[IsReadOnly]`, mirrors `api.v1.possessions.timeline`, schema `vault`, `possession`, `section`, `page`, `per_page`), modelled on a sibling possession list tool, registered in `app/Mcp/Servers/MonicaServer.php` (depends on T015)
- [X] T018 Document the endpoint in the possessions file under `resources/docs/api/`, add its case to `tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php`, raise the section count in `ApiDocsControllerTest`, and add an entry to `resources/docs/api-changelog/2026-11-19.php`, with the `api-docs-writer` skill (depends on T015)

---

## Phase 7: Polish

- [X] T019 Run `php artisan monica:localize` and translate the new keys in every `lang/*.json` with subagents on model sonnet (translations skill, `.claude/rules/translations.md`); `--check` passes
- [X] T020 [P] Update the possession guide under `docs/portal/en` to describe the Timeline, with the `writer` skill; point the `x-help` of the screen at it
- [X] T021 [P] Write the changelog entry with the `changelog` skill
- [X] T022 Pint, PHPStan, the whole suite (`PAO_DISABLE=true php artisan test --parallel`)
- [X] T023 Run the `rules-reviewer` and `accessibility-reviewer` agents, check their evidence, fix what holds
- [ ] T024 Run `definition-of-done`, then commit (`feat(possessions): possession timeline`) when asked

---

## Dependencies

- T002, T003 → T004 → T005; everything after needs T004.
- US1: T008 → T009, T010 → T011 → T012. US2 and US3 need T011. Phase 6 needs T004 only and can run beside US1.

## Implementation strategy

Phase 2, then US1 (the screen): shippable alone. Then the chips and the links, then the API and MCP, then polish, one commit on the stack.
