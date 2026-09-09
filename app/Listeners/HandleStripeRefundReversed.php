<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\RestoreAccess;
use App\Helpers\StripeCharge;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Put full access back when a refund that had taken it away does not go through after all.
 *
 * Stripe reports every step a refund takes, and only two of them mean the money stayed with us: it
 * failed, or it was called off. A refund merely succeeding is the case access was already withdrawn
 * for, so it is left alone.
 */
class HandleStripeRefundReversed implements ShouldQueue
{
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function handle(WebhookReceived $event): void
    {
        if (($event->payload['type'] ?? null) !== 'charge.refund.updated') {
            return;
        }

        $refund = $event->payload['data']['object'] ?? [];

        if (! is_array($refund) || ! in_array($refund['status'] ?? null, ['failed', 'canceled'], true)) {
            return;
        }

        $charge = StripeCharge::behind($refund['charge'] ?? null);

        if ($charge === null) {
            return;
        }

        $account = StripeCharge::accountFor($charge);

        if ($account === null) {
            return;
        }

        new RestoreAccess(account: $account, payment: StripeCharge::paymentFor($charge))->execute();
    }
}
