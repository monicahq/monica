# Research: Favourite people and Recently viewed as dashboard tabs

## R1. Which tab component draws the bar

- **Decision**: `x-tabs` with `x-tabs.item`, the row of tabs on a full width rule that the admin screens use. Its items already take `icon` and `count`.
- **Rationale**: the design draws the design system's `Tabs` at 100% width, which is this component. The bar on the dashboard today is a segmented control written by hand, and it is the only one of its kind.
- **Alternatives**: keep the segmented control and add two buttons (rejected: not the design); write a new component (rejected: `x-tabs` exists).

`x-tabs.item` prints a static `aria-selected`. On the dashboard each item also takes `id`, `aria-controls`, `x-bind:aria-selected`, `x-bind:tabindex` and `x-on:click` through `$attributes`. Alpine's binding replaces the static value once it starts, and without script the server's value (Timeline) is the right one.

## R2. Where the tab state lives

- **Decision**: the `view` state stays on the `#contact-timeline` section. That section carries `lifeEventPanel(...)` when the vault has a Me contact, and `{ view: 'timeline' }` otherwise. The tablist gets its own small `x-data` holding the ordered keys and a `move(step)` / `select(key)` pair. Alpine writes `view` to the enclosing scope that owns it.
- **Rationale**: one section and one bar for both kinds of vault (FR-006). Today the vault without a Me contact has no bar at all. The keyboard logic for four tabs (arrows with wrapping, Home, End) is too long for the inline ternaries the two tab version used.
- **Alternatives**: a separate `dashboard-tabs.js` module (rejected: about ten lines, used once); keeping the selected tab in the address (rejected: the spec keeps Timeline on load).

## R3. The people panels inside the tabs

- **Decision**: the existing favourites and recently viewed markup moves into two `role="tabpanel"` blocks in `show.blade.php`, each in an `x-panel :flush="true"`, preceded by its sentence. Empty states use `x-empty-state`, with the existing sentences as the body. Favourite rows gain the `star-filled` icon, and avatars become 32px in both lists, as drawn.
- **Rationale**: the rows, the cap and "All favourites" are already right. Only where they stand changes.

## R4. Counts on the tabs

- **Decision**: `VaultDashboardViewModel` gains `favouritesCount()` (the size of `favouriteContacts()`, already loaded), and `tabs()` returning `list<array{key, label, icon, count}>` for the four tabs. The Recently viewed count is `count(recentlyViewed())`. The Timeline count keeps `timelineCount()`, and Needs your attention keeps `attention()->count()`.
- **Rationale**: the view model owns what the screen shows, and the labels are copy. No query is added: favourites are fetched once and memoised.

## R5. The "Past" slice

- **Decision**: add `LifeEventTimeline::PAST = 'past'`.
  - `slices()` inserts `{slice: past, count: sum(past), total: sum(past_all)}` right after Upcoming, from the aggregate it already runs.
  - `query('past')` is `happened_on < today`, ordered by `happened_on` descending then `id` descending.
  - `defaultSlice()` returns Past when its count is above zero, otherwise keeps today's first non-empty rule.
- **Rationale**: the dashboard and the API read the same class (its docblock says so), so both gain Past at once (FR-009). No extra query is added.
- **Alternatives**: Past only in `DashboardTimelineBlock` (rejected: the API and the dashboard would then disagree); Past as the API's `everything` (rejected: `everything` includes upcoming entries).

## R6. Past in `DashboardTimelineBlock`

- `labelOf('past')` is `__('Past')`.
- `months()` writes month headings as `F Y` for Past, as Upcoming does. Undated "Sometime in Y" groups are placed after the last dated month of year Y in descending order, mirroring the ascending placement Upcoming already does.
- `newer()` and `older()` walk only Upcoming and the years. From Past, Newer is Upcoming and Older is null. Upcoming's Older stays the most recent year.
- `_timeline.blade.php` puts the first two options (Upcoming, Past) outside the "Years" optgroup instead of one.
- `sliceOf()` still returns the year of a past date, so returning after adding a life event shows that year's slice with the entry focused (spec scenario 2.3).

## R7. API, documentation and MCP

- `Api\LifeEventController` regex becomes `^(upcoming|past|\d{4})$`.
- `resources/docs/api/22-life-events.php` describes `past` for the parameter and in the slices response.
- An entry is added under `resources/docs/api-changelog/`.
- `ListLifeEvents` and `ListLifeEventSlices` update their descriptions only. They already answer through the controllers (`.claude/rules/mcp.md`), so they need no new test.

## R8. Copy and translations

New strings: "Past", "People you marked as a favourite on their page, one step away.", "The last people whose page you opened in this vault.", "Nobody here yet", "Nobody opened yet". None of them carries a number, so `__()` is enough. They are translated with `monica:localize` by Sonnet subagents.
