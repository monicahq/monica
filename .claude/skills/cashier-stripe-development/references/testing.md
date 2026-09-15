# Testing Reference

Use `search-docs` for authoritative documentation on testing Cashier integrations.

## Test Cards and Tokens

Use card numbers for browser-based flows (Stripe.js / Checkout). Use `pm_card_*` tokens directly in feature tests that call the Stripe API.

| Card Number | Token | Behavior |
|---|---|---|
| `4242 4242 4242 4242` | `pm_card_visa` | Succeeds immediately |
| `4000 0025 0000 3155` | `pm_card_threeDSecure2Required` | Requires SCA/3DS |
| `4000 0027 6000 3184` | `pm_card_authenticationRequired` | Requires authentication |
| `4000 0000 0000 9995` | `pm_card_chargeDeclinedInsufficientFunds` | Declined, insufficient funds |
| `4000 0000 0000 0002` | `pm_card_chargeDeclined` | Declined |

Use expiry `12/34`, any CVC, any ZIP for card number inputs.

## Feature Test Example

Feature tests that hit the real Stripe test API use `pm_card_*` tokens:

```php
public function test_user_can_subscribe(): void
{
    $user = User::factory()->create();

    $user->newSubscription('default', 'price_xxxx')
        ->create('pm_card_visa');

    $this->assertTrue($user->subscribed('default'));
}

public function test_incomplete_payment_is_handled(): void
{
    $user = User::factory()->create();

    try {
        $user->newSubscription('default', 'price_xxxx')
            ->create('pm_card_threeDSecure2Required');
    } catch (\Laravel\Cashier\Exceptions\IncompletePayment $e) {
        $this->assertTrue($user->subscription('default')->incomplete());
    }
}
```

## Setup Notes

- Use Stripe test mode keys (`sk_test_...`, `pk_test_...`) in your test environment
- Cashier does not ship a global `fake()` helper. Tests hit the real Stripe test API by default.
- Refer to `tests/Feature/` in the Cashier package itself for integration test patterns covering subscription creation, payment methods, and webhook handling
- Use `search-docs` for current guidance on mocking Stripe HTTP calls or using Stripe's test clock feature for time-sensitive scenarios
