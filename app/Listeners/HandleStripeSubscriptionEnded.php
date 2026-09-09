<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\EndAccess;
use App\Enums\AccessEndReasonEnum;
use App\Models\Account;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;

/**
 * Put an account back on the free offer once Stripe has given up on its subscription, or once the
 * period a cancelled one had already paid for is over.
 *
 * Why it ended is in the payload rather than in the local row, so this can run after Cashier has
 * recorded the cancellation and on a queue: a card refused once must not end anything, and only
 * Stripe abandoning the subscription does.
 */
class HandleStripeSubscriptionEnded implements ShouldQueue
{
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function handle(WebhookHandled $event): void
    {
        if (($event->payload['type'] ?? null) !== 'customer.subscription.deleted') {
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

        $account = $subscription->owner;

        if (! $account instanceof Account) {
            return;
        }

        $reason = ($object['cancellation_details']['reason'] ?? null) === 'payment_failed'
            ? AccessEndReasonEnum::PaymentFailed
            : AccessEndReasonEnum::Cancelled;

        new EndAccess(account: $account, reason: $reason)->execute();
    }
}
