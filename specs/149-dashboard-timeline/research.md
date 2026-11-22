# Research: A year index for the dashboard timeline

Every decision below serves FR-014: a fixed number of reads, at most six, whatever the vault holds.

## R1. One shared reader for the dashboard and the API

**Decision**: a helper, `App\Helpers\LifeEventTimeline`, built from a vault, the reader's today and the
filters (`?LifeEventType`, `?Contact`). It answers three questions and nothing else: the counts per slice
(`slices()`), the life events of one slice (`page(string $slice, int $page)`), and the types in use
(`types()`). The dashboard block and both API controllers call it.

**Rationale**: the spec makes the dashboard's counts equal the API's (US4, scenario 3). Two copies of
the counting query are how they drift, which `.claude/rules/mcp.md` names as the defect. A view model may
not be consumed outside Blade (principle I), so the query cannot live there. `app/Helpers` already holds
the same kind of reader for possessions (`PossessionTimeline`).

**Alternatives**: the query in the view model and copied into the API controller (two copies); an action
(actions write, this only reads).

## R2. Every count in one read

**Decision**: one aggregate over `life_events` of the vault, not deleted, grouped by year:

```sql
SELECT {year} AS year,
       SUM(CASE WHEN happened_on <  :today THEN 1 ELSE 0 END)               AS past_all,
       SUM(CASE WHEN happened_on >= :today THEN 1 ELSE 0 END)               AS upcoming_all,
       SUM(CASE WHEN happened_on <  :today AND {matches} THEN 1 ELSE 0 END) AS past,
       SUM(CASE WHEN happened_on >= :today AND {matches} THEN 1 ELSE 0 END) AS upcoming
FROM life_events WHERE vault_id = :vault AND deleted_at IS NULL GROUP BY year
```

`{matches}` is `1 = 1` without filters, `life_event_type_id = :type` for a type, and
`EXISTS (SELECT 1 FROM life_event_participants WHERE life_event_id = life_events.id AND participant_type = :morph AND participant_id = :contact)`
for a person, both joined with `AND`. From the rows: the years of the index are those with `past_all > 0`
(a year stays listed at zero under filters, US2 scenario 5); each year's count is `past`; "Upcoming" is the
sum of `upcoming`; the switch's count is the sum of `past_all` and `upcoming_all`.

`{year}` depends on the driver, since the application runs on SQLite, MySQL, MariaDB and PostgreSQL:
`CAST(strftime('%Y', happened_on) AS INTEGER)`, `YEAR(happened_on)`, and
`CAST(EXTRACT(YEAR FROM happened_on) AS INTEGER)`. A `match` on `getDriverName()` in one private method,
as `RebuildOrderingKeys` and `FlagState` already do.

**Rationale**: one read regardless of years (FR-015). The grouped result holds one row per year, thirty
at most in the spec's large vault.

**Alternatives**: one count per year (grows with years, forbidden); grouping by day and summing in PHP
(thousands of rows); a stored `happened_year` column (a schema change and a backfill for what one
expression does).

## R3. The slice in one read, its people in one more

**Decision**: the slice is `life_events` of the vault, not deleted, between the slice's bounds
(`>= today` for upcoming; `>= Y-01-01 AND < min(today, Y+1-01-01)` for a year), the same `{matches}`,
ordered `happened_on` then `id` (ascending for upcoming, descending for a year), `limit 101, offset (page-1)*100`.
The 101st row only says whether a next page exists (`simplePaginate`), so no count query is spent.

Types are not loaded with the slice: `types()` already holds every type used in the vault, so the block
maps each entry's `life_event_type_id` onto it. The people are one query on `contacts` joined to
`life_event_participants` for the ids of the page, selecting the participation's `life_event_id` and
`position`, contacts only, not deleted. Entities taking part are left out, as the contact page leaves
them out.

**Rationale**: eager loading `participants.participant` costs two reads (participations, then the morph
target) plus one for types; the join costs one.

## R4. The reads, counted

| # | Read | When |
| --- | --- | --- |
| 1 | Counts per slice (R2) | always |
| 2 | Types in use, with their category (`whereIn` a distinct subquery, `with('lifeEventCategory')`) | always |
| 3 | Categories of those types | always |
| 4 | The chosen person, by route key in the vault | a person is chosen |
| 5 | The slice (R3) | the vault holds a life event |
| 6 | The slice's people (R3) | the slice holds an entry |

Six at most, five without a person. The chosen type is found in read 2, so it costs nothing. The rest of
the dashboard (vault, user, attention, favourites) is outside the timeline area and is not counted; on a
fragment request (R5) it is not run at all.

The default slice (FR-004) needs no read: read 1 says whether "Upcoming" holds anything and which year
is the latest.

