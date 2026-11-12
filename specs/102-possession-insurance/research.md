# Research: Possession Insurance

## R1 Storage
**Decision**: a `possession_insurance_records` table carrying `account_id`, `vault_id` and
`possession_id`, model `PossessionInsuranceRecord`. Shapes in [data-model.md](data-model.md).
**Rationale**: the same reason as 099 R1: `ResolvesModelInVault` and `RouteProtectionTest` need the
account and the vault, and the controller checks `possession_id` with `abort_unless(..., 404)`.
"Insurance record" is the design's own word, used everywhere (one word for one meaning).
**Rejected**: columns on `possessions` (one policy only, no history, which the help text promises).

## R2 Money
**Decision**: `insured_value` and `excess` as `decimal(12, 2)` cast `decimal:2`, capped at
`PossessionDeclaredValue::MAX_DECLARED_VALUE`, one `CurrencyEnum` currency for both, as 099 R2.

## R3 Status
**Decision**: `PossessionInsuranceStatusEnum` (Active, Pending, Expired, Cancelled) with `label()`,
`tone()` for the badge (success, warning, neutral, neutral), `icon()` (check, none, none, close) and
`position()` for the list order. Entered, never derived from the dates (spec Assumptions).

## R4 Derived figures
**Decision**: `Possession::currentInsuranceRecord()` picks, among the loaded records, the active one
with the latest start (undated last), ties by highest `id`. The gap against the declared value is
computed by the view model with `bcsub`, only when both values exist in the same currency.
**Rationale**: as 099 R4, a sort in PHP is portable and free at this size; nothing is stored (FR-004).

## R5 Renewal wording
**Decision**: whole months between today, in the user's timezone, and `ends_on`: below zero reads
"Passed", zero "This month", otherwise `trans_choice('In :count month|In :count months', $n)`.
**Rationale**: the code style forbids choosing a wording by comparing to one; zero is its own sentence.
**Rejected**: the design's spelled numbers ("in two months"), which no locale can inflect.

## R6 Screen behaviour
**Decision**: as 099 R9. One GET renders the section; the add form and the delete warning are Alpine
state; Edit is its own GET rendering the page with the filled form in place of the entry. Create,
update and destroy redirect back with a flash. The status is a radio group drawn as chips, so it
works without JavaScript; the currency uses `x-currency-picker` (FR-005).

## R7 Sections list
**Decision**: Insurance moves directly under Transactions in `PossessionViewModel::sections()`, gains
its URL and its count. The colour (Rust) is kept from 099 R8.

## R8 Overview
**Decision**: `facts()` adds "Insured", reading ":insurer, :value" from the current record, after
the acquisition facts; absent without one.

## R9 API and assistant
**Decision**: CRUD under `v1/vaults/{vault}/possessions/{possession}/insurance-records`, a resource
`PossessionInsuranceRecordResource`, MCP tools list, get, create, update and delete, each calling the
action. Contract in [contracts/api.md](contracts/api.md). No webhook: none exists for transactions yet.

## R10 Constitution
**Decision**: a MINOR amendment (2.14.0) adds Possession insurance record to the fixed concepts of
principle VII, one sentence on what it records, and the Possession sentence says it holds its
insurance records. `spec/models/062` follows and `spec/models/067-possession-insurance-record` is new.

## R11 Contact links
**Decision**: the email is validated as an email and rendered as `mailto:`; the phone is free text,
rendered as `tel:` with spaces removed. Neither is a contact (spec Assumptions).
