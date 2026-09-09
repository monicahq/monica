# Quickstart: verifying the order

## Prerequisites

The prerequisites of [024's quickstart](../024-stripe-paywall/quickstart.md), unchanged: Stripe test keys, three tagged prices, `stripe listen` forwarding to `/stripe/webhook` with its signing secret in `.env`, and a queue worker running, since the listeners are queued.

One event has to be forwarded that 024 did not need. `stripe listen` forwards everything, so nothing is needed locally, but the endpoint created at Stripe by `php artisan cashier:webhook` now has to carry `checkout.session.completed` as well. It is in `config/cashier.php`, and the guard test in `tests/Feature/Webhooks/StripeWebhookTest.php` fails when a listener names an event the config does not.

## The suite

```bash
php artisan test --compact
```

Then the narrow ones while working:

```bash
php artisan test --compact --filter=StripeOrder
```

```bash
vendor/bin/pint --dirty --format agent && vendor/bin/phpstan analyse --memory-limit=2G
```

```bash
php artisan monica:localize --check
```

## What to walk

1. **The buyer who comes back.** Buy lifetime access with `4242 4242 4242 4242`. Before paying, check `stripe_orders` holds one row, `unpaid`, naming the session. Pay. The billing screen says full access is held, and the row is `paid`.
2. **The buyer who does not.** Open the checkout, pay, and close the tab instead of following the redirect. Within a few seconds of the queue worker picking up `checkout.session.completed`, the row is `paid` and the caps are lifted. This is the case that is broken today.
3. **The abandoned session.** Open the checkout and close it without paying. The row stays `unpaid` for good, no screen mentions it, and the caps are untouched.
4. **Somebody else's session.** Take the `session_id` from step 1 and load the return address as another account. Nothing is settled, no order is written and the caps do not move.
5. **A second purchase.** With access held, the offers are gone from the billing screen, and `POST api/v1/billing/checkout-sessions` for the lifetime price answers `422`.
6. **The grant.** Grant full access from the admin space. The account holds it, no order is written, the admin screen names the administrator and the day. Withdraw it and access goes.
7. **The refund.** Refund the payment in the Stripe dashboard. Access goes on its own, the order reads `refunded`, the billing screen says when and why, and the owner is emailed. Then refund something on an account holding nothing and check `storage/logs/laravel.log` carries the error naming the charge.
8. **The subscription.** Subscribe monthly, then buy lifetime access. One order, `paid`, the subscription stops renewing, and access never gaps.

## Before calling it done

`accounts` no longer has `has_lifetime_access` or `purchased_at`, and nothing in `app/`, `database/` or `resources/` names them. `docs/STRIPE.md` no longer claims the lifetime offer needs Stripe to raise an invoice, and its event table carries `checkout.session.completed`. The three database connections pass, Pint is clean, PHPStan reports nothing new, and `monica:localize --check` is quiet in nine locales.
