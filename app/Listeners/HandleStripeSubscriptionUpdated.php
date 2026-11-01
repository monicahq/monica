<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;

/**
 * Forget a move to a longer subscription once it has happened, or once the schedule carrying it is
 * gone. Cashier has already written the new price by then; this only clears what the screen shows
 * as still to come.
 */
class HandleStripeSubscriptionUpdated implements ShouldQueue
{
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function handle(WebhookHandled $event): void
    {
        if (($event->payload['type'] ?? null) !== 'customer.subscription.updated') {
            return;
        }

        $object = $event->payload['data']['object'] ?? [];

        if (! is_array($object) || ! isset($object['id'])) {
            return;
        }

        $subscription = Cashier::$subscriptionModel::query()->where('stripe_id', $object['id'])->first();

        if ($subscription === null) {
            return;
        }

        if ($subscription->scheduled_stripe_price === null) {
            return;
        }

        $scheduleIsGone = ($object['schedule'] ?? null) === null;
        $moveHappened = ($object['items']['data'][0]['price']['id'] ?? null) === $subscription->scheduled_stripe_price;

        if (! $scheduleIsGone && ! $moveHappened) {
            return;
        }

        $subscription->forceFill(['scheduled_stripe_price' => null])->save();
    }
}
