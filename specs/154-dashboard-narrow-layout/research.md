# Research: A one column dashboard for narrow screens

## R1. The column width comes from the shell, not from the dashboard

- **Decision**: drop `wide` from `<x-shell>` in `vaults/show.blade.php`. The non wide shell already caps the body at `max-w-240` (960px) and centres it, which is exactly the design's cap.
- **Rationale**: the cramping comes from `wide` (1400px) split into 2/3 and 1/3, with a second 140px column of years inside the 2/3. One column at 960px gives the timeline about 860px at 1180px with the sidebar shown, against about 420px today.
- **Alternatives**: keep `wide` and add `max-w-240` on the dashboard (two caps for one rule).

## R2. The Period choice is the filter form's `slice` field

- **Decision**: replace the form's hidden `slice` input with an `x-select` named `slice`, `aria-label` "Period", submitting the form on change exactly as the type select does. One option per entry of `DashboardTimelineBlock::index()`, selected when `isCurrent`.
- **Rationale**: the form already carries `slice`, `contact` and `type` to `filterUrl()` with `x-target.push.nofocus="dashboard-timeline"`, so the address, the back button, the kept filters and the `noscript` Filter button all come for free (FR-007). No controller, route or view model query changes.
- **Alternatives**: a menu of links (`x-actions-menu`): a second widget for what is a single choice, and it cannot carry the filters without a URL per option.

## R3. Option text

- **Decision**: `{{ label }} · {{ trans_choice(':count entry|:count entries', count) }}`, so "2024 · 16 entries". The phrase already exists and is translated; the middle dot is already used as a separator in views.
- **Rationale**: a bare "2024 · 16" is read aloud as two numbers. A screen reader reads an option's text, and `aria-label` on `<option>` is not reliably announced (FR-014).
- **Alternatives**: the design's "Upcoming · 60" (ambiguous when read aloud).

## R4. The view model's index loses what only the link column used

- **Decision**: remove `url` and `startsYears` from `index()`. `url()` itself stays, used by newer, older and the next page.
- **Rationale**: a key nothing reads is dead code. The divider has no equivalent in a `<select>`; the order carries it (spec, Assumptions).

## R5. The filters share a row

- **Decision**: the three controls sit in a grid `grid-cols-[repeat(auto-fit,minmax(11.25rem,1fr))]` (the design's 180px); the `noscript` button and "Clear filters" follow on their own line. The compact `x-record-picker` loses its fixed `w-56` for `w-full` on both its search field and its chosen button.
- **Rationale**: the dashboard is the only caller of the compact picker (`grep compact` over `resources/views`), so the change reaches no other screen. A fixed 224px field would overflow a 180px cell.

## R6. The entry grid

- **Decision**: `grid-cols-[5.5rem_2rem_minmax(0,1fr)]`: the date and, under it, the relative date; the marker; the existing hover card with the title, "With …" and the actions. The details move to `col-start-3`. Entries are separated by `border-b border-hairline-soft`; the `before:` vertical line goes.
- **Rationale**: 5.5rem holds "December 11" at `text-sm`; a decade ("Between 1990 and 1999") or "Date unknown" wraps inside its column rather than widening it. The date strings are unchanged (`dateOf()` already drops the year). A grid mirrors in right to left without extra classes (FR-013).
- **Alternatives**: the design's 72px column (too narrow for long month names in several locales).

## R7. The two people panels

- **Decision**: keep `x-page-section` and `x-panel` as they are and put the two sections in a grid `grid-cols-[repeat(auto-fit,minmax(16.25rem,1fr))] gap-4` (the design's 260px) above the tabs section.
- **Rationale**: reusing the components keeps the headings, empty sentences and footer link without new markup (`components.md`). The design draws the title inside the card; that is a difference of look, not of what the panels do.

## R8. The empty slice copy

- **Decision**: "No life event in this period fits the filters. Choose another period: the counts beside each one show where they are." replaces the sentence naming "the counts beside the years". Translated by Sonnet subagents, per the translations skill and the user's standing rule.
