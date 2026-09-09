<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\RestoreAccess;
use App\Helpers\StripeCharge;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Put full access back when a dispute is decided in our favour.
 *
 * A dispute closes several ways and only one of them returns the money, so nothing happens unless
 * Stripe says the dispute was won. Losing it changes nothing either: access came off when the
 * dispute was opened and stays off.
 */
class HandleStripeDisputeClosed implements ShouldQueue
{
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function handle(WebhookReceived $event): void
    {
        if (($event->payload['type'] ?? null) !== 'charge.dispute.closed') {
            return;
        }

        $dispute = $event->payload['data']['object'] ?? [];

        if (! is_array($dispute) || ($dispute['status'] ?? null) !== 'won') {
            return;
        }

        $charge = StripeCharge::behind($dispute['charge'] ?? null);

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
