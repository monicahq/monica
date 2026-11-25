---

description: "Tasks for the year index of the dashboard timeline"
---

# Tasks: A year index for the dashboard timeline

**Input**: `specs/149-dashboard-timeline/` (spec.md, plan.md, research.md, data-model.md, contracts/, quickstart.md)

**Tests**: included. Principle III of the constitution makes them a merge condition, and FR-014 is only
proven by a test. Follow `.claude/rules/testing.md`: one rule tested once, in the layer that owns it.

**Organization**: by user story. US1, US2 and US3 are all P1; US4 is P2. R-numbers point at research.md.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel (different files, no dependency on an unfinished task)
- **[Story]**: the user story of spec.md the task serves

---

## Phase 1: Setup

- [X] T001 Exclude the `benchmark` group from the ordinary run in `phpunit.xml` (`<groups><exclude><group>benchmark</group></exclude></groups>`), so `php artisan test --group=benchmark` is the only way to run it (quickstart)

---

## Phase 2: Foundational (the shared reader)

**Purpose**: `LifeEventTimeline` is what every story reads through. No story starts before it passes.

- [X] T002 Create `app/Helpers/LifeEventTimeline.php`, constructed with `Vault $vault`, `CarbonImmutable $today`, `?LifeEventType $type`, `?Contact $person` (promoted, readonly), holding:
  - `slices()`: the single aggregate of R2 over `life_events` of the vault with `deleted_at` null, grouped by a year expression chosen by `match (DB::connection()->getDriverName())` (`sqlite`: `CAST(strftime('%Y', happened_on) AS INTEGER)`; `mysql`, `mariadb`: `YEAR(happened_on)`; `pgsql`: `CAST(EXTRACT(YEAR FROM happened_on) AS INTEGER)`), selecting `past_all`, `upcoming_all`, `past`, `upcoming` with the `{matches}` condition of R2 (`life_event_type_id = ?` for a type; `EXISTS` on `life_event_participants` with `participant_type` the contact morph class and `participant_id` for a person). Returns `list<array{slice: string, count: int, total: int}>`: `upcoming` first (sum of `upcoming`, total sum of `upcoming_all`), then each year with `past_all > 0`, newest first, `count` = `past`, `total` = `past_all`. Computed once (`once()`).
  - `total()`: sum of `past_all` and `upcoming_all` from the same result.
  - `defaultSlice()`: `upcoming` when its count is above zero, otherwise the newest year with a count above zero, otherwise `upcoming` (data-model).
  - `query(string $slice)`: `Builder<LifeEvent>` of the vault, the same `{matches}`, bounded as data-model's Slice table (`upcoming`: `happened_on >= today`, ascending `happened_on` then `id`; year `Y`: `>= Y-01-01` and `< min(today, (Y+1)-01-01)`, descending `happened_on` then `id`).
  - `types()`: `LifeEventType` whose id is in the distinct `life_event_type_id` of the vault's life events not deleted, `with('lifeEventCategory')`, ordered by category position then type position, deactivated types included. Computed once.
  - Class docblock: none beyond one line if needed (`.claude/rules/code-style.md`, Comments; helpers get none by default).
- [X] T003 Create `tests/Unit/Helpers/LifeEventTimelineTest.php` (The Office data): counts per slice with no filter, a type, a person, both; a year with past life events but none matching stays listed with count 0 and its total; a life event dated today counts as upcoming and one dated yesterday in the current year; the current year's future life events are counted under upcoming only; soft deleted life events and another vault's are left out; `total()`; `defaultSlice()` in its three cases; `query()` bounds and order for `upcoming` and for a year, and with both filters; `types()` lists only types in use, a deactivated one included, in category then type order. Run with `php artisan test --compact tests/Unit/Helpers/LifeEventTimelineTest.php`.

**Checkpoint**: the reader passes on SQLite. The year expression for MySQL and PostgreSQL is checked by reading it against the three drivers' documentation (search-docs), since the suite runs on SQLite only.

---

## Phase 3: User Story 1 - Upcoming, then the years (P1) MVP

