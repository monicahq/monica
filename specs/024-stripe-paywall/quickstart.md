# Quickstart: verifying the paywall

Field names and shapes are in [data-model.md](data-model.md) and [contracts/](contracts/). The scenarios 022's quickstart walks for caps, lapses and the self hosted instance hold unchanged; below is what Stripe changes.

## Prerequisites

[`docs/STRIPE.md`](../../docs/STRIPE.md) is this same setup written for an operator rather than for this walk.

```bash
composer require laravel/cashier "^16.8" && php artisan vendor:publish --tag="cashier-config" && php artisan migrate
```

1. A Stripe account in **test mode**. Never point a development instance at live keys.
2. Three test prices at least, each with metadata `offer` set to `monthly`, `yearly` or `lifetime`, the lifetime one a one time price, the others billed every month and every year, and each priced in at least two currencies so the currency picker has something to show.
3. Stripe Tax turned on with an origin address, and set there to treat prices as tax inclusive, so that the amount shown is the amount paid and the checkout can work the tax share out of it (FR-012). Nothing checks this per price: it is configuration, not data.
4. Subscriptions settings: Smart Retries on, and after all retries fail, **cancel the subscription**. Without it a lapsed card never ends access (FR-020).
5. Customer portal configured to show invoice history and update the payment method, with cancellation and plan changes turned off there, so the product stays the one place decisions are made.
6. Emails: successful payments and failed payments turned on in Stripe (FR-023).
7. Webhooks forwarded to your machine, and every event in research.md's table enabled:

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

9. In `.env`: `MONICA_SELF_HOSTED=false`, the free ceilings, `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` (printed by `stripe listen`), `CASHIER_CURRENCY=eur`.

## The suite

```bash
php artisan test --compact --filter='Purchase|Subscription|Access|Payment|Catalogue|Billing'
```

## What to walk, with a queue worker running

1. Billing lists three offers in three columns, each priced tax inclusive in the currency you pick. Every button is a form that redirects to Stripe, and the back button returns you to billing with nothing changed.
2. Pay the lifetime offer with `4242 4242 4242 4242`. You come back to a notice that the payment is being confirmed; within seconds the webhook lands, access is held, one `payments` row exists with an invoice number, the Invoices tab links to it, and the `AccessUnlocked` email went out once.
3. On a fresh account, subscribe monthly, then move up to yearly: the modal says nothing is charged today, one subscription row remains with the yearly price, the renewal date has not moved. The upcoming invoice in Stripe shows the yearly amount on that date (FR-021).
4. From that account, buy lifetime. The subscription stops renewing, access never drops, offers empty.
5. Cancel a subscription: warned, end date shown, access continues, and the cancellation can be taken back from the same screen.
6. Subscribe with `4000 0000 0000 0341`, whose renewals fail: access survives the failures and ends only when Stripe cancels the subscription after its retries, with the `AccessEnded` email naming the failed payment (FR-020). Shorten the retry schedule in test mode rather than waiting.
7. Refund the lifetime payment in full from the dashboard: access is withdrawn, the payment row stays, the email says refunded. Refund it partially on another account: nothing changes.
8. Delete an account holding a subscription: the warning says the subscription stops now, and it is cancelled in Stripe the moment the account is gone (FR-022).
9. Tag a recurring price `lifetime`: it disappears from the offers and the mismatch is logged.

## Before calling it done

```bash
php artisan monica:localize --check && vendor/bin/pint --dirty --format agent && vendor/bin/phpstan analyse
```
