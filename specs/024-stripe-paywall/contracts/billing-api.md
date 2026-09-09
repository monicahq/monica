# Contract: billing API

All under `api/v1/billing`, `auth:sanctum`, throttled with the rest of v1, documented in `resources/docs/api/12-billing.php`. `401` when unauthenticated; there is no `404` on the account, every authenticated user has one. What the provider changes from 022's contract is the checkout and the payment record; the rest is carried over and restated here only where a field differs.

## Reads

| Endpoint | Returns |
| --- | --- |
| `GET billing` | `BillingResource`: `has_full_access`, `access_kind` (`lifetime`, `subscription`, `granted`, null), `renews_at`, `ends_at` when a cancellation is scheduled, `purchased_at`, `billing_email`, `limits{vaults,contacts,storage_in_bytes}` with null meaning no ceiling, `usage{}`, `payment` (the latest, or null), `offers_available`, and `offers[]` each with `price_id`, `kind`, `renews`, `amount`, `currency`, and `prices[]{currency, amount}` |
| `GET billing/payments`, `GET billing/payments/{payment}` | `PaymentResource` from our `payments` table, newest first, paginated like the logs: `provider_id` (the invoice id), `offer`, `status`, `total`, `tax`, `currency`, `invoice_number`, `invoice_url`, `invoice_pdf_url`, `billed_at`. `404` for another account's |

`offers` is empty once the account holds lifetime access (FR-008), and lists only what sits above a subscriber's current offer (FR-021). On a self hosted instance every ceiling is null, `payment` is null, `offers` is empty and `access_kind` is null (FR-002).

## Writes

| Endpoint | Effect |
| --- | --- |
| `POST billing/checkout-sessions` with `price_id` and optional `currency` | Opens a Stripe Checkout session for that offer and answers `201` with `{data: {type: 'checkout_session', attributes: {url}}}`, the page the client sends the buyer to. `422` when the price is not an offer this account may take, `503` when Stripe does not answer |
| `PUT billing/subscription` with `price_id` | Moves the running subscription up in place, nothing charged today, the new price billed from the next renewal (FR-021). `200` with the record, `422` when there is no subscription, it is ending, or the price is not a longer subscription |
| `DELETE billing/subscription` | Cancels at the end of the paid period (FR-019). `204`, or `422` when there is nothing to cancel |
| `DELETE billing/subscription/cancellation` | Takes a cancellation back before the period ends (FR-019). `200` with the record, `422` when it is not ending |
| `POST billing/portal-sessions` | Opens Stripe's customer portal and answers `201` with its short lived `url` (FR-009). `422` when Stripe does not know the account yet, `503` when it does not answer |

Nothing is bought through the API itself: payment is taken on the page `checkout-sessions` returns, and access moves only when Stripe's webhook says so (FR-005).

## Web routes, for symmetry

`settings/account/billing` (index), `.../billing/invoices`, `PUT .../billing/currency`, `POST .../billing/checkout/{price}` redirecting to Stripe, `PUT .../billing/subscription/{price}`, `DELETE .../billing/subscription`, `DELETE .../billing/subscription/cancellation`, `GET .../billing/subscription/portal` redirecting to Stripe, `GET .../billing/invoices/{payment}` redirecting to the hosted invoice. Stripe returns the buyer to the index with `?checkout=success` or `?checkout=cancelled`, and the index says the payment is being confirmed until the webhook lands.

## Inbound

`POST stripe/webhook`, Cashier's controller, signature checked with `STRIPE_WEBHOOK_SECRET`, exempt from CSRF through `stripe/*`. Which events do what is the table in [research.md](research.md).

## Capped actions

No new endpoint. The existing create endpoints refuse at their cap with a `ValidationException` naming it, the way `Api\ContactAvatarController` reports `NotEnoughStorageLeft`.
