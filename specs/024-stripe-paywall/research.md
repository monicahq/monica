# Research: Stripe Paywall

Everything 022's research decided about caps, one access concept, offers as tagged prices, the product knowing paid from free and nothing else, lapse and cancellation, refusing a capped action and dropping `is_self_hosted` holds unchanged. Read it on branch `2026-09-06-paddle-paywall`. Only what the provider changes is decided here.

## Integrate through `laravel/cashier` 16

**Decision**: `laravel/cashier` ^16.8, namespace `Laravel\Cashier`, `Account` the billable model through `Cashier::useCustomerModel()`, plus `Cashier::keepPastDueSubscriptionsActive()` (FR-020) and `Cashier::calculateTaxes()` (FR-012) in `AppServiceProvider`. Its migrations are not published, because Principle IV leaves a package's migrations as they ship: ours create the same tables with `account_id` in place of `user_id`, the foreign key Cashier derives from the billable model, and the customer columns on `accounts`.

**Rationale**: first party, documented, owns signature verification, the subscription lifecycle and the Checkout and portal sessions. `stripe/stripe-php` uses cURL, so no Guzzle conflict.

**Alternative**: `stripe/stripe-php` alone with a hand written webhook controller, rejected for the same reason 022 rejected a hand written Paddle integration.

## Checkout is a redirect, not a script

**Decision**: Stripe's hosted Checkout, `mode=payment` for lifetime through `$account->checkout([$price => 1], $options)` and `mode=subscription` through `newSubscription('default', $price)->checkout($options)`; both return a `Checkout` that redirects. Options: `success_url` and `cancel_url` back to billing, `currency` set to the reader's pick, `customer_update.address=auto` so tax can be worked out, and for the one time purchase `invoice_creation.enabled=true` so the purchase has an invoice (FR-006). The API answers the session `url` for a client to open.

**Rationale**: a redirect needs no provider script in our screens, which removes the Turbo head script race 022 had to work around, and no client side key.

## Tax inclusive prices, computed by Stripe

**Decision**: nothing about tax is read off a price. A price created without `tax_behavior` reads `unspecified`, and Stripe then resolves it from the account's own tax settings, so a per price check would drop perfectly good prices rather than catch a mistake. Whether a quoted amount includes tax is the operator's Stripe Tax configuration, which the quickstart asks for. `Cashier::calculateTaxes()` puts `automatic_tax` on both checkouts, and that is what makes Stripe work the share out from where the buyer is.

**Rationale**: FR-012. With inclusive prices the figure shown is the figure paid, and the invoice carries the split.

## The catalogue is Stripe's prices, tagged in metadata

**Decision**: `StripeCatalogue` reads `prices` with `active=true`, `limit=100` and `expand[]=data.currency_options`, keeps the result in the cache for an hour, and offers a price when `metadata[monica.offer_tag]` is `monthly`, `yearly` or `lifetime` and the price bills like it: `type=one_time` for lifetime, `recurring.interval` `month` or `year` with `interval_count=1` otherwise. `prices` per currency come from `unit_amount` and `currency_options`. A failed read is logged and not cached, so the next screen tries again.

**Rationale**: Paddle's `custom_data` becomes Stripe's `metadata`, otherwise FR-017 is 022's decision. More than 100 active prices would need paging and is not a case worth code.

## A payment record of our own

**Decision**: a `payments` table, one row per Stripe invoice, written by `RecordPayment` on `invoice.paid`, keyed on the invoice id so a repeated event updates one row (FR-005). It holds what the invoices tab and `GET billing/payments` show: kind, status, total, tax, currency, invoice number, the hosted invoice URL and the PDF URL, billed date. It cascades with the account (FR-022).

**Rationale**: Cashier Stripe has no local invoice table and `invoices()` calls the API. With `invoice_creation` on, the lifetime purchase produces an invoice too, so one event covers both offers and there is no second path for the one time purchase.

## Which webhooks move what

