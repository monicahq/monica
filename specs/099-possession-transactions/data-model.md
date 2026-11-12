# Data Model: Possession Transactions

## possession_transactions (new)

| Column | Type | Rule |
| --- | --- | --- |
| id, uuid | bigint, uuid | uuid is the route key |
| account_id, vault_id | foreign keys | the possession's, for `ResolvesModelInVault` (R1) |
| possession_id | foreign key | cascade on delete |
| type | string | a `PossessionTransactionTypeEnum` value |
| occurred_on | date, nullable | undated is allowed (R5) |
| counterparty | string(255), nullable | text, not a contact |
| amount, tax, fees, shipping | decimal(12, 2), nullable | 0 to `MAX_DECLARED_VALUE` (R2) |
| entered_total | decimal(12, 2), nullable | overrides the sum when set |
| currency | string(3), nullable | a `CurrencyEnum` value, required when any figure or total is set |
| reference | string(255), nullable | |
| source_url | string(2048), nullable | a valid URL |
| note | text, nullable | |
| created_at, updated_at | timestamps | "Added", "last edited"; index on `(possession_id, occurred_on)` |

## possession_transaction_file (new)

`possession_transaction_id` (cascade) and `file_id` (cascade), unique together. Each row holds one
`AttachFile` reference; removing a row runs `DetachFile`, so the file enters the grace period.

## possessions (changed)

`acquisition`, `acquired_from_contact_id` and `acquired_on` are converted (R6), then dropped.
`declared_value` and `declared_value_currency` stay. `Possession` gains `transactions(): HasMany`
and `acquisitionTransaction(): ?PossessionTransaction`. `PossessionAcquisitionEnum` and the
acquisition half of `PossessionAcquisitionAttributes` are deleted once nothing reads them.

## PossessionTransactionTypeEnum

| Case | Value | Ownership | Acquisition | Counterparty label | Icon |
| --- | --- | --- | --- | --- | --- |
| Purchase | purchase | yes | yes | Seller | coin |
| Sale | sale | yes | no | Buyer | coin |
| Exchange | exchange | yes | yes | Exchanged with | layers |
| GiftReceived | gift_received | yes | yes | Given by | gift |
| GiftGiven | gift_given | yes | no | Given to | gift |
| Inheritance | inheritance | yes | yes | Inherited from | family |
| Refund | refund | no | no | Counterparty | arrow-right |
| Fee | fee | no | no | Counterparty | file |
| Tax | tax | no | no | Counterparty | file |
| Shipping | shipping | no | no | Counterparty | layers |
| Other | other | no | no | Counterparty | note |

## Derived, never stored

- **Total**: `entered_total`, else the sum of the figures set, else unknown; with its source (R4).
- **Acquisition**: the earliest acquisition transaction, undated first, then by id.
- **Costs since**: money only transactions on or after the acquisition (all when it is undated), in
  its currency, refunds subtracted; the others counted and named as left out.

## Journal

`UserActionEnum` gains `PossessionTransactionCreation`, `PossessionTransactionUpdate` and
`PossessionTransactionDeletion`, naming the type and the possession.
