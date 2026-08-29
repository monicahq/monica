# Phase 1 Data Model: Locale Aware Sorting

No new table, no new concept. Two derived columns on the two models that already carry a
`sort_name`, both rebuilt together when the reader's language changes.

## Contact, and Entity

Both existing. Each adds:

| Column | Type | Null | Purpose |
|---|---|---|---|
| `sort_key` | binary, 2048 | no | Collation key for `sort_name` under the account's language. Ordered and paged on. Bytewise comparable, so identical on every database. |
| `search_key` | string, 511 | no | `sort_name` lowercased and folded for the account's language. Matched inside with `like`. |

`sort_name` stays. It is the readable basis both keys derive from, and what a heading letter
is taken from.

Indexes:

- `(vault_id, sort_key)` added, and becomes what the contact list pages on. The query plan
  confirms it is used, with no temporary sort.
- `(vault_id, sort_name)` kept. Nothing orders on it now, but search still reads `sort_name`
  for a term typed with the accents.
- Nothing added for `search_key`: a `like '%term%'` cannot use an index, which is already
  true today.

Derivation rules:

- Both keys are derived. Neither is ever written by a user, accepted from a request, or put
  in a fillable list.
- Both are recomputed wherever `sort_name` is, which is the saving hook already calling
  `buildSortName()`.
- `sort_key` is capped at 2048 bytes. Measured worst case is 1546, so the cap is headroom
  rather than truncation, and it keeps the index inside MySQL's limit.
- A contact with no buildable `sort_name` already throws, so neither key can be empty.

`Entity` is included because leaving it out would order one kind of named thing by the
reader's alphabet and another by bytes.

## Account language

Existing `users.locale`, unchanged in shape. It gains a consequence: changing it rebuilds
every key in the account in the same transaction, and a failed rebuild rolls the language
change back with it.

One user per account means one language and one stored key per entry, which is what makes a
stored key viable. A share link is not an exception: it renders one person, not a list.

## Lifecycle

```text
name written or changed
  └─> sort_name rebuilt (existing)
        ├─> sort_key rebuilt     (collation key, account language)
        └─> search_key rebuilt   (folded text, account language)

account language changed
  └─> one transaction:
        ├─> users.locale updated
        ├─> every sort_key and search_key in the account rebuilt
        └─> commit, or roll back all of it including the language
```

## What gains nothing

Lists the reader arranged keep their `position` column and are untouched: templates, pages,
sections, fields, value lists and their items, vaults, entity types.

## The one list with no stored key

The marketplace catalogue. It is one shared list read by users of all nine languages, so
there is no single language to build a key for, unlike a vault whose account holds one
person. Its name order is therefore worked out when the catalogue is read: the names of
everything matching are ordered in PHP and the page taken from the result. Only the sort by
name pays for it; the other three are dates and counts, which every language reads alike.

The administrator filter has no stored key either, for the simpler reason that it is held
whole and never paged.
