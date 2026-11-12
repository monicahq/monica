# Data Model: Possession Valuations

## possession_valuations (new)

| Column | Type | Rule |
| --- | --- | --- |
| id, uuid | bigint, uuid | uuid is the route key |
| account_id, vault_id | foreign keys | the possession's (R1) |
| possession_id | foreign key | cascade on delete |
| type | string | a `PossessionValuationTypeEnum` value, required |
| valued_on | date | required |
| confidence | string | a `PossessionValuationConfidenceEnum` value, default `unknown` |
| amount | decimal(12, 2) | required, 0 to `MAX_DECLARED_VALUE` (R2) |
| currency | string(3) | a `CurrencyEnum` value, required |
| valuer | string(255), nullable | |
| method | string(255), nullable | |
| reference_number | string(255), nullable | |
| source_url | string(255), nullable | a valid URL |
| note | text, nullable | at most 5000 characters, as transactions |
| created_at, updated_at | timestamps | "Added"; index on `(possession_id, valued_on)` |

## possession_valuation_file (new)

`possession_valuation_id` and `file_id`, both cascade on delete, unique together, timestamps; copied
from `possession_transaction_file` (R7).

`Possession` gains `valuations(): HasMany`, `currentValuation(): ?PossessionValuation` and
`currentValue(): ?array{amount: string, currency: string, isValuation: bool}` (R4).
`PossessionValuation` has `documents(): BelongsToMany<File>`.

## PossessionValuationTypeEnum

| Case | Value | Label | Icon |
| --- | --- | --- | --- |
| OwnEstimate | own_estimate | Own estimate | note |
| Appraisal | appraisal | Appraisal | activity |
| InsuranceDeclaration | insurance_declaration | Insurance declaration | lock |
| AuctionResult | auction_result | Auction result | coin |
| ComparableListing | comparable_listing | Comparable listing | link |
| OfferReceived | offer_received | Offer received | relationship |

## PossessionValuationConfidenceEnum

Unknown (`unknown`), Certain (`certain`), Probable (`probable`), Uncertain (`uncertain`), with `label()`.

## Derived, never stored

- **Current valuation**: latest `valued_on`, ties by highest id.
- **Change**: amount minus the one before it by date, same currency only (R11).
- **History order**: the reverse of the current valuation's order.

## Journal

`UserActionEnum` gains `PossessionValuationCreation`, `PossessionValuationDeletion`,
`PossessionValuationDocumentAddition` and `PossessionValuationDocumentRemoval`, naming the type,
the date and the possession, as the transaction document cases do.
