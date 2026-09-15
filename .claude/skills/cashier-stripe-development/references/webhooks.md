# Webhooks Reference

Use `search-docs` for authoritative documentation on webhooks.

## Auto-Registered Routes

Cashier registers two routes automatically under the `cashier.path` prefix (`config('cashier.path')`, default `stripe`):

- `POST /{cashier.path}/webhook` named `cashier.webhook`
- `GET /{cashier.path}/payment/{id}` named `cashier.payment`

With the default config these are `/stripe/webhook` and `/stripe/payment/{id}`. If you set `CASHIER_PATH=billing`, they become `/billing/webhook` and `/billing/payment/{id}`.

## CSRF Exclusion

Use the same path prefix you configured for Cashier here. If `CASHIER_PATH=billing`, exclude `billing/*` instead of `stripe/*`.

**Laravel 11+ (`bootstrap/app.php`, default path example):**

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['stripe/*']);
})
```

**Laravel 10 (`app/Http/Middleware/VerifyCsrfToken.php`, default path example):**

```php
protected $except = [
    'stripe/*',
];
```

## Local Development with Stripe CLI

If you changed `cashier.path`, forward Stripe CLI events to that URL instead of `/stripe/webhook`.

```bash
stripe login
stripe listen --forward-to your-app.test/stripe/webhook
stripe trigger invoice.payment_succeeded
```

The CLI outputs a `whsec_...` signing secret specific to that session. Set it as `STRIPE_WEBHOOK_SECRET` locally. It is not the same as the Dashboard endpoint secret.

## Registering Events in the Stripe Dashboard

Use the Artisan command to create the endpoint automatically with all required events:

```bash
php artisan cashier:webhook
```

Cashier's `cashier:webhook` command registers these events by default:

- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- `customer.updated` / `customer.deleted`
- `invoice.payment_action_required`
- `invoice.payment_succeeded`
- `payment_method.automatically_updated`

Cashier's `WebhookController` has built-in handlers for all of the above except `invoice.payment_succeeded`. For renewal hooks, prefer `WebhookReceived` / `WebhookHandled` listeners unless you intentionally add your own controller method.

## Custom Handlers: Extending WebhookController

Method name pattern: `handle` + StudlyCase of event type with dots replaced by underscores.

`customer.subscription.created` becomes `handleCustomerSubscriptionCreated`.

```php
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

class StripeWebhookController extends CashierController
{
    public function handleCustomerSubscriptionCreated(array $payload)
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);

        // your logic after Cashier syncs the subscription

        return $response;
    }
}
```

If you add a method for an event Cashier does not handle internally, such as `invoice.payment_succeeded`, do not call `parent::handle...()` unless the base controller actually defines that method.

In a service provider, disable auto-registration and re-register both Cashier routes so the incomplete-payment flow and `cashier:webhook` command keep working:

```php
Cashier::ignoreRoutes();
```

```php
// routes/web.php
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\PaymentController;

Route::prefix(config('cashier.path'))
    ->name('cashier.')
    ->group(function () {
        Route::get('payment/{id}', [PaymentController::class, 'show'])->name('payment');
        Route::post('webhook', [StripeWebhookController::class, 'handleWebhook'])->name('webhook');
    });
```

Keep the `cashier.webhook` route name unless you plan to pass `--url` explicitly to `php artisan cashier:webhook`.

## Custom Handlers: Listening to Events

The simpler option when you do not need to replace Cashier's internal logic, or when you want to react to events such as `invoice.payment_succeeded` that Cashier does not process itself:

```php
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Cashier\Events\WebhookHandled;

// WebhookReceived fires for every event before Cashier processes it
// WebhookHandled fires after Cashier processes it

Event::listen(WebhookReceived::class, function (WebhookReceived $event) {
    if ($event->payload['type'] === 'invoice.payment_succeeded') {
        // handle renewal
    }
});
```

## Signature Verification

`VerifyWebhookSignature` middleware is applied automatically when `cashier.webhook.secret` is set. No extra wiring is needed.
