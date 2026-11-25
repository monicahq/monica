# Tasks: Favourite people and Recently viewed as dashboard tabs

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md). Tests are required by constitution principle III.

## Phase 1: Setup

None. No dependency, migration or configuration is added.

## Phase 2: Foundational

None. Each story touches its own files, except `show.blade.php`, which US1 and US3 share in sequence.

## Phase 3: User Story 1 - People lists as tabs (P1)

- [X] T001 [US1] Add `favouritesCount()` and `tabs()` (key, label, icon, count for the four tabs, in spec order) to `app/ViewModels/Vaults/VaultDashboardViewModel.php`, per research R4
- [X] T002 [US1] Test `tabs()` order, labels, icons and counts, a favourites count beyond the cap of ten, and an unlisted favourite left out, in `tests/Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php`
- [X] T003 [US1] In `resources/views/app/vaults/show.blade.php`, remove the two panels above the timeline. Always render `#contact-timeline`, with `x-data` set to `lifeEventPanel(...)` when there is a form and to `{ view: 'timeline' }` otherwise (R2). Replace the segmented control with `x-tabs` and `x-tabs.item` fed by `tabs()` (R1)
- [X] T004 [US1] In `resources/views/app/vaults/show.blade.php`, add the `dashboard-panel-favourites` and `dashboard-panel-recent` tabpanels: sentence, `x-panel :flush="true"`, 32px avatars, `star-filled` on favourites, `x-empty-state` titled "Nobody here yet" and "Nobody opened yet" with the existing sentences as body, and "All favourites" kept (R3)
- [X] T005 [US1] In `resources/views/app/vaults/show.blade.php`, keep the "Add a life event" button `x-show` on the Timeline tab and only when a form exists, and keep "All reminders" `x-show` on the attention tab
- [X] T006 [US1] Update `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php`. Assert the four tab labels, that the people lists sit in their tabpanels, and that a vault without a Me contact renders the same tabs without "Add a life event". Rewrite any assertion that described the old panels or the segmented control

## Phase 4: User Story 2 - Timeline opens on Past (P1)

- [X] T007 [P] [US2] In `app/Helpers/LifeEventTimeline.php`, add `PAST = 'past'`. Insert it second in `slices()` from the existing aggregate, add `query('past')` (before today, newest first), and make `defaultSlice()` prefer Past when its count is above zero. Update the class docblock (R5)
- [X] T008 [P] [US2] Test in `tests/Unit/Helpers/LifeEventTimelineTest.php`: Past count, total and filtered count; slice order; `query('past')` bounds and order; default is Past; default falls back to the first non-empty slice, then to Upcoming
- [X] T009 [US2] In `app/ViewModels/Vaults/DashboardTimelineBlock.php`, make `labelOf('past')` return "Past". `months()` writes `F Y` headings for Past and places "Sometime in Y" groups after year Y's dated months. `newer()` and `older()` skip Past, and Past gets Newer as Upcoming and Older as null (R6)
- [X] T010 [US2] Test label, headings, undated group placement across two years, and Newer/Older from Past and from Upcoming in `tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php`
- [X] T011 [US2] In `resources/views/app/vaults/_timeline.blade.php`, put the first two index entries (Upcoming, Past) before the "Years" optgroup, and the rest inside it
- [X] T012 [P] [US2] In `app/Http/Controllers/Api/LifeEventController.php`, change the slice regex to `/^(upcoming|past|\d{4})$/`
- [X] T013 [P] [US2] Test `slice=past` in `tests/Feature/Controllers/Api/LifeEventControllerTest.php`, and that `past` is listed second in `tests/Feature/Controllers/Api/LifeEventSliceControllerTest.php`
- [X] T014 [P] [US2] Document `past` for the slice parameter, the slices response and its example in `resources/docs/api/22-life-events.php`, and add an entry to `resources/docs/api-changelog/2026-11-24.php`
- [X] T015 [P] [US2] Mention `past` in the descriptions of `app/Mcp/Tools/ListLifeEvents.php` and `app/Mcp/Tools/ListLifeEventSlices.php`, and in `app/ViewModels/Docs/McpDocsViewModel.php` if it repeats them

## Phase 5: User Story 3 - Keyboard and screen reader (P2)

- [X] T016 [US3] In `resources/views/app/vaults/show.blade.php`, give the tablist an `x-data` with the ordered keys, right and left arrows with wrapping, Home and End, and focus following the selection. Each item gets `id`, `aria-controls`, `x-bind:aria-selected` and `x-bind:tabindex`, and each panel `aria-labelledby` (R2). Check the bar wraps at 375px
- [X] T017 [US3] Assert the tab and panel ARIA contract (`role`, `aria-controls`, `aria-labelledby`, the tablist's name) in `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php`

## Phase 6: Polish

- [X] T018 Run `php artisan monica:localize`, and translate the five new strings in every `lang/*.json` with Sonnet subagents (R8). Then run `php artisan monica:localize --check`
- [X] T019 Write the What's new entry with the `changelog` skill
- [X] T020 Run `vendor/bin/pint --dirty --format agent`, PHPStan and `composer test:parallel`
- [X] T021 Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff and fix what they confirm
- [ ] T022 Walk `quickstart.md` by hand in the browser, at 1180px and at 375px
- [X] T023 Run the `definition-of-done` skill

## Dependencies

- US1 (T001–T006) and US2 (T007–T015) are independent, and either can ship first. Inside US2, T009 depends on T007, and T011 on T009.
- US3 (T016–T017) follows T003, since it edits the same tab bar.
- Polish follows all stories. The MVP is US1 plus US2, since both are P1.
- Can run in parallel: T007/T008 alongside T012–T015, and the whole of US2 alongside US1.
