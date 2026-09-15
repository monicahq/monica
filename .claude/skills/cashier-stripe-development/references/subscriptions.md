# Subscriptions Reference

Use `search-docs` for authoritative documentation on subscriptions.

## Status Checks

| Method | Returns true when |
|---|---|
| `$user->subscribed('default')` | Active or on grace period |
| `->onTrial()` | Trial period active |
| `->onGracePeriod()` | Canceled, period not yet ended |
| `->canceled()` | `ends_at` is set, may still have access |
| `->ended()` | Canceled and grace period expired |
| `->incomplete()` | Awaiting SCA/3DS confirmation |
| `->pastDue()` | Payment overdue |
| `->recurring()` | Active and not on trial |

Check by product or price:

```php
$user->subscribedToProduct('prod_premium', 'default');
$user->subscribedToPrice('price_monthly', 'default');
```

## Swapping Plans

```php
$user->subscription('default')->swap('price_new');
$user->subscription('default')->noProrate()->swap('price_new');
$user->subscription('default')->swapAndInvoice('price_new');
$user->subscription('default')->skipTrial()->swap('price_new');
```

## Quantity

```php
$user->subscription('default')->incrementQuantity();
$user->subscription('default')->decrementQuantity();
$user->subscription('default')->updateQuantity(10);
$user->subscription('default')->noProrate()->updateQuantity(10);
```

## Trials

```php
$user->newSubscription('default', 'price_xxxx')
    ->trialDays(14)
    ->create($paymentMethodId);

$subscription->extendTrial(now()->addDays(7));
```

## Multiple Products on One Subscription

```php
$user->newSubscription('default', ['price_monthly', 'price_chat'])
    ->quantity(5, 'price_chat')
    ->create($paymentMethod);

$user->subscription('default')->addPrice('price_chat');
$user->subscription('default')->removePrice('price_chat');
$user->subscription('default')->swap(['price_pro', 'price_chat']);
```

## Multiple Subscriptions

```php
$user->newSubscription('swimming', 'price_swimming_monthly')->create($pm);
$user->newSubscription('gym', 'price_gym_monthly')->create($pm);

$user->subscription('swimming')->swap('price_swimming_yearly');
$user->subscription('gym')->cancel();
```

## Cancellation and Resumption

```php
$user->subscription('default')->cancel();        // At end of billing period
$user->subscription('default')->cancelNow();     // Immediately
$user->subscription('default')->resume();        // During grace period only
```

## Incomplete Payment Handling

```php
if ($user->hasIncompletePayment('default')) {
    $paymentId = $user->subscription('default')->latestPayment()->id;
    return redirect()->route('cashier.payment', $paymentId);
}
```

Opt out of default deactivation behavior:

```php
Cashier::keepPastDueSubscriptionsActive();
Cashier::keepIncompleteSubscriptionsActive();
```

## Metered / Usage-Based Billing

```php
$user->newSubscription('default')
    ->meteredPrice('price_metered')
    ->create($paymentMethodId);

$user->reportMeterEvent('emails-sent');
$user->reportMeterEvent('emails-sent', quantity: 15);
```
