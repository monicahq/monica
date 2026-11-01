<?php

declare(strict_types=1);

namespace App\Actions;

use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;

/**
 * Detach the schedule Stripe holds on a subscription, dropping any move to a longer one it still
 * carries, because Stripe refuses to cancel a subscription a schedule manages. Stripe is asked rather
 * than the local row, since the schedule stays attached for a year after the move has happened.
 */
class ReleaseSubscriptionSchedule
{
    public function __construct(
        private readonly Subscription $subscription,
    ) {}

    public function execute(): void
    {
        $schedule = $this->subscription->asStripeSubscription()->schedule;

        if ($schedule !== null) {
            Cashier::stripe()->subscriptionSchedules->release(is_string($schedule) ? $schedule : $schedule->id);
        }

        $this->subscription->forceFill(['scheduled_stripe_price' => null])->save();
    }
}
