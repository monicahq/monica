# Research: Possession Provenance

## R1 Storage
**Decision**: a `possession_provenance_events` table carrying `account_id`, `vault_id` and
`possession_id`, model `PossessionProvenanceEvent`, `ResolvesModelInVault`. Shapes in
[data-model.md](data-model.md).
**Rationale**: as 102 R1 and 115 R1. "Provenance event" is the design's word ("Add an event").

## R2 Enums
**Decision**: `PossessionProvenanceEventTypeEnum` (twelve cases in the design's order, `label()`,
`icon()` and `group()` returning `ownership`, `custody` or `authenticity`) and
`PossessionProvenanceDatePrecisionEnum` (Day, Month, Year, Circa, Unknown; `label()` and `help()`).
**Rejected**: a third enum for the group; three strings read by one method are enough.

## R3 A date and its precision
**Decision**: `occurred_on` is a nullable `date`; `date_precision` says how much of it is meant. Month
stores the first of the month, Year and Circa the first of January, Unknown stores null. The action
takes the precision and a string shaped by it (`1962-03-14`, `1962-03`, `1962`, empty), refuses a
mismatch with a `ValidationException` on `occurred_on`, and normalises. Web and API send the same
string, so the rule lives once.
**Rejected**: separate year, month and day columns; a free text date that cannot be ordered.

## R4 Date input
**Decision**: the form shows a `date` input for Day, a `month` input for Month, a number input
(1000 to the current year) for Year and Circa, nothing for Unknown, inside one `<fieldset>` switched
by Alpine (components rule on `@js()`). A browser without `month` falls back to text with the
placeholder `1962-03`, which the action accepts. Changing the precision clears the field.

## R5 Order
**Decision**: `Possession::provenanceEvents()` is a `HasMany` ordered by `occurred_on is null`, then
`occurred_on`, then `id` (spec US2), so the screen, the API and the assistant share one order. The
expression is valid on SQLite, MySQL and PostgreSQL.

## R6 Title
**Decision**: `title` is nullable; an empty title is stored as null and shown as the type's label, so
it reads in the reader's language rather than the author's.

## R7 Linked transaction
**Decision**: a nullable `possession_transaction_id`, `nullOnDelete()`. The action refuses a
transaction of another possession on `possession_transaction_id`. The entry shows the transaction's
type, date and `total()`; nothing is copied (FR-003). Deleting a transaction keeps the event.
`DestroyPossessionTransaction` is not touched; the foreign key does the work on all three databases.

## R8 Summary and filters
**Decision**: computed by `PossessionProvenanceViewModel` from the sorted events: origin is the
oldest Origin event; owners are the distinct non empty `to` of ownership events, in chain order,
compared after trimming; verified counts all events. Counted phrases go through `trans_choice()`.

## R9 Screen behaviour
**Decision**: as 102 R6. One GET renders the section; the add form, the filters and the delete
warning are Alpine state; Edit is its own GET rendering the page with the filled form in place of the
entry. Create, update and destroy redirect back with a flash, which also resets the filter.

## R10 Sections list
**Decision**: Provenance keeps its place, icon and Violet colour in `PossessionViewModel::sections()`
and gains its URL and count.

## R11 API and assistant
**Decision**: index, create, show, update and destroy under `possessions/{possession}/provenance`;
`PossessionProvenanceEventResource`; MCP tools list, get, create, update and delete, as the
transaction tools. No document endpoint (spec Assumptions). Contract in
[contracts/api.md](contracts/api.md).

## R12 Journal
**Decision**: `UserActionEnum` gains `PossessionProvenanceEventCreation`, `…Update` and `…Deletion`,
naming the possession, as the transaction cases do.

## R13 Constitution and models
**Decision**: MINOR amendment (2.18.0): Possession provenance event joins principle VII (twenty four
concepts), with one sentence saying it records one moment of origin, ownership, custody or
authenticity, holds no money and may point at one transaction. `spec/models/062` follows;
`spec/models/075-possession-provenance-event-in-progress.md` is new.

## R14 Documentation
**Decision**: portal page `21-possession-provenance.md` (the help mark points at it), a line in
`16-possessions.md` and `17-possession-transactions.md`, API reference
`37-possession-provenance.php`, an entry in `docs/FEATURES.md`.
