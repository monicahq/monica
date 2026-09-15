---
name: cashier-stripe-development
description: "Handles Laravel Cashier Stripe integration including subscriptions, webhooks, Stripe Checkout, invoices, charges, refunds, trials, coupons, metered billing, and payment failure handling. Triggered when a user mentions Cashier, Billable, IncompletePayment, stripe_id, newSubscription, Stripe subscriptions, or billing. Also applies when setting up webhooks, handling SCA/3DS payment failures, testing with Stripe test cards, or troubleshooting incomplete subscriptions, CSRF webhook errors, or migration publish issues."
license: MIT
metadata:
  author: laravel
---

# Cashier Stripe Development

## Documentation

Use `search-docs` for detailed Cashier patterns and documentation covering subscriptions, webhooks, Stripe Checkout, invoices, payment methods, and testing.

For deeper guidance on specific topics, read the relevant reference file before implementing:

- `references/subscriptions.md` covers subscription creation, status checks, swapping, trials, quantities, and multiple products
- `references/webhooks.md` covers webhook setup, custom handlers, CSRF exclusion, and local development with the Stripe CLI
- `references/testing.md` covers Stripe test cards, payment method tokens, and feature test patterns

## Basic Usage

### Installation

```bash
php artisan vendor:publish --tag="cashier-migrations"
php artisan migrate
php artisan vendor:publish --tag="cashier-config"
```

### Environment Variables

```
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
CASHIER_CURRENCY=usd
CASHIER_CURRENCY_LOCALE=en_US
```

### Billable Model

<!-- Add Billable Trait -->
```php
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Billable;
}
```

For a non-User model, register it in a service provider:

<!-- Custom Billable Model -->
```php
// In AppServiceProvider::boot()
Cashier::useCustomerModel(Team::class);
```

### Creating a Subscription

<!-- Create Subscription -->
```php
use Laravel\Cashier\Exceptions\IncompletePayment;

try {
    $user->newSubscription('default', 'price_xxxx')->create($paymentMethodId);
} catch (IncompletePayment $e) {
    return redirect()->route('cashier.payment', [$e->payment->id, 'redirect' => route('home')]);
}
```

Always wrap subscription creation in a try/catch for `IncompletePayment`. When a card requires 3DS authentication, Cashier throws this exception. The `cashier.payment` route is auto-registered and handles the confirmation flow.

## Verification

1. Run migrations and confirm `stripe_id`, `pm_type`, `pm_last_four`, and `trial_ends_at` columns exist on the billable model table
2. Test the webhook endpoint with `stripe listen --forward-to localhost/stripe/webhook` if you use the default path, or swap `stripe` for your configured `CASHIER_PATH`
3. Confirm `$user->subscribed('default')` returns the expected value for active and incomplete subscriptions

## Common Pitfalls

- The migration publish tag is `cashier-migrations`, not `cashier`. Running `migrate` before publishing results in missing columns and tables.
- `CASHIER_CURRENCY` must be set explicitly. It defaults to USD, which silently breaks non-US apps.
- The Stripe CLI generates its own webhook signing secret. It is different from the Dashboard endpoint secret. Using the wrong one causes signature verification failures.
- The webhook route must be excluded from CSRF verification using your configured `cashier.path`. If you change `CASHIER_PATH` from `stripe` to `billing`, exclude `billing/*`, not `stripe/*`.
- `canceled()` returns true as soon as `cancel()` is called, but the user still has access during the grace period. Use `ended()` to confirm access is fully revoked.
- `subscribed()` returns true during the grace period even though the subscription is canceled.
- `subscribed()` returns false for `incomplete` and `past_due` subscriptions by default.
- Prices cannot be swapped and quantity cannot be updated while a subscription has an incomplete payment.
- When extending `WebhookController`, call `Cashier::ignoreRoutes()` in a service provider and re-register both `cashier.payment` and `cashier.webhook` under the configured `cashier.path`.
- Use `Cashier::useCustomerModel()` in a service provider to set a custom billable model. There is no `CASHIER_MODEL` env var.
- `trial_ends_at` is a local database column synced via webhooks. It will be stale if webhooks are not configured in production.
- In MySQL, the `stripe_id` column must use `utf8_bin` collation to avoid case-sensitivity issues.
- `noProrate()` has no effect when combined with `swapAndInvoice()`. That method always prorates.
- Methods like `withPromotionCode()` require the Stripe API ID such as `promo_xxxx`, not the customer-facing code. Use `findPromotionCode()` to resolve a code to its ID.
- Always use `search-docs` for the latest Cashier documentation rather than relying on this skill alone.
