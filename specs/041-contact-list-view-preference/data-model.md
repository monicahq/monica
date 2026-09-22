# Data model: Contact list view preference

Phase 1. What is stored and what shape it has. Why it is stored this way is in
[research.md](./research.md).

## Columns added to `users`

Added in place to `database/migrations/0001_01_01_000000_create_users_table.php`, beside
`number_format` and before `uses_readable_addresses`, so the preferences stay together. A database
that predates the edit is rebuilt with `php artisan migrate:fresh`.

| Column | Type | Null | Comment written on the column |
| --- | --- | --- | --- |
| `contact_list_grouping` | `string(20)` | yes | grouping the people list was last looked at with, null until the user has looked at it |
| `contact_list_sort` | `string(20)` | yes | field the people list was last sorted by, null until the user has looked at it |
| `contact_list_direction` | `string(4)` | yes | direction the people list was last sorted in, null until the user has looked at it |

Null on all three is the state every user is in on the day this ships. No backfill runs, and none is
needed: null resolves to the default at read time.

Widths follow the longest value the enum can hold, with room to spare. `names-only` is ten characters,
`last-spoke` is ten, `desc` is four.

## Properties on `User`, and why there is no cast

Three `@property` lines join the model docblock, each `?string`. The three columns are **not** added to
`#[Fillable]`: the action assigns them by name, the way `UpdatePreferences` assigns `locale` and
`time_format`, and nothing mass assigns a user preference.

They are deliberately **not** cast to their enums, which is the one place this feature departs from
what the six preferences beside them do. An enum cast throws when the stored value is not a case, and a
stored value that is no longer a case is exactly what FR-012 says must fall back rather than fail. A
withdrawn `ContactSortEnum` case would otherwise take down the People list of everybody who had last
sorted by it, and take `GET me` with it.

So the columns hold plain strings, and every read goes through the resolution below, which is already
how the controller treats the same three values arriving from the query string.

## Resolution

One rule, applied to each of the three choices independently:

```
the value the address states
  ↳ else the value recorded on the user
    ↳ else the default
```

The defaults are `ContactGroupingEnum::Everyone`, `ContactSortEnum::Name` and
`SortDirectionEnum::Ascending`, which is Everybody sorted by Name, A to Z, and is what FR-010 asks for.

A recorded value the product no longer recognises resolves to the default for that one choice and
leaves the other two standing, which is FR-012. The three helpers do exactly this, and the rule is three lines wherever it is needed rather than a
class of its own:

```php
$grouping = ContactGroupingEnum::fromValueOrEveryone($filter ?? $user->contact_list_grouping);
$sort = ContactSortEnum::fromValueOrName($request->string('sort')->toString() ?: $user->contact_list_sort);
$direction = SortDirectionEnum::fromValueOrAscending($request->string('direction')->toString() ?: $user->contact_list_direction);
```

## `SortDirectionEnum`

New, at `app/Enums/SortDirectionEnum.php`. One class per file.

| Case | Value |
| --- | --- |
| `Ascending` | `asc` |
| `Descending` | `desc` |

It carries `fromValueOrAscending(?string $value): self` and `values(): array`, matching the two enums
beside it. It implements no `Translatable` and carries no `label()`: what a direction is called depends
on the sort it belongs to, and that pairing stays in `ContactsViewModel::directions()`.

It replaces `ContactsViewModel::DIRECTION_ASC` and `DIRECTION_DESC`, which are deleted, and it becomes
the second parameter of `Contact::scopeSortedBy()`, whose
`$direction === 'desc' ? 'desc' : 'asc'` goes with it.

## Enums reused unchanged

`ContactGroupingEnum` (`all`, `favourites`, `archived`, `names-only`) and `ContactSortEnum` (`name`,
`nickname`, `last-spoke`, `added`) are not touched. Their values are already the URL spellings and
already the values the JSON API validates.

## Reserved addresses

`ReservedAddressWords::forContact()` gains `ContactGroupingEnum::Everyone->value`, joining
`Favourites`, `Archived` and `NamesOnly`, which are already there. Its docblock is corrected: it
currently names only `favourites` and `archived` as route words, and now all four groupings sit in the
place a contact's own address would.

The list is read by `Contact::slugIsSpent()`, so it governs slugs built from now on. A database built
before this change could hold a contact slugged `all`, which the grouping route would then shadow;
rebuilding with `migrate:fresh`, which the schema edit requires anyway, regenerates the slug.