**Goal**: the dashboard's timeline is the whole vault's, as design 1c: the index of slices beside one slice grouped by month, with newer and older, without filters yet.

**Independent Test**: a vault with two life events after today, three in 2025, two in 2023; the dashboard opens on Upcoming; `?slice=2023` shows 2023 only.

- [X] T004 [US1] Create `app/ViewModels/Vaults/DashboardTimelineBlock.php` with `User $user`, `Vault $vault`, `?string $slice`, `?string $person`, `?string $type`, `int $page = 1`, `?int $justAddedId = null`. It builds one `LifeEventTimeline` (today = `CarbonImmutable::now($user->timezone)->startOfDay()`; filters resolved in US2, null for now). Methods per data-model.md: `index()`, `title()`, `countLabel()` (`trans_choice(':count entry|:count entries', …)`), `months()`, `nextPageUrl()`, `newer()`, `older()`, `holdsNothing()` (`total() === 0`), `isEmptySlice()`. Rules:
  - The selected slice is `$slice` when it is `upcoming` or a year listed by `slices()`, otherwise `defaultSlice()` (spec edge case: an unknown year selects the default).
  - The page is `query($slice)->limit(101)->offset(($page - 1) * 100)`; the 101st row only sets `nextPageUrl()`; 100 entries listed (FR-013).
  - The people of the page in one read (R3): `Contact::query()` joined to `life_event_participants` on `participant_type` = contact morph class, `whereIn('life_event_participants.life_event_id', $ids)`, selecting `contacts.*`, `life_event_participants.life_event_id`, `life_event_participants.position`, ordered by position; deleted contacts excluded by the model's scope.
  - Each entry's type comes from `types()` keyed by id, never from a relation load.
  - Entry shape `{id, icon, tint, title, date, relative, with: list<{name, url}>, editUrl, isJustAdded}`: icon and tint as `ContactTimelineBlock::entry()` computes them (`Section::ICONS` fallback, `VaultColourEnum::tintFor`); title `title ?? type name`; date without the year in the user's date format (reuse the `withoutTheYear` approach); `relative` per R9 with `trans_choice()` ("today", "tomorrow", "yesterday", "in :count days", ":count weeks ago"…), only within 56 days, never for a precision other than `Day`; `with` every contact by position, `url` null when `is_partial`; `editUrl` `contacts.lifeEvents.edit` on the first participant by position who is not partial, null when none.
  - Months: key `Y-m`; label the month and year under upcoming, the month alone under a year (localized month name through Carbon in the app locale); life events with `date_precision` `Year`, `Decade` or `Unknown` go to a last group labelled `__('Sometime in :year', …)`; `Month` precision shows the month without a day; each group carries `countLabel` and `showsHeading` (false for the first group of a page that continues the previous page's last month, as `ContactTimelineBlock::yearThePreviousPageEndedIn()` does).
  - `index()`: `{label, count, url, isCurrent, startsYears}` per slice; `url` is `route('vaults.show', [...])` with `slice` and the current filters, no `page`.
  - `newer()`/`older()`: the neighbour in `slices()` order, labelled `__('Newer: :slice', …)` / `__('Older: :slice', …)`; null at the ends (US1 scenario 4).
- [X] T005 [US1] In `app/ViewModels/Vaults/VaultDashboardViewModel.php`: constructor gains `?string $timelineSlice`, `?string $timelinePerson`, `?string $timelineType`, `int $timelinePage = 1` (promoted, defaults null/1); `timeline()` returns a `DashboardTimelineBlock` built from them and `lifeEventJustAddedId`, for every vault (no Me contact needed); `timelineCount()` returns the block's `total()`; delete `lifeEvents()`, `fullTimelineUrl()`, `LIFE_EVENTS_SHOWN`, `TIMELINE_SHOWN`, and `lifeEventsSince()`/`lifeEventDate()` if nothing else uses them (grep first). `lifeEventForm()` and `meContact()` stay.
- [X] T006 [US1] In `app/Http/Controllers/App/Vaults/VaultController.php` `show`: pass `timelineSlice: $request->string('slice')->toString() ?: null`, `timelinePerson`, `timelineType`, `timelinePage: max(1, $request->integer('page', 1))` to the view model. No validation: the block ignores unknown values (R6).
- [X] T007 [US1] In `app/Http/Controllers/App/Vaults/VaultLifeEventController.php` `create`: add `slice` to the redirect to `vaults.show`, `upcoming` when the created life event's `happened_on` is on or after the user's today, otherwise its year (R10).
- [X] T008 [US1] Create `resources/views/app/vaults/_timeline.blade.php` (first line a one line `{{-- --}}` comment, then the `@var` block naming `DashboardTimelineBlock $timeline`, per code-style Comments): `<section id="dashboard-timeline">` drawing design 1c's right column and rail: a two column grid (`140px` index, the slice) that stacks on a narrow screen and mirrors in right to left (logical properties: `ps-`, `me-`, `start-`); the index as `<nav aria-label="{{ __('Years') }}">` of links with `aria-current="page"` on the selected one and a divider before the first year; the slice title and count; month groups as `<button aria-expanded aria-controls>` toggling a local Alpine `open` state, chevron icon through `x-icon`; the vertical rule and entries drawn as the design's `TimelineEvent` (32px tinted round icon tile, title, date, relative, "With" names as links or plain text) linking each title to `editUrl` when set; "Nothing matches here" and its line for `isEmptySlice()`; the empty state of US1 scenario 7 for `holdsNothing()` with no index; the "Newer"/"Older" links in the foot; a "Show more" link to `nextPageUrl()`. Read `.claude/rules/components.md` and list `resources/views/components` first; use `x-link`, `x-icon`, `x-caption` and the `focus-ring` recipe where they fit. No class string asserted anywhere (testing rules).
- [X] T009 [US1] In `resources/views/app/vaults/show.blade.php`: the switch's timeline label becomes `__('Timeline')`; its tab panel keeps the "Add a life event" panel (`$lifeEventForm`, still only with a Me contact) and replaces the `contacts._timeline` include and "View full timeline" with `@include('app.vaults._timeline', ['timeline' => $viewModel->timeline()])`; delete the sidebar's "Recent life events" `x-page-section` and the `$lifeEvents` variable. Show the switch even without a Me contact.
- [X] T010 [P] [US1] In `app/ViewModels/Vaults/ContactTimelineBlock.php` delete the `isReadOnly` parameter and method and the `null` return of `nextPageUrl()` it drove; keep `perPage`. In `resources/views/app/vaults/contacts/_timeline.blade.php` delete the read only branches (R11).
- [X] T011 [P] [US1] Create `tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php`: opens on upcoming, or on the newest year when nothing is upcoming; an unknown `slice` selects the default; month grouping and labels (with the year under upcoming, without under a year); a "Sometime in" group for year precision; month precision without a day; relative labels at 0, 1, -1, 56 and 57 days; a partial contact has no url; `editUrl` on the first non partial participant, null when every participant is gone; 101 life events in a year give 100 entries and a next page, the second page continues the month without its heading; `newer()`/`older()` at both ends; `isJustAdded`; `holdsNothing()` on an empty vault; another vault's life events never listed.
- [X] T012 [P] [US1] In `tests/Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php` delete the tests of `lifeEvents()`, `fullTimelineUrl()` and the read only timeline (lines around 86 to 183), add one proving `timelineCount()` counts every life event of the vault; in `tests/Unit/ViewModels/Vaults/ContactTimelineBlockTest.php` delete the read only test (around line 254), keep the `perPage: 1` paging tests.
- [X] T013 [P] [US1] In `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php`: the dashboard renders "Upcoming" and the year of a past life event; replace any assertion on "View full timeline", "Your timeline" or "Recent life events". In `tests/Feature/Controllers/App/Vaults/VaultLifeEventControllerTest.php`: the redirect carries `slice=upcoming` for a future date and the year for a past one.

**Checkpoint**: the dashboard shows design 1c without filters; `php artisan test --compact tests/Unit/ViewModels/Vaults tests/Feature/Controllers/App/Vaults` passes.

---

## Phase 4: User Story 2 - One person, one kind of event (P1)

**Goal**: the person search and the type choice narrow the slice and every count; "Clear filters" resets them.

**Independent Test**: life events with Dwight, with Jim, of two types over three years; `?person=dwight-schrute&type={uuid}` narrows the slice and the index counts.

- [X] T014 [US2] In `app/ViewModels/Vaults/DashboardTimelineBlock.php` resolve the filters before building the reader: `type` is matched against `types()` by `uuid` (no extra read, R4); `person` is `Contact::query()->where('vault_id', $vault->id)->where('slug', $person)->first()` (one read, only when set). An unmatched value is ignored as though unset (spec edge case). Add `personChoice()` (`{slug, name}|null` and the search URL `vaults.timelinePeople.index`), `typeOptions()` (`list<{category, types: list<{uuid, name, isSelected}>}>` from `types()` grouped by `lifeEventCategory`), `filters()` (`isSet`, and the clear URL keeping `slice` and dropping `person`, `type`, `page`). Every URL the block builds (index, newer, older, next page) carries the resolved filters (FR-007, FR-009). `LifeEventTimeline` is built with the resolved `?LifeEventType` and `?Contact`.
- [X] T015 [P] [US2] Create `app/Http/Controllers/App/Vaults/VaultTimelinePersonController.php` `index(Request $request, Vault $vault): View`: validate `query` as `['nullable', 'string', 'max:255']` inline; list `Contact::query()->where('vault_id', $vault->id)->whereExists(LifeEventParticipant joined to a life event of this vault with deleted_at null, participant_type the contact morph class, participant_id = contacts.id)->matchingSearch((string) $request->string('query'), $request->user()->locale)->orderBy('sort_key')->limit(10)` mapped to `{slug, name}`; render `app.vaults._timeline-people`. Mirror `VaultLifeEventCandidateController` (R7).
- [X] T016 [US2] Add `Route::get('vaults/{vault}/timeline-people', [VaultTimelinePersonController::class, 'index'])->name('vaults.timelinePeople.index');` next to the vault life event routes in `routes/web.php`, inside the same group and middleware (read the `routes` skill first).
- [X] T017 [P] [US2] Create `resources/views/app/vaults/_timeline-people.blade.php`: the `role="listbox"` of at most ten `role="option"` items carrying `data-slug` and the name, with an empty line when nothing matches, inside the id the search targets.
- [X] T018 [US2] Person search script: read `resources/js/life-event-panel.js` (`searchPeople()`, `searchSubjects()`, `focusOn()`) and either extract the shared search-and-pick contract into one module both use, or create `resources/js/timeline-person-search.js` if the two share nothing beyond a fetch, per `.claude/rules/components.md` (the contract rule at the second file). It debounces input, fetches `vaults.timelinePeople.index` into its listbox with `$ajax`, moves through options with the arrow keys, picks with Enter, closes with Escape, sets the hidden `person` input and submits the filter form; "With anyone" clears it. Register it with Alpine where the other components are registered.
- [X] T019 [US2] In `resources/views/app/vaults/_timeline.blade.php` add the filter row above the slice title: a `<form method="get" action="{{ route('vaults.show', $vault) }}" x-target.push="dashboard-timeline">` holding a hidden `slice`, the person search (labelled input, the chosen person's name as its value, a hidden `person`), a `<select name="type" aria-label="{{ __('Event type') }}">` with `__('All event types')` then `<optgroup>` per category, submitting on change, and the "Clear filters" link shown only when `filters()` says one is set.
- [X] T020 [P] [US2] Extend `tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php`: an unknown or foreign `person` or `type` is ignored; a known one narrows the entries; `typeOptions()` grouping and selection; every URL keeps the filters; the clear URL keeps the slice.
- [X] T021 [P] [US2] Create `tests/Feature/Controllers/App/Vaults/VaultTimelinePersonControllerTest.php`: matches by part of a name; leaves out a contact with no life event and one whose only life event is deleted; ten at most; another vault's contacts left out.

**Checkpoint**: filters work through full page loads and through the form.

---

## Phase 5: User Story 3 - Fast however large the vault (P1)

**Goal**: a fixed number of reads, a fragment that recomputes nothing else, and the time target.

**Independent Test**: the read count of the timeline area is equal on a small and a larger vault and at most six.

- [X] T022 [US3] In `app/Http/Controllers/App/Vaults/VaultController.php` `show`: read `AlpineAjaxTargets::from($request)`; when the targets are exactly `['dashboard-timeline']`, or one `dashboard-timeline-page-{n}`, return `view('app.vaults._timeline', ['timeline' => $viewModel->timeline()])` (and the page fragment for the latter) without dispatching `RecordVaultVisit`; otherwise the whole page as today (R5). Follow how `ContactController@show` does it.
- [X] T023 [US3] In `resources/views/app/vaults/_timeline.blade.php`: index links, newer, older and "Clear filters" carry `x-target.push="dashboard-timeline"`; "Show more" targets `dashboard-timeline-page-{n}` with `x-merge="append"`; add a visually hidden polite live region (`aria-live="polite"`) holding the slice title and count, refreshed by each swap (FR-017).
- [X] T024 [P] [US3] In `tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php` add the read count test: `DB::enableQueryLog()` as `ImmediateFamilyBlockTest` does, render every method the view calls on a vault of 3 life events in 1 year and on one of 60 over 20 years with 30 people, a type and a person set, and assert the two counts are equal and at most 6 (FR-014, SC-001).
- [X] T025 [P] [US3] In `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php`: a request with `X-Alpine-Request` and `X-Alpine-Target: dashboard-timeline` renders the section and not the attention panel nor the favourites; `RecordVaultVisit` is not pushed (`Queue::fake()`).
- [X] T026 [P] [US3] Create `tests/Unit/Helpers/LifeEventTimelineBenchmarkTest.php` with `#[Group('benchmark')]`: bulk insert (`DB::table()->insert()` in chunks, not factories) 20,000 life events over 30 years, 2,000 contacts and 40,000 participations in one vault, then time `DashboardTimelineBlock` drawing the default slice, a year, a type, a person, and both, asserting each under 100 ms (FR-016). Run `php artisan test --compact --group=benchmark`. If the type or person cases miss, add a new migration with the index `(vault_id, life_event_type_id, happened_on)` on `life_events` per the `migrations` skill and run again (R8).

**Checkpoint**: the read count test passes in the suite; the benchmark passes in its group.

---

## Phase 6: User Story 4 - The API and the assistant (P2)

**Goal**: the API list takes `slice` and `life_event_type`; a counts endpoint and its MCP tool exist.

**Independent Test**: the list and the counts with a year, a type and a contact equal the dashboard's over the same data.

- [X] T027 [US4] In `app/Http/Controllers/Api/LifeEventController.php` `index`: validate `slice` as `['nullable', 'string', 'regex:/^(upcoming|\d{4})$/']` and `life_event_type` as `['nullable', 'string']`; resolve the type with `LifeEventType::query()->where('account_id', $vault->account_id)->where('uuid', …)->firstOrFail()`; when `slice` is set, build the list from `new LifeEventTimeline(...)->query($slice)` (today from the token owner's time zone) instead of the current query, keeping `with(LifeEventResource::RELATIONS)` and `paginate($perPage)`; without `slice`, the current order with the type filter added (contracts/api.md).
- [X] T028 [P] [US4] Create `app/Http/Controllers/Api/LifeEventSliceController.php` `index(Request $request, string $vault): JsonResponse`: the same vault, `contact` and `life_event_type` resolution as T027, answering `{data: list<{slice, count, total}>, meta: {total, today}}` from `slices()` and `total()` (contracts/api.md).
- [X] T029 [US4] Add `Route::match(['GET', 'QUERY'], 'vaults/{vault}/life-event-slices', [LifeEventSliceController::class, 'index'])->name('lifeEventSlices');` in `routes/api.php` next to the life event routes.
- [X] T030 [P] [US4] In `app/Mcp/Tools/ListLifeEvents.php` add `slice` ("upcoming, or a year such as 2024: only that part of the timeline, in its order") and `life_event_type` ("The id of a life event type, as list-life-event-types gives it.") to `schema()`; `handle()` stays `answerThroughApi()`.
- [X] T031 [P] [US4] Create `app/Mcp/Tools/ListLifeEventSlices.php` mirroring `lifeEventSlices`, read only, schema `vault` (required), `contact`, `life_event_type`; `handle()` looks up the vault through the trait and calls `answerThroughApi()`; register it in `app/Mcp/Servers/MonicaServer.php`. Read `.claude/rules/mcp.md` and the `mcp-development` skill first. No test file of its own.
- [X] T032 [P] [US4] Extend `tests/Feature/Controllers/Api/LifeEventControllerTest.php`: `slice=upcoming` and `slice=2024` filter and order; `life_event_type` filters; combined with `contact`; `slice=next` is 422; a type of another account is 404.
- [X] T033 [P] [US4] Create `tests/Feature/Controllers/Api/LifeEventSliceControllerTest.php` with a shared `$jsonStructure`: the shape; counts under a type and a contact; a year kept at zero; 404 for a foreign contact or type; another vault's life events not counted.
- [X] T034 [US4] Document the two parameters and the new endpoint in `resources/docs/api/22-life-events.php` and add `resources/docs/api-changelog/2026-11-22.php`, using the `api-docs-writer` skill.

**Checkpoint**: `php artisan test --compact tests/Feature/Controllers/Api` passes; `McpCatalogueTest` passes.

---

## Phase 7: Polish

- [X] T035 Run `php artisan monica:localize`, then translate every new string in every locale through sonnet subagents, per the `translations` skill; `php artisan monica:localize --check` passes (counted phrases with `:count` in every segment; no pinned wording in Blade).
- [X] T036 [P] Write the What's new entry with the `changelog` skill: the timeline covers the vault, the year index, the filters, and that "Recent life events" left the sidebar (principle VIII).
- [X] T037 [P] Run the `accessibility-reviewer` agent on `resources/views/app/vaults/_timeline.blade.php`, `_timeline-people.blade.php`, `show.blade.php` and the search script; fix what it confirms.
- [X] T038 [P] Run the `rules-reviewer` agent on the whole diff; fix what it confirms.
- [X] T039 Build the assets with `bun run build` and walk the "By hand" list of `quickstart.md` on `https://monica.test` with the iris `take-screenshot` skill, including a narrow window and a right to left locale.
- [X] T040 Run the `definition-of-done` skill: `vendor/bin/pint --dirty --format agent`, PHPStan, and the whole suite with `PAO_DISABLE=true php artisan test --compact --parallel`.

---

## Dependencies

- T001 before T026.
- T002, T003 (Phase 2) before every story.
- US1 (T004 to T013) before US2 and US3: both extend the block and the partial US1 creates.
- US2 (T014 to T021) before T024, whose read count test sets a person and a type.
- US4 (T027 to T034) depends only on Phase 2 and can run beside US1 to US3.
- Phase 7 last.

Within a story: T004 before T005, T008, T011; T005 before T006 and T009; T015 before T016 and T021; T017 and T018 before T019; T027 and T028 before T029 to T033.

## Parallel examples

- After Phase 2: one agent on US1, another on US4 (T027 to T034), since they share no file.
- In US1, after T004 to T009: T010, T011, T012, T013 together.
- In US2: T015, T017 together, then T020 and T021.
- In US4: T028, T030, T031 together, then T032 and T033.

## Implementation strategy

1. MVP: Phase 1, Phase 2, US1. The dashboard shows the vault's timeline by slice through full page loads.
2. US2 adds the filters, US3 the fragment swaps and the proof of the read ceiling. The three P1 stories ship together: the request makes filters and performance part of the feature, not a follow-up.
3. US4 can land in the same pull request or right after it; the constitution makes the capability incomplete without it.
