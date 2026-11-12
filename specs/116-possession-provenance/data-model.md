# Data Model: Possession Provenance

## possession_provenance_events (new)

| Column | Type | Rule |
| --- | --- | --- |
| id, uuid | bigint, uuid | uuid is the route key |
| account_id, vault_id | foreign keys | the possession's (R1) |
| possession_id | foreign key | cascade on delete |
| possession_transaction_id | foreign key, nullable | same possession; null on delete (R7) |
| type | string | a `PossessionProvenanceEventTypeEnum` value, required |
| title | string(255), nullable | shown as the type's label when null (R6) |
| date_precision | string | a `PossessionProvenanceDatePrecisionEnum` value, default `day` |
| occurred_on | date, nullable | normalised by precision, null when Unknown (R3) |
| from, to, location | string(255), nullable | text, not contacts or locations |
| description | text, nullable | at most 5000 characters |
| reference_number | string(255), nullable | |
| source_url | string(255), nullable | a valid URL |
| is_verified | boolean | default false |
| created_at, updated_at | timestamps | index on `(possession_id, occurred_on)` |

`Possession` gains `provenanceEvents(): HasMany`; the event has `possession()` and `transaction()`.

## PossessionProvenanceEventTypeEnum

| Case | Value | Label | Icon | Group |
| --- | --- | --- | --- | --- |
| Origin | origin | Origin | star | ownership |
| Acquisition | acquisition | Acquisition | coin | ownership |
| Inheritance | inheritance | Inheritance | family | ownership |
| Gift | gift | Gift | gift | ownership |
| Sale | sale | Sale | coin | ownership |
| Custody | custody | Change of custody | place | custody |
| Loss | loss | Lost or stolen | info | custody |
| Recovery | recovery | Recovered | check-circle | custody |
| Authentication | authentication | Authentication | check | authenticity |
| Exhibition | exhibition | Exhibition | layers | authenticity |
| Publication | publication | Publication | file | authenticity |
| Other | other | Other | note | authenticity |

## PossessionProvenanceDatePrecisionEnum

| Case | Value | Label | Input | Shown as |
| --- | --- | --- | --- | --- |
| Day | day | Exact date | `1962-03-14` | 14 March 1962 |
| Month | month | Month and year | `1962-03` | March 1962 |
| Year | year | Year | `1962` | 1962 |
| Circa | circa | Around a year | `1975` | About 1975 |
| Unknown | unknown | Unknown | empty | Date unknown |

Dates use the user's date format; "About :year" is translated.

## Derived, never stored

- **Chain order**: `occurred_on` ascending, null last, ties by `id` (R5).
- **Origin, owners, verified count, filter counts**: R8.

## Journal

Three `UserActionEnum` cases: creation, update and deletion (R12).
