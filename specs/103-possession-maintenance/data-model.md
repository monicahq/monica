# Data Model: Possession Maintenance

## possession_maintenance_records (new)

| Column | Type | Rule |
| --- | --- | --- |
| id, uuid | bigint, uuid | uuid is the route key |
| account_id, vault_id | foreign keys | the possession's (R1) |
| possession_id | foreign key | cascade on delete |
| type | string | a `PossessionMaintenanceTypeEnum` value, required |
| title | string(255) | required, plain text |
| performed_by | string(255), nullable | plain text |
| performed_on | date | required, not after today (R6) |
| cost | decimal(12, 2), nullable | 0 to `MAX_DECLARED_VALUE` (R2) |
| currency | string(3), nullable | a `CurrencyEnum` value, required with a cost |
| condition_before_id, condition_after_id | foreign keys, nullable | `possession_conditions`, null on delete, same account (R4) |
| next_due_on | date, nullable | not before `performed_on` |
| description | text, nullable | at most 5000 characters, as transactions |
| created_at, updated_at | timestamps | index on `possession_id` |

`Possession` gains `maintenanceRecords(): HasMany`, ordered (R5), and
`overdueMaintenanceRecord(CarbonImmutable $today): ?PossessionMaintenanceRecord`.
`PossessionMaintenanceRecord` gains `conditionBefore()` and `conditionAfter()`: `BelongsTo`.
`PossessionCondition` gains nothing; its docblock stops saying nothing points at a condition.

## PossessionMaintenanceTypeEnum

| Case | Value | Label | Colour |
| --- | --- | --- | --- |
| Inspection | inspection | Inspection | Blue |
| Cleaning | cleaning | Cleaning | Teal |
| Servicing | servicing | Servicing | Green |
| Repair | repair | Repair | Rust |
| Restoration | restoration | Restoration | Plum |
| PartReplacement | part_replacement | Part replacement | Amber |
| Conservation | conservation | Conservation | Violet |
| Other | other | Other | Slate |

## Derived, never stored

- **Overdue**: the latest record, when its next due date is before today in the user's timezone.
- **Condition**: "before → after", a missing side "Not recorded", both missing "Not recorded" once.

## Journal

`UserActionEnum` gains `PossessionMaintenanceRecordCreation`, `PossessionMaintenanceRecordUpdate` and
`PossessionMaintenanceRecordDeletion`, naming the possession in the voice of the insurance cases.
