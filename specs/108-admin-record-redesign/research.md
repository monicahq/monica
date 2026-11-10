# Research: Admin record screen redesign

Source: Claude Design project `b78dc791-8645-424e-b24c-635ab7aea4f3`, `Admin Record.dc.html`. The
shell is [106 research](../106-admin-redesign/research.md); the reading column is
[107 R1](../107-admin-account-redesign/research.md).

## R1. What a sentence is made of

- **Decision**: `AdministratorLogsViewModel::sentence(AdministratorLog $entry)` returns a list of
  parts `{text, as: text|code|link, url?}`. It translates the action's wording with each placeholder
  swapped for a marker, splits on the markers, and puts each value back as its own part. The view
  loops over the parts; Blade escapes every text, so no HTML is built in PHP.
- **Alternatives**: building an `HtmlString` in the view model (escaping by hand); keeping the plain
  `description` (the design's code face and link are lost).

## R2. Which placeholder becomes what

| Placeholder | Becomes | When |
| --- | --- | --- |
| `:flag` | code | always |
| `:name` | link to `admin.accounts.show` | `AdministratorActionEnum::namesTheAccountHolder()` is true and the entry's account still exists |
| `:name` | text | otherwise: a marketplace listing, an instance setting (stored as its readable description), a deleted account |
| `:url`, `:version` | text | always |

`namesTheAccountHolder()` is true for: user deactivated and reactivated, storage limit, lifetime
access granted and withdrawn, administrator access granted and withdrawn, flag switched for one
account. A new enum method rather than a list in the view model, so the next action has to decide.

## R3. Icons

- **Decision**: `AdministratorActionEnum::icon()`: flags `tag`, administrator access `key`, user
  (de/re)activation `profile`, storage `layers`, lifetime access `star`, instance settings and the
  Telegram bot `settings`, marketplace `tasks`. An unknown action uses `timeline`. The author line
  uses `device` for the console and `profile` for a person.

## R4. Flag wording

| Today | Becomes |
| --- | --- |
| Activated the flag :flag for :name | Switched on :flag for :name |
| Deactivated the flag :flag for :name | Switched off :flag for :name |
| Activated the flag :flag for every account | Switched on :flag for every account |
| Deactivated the flag :flag for every account | Switched off :flag for every account |

Stored rows keep the action key, not the wording, so every past entry reads the new sentence.

## R5. Days

- **Decision**: the view model takes the viewing `User`. `days()` groups the page's entries by
  `created_at` in the viewer's zone (`CarbonImmutable::setTimezone`) and returns, per day, `heading`
  ("Today", "Yesterday" or the date in the viewer's `date_format`, as `McpCallsViewModel` writes it),
  `summary` (the date for Today and Yesterday, then
  `trans_choice(':count action|:count actions', …)`), and its entries in order. Grouping in PHP over one page keeps the query driver neutral.
- `time(entry)` is `$viewer->time_format->format()` of the moment in the viewer's zone, given to
  `x-timestamp`; `ago(entry)` stays `diffForHumans()`.

## R6. Layout

- **Decision**: one `x-list-panel` (it owns the empty state, the count, the page size and the
  paging), holding per day a plain `<section>` (not a named region, so the landmark list stays short) whose header band (`bg-card`, `border-b`)
  carries the `h2` and its summary, then an `<ol>` of entries. The design draws one panel per day
  with the footer on the last; one panel keeps the existing component.
- The filter form stays above it, unchanged. "Write once" is `x-badge` with the `lock` icon in the
  `x-page-header` actions slot.
- **Alternatives**: a panel per day (the paging footer would need moving out of `x-list-panel`).

## R7. The design's "on behalf of account 1"

- **Decision**: left out. The account is linked in the sentence where it names a person; the meta
  line names the author alone (spec, Assumptions).
