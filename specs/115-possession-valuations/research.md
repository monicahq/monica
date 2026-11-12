# Research: Possession Valuations

## R1 Storage
**Decision**: a `possession_valuations` table carrying `account_id`, `vault_id` and `possession_id`,
model `PossessionValuation`, `ResolvesModelInVault`. Shapes in [data-model.md](data-model.md).
**Rationale**: as 102 R1. "Valuation" is the design's word, used everywhere.
**Rejected**: turning the declared value into the first valuation (spec Assumptions).

## R2 Money
**Decision**: `amount` as `decimal(12, 2)` cast `decimal:2`, 0 to
`PossessionDeclaredValue::MAX_DECLARED_VALUE`, one `CurrencyEnum` currency, never converted (as 102 R2).

## R3 Enums
**Decision**: `PossessionValuationTypeEnum` (six cases, `label()` and `icon()`, design order) and
`PossessionValuationConfidenceEnum` (Unknown, Certain, Probable, Uncertain, `label()`). Confidence
is required and defaults to Unknown, so "not recorded" has one spelling.

## R4 Current value
**Decision**: `Possession::currentValuation()` picks, among the loaded valuations, the latest
`valued_on`, ties by highest `id`. `Possession::currentValue()` returns
`array{amount, currency, isValuation}` from it, else from the declared value, else null (FR-011).
The Overview's value panel and `PossessionInsuranceViewModel` read `currentValue()` only.
**Rationale**: one rule in one place; a sort in PHP is portable and free at 100 rows (as 099 R4).

## R5 Insurance wording
**Decision**: the summary note and the under insured warning name "the current value" when it comes
from a valuation and keep "the declared value" otherwise; two sentences each, both translated.
**Rejected**: one neutral sentence, which would change shipped copy for possessions without valuations.

## R6 Never edited
**Decision**: no update action, no edit route, no PUT, no update tool. Create and destroy only.
**Rationale**: FR-003; the help mark promises it.

## R7 Documents
**Decision**: a `possession_valuation_file` pivot and `AttachPossessionValuationDocument` /
`DetachPossessionValuationDocument`, copied from the transaction pair. The API document routes sit behind `flag:file-upload`; the web form hides its upload while the flag is off, as `_transaction-form` does. The
web form takes one optional file on create, as `_transaction-form` does; there is no attach or detach
control on a recorded entry (spec Assumptions). The API keeps both document endpoints, its only way
to upload. Destroying a valuation detaches its files, which then follow the usual grace period.

## R8 Preview line
**Decision**: Alpine state inline in the form, as the transaction total is. The view model hands the
current valuation's date, amount and currency, and the four sentences already translated with their
placeholders; Alpine picks one and fills it. No new JavaScript file.
**Rejected**: a server round trip per keystroke.

## R9 Screen behaviour
**Decision**: as 102 R6 without Edit. One GET renders the section; the form and the delete warning
are Alpine state; create and destroy redirect back with a flash. The header's "Record a valuation"
links to the section with `#new-valuation`, which opens the form on load.

## R10 Sections list
**Decision**: Valuations keeps its place and colour in `PossessionViewModel::sections()`, gains its
URL and count.

## R11 Changes between valuations
**Decision**: computed in the view model with `bcsub`, only between two valuations in one currency;
"Up :amount", "Down :amount", "Unchanged". Same rule for the summary, entries, preview and delete text.

## R12 API and assistant
**Decision**: index, create, show and destroy under `possessions/{possession}/valuations`, plus the
two document endpoints; `PossessionValuationResource`; MCP tools list, get, create and delete. No
webhook, as 099 and 102. Contract in [contracts/api.md](contracts/api.md).

## R13 Constitution and models
**Decision**: MINOR amendment (2.17.0): Possession valuation joins principle VII (twenty three
concepts), one sentence on what it records, and the Possession sentence says it holds its
valuations. `spec/models/062` follows; `spec/models/074-possession-valuation-in-progress.md` is new.

## R14 Documentation
**Decision**: portal page `20-possession-valuations.md` (the help mark points at it), a line in
`16-possessions.md` and `18-possession-insurance.md`, API reference `36-possession-valuations.php`,
an entry in `docs/FEATURES.md`.
