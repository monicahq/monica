# Research: Paginate long lists

## R1. Numbered pages are one component over `paginate()`

- **Decision**: every numbered list calls `paginate()` (a `LengthAwarePaginator`, since page numbers and the "1 to 50 of 3,000" line need the total) with `withQueryString()`, and renders `<x-pagination :paginator :size :default>`. The component draws the summary line, Previous, the page window from `$paginator->onEachSide(1)->linkCollection()` (the only public way to the window; `elements()` is protected) skipping its first and last entries, Next, and the page size field. `$pairs->links()` on Duplicates is removed and nothing publishes `vendor/pagination`.
- **Rationale**: `links()` cannot hold the page size field, and its stock view is outside the project's components, tokens and `focus-ring` recipes. One component gives twelve screens one contract: `aria-current="page"` on the current number, Previous and Next kept on screen with `aria-disabled` at the ends (policies.md: a refused control stays), a short window that fits 375 px.
- **Alternatives**: `vendor:publish --tag=laravel-pagination` and restyle `tailwind.blade.php`. Rejected: a second place to draw a control, and no room for the size field.

## R2. The page size travels in `size`

- **Decision**: `app/Helpers/PageSize.php`: `CHOICES = [10, 20, 50, 100, 500]`, `ALL = 'all'`, `from(Request, int $default): int|string` (anything else answers the default), and `paginate($query, int|string $size)`, where All pages at `max(count, 1)` so the paginator stays length aware and the select can be told "all" without guessing from `perPage()`. Each view model keeps its own `PER_PAGE` as the default: People 50, the rest 20.
- **Rationale**: twelve screens read one rule. A size in the address survives reload, Previous and Next, and sharing a link.
- **Remembered per screen in a cookie** (added 2026-10-16): `from()` writes the size the address names into the encrypted `page_sizes` cookie under the route name, for a year, and reads it back when the address names none. The address wins over the cookie, the cookie over the screen's default. A per person preference (spec 041's `contact_list_*` columns, a JSON column, a preferences table) was weighed and set aside for the cookie: no migration, no action, no API surface, at the price of a choice that follows the browser rather than the person.
- **Alternatives**: links instead of a select. Rejected: the request asked for a field with a list.

## R3. The size field is a GET form that submits on change

- **Decision**: inside the component, `<form method="get">` with hidden inputs for `request()->except(['page', 'size'])`, an `x-select` named `size` that submits on change, and a submit button hidden once Alpine boots (`x-show="false"`) so the form still works without JavaScript. Changing the size lands on page 1 because no `page` input is carried.
- **Rationale**: a GET form keeps the filters, needs no JS to work and no route of its own.

## R4. Page links navigate; the People list keeps its targets

- **Decision**: the component's links are plain `<a href>`. An optional `target` prop adds `x-target.push="..."` to the links and the form; only the People list passes it (`contacts`, the id its chips, search and sort headings already push).
- **Rationale**: a page change belongs in the address and the history. Turbo makes the full navigation cheap on every other screen, and the People list stays consistent with its own controls.

## R5. Load more keeps `x-list-panel`, which learns to move focus and announce

- **Decision**: the four Load more lists use `x-list-panel` with `merge="append"`, `target` and `footer-id`, as the reminder detail history does (`reminders/show.blade.php:132`). The panel's Load more link carries `x-autofocus`, so Alpine Ajax focuses the new link after every merge (FR-009). The panel grows a `<p aria-live="polite" class="sr-only">` outside the merged targets, filled on `ajax:merged` from a `data-added` count the new footer carries. On the last page the footer holds an "End of list" line with `tabindex="-1" x-autofocus`, so focus still has somewhere to land.
- **Rationale**: `x-autofocus` is already in use (`_timeline.blade.php:39`) and needs no JavaScript file. A live region replaced by the merge is not announced, so it lives outside the targets.
- **Spec change**: the edge case "focus lands on the first row that page added" becomes the end-of-list line, which no row partial has to know about.

## R6. Repeated values page through a route of their own

- **Decision**: `GET vaults/{vault}/contacts/{contact}/fields/{field}/rows` (`contacts.fieldRows.index`, `ContactFieldRowController::index`) answers one page of a repeater's rows through `ContactFieldRowsViewModel` (10 a page; notes ordered `created_at` desc then `id` desc, every other repeater `position` then `id`). `ContactViewModel` reads page 1 through the same view model for each repeater on the read side, and keeps `repetitions()` loading every row for the edit form, which `SetFieldValues` then receives in full (FR-014).
- **Rationale**: `contacts.show` already uses `page` for the page tab and `timelinePage` for the timeline; a route per field keeps `page` free and gives the Load more link an address. One query per repeater field on the read side replaces one query for all of them, and a section rarely holds more than a few.
- **Alternatives**: a window function in the single query. Rejected: three dialects to guard.

## R7. Reminder history reuses the API's helper

- **Decision**: `ContactRemindersViewModel` takes the history rows from `ContactReminderDates::page($user, $contact, 'history', 25, $page)`, which already pages in the database, and reports its total for the filter chip. `ContactDeadlinesBlock` never shows history and stops computing it.
- **Rationale**: one query path for the web and the API, and the contact page stops reading every answered occurrence.

## R8. The delivery log pages its merge

- **Decision**: `ReminderHistory` takes a page: each source reads `page * 50 + 1` rows (one past the window, so a log fed by one source alone can still say there is more), the merge is sorted and the page's 50 kept, and `hasMore` is true when the merge held more than `page * 50`. The screen gets a hand-built simple `Paginator` as `MarketplaceViewModel::pageOrderedByName()` does.
- **Rationale**: three tables with three timestamps cannot be paged by one offset without a `UNION`, which is dialect work. Reading `page * 50` from each grows with the page, which a delivery log reaches slowly.

## R9. The notification panel pages where it loads

- **Decision**: `NotificationBarViewModel` uses `simplePaginate(25)`; `_list.blade.php` becomes an `x-list-panel` whose Load more targets the list and its footer. The initial `$ajax` load keeps replacing the container.

## R10. Strings stay in `lang/*.json`

- **Decision**: Previous, Next, Page :page, Rows per page, All, End of list and ":first to :last of :total" go through `__()`. `lang/*/pagination.php` is left alone: `monica:localize` reads JSON only. Numbers are formatted with the person's `NumberFormatEnum`, resolved as `FieldValueFormatter::numberFormat()` does.

## R11. The count query is accepted

Spec 001 chose `simplePaginate` for the admin lists to avoid `COUNT(*)`. Page numbers need the count, so those four screens, People and Duplicates move to `paginate()`. One count per page view on indexed account scoped tables is the price of a page number.
