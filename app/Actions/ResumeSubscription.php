<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\AccountHasNoSubscription;
use App\Exceptions\SubscriptionCannotBeChanged;
use App\Jobs\LogUserAction;
use App\Models\User;
use Laravel\Cashier\Subscription;
use Stripe\Exception\ApiErrorException;

/**
 * Take back the cancellation of a subscription that has not ended yet. Stripe drops the scheduled
 * end, so the same subscription renews on the day it always would have, and nothing is billed for
 * changing one's mind.
 */
class ResumeSubscription
{
    private Subscription $subscription;

    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): Subscription
    {
        $this->validate();
        $this->resume();
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

        if (! $subscription->onGracePeriod()) {
            throw new SubscriptionCannotBeChanged(__('The subscription is not ending, so there is nothing to keep.'));
        }

        $this->subscription = $subscription;
    }

    private function resume(): void
    {
        try {
            $this->subscription->resume();
        } catch (ApiErrorException $exception) {
            throw new SubscriptionCannotBeChanged(__('Stripe refused the change, so nothing was changed.'), 0, $exception);
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SubscriptionResumed,
            loggable: $this->subscription,
        )->onQueue('low');
    }
}
