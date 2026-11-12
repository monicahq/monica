# Data Model: Possession Insurance

## possession_insurance_records (new)

| Column | Type | Rule |
| --- | --- | --- |
| id, uuid | bigint, uuid | uuid is the route key |
| account_id, vault_id | foreign keys | the possession's (R1) |
| possession_id | foreign key | cascade on delete |
| insurer | string(255) | required |
| status | string | a `PossessionInsuranceStatusEnum` value, required |
| policy_number | string(255), nullable | |
| coverage | string(255), nullable | |
| insured_value | decimal(12, 2) | required, 0 to `MAX_DECLARED_VALUE` (R2) |
| excess | decimal(12, 2), nullable | same bounds; zero reads "None" |
| currency | string(3) | a `CurrencyEnum` value, required, for both figures |
| starts_on, ends_on | date, nullable | `ends_on` not before `starts_on` |
| is_listed_individually | boolean, default false | |
| contact_name | string(255), nullable | |
| contact_email | string(255), nullable | a valid email |
| contact_phone | string(50), nullable | |
| note | text, nullable | at most 5000 characters, as transactions |
| created_at, updated_at | timestamps | "Added", "last edited"; index on `possession_id` |

`Possession` gains `insuranceRecords(): HasMany` and `currentInsuranceRecord(): ?PossessionInsuranceRecord` (R4).

## PossessionInsuranceStatusEnum

| Case | Value | Label | Badge tone | Icon | Position |
| --- | --- | --- | --- | --- | --- |
| Active | active | Active | success | check | 0 |
| Pending | pending | Pending | warning | none | 1 |
| Expired | expired | Expired | neutral | none | 2 |
| Cancelled | cancelled | Cancelled | neutral | close | 3 |

## Derived, never stored

- **Current record**: the active one with the latest start, undated last, ties by highest id.
- **Gap**: declared value minus the current insured value, same currency only; positive is under-insured.
- **Period**: "a to b", "From a", "Until b" or "No dates recorded".
- **List order**: status position, then latest start first, undated last, then highest id.

## Journal

`UserActionEnum` gains `PossessionInsuranceRecordCreation`, `PossessionInsuranceRecordUpdate` and
`PossessionInsuranceRecordDeletion`, naming the insurer and the possession.
