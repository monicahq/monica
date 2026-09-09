<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\RecordSubscription;
use App\Models\Account;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;

/**
 * Write down that a subscription started, and tell the owner full access is open.
 *
 * It listens after Cashier has handled the event rather than before, because the subscription row is
 * what says access is held and it does not exist until then. Nothing about access is written here.
 */
class HandleStripeSubscription implements ShouldQueue
{
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function handle(WebhookHandled $event): void
    {
        if (($event->payload['type'] ?? null) !== 'customer.subscription.created') {
            return;
        }

        $id = $event->payload['data']['object']['id'] ?? null;

        if (! is_string($id) || $id === '') {
            return;
        }

        $subscription = Cashier::$subscriptionModel::query()->where('stripe_id', $id)->first();

        if ($subscription === null) {
            return;
        }

        $account = $subscription->owner;

        if (! $account instanceof Account) {
            return;
        }

        new RecordSubscription(account: $account, subscription: $subscription)->execute();
    }
}
