<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\AccountHasNoSubscription;
use App\Exceptions\SubscriptionCannotBeChanged;
use App\Helpers\StripeCatalogue;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;
use Stripe\Exception\ApiErrorException;

/**
 * Move a running subscription up to a longer one, monthly to yearly, at its next renewal, through a
 * Stripe subscription schedule. Swapping the price at once would restart the billing cycle and charge
 * the yearly price today, which is why nothing is changed until the period already paid for ends.
 */
class UpgradeSubscription
{
    private Subscription $subscription;

    public function __construct(
        private readonly User $user,
        private readonly string $priceId,
    ) {}

    public function execute(): Subscription
    {
        $this->validate();
        $this->schedule();
        $this->log();

        return $this->subscription;
    }

    private function validate(): void
    {
        $subscription = $this->user->account->subscription();

        if ($subscription === null) {
            throw new AccountHasNoSubscription(__('There is no subscription on this account.'));
        }

        if (! $subscription->valid()) {
            throw new AccountHasNoSubscription(__('There is no subscription on this account.'));
        }

        if ($subscription->onGracePeriod()) {
            throw new AccountHasNoSubscription(__('The subscription is already ending.'));
        }

        if ($subscription->scheduled_stripe_price !== null) {
            throw new SubscriptionCannotBeChanged(__('The subscription already moves up at its next renewal.'));
        }

        $kind = StripeCatalogue::kindOf($this->priceId);

        if ($kind === null || ! $kind->renews()) {
            throw new SubscriptionCannotBeChanged(__('This plan is not a subscription this account can move to.'));
        }

        $current = StripeCatalogue::kindOfSubscription($subscription);

        if ($current === null || ! $kind->isAbove($current)) {
            throw new SubscriptionCannotBeChanged(__('The subscription can only move up to a longer one.'));
        }

        $this->subscription = $subscription;
    }

    private function schedule(): void
    {
        $schedules = Cashier::stripe()->subscriptionSchedules;

        try {
            $schedule = $schedules->create(['from_subscription' => $this->subscription->stripe_id]);
        } catch (ApiErrorException $exception) {
            Log::error("[UpgradeSubscription] Stripe refused the change: " . $exception->getMessage(), [
                'accountId' => $this->user->account->id,
                'stripeId' => $this->subscription->stripe_id,
                'priceId' => $this->priceId,
                'exception' => $exception,
            ]);
            throw new SubscriptionCannotBeChanged(__('Stripe refused the change, so nothing was changed.'), 0, $exception);
        }

        $currentPhase = $schedule->phases[0];
        $currentItem = $currentPhase->items[0];

        try {
            $schedules->update($schedule->id, [
                'end_behavior' => 'release',
                'proration_behavior' => 'none',
                'phases' => [
                    [
                        'items' => [[
                            'price' => is_string($currentItem->price) ? $currentItem->price : $currentItem->price->id,
                            'quantity' => $currentItem->quantity ?? 1,
                        ]],
                        'start_date' => $currentPhase->start_date,
                        'end_date' => $currentPhase->end_date,
                        'automatic_tax' => ['enabled' => Cashier::$calculatesTaxes],
                        'proration_behavior' => 'none',
                    ],
                    [
                        'items' => [['price' => $this->priceId, 'quantity' => 1]],
                        'duration' => ['interval' => 'year', 'interval_count' => 1],
                        'billing_cycle_anchor' => 'phase_start',
                        'automatic_tax' => ['enabled' => Cashier::$calculatesTaxes],
                        'proration_behavior' => 'none',
                    ],
                ],
            ]);
        } catch (ApiErrorException $exception) {
            // A schedule left attached would refuse the next attempt and every cancellation.
            rescue(fn () => $schedules->release($schedule->id));

            Log::error("[UpgradeSubscription] Failed to update schedule, released schedule: " . $exception->getMessage(), [
                'accountId' => $this->user->account->id,
                'stripeId' => $this->subscription->stripe_id,
                'priceId' => $this->priceId,
                'exception' => $exception,
            ]);
            throw new SubscriptionCannotBeChanged(__('Stripe refused the change, so nothing was changed.'), 0, $exception);
        }

        $this->subscription->forceFill(['scheduled_stripe_price' => $this->priceId])->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SubscriptionUpgraded,
            loggable: $this->subscription,
        );
    }
}
