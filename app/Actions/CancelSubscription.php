<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\AccountHasNoSubscription;
use App\Jobs\LogUserAction;
use App\Models\User;
use Laravel\Cashier\Subscription;

/**
 * Stop a subscription renewing. Access stays until the end of the period already paid for and never
 * a day less, which is why Stripe is asked to end it at the next billing date rather than at once. A
 * move to a longer subscription still waiting for that date is dropped with it.
 */
class CancelSubscription
{
    private Subscription $subscription;

    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): Subscription
    {
        $this->validate();
        $this->cancel();
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

        $this->subscription = $subscription;
    }

    private function cancel(): void
    {
        new ReleaseSubscriptionSchedule(subscription: $this->subscription)->execute();

        $this->subscription->cancel();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SubscriptionCancelled,
            loggable: $this->subscription,
        );
    }
}
