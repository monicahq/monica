# Data model: Detect and merge duplicate contacts

## Added to `fields`

| Column | Type | Meaning |
| --- | --- | --- |
| `is_identifying` | boolean, default false | whether two records sharing this value are the same one, so the pair is proposed |

Frozen with its published version, since the row hangs off `section_version_id`. `DefaultFieldEnum`
gains `isIdentifying()`, true for `Email` and `Phone`, which `CreateDefaultTemplate` reads beside `is_unique`. Rejected alternatives are in [research.md](research.md).

## Added to `contacts`

| Column | Type | Meaning |
| --- | --- | --- |
| `match_key` | string (64), indexed with `vault_id` | folded last name, or the only name token, used to group contacts before their names are compared |

Written in the `saving` hook beside `sort_key`, through a new `LocaleCollation::matchKey()`. Empty when the contact has no name, and an empty key is never grouped.

## `contact_duplicate_candidates`

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | id | primary key |
| `account_id`, `vault_id` | foreign keys | the compartments the pair sits in |
| `contact_id`, `other_contact_id` | foreign keys | the pair, `contact_id` always the lower of the two, so a pair is one row |
| `score` | unsigned tinyint | how strong the resemblance is, 0 to 100 |
| `reasons` | json | one entry per signal that fired, each naming its kind and, for an identifying field, the field key and the shared value |
| `dismissed_at` | timestamp, nullable | when somebody said these are two people, null while the pair stands |
| `dismissed_by_user_id` | foreign key, nullable | who said so |
| `computed_at` | timestamp | when detection last looked at this pair |

Unique on `contact_id, other_contact_id`, indexed on `vault_id, dismissed_at, score` for the screen's
ordering. Detection upserts by pair and deletes only rows that stopped matching and were never dismissed.

## `contact_merges`

| Column | Type | Meaning |
| --- | --- | --- |
| `id`, `uuid` | id, string | primary key, and the identifier the API and the undo route use |
| `account_id`, `vault_id` | foreign keys | the compartments the merge happened in |
| `kept_contact_id` | id, no foreign key | the contact that survived |
| `merged_contact_id` | id, no foreign key | the contact that was absorbed, soft deleted rather than destroyed |
| `resolutions` | json | how each conflict was decided, by field key and repeater row, and which collisions were kept or dropped |
| `ledger` | json | what moved, each entry naming the table, the row and the record it came from, so the undo can replay it backwards |
| `snapshot_id` | foreign key, nullable | the survivor's state before anything was written, null once pruned |
| `merged_by_user_id` | foreign key, nullable | who ran it, null once that user is gone |
| `undone_at` | timestamp, nullable | when it was reversed, null while it stands |
| `undone_by_user_id` | foreign key, nullable | who reversed it |

Indexed on `merged_contact_id, undone_at`, the lookup that redirects an absorbed contact's address.
`RouteKey` is the uuid, and `SnapshotReasonEnum::Merge` already exists for the snapshot. Neither
contact column carries a foreign key: the record of a merge is history and outlives what it names, as
a log row does, so purging a contact for good must not take the explanation with it.

## Rules the models carry

- A candidate pair MUST hold two different contacts of one vault, and neither may be soft deleted.
- A merge MUST NOT name one contact twice, MUST keep both in one vault, MUST NOT absorb a `is_me` one.
- An undo MUST refuse unless `undone_at` is null and the absorbed contact is still retrievable.
