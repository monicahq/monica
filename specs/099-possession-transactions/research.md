# Research: Possession Transactions

## R1 Storage
**Decision**: a `possession_transactions` table carrying `account_id`, `vault_id` and `possession_id`,
and a `possession_transaction_file` pivot for documents. Shapes in [data-model.md](data-model.md).
**Rationale**: `account_id` and `vault_id` let the model use `ResolvesModelInVault`, which
`RouteProtectionTest` demands of every bound model; the controller then checks
`possession_id` with `abort_unless(..., 404)`, as the vault check is done elsewhere. Files are
referenced by a column and counted (`AttachFile`, `DetachFile`), so several per transaction need a pivot.
**Rejected**: documents as a JSON list of file ids (the orphan sweep cannot see them).

## R2 Money
**Decision**: `decimal(12, 2)` columns cast `decimal:2`, read as strings, summed with `bcadd`, capped
at `PossessionAcquisitionAttributes::MAX_DECLARED_VALUE`, as `declared_value` already is.
**Rejected**: integer minor units (a second money convention in one table family).

## R3 Types
**Decision**: `PossessionTransactionTypeEnum`, eleven cases, with `label()`, `icon()`,
`changesOwnership()`, `isAcquisition()` and `counterpartyLabel()`. The design's icons are kept.

## R4 Derived figures
**Decision**: `PossessionTransaction::total()` returns the entered total, else the `bcadd` of the
figures entered, else null; `totalSource()` says which. `Possession::acquisitionTransaction()` picks
the earliest acquisition among its loaded transactions, undated first, then by `id`. Costs since
are summed by the view model and the API resource, the only two readers.
**Rationale**: sorting nulls first is spelled differently on SQLite, MySQL and PostgreSQL; at 200
rows a sort in PHP is free and portable. Nothing is stored, so nothing drifts (FR-006).

## R5 Undated transactions
**Decision**: `occurred_on` is nullable, and an undated acquisition sorts before every dated one.
**Rationale**: the invariant "Imprecision is a first class state" forbids inventing a date for an
acquisition recorded without one, which the spec first did. An undated converted acquisition stays
the acquisition.

## R6 Converting existing acquisitions
**Decision**: one new migration inserts a transaction per possession whose `acquisition` is set
(`bought` to purchase, `gift` to gift received, `inherited` to inheritance, `made` to other), with
`acquired_on` and the contact's display name, trashed contacts included, and no figure, so no
currency (FR-002). The form preselects the currency of the possession's latest transaction, else its
declared value's, else none. A second migration drops `acquisition`,
`acquired_from_contact_id` and `acquired_on`. Existing migrations are not edited (the app is in
production). The contact's name is read through the model, accepted because the migration runs once.
**Rejected**: keeping the columns read only (two sources, FR-012).

## R7 Currency picker
**Decision**: an `x-currency-picker` component: a button and a `role="listbox"` of options, each a
flag and the code, `aria-selected` on the chosen one, the full name as accessible name, a hidden
input carrying the value, arrow keys, Home, End, Enter and Escape. `CurrencyEnum` gains `code()` and
`flag()`, a CSS background like the locale flags in `config/localization.php`.
**Rationale**: it carries an ARIA and keyboard contract and stands in two files (the transaction
form and the declared value of the add form), so `components.md` asks for extraction at the second.
**Rejected**: a native `<select>` (cannot draw a flag); `x-combo-box` (free text, not a choice).

## R8 Section colours
**Decision**: `sections()` gains a `colour` (`?VaultColourEnum`), mapped from the design's identity
tokens: Transactions Green, Valuations Blue, Provenance Violet, Insurance Rust, Maintenance Amber,
Loans Plum, Locations Teal, Documents Slate; Overview and Timeline null (ink). `VaultColourEnum` gains
`iconForeground()`, the text half of `tint()` with its dark variant.

## R9 Screen behaviour
**Decision**: one GET renders the section; the add form, the filters, the live total and the delete
warning are Alpine state on that page; Edit is its own GET rendering the same page with the entry
replaced by the filled form, so it works without JavaScript and survives a validation error.
Create, update and destroy redirect back with a flash. No query string (code style).

## R10 API and assistant
**Decision**: CRUD under `v1/vaults/{vault}/possessions/{possession}/transactions`, documents
attached by upload behind `flag:file-upload` and detached by file id. `PossessionResource` keeps
`acquisition`, now derived and read only: `way` from the type, `acquired_on` from the date,
`acquired_from` as `{name}` without an id. Create and update stop accepting the three fields. MCP
gains list, create, update and delete transaction tools; its possession tools lose the three fields.
**Rationale**: principle X. Losing `acquired_from.id` and the writable fields is a breaking change to
`v1`; the API is not yet public outside this instance's own clients, so it is made in place and the
reference says so. **Flagged** for the pull request.

## R11 Constitution
Principle VII describes a possession as recording "how it was acquired, from whom, when". A MINOR
amendment (2.13.0) rewrites that sentence and adds Possession transaction to the fixed concepts;
`spec/models/062` follows and `spec/models/066-possession-transaction` is new.
