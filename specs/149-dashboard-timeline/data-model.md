# Data model: A year index for the dashboard timeline

No table and no column change (research R2, R8). What follows is what the new reader returns.

## Slice

A string: `upcoming`, or a four digit year written as a string (`"2024"`).

| Slice | Holds | Order |
| --- | --- | --- |
| `upcoming` | `happened_on >= today` | `happened_on` ascending, then `id` ascending |
| `"Y"` | `happened_on >= Y-01-01` and `< min(today, (Y+1)-01-01)` | `happened_on` descending, then `id` descending |

Today is the reader's today, in their time zone. A slice holds only life events of the vault, not
deleted, matching every filter set.

## Filters

| Filter | Address parameter (web) | API parameter | Resolved to | Unknown or foreign value |
| --- | --- | --- | --- | --- |
| Slice | `slice` | `slice` | Slice | web: the default slice; API: 422 |
| Contact | `contact` (its uuid) | `contact` (as today) | `Contact` of the vault | web: ignored; API: 404 |
| Type | `type` (uuid) | `life_event_type` (uuid) | `LifeEventType` used in the vault | web: ignored; API: 404 |
| Page | `page` | `page`, `per_page` | integer from 1 | the first page |

## `LifeEventTimeline` (app/Helpers)

Built with `vault`, `today`, `?type`, `?person`.

| Method | Returns | Reads |
| --- | --- | --- |
| `slices()` | `list<array{slice: string, count: int, total: int}>`: `upcoming` first, then each year holding a past life event, newest first. `count` under the filters, `total` without them | 1 |
| `total()` | every life event of the vault, from the same read | 0 more |
| `defaultSlice()` | `upcoming` when its count is above zero, otherwise the newest year with a count above zero, otherwise `upcoming` | 0 more |
| `query(string $slice)` | `Builder<LifeEvent>` for the slice, filtered and ordered, for the caller to page | 0 |
| `types()` | `Collection<LifeEventType>` used by a life event of the vault, with `lifeEventCategory`, by category position then type position | 2 |

## `DashboardTimelineBlock` (app/ViewModels/Vaults)

Built with `user`, `vault`, and the raw `slice`, `person`, `type`, `page`, `justAddedId`. Next to
`VaultDashboardViewModel`, which hands it out through `timeline()`.

| Method | Gives the view |
| --- | --- |
| `index()` | `list<{label, count, url, isCurrent, startsYears}>`; `startsYears` draws the divider before the first year |
| `title()`, `countLabel()` | "Upcoming" or the year, and `trans_choice(':count entry|:count entries', …)` |
| `months()` | `list<{key, label, countLabel, showsHeading, entries}>`; the label carries the year under upcoming; a last group "Sometime in :year" for imprecise dates |
| entry | `{id, icon, tint, title, date, relative, with: list<{name, url}>, editUrl, isJustAdded}` |
| `nextPageUrl()` | the same address with `page + 1`, or null |
| `newer()`, `older()` | `{label, url}` or null |
| `personChoice()` | `{uuid, name}` of the chosen person, or null, and the search URL |
| `typeOptions()` | `list<{category, types: list<{uuid, name, isSelected}>}>` |
| `filters()` | whether one is set, and the `Clear filters` URL keeping the slice |
| `holdsNothing()` | the vault holds no life event at all (the empty state of US1, scenario 7) |
| `isEmptySlice()` | the slice holds nothing under the filters ("Nothing matches here") |

`editUrl` is `contacts.lifeEvents.edit` on the first participant by position who is not partial, or
null when none is left.

## State held in the browser only

The fold of each month group, in Alpine, reset on every swap.