## R5. Only the timeline is recomputed on a change

**Decision**: the timeline lives in `<section id="dashboard-timeline">`. Its links and its filter form
carry `x-target.push="dashboard-timeline"`, so Alpine Ajax replaces only that element and pushes the
address (FR-009). `VaultController@show` reads `AlpineAjaxTargets::from($request)`, and when the only
target is `dashboard-timeline` it renders `app.vaults._timeline` alone, with the block and nothing else,
as `ContactController@show` already does for its panels. `RecordVaultVisit` is not dispatched for a
fragment.

"Show the next 100" uses `x-target="dashboard-timeline-page-{n}"` with `x-merge="append"`; a month cut
across two pages does not repeat its heading, using the rule `ContactTimelineBlock` already applies
across pages.

**Rationale**: view models are lazy, so a fragment that never calls the dashboard's other methods never
queries them (US3, scenario 2).

## R6. The address

**Decision**: query parameters on `vaults.show`: `slice` (`upcoming` or a four digit year), `contact`
(the contact's uuid, which survives a rename, unlike its slug), `type` (the life event type's uuid), `page`. All optional. An
unknown or foreign value is ignored (spec, edge cases), never a 404, because a shared link outlives the
records it names. `.claude/rules/code-style.md` allows a query string with a reason: filters on one
screen are that reason.

## R7. The person search

**Decision**: a new `VaultTimelineContactCandidateController@index` at `vaults/{vault}/timeline/candidates`, drawn by the `compact` variant of `x-record-picker`, answering
the matches as a list fragment, as `VaultLifeEventCandidateController` does for the life event panel:
`Contact::query()->where('vault_id')->whereExists(participation in a life event of the vault, not
deleted)->matchingSearch($query, $locale)->orderBy('sort_key')->limit(10)`. One read per keystroke
batch. The box is the panel's existing search pattern (input, `role="listbox"`, debounced fetch), not
`x-combo-box`, which picks a wording rather than searching.

**Alternatives**: a `<select>` of every person (thousands of options, and a read proportional to them).

## R8. Indexes

**Decision**: none added. `life_events (vault_id, happened_on)` serves R2 and R3 for a year range or the
upcoming range; the participant index `(participant_type, participant_id, life_event_id)` serves the
`EXISTS`. The type filter narrows rows already selected by that range. The quickstart's benchmark is the
check: a migration adding `(vault_id, life_event_type_id, happened_on)` is the fallback if the type
filter misses FR-016, and only then.

## R9. Dates

- Today is `CarbonImmutable::now($user->timezone)->startOfDay()`, as the dashboard already computes it.
- A life event with `date_precision` `Month` shows the month; `Year`, `Decade` or `Unknown` sit in a last
  group of their year, "Sometime in :year", with no relative label. `CreateLifeEvent` only writes `Day`
  today, so these come from imports.
- The relative label is built in the block, with `trans_choice()`, within 56 days (FR-011).

## R10. A life event just added

**Decision**: `VaultLifeEventController@create` already redirects to `vaults.show`; it adds
`slice` (upcoming, or the year of the life event it just created) to that redirect, so the new entry is
on the slice shown without a read (FR-019).

## R11. What leaves the dashboard

- The sidebar's "Recent life events" (`lifeEvents()`, `LIFE_EVENTS_SHOWN`) goes: the timeline's first
  entries are the same list (spec, assumptions).
- `timeline()`, `timelineCount()` and `fullTimelineUrl()` of `VaultDashboardViewModel` go, replaced by
  the block.
- `ContactTimelineBlock`'s `isReadOnly` parameter and the read only branches of
  `contacts/_timeline.blade.php` existed only for the dashboard and go with it. `perPage` stays: the
  block's own paging tests pass it. The contact page is
  unchanged.

## R12. The API and MCP

**Decision**: `LifeEventController@index` accepts `slice` and `life_event_type` (its uuid), next to its
`contact`, and orders as the slice does. A new `LifeEventSliceController@index` at
`vaults/{vault}/life-event-slices` answers the counts. Both call `LifeEventTimeline`. Unlike the
screen, an unknown `life_event_type` or `contact` is a 404 there, as the endpoint already answers for
`contact`. `ListLifeEvents` gains the two parameters; a new `ListLifeEventSlices` tool mirrors the new
route. Both are documented in `resources/docs/api/22-life-events.php` with an API changelog entry.

The API keeps its own paging (`page`, `per_page`, `meta.total`) rather than the screen's 100, since its
contract already promises a total.

The counts endpoint and `ListLifeEventSlices` are not paged, a decided exception to the paging rule of
`.claude/rules/mcp.md`: the answer holds one row per year that holds a life event, a few dozen at most,
and it comes from one aggregate that cannot be cut into pages without counting every year anyway.
