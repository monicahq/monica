# Data Model: Stripe Paywall

Only what changes. The account's existing columns are in `spec/models/001-account-in-progress.md`, and the access read model, the `accounts` changes, the derived accessors, `OfferKindEnum` and the `monica.*` configuration keys are exactly 022's `data-model.md`, read on its branch. What the provider changes is below.

## Cashier's tables, created by migrations of ours

| Table | Used for |
| --- | --- |
| `accounts` gains `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at` | the account as Stripe customer, added to the accounts migration rather than by the package's, which puts them on `users` |
| `subscriptions`, `subscription_items` | the monthly and yearly offers, their `stripe_status`, `ends_at` and the item's `stripe_price`, which names the subscription's offer. The package's columns, `account_id` in place of `user_id`, since its migrations stay unpublished (Principle IV) |

There is no `customers` and no `transactions` table: Stripe Cashier keeps neither.

## New table, `payments`

One row per Stripe invoice the account paid, written by `RecordPayment` and read by the invoices tab and the API. It is what FR-005 calls the payment record.

| Column | Meaning |
| --- | --- |
| `account_id` | owner, foreign key declared at the bottom of the migration, cascades on delete (FR-022) |
| `stripe_id` | the invoice id, unique. A repeated `invoice.paid` updates this row rather than inserting a second |
| `stripe_subscription_id` | null for the lifetime purchase, which is how the kind is derived (FR-017) |
| `kind` | `OfferKindEnum` value, stored at payment time so the history survives a retagged price |
| `status` | the invoice status as Stripe names it |
| `total`, `tax` | integers in the smallest unit of `currency` |
| `currency` | three letters |
| `invoice_number`, `hosted_invoice_url`, `invoice_pdf_url` | how the owner reaches the invoice (FR-006, FR-009) |
| `billed_at` | when Stripe finalised it, the sort key of the history |

`Payment::total()` writes the amount for the reader's locale through `Cashier::formatAmount()`, the way 022's `Transaction::total()` override did.

## New and changed enums

- `AccessKindEnum`, `AccessEndReasonEnum`, the `UserActionEnum` cases and `EmailType::AccessEnded` come from 022 unchanged. `AccessEndReasonEnum` gains `Disputed`, and `EmailType` gains `AccessUnlocked` (FR-023).

## The catalogue, not stored

`StripeCatalogue` hands back `{price_id, kind, amount, currency, prices}` for every active price tagged in `metadata` and billing like its tag, cached for an hour. Prices, names and currencies stay at Stripe.

## Configuration

| Key | Default |
| --- | --- |
| `monica.free_vault_limit`, `monica.free_contact_limit`, `monica.paid_storage_limit_in_bytes`, `monica.offer_tag`, `monica.locales.*.currency` | as in 022 |
| `cashier.*` and `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `CASHIER_CURRENCY` | published by the package, left as it ships. No client side key is needed: checkout is a redirect |
