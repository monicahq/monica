# Research: Possession Maintenance

## R1 Storage
**Decision**: a `possession_maintenance_records` table carrying `account_id`, `vault_id` and
`possession_id`, model `PossessionMaintenanceRecord`. Shapes in [data-model.md](data-model.md).
**Rationale**: as 099 R1 and 102 R1: `ResolvesModelInVault` and `RouteProtectionTest` need the account
and the vault, and the controller checks `possession_id` with `abort_unless(..., 404)`.
"Maintenance record" is the design's word, used everywhere.
**Rejected**: a transaction type "Maintenance" (no title, no conditions, no next due date).

## R2 Money
**Decision**: `cost` as `decimal(12, 2)` cast `decimal:2`, nullable, capped at
`PossessionDeclaredValue::MAX_DECLARED_VALUE`; `currency` a nullable `CurrencyEnum`, required when a
cost is given and cleared when it is not. As 102 R2.

## R3 Type
**Decision**: `PossessionMaintenanceTypeEnum` (Inspection, Cleaning, Servicing, Repair, Restoration,
PartReplacement, Conservation, Other) with `label()` and `colour(): VaultColourEnum`. The chip is drawn
the way the header draws a possession tag: a dot and the label, in the colour's tint.
**Rationale**: eight types, eight vault colours, so no two types share one (table in data-model.md).
**Rejected**: an account list of types (a second settings screen nobody asked for).

## R4 Conditions
**Decision**: `condition_before_id` and `condition_after_id` reference `possession_conditions`,
`nullOnDelete()`. The action refuses a condition of another account with `ModelNotFoundException`,
as it refuses the possession. Deleting a condition warns, in the conditions settings, how many
maintenance records name it, through `trans_choice()`, only when that count is above zero.
**Rationale**: principle VIII: a value that will read "Not recorded" afterwards is not lost silently.
**Rejected**: refusing to delete a used condition (blocks a settings change for history's sake);
copying the name onto the record (a rename would not follow, against the edge case).

## R5 Order and overdue
**Decision**: `Possession::maintenanceRecords()` orders by `performed_on` descending, then `id`
descending. `Possession::overdueMaintenanceRecord(CarbonImmutable $today)` returns the first loaded
record when its `next_due_on` is before `$today`, else null. `$today` is today in the user's timezone.
**Rationale**: only the latest job's schedule counts (spec US2.3); nothing stored (FR-006); one method
serves the screen, the API resource and the assistant.

## R6 Dates
**Decision**: `performed_on` required, not after today in the user's timezone; `next_due_on` not
before `performed_on`. Errors "The job cannot be performed in the future." and "The next due date
cannot come before the job.".

## R7 Screen behaviour
**Decision**: as 099 R9 and 102 R6. One GET renders the section; the add form and the delete warning
are Alpine state; Edit is its own GET rendering the page with the filled form in place of the card.
Create, update and destroy redirect back with a flash. The type is a `<select>`; the currency uses
`x-currency-picker`; the conditions are two `<select>` with "Not recorded" first. The card's facts use
`x-detail-list` laid out as the design's four cells. Overdue is `x-badge tone="warning" icon="bell"`.
Long text is truncated with its full value in `title` and left whole for assistive technology.

## R8 Sections list
**Decision**: the existing `maintenance` entry in `PossessionViewModel::sections()` gains its URL and
its count; its place, its icon and its colour (Amber) do not change.

## R9 API and assistant
**Decision**: CRUD under `v1/vaults/{vault}/possessions/{possession}/maintenance-records`, a resource
`PossessionMaintenanceRecordResource`, MCP tools list, get, create, update and delete, each calling
the action, as 102 R9. Conditions travel as their uuid with their name. Contract in
[contracts/api.md](contracts/api.md).

## R10 Constitution
**Decision**: a MINOR amendment (2.15.0) adds Possession maintenance record to the fixed concepts of
principle VII (seventeen), one sentence on what it records, and the Possession sentence says it holds
its maintenance records. `spec/models/062` and `065` follow (a condition is now named by maintenance
records), `spec/models/068-possession-maintenance-record` is new.

## R11 Documentation
**Decision**: `resources/docs/api/32-possession-maintenance-records.php` through the `api-docs-writer`
skill; `docs/portal/en/9-templates-and-customisation/19-possession-maintenance.md` through the
`writer` skill, linked from `16-possessions.md`; an entry in `docs/FEATURES.md`.
