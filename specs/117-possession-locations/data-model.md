# Data Model: Possession Locations

## possession_location_stays (new)

| Column | Type | Rule |
| --- | --- | --- |
| id, uuid | bigint, uuid | uuid is the route key |
| account_id, vault_id | foreign keys | the possession's (R1), cascade on delete |
| possession_id | foreign key | cascade on delete |
| location_id | foreign key | same account; cascade on delete, refused first by the policy (R7) |
| arrived_on | date, nullable | null when the day is unknown |
| left_on | date, nullable | null on the open stay; not before `arrived_on` |
| reason | string(255), nullable | |
| note | text, nullable | at most 5000 characters |
| created_at, updated_at | timestamps | index on `(possession_id, left_on)` |

At most one row per possession has `left_on` null; the actions keep it so under a row lock (R3).

`Possession` gains `locationStays(): HasMany` (ordered, R6) and `openLocationStay(): HasOne`
(`whereNull('left_on')`). `Location` gains `stays(): HasMany`. The stay has `possession()` and
`location()`.

## possessions (unchanged column, new rule)

`current_location_id` always equals the open stay's `location_id`, or null without one (R2).

## Derived, never stored

| Figure | Rule |
| --- | --- |
| Length | `arrived_on` to `left_on`, or to today; "length unknown" without `arrived_on` (R8) |
| Gap | between two consecutive stays when the older `left_on` is before the newer `arrived_on` |
| Count line | stays, and distinct `location_id` among them |
| Section count | number of stays |

## Journal

Three `UserActionEnum` cases: move, update and deletion (R12).