| Stripe event | Reached through | What we do |
| --- | --- | --- |
| `invoice.paid` | `WebhookReceived` | `RecordPayment`. An invoice with no subscription behind it is the lifetime purchase: `RecordPurchase`, which also stops a running subscription renewing (FR-021) |
| `customer.subscription.created` | `WebhookHandled`, after Cashier wrote the row | `RecordSubscription`: log, `AccessUnlocked` email |
| `customer.subscription.deleted` | `WebhookHandled`, after Cashier marked it cancelled | `EndAccess`, reason `PaymentFailed` when `cancellation_details.reason` is `payment_failed`, `Cancelled` otherwise |
| `charge.refunded` when `amount_refunded` equals `amount` | `WebhookReceived` | `EndAccess` `Refunded`: withdraws the lifetime flag when the charge paid the purchase, cancels the subscription now when it paid a renewal |
| `charge.dispute.created` | `WebhookReceived` | `EndAccess` `Disputed`, same effects |
| `charge.dispute.closed` with `status=won` | `WebhookReceived` | `RestoreAccess`: the purchase flag goes back on. A cancelled subscription cannot be brought back at Stripe, so a reversed renewal leaves the owner to subscribe again |
| `charge.refund.updated` with `status` `failed` or `canceled` | `WebhookReceived` | `RestoreAccess`, same reasoning: the money never left |

Everything else Cashier handles is left to it: `customer.subscription.updated` keeps the local row in step, which is how a cancellation, a resume and a swap made by us are confirmed. What a payment unlocked is derived from whether its invoice belongs to a subscription, never from the price's tag (FR-017).

## Cancel, resume, move up

**Decision**: `cancel()` at period end and `resume()` inside the grace period, both Cashier's. Moving up is a Stripe subscription schedule made from the running subscription: a first phase keeps the monthly price until the period already paid for ends, a second phase bills the yearly price from that date, and the schedule then lets go of the subscription. Nothing is charged today and the renewal date does not move, which is FR-021 as clarified. `swap()` cannot do it: Stripe resets the billing cycle anchor to now whenever the interval changes, and invoices the yearly price on the spot. A schedule refuses `cancel()`, so cancelling releases it first and drops a move still waiting.

**Alternative**: `swapAndInvoice()` with prorations, which is what 022 first shipped; rejected by the clarification session.

## Deleting an account

**Decision**: `DestroyAccount` calls `cancelNow()` on a valid subscription before deleting, and refuses the deletion with `ProviderUnreachable` when Stripe does not answer, because deleting anyway would leave somebody charged with no account (FR-022). The Stripe customer is kept: the invoices are the operator's accounting.

## Emails

**Decision**: the product sends `AccessUnlocked` and `AccessEnded` only (FR-023). Receipts and failed payment emails are Stripe's, turned on in the dashboard; Cashier's `payment_notification` stays off.

## Faking Stripe in tests

**Decision**: a fixture `Tests\Fixtures\Stripe\FakeStripeClient` implementing the SDK's `ClientInterface`, installed with `ApiRequestor::setHttpClient()`, answering canned bodies per method and path and recording every request for assertion. Webhooks are tested by dispatching `WebhookReceived` and `WebhookHandled` with payload arrays, since Cashier owns the signature check.

## Verified against 16.8 while building

Cashier 16.8.0 pins Stripe at API `2026-08-26.dahlia`, whose invoice carries no `tax` and no
`subscription`: the tax share is the sum of `total_taxes[].amount`, and a null `parent` is what says an
invoice is the purchase. `RecordPayment` reads the payload, Cashier's `Invoice::tax()` being stale.

Cashier's default event list holds none of the five events acted on here, so `config/cashier.php` names
them itself. Without that, `cashier:webhook` opens an endpoint that unlocks nothing, and `stripe listen`
hides it by forwarding everything. `docs/STRIPE.md` is where an operator reads all of this.

`CheckoutBuilder` adds `automatic_tax` whenever `Cashier::calculateTaxes()` is on, so only `customer_update.address` is ours.
`resume()` refuses outside the grace period, and cancelling reads the period end off the subscription's items, which is why `subscribeAccount()` creates that row.
