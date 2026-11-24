# Tasks: A one column dashboard for narrow screens

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md)

**Tests**: the plan's Tests section. The view model test is adjusted and one render test is added. Layout is checked by looking at it, never by a test (`.claude/rules/testing.md`).

## Phase 1: Setup

None. The branch and the views exist.

## Phase 2: Foundational

- [X] T001 Read `.claude/rules/components.md`, `.claude/skills/views/SKILL.md` and `.claude/skills/tailwindcss-development/SKILL.md` before touching `resources/views/app/vaults/show.blade.php`

## Phase 3: User Story 1, the dashboard on a laptop screen (P1)

**Goal**: one column capped at 960px, the people panels above the tabs, entries on a fixed grid. **Independent test**: US1 of the spec at 1180px.

- [X] T002 [US1] In `resources/views/app/vaults/show.blade.php`, remove `wide` from `<x-shell>` and the `lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]` wrapper, and keep the header and the paywall prompt first (research R1)
- [X] T003 [US1] In `resources/views/app/vaults/show.blade.php`, move the "Favourite people" and "Recently viewed" `x-page-section` blocks unchanged into a `grid grid-cols-[repeat(auto-fit,minmax(16.25rem,1fr))] items-start gap-4` placed after the paywall prompt and before the `#contact-timeline` section (and before `_attention` in the branch without a form) (R7)
- [X] T004 [US1] In `resources/views/app/vaults/_timeline.blade.php`, remove the `<nav aria-label="Years">` block and its `md:grid-cols-[8.75rem_minmax(0,1fr)]` wrapper, keeping the inner `flex min-w-0 flex-col gap-4` column as the direct child of the `@else` branch
- [X] T005 [US1] In `resources/views/app/vaults/_timeline.blade.php`, give each `<li>` the grid `grid-cols-[5.5rem_2rem_minmax(0,1fr)] gap-x-3.5 border-b border-hairline-soft py-3 last:border-b-0`. Its first cell holds `$entry['date']` (`text-sm font-semibold text-ink tabular-nums`) over `$entry['relative']` (`text-xs text-muted`, when not null), followed by the marker, then the existing hover card. Remove the date and the relative date from the title's `<p>` (R6)
- [X] T006 [US1] In `resources/views/app/vaults/_timeline.blade.php`, remove the `before:` vertical line classes from the month `<ol>`, move the details container from `col-start-2` to `col-start-3`, and keep the `sr-only` status `<p>` out of the grid flow (`col-span-full` or `sr-only` stays sufficient) (R6)

## Phase 4: User Story 2, choose a period from the filters (P1)

**Goal**: the Period select replaces the year column. **Independent test**: US2 of the spec, with and without scripts.

- [X] T007 [US2] In `app/ViewModels/Vaults/DashboardTimelineBlock.php`, make `index()` return `slice`, `label`, `count` and `isCurrent`, dropping `url` and `startsYears`, and update its `@return` shape (R4)
- [X] T008 [US2] In `tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php`, replace the `startsYears` and `$index[2]['url']` assertions with `assertSame(['upcoming', '2025', '2023'], array_column($index, 'slice'))`, and delete the two `index()[1]['url']` filter assertions
- [X] T009 [US2] In `resources/views/app/vaults/_timeline.blade.php`, replace the hidden `slice` input with `<x-select id="dashboard-timeline-period" name="slice" size="small" aria-label="{{ __('Period') }}" x-on:change="$el.form.requestSubmit()">`, with one `<option value="{{ $slice['slice'] }}" @selected($slice['isCurrent'])>{{ $slice['label'] }} · {{ trans_choice(':count entry|:count entries', $slice['count']) }}</option>` per `$timeline->index()` entry, placed first in the form (R2, R3)
- [X] T010 [US2] In `resources/views/app/vaults/_timeline.blade.php`, wrap the Period select, the record picker and the type select in `grid grid-cols-[repeat(auto-fit,minmax(11.25rem,1fr))] gap-2`, and put the `noscript` Filter button and "Clear filters" on a following `flex flex-wrap items-center gap-2` line rendered only when one of them shows (R5)
- [X] T011 [US2] In `resources/views/components/record-picker/index.blade.php`, compact branch only, replace `w-56` on the search `x-input` with `w-full`, and add `w-full` to the chosen button and the root `div`, so the picker fills its grid cell (R5)
- [X] T012 [US2] In `resources/views/app/vaults/_timeline.blade.php`, replace the "Nothing matches here" line with `__('No life event in this period fits the filters. The counts in the period list show where they are.')` (R8)
- [X] T013 [US2] In `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php`, add `it_offers_the_periods_of_the_timeline_as_a_choice`: life events in 2025 and 2023, get `vaults.show` with `slice=2023`, assert `name="slice"`, `aria-label="Period"`, `value="upcoming"`, and the `2023` option selected
- [X] T014 [US2] Run `php artisan test --compact tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php tests/Feature/Controllers/App/Vaults/VaultControllerTest.php`

## Phase 5: User Story 3, the dashboard on a tablet or a phone (P2)

**Goal**: the same column reflows down to 320px. **Independent test**: US3 of the spec at 820px and 375px.

- [X] T015 [US3] In `resources/views/app/vaults/_timeline.blade.php` and `resources/views/app/vaults/show.blade.php`, load the dashboard at 820px, 375px and 320px with the `take-screenshot` skill and fix any horizontal overflow: the tab row wrapping under "Add a life event", a long option label, a decade date in the 5.5rem column

## Phase 6: Polish and delivery

- [X] T016 Run `php artisan monica:localize`, then have Sonnet subagents translate "Period" and the new empty slice line into every locale, following `.claude/skills/translations/SKILL.md` and `.claude/rules/translations.md`; check with `php artisan monica:localize --check`, in `lang/*.json`
- [X] T017 Write the "What's new" entry with the `changelog` skill, saying the dashboard reads on narrow screens and the years are now chosen under "Period"
- [X] T018 Take screenshots at 1180px with the sidebar shown, 820px and 375px, and one in a right to left locale, with the `take-screenshot` skill, of the vault dashboard (`resources/views/app/vaults/show.blade.php`)
- [X] T019 Run `vendor/bin/pint --dirty --format agent` and PHPStan on `app/ViewModels/Vaults/DashboardTimelineBlock.php`
- [X] T020 Run the `accessibility-reviewer` and `rules-reviewer` agents over the changed views and component in `resources/views`, and check their evidence before acting on it
- [X] T021 Run the full suite with `composer test:parallel`, since `resources/views/components/record-picker/index.blade.php` is a shared component
- [X] T022 Run the `definition-of-done` skill over the change before any commit

## Dependencies

- T001 before everything.
- US1: T002 → T003; T004 → T005 → T006.
- US2: T007 → T008; T004 → T009 → T010 → T012; T011 is independent; T013 after T009; T014 after T008 and T013.
- US3 (T015) after US1 and US2.
- Phase 6 after T015; T022 last.

## Parallel opportunities

- T002–T003 (`show.blade.php`) run beside T004–T006 (`_timeline.blade.php`).
- T007–T008 (view model and its test) and T011 (component) run beside the view work.

## Implementation strategy

US1 alone already fixes the reported cramping, but it removes the only way to choose a year except paging. US1 and US2 therefore ship together as the minimum. US3 is a pass of checks over the same markup.
