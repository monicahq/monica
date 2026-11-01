<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\AccountHasNoSubscription;
use App\Exceptions\SubscriptionCannotBeChanged;
use App\Helpers\StripeCatalogue;
use App\Jobs\LogUserAction;
use App\Models\User;
use Laravel\Cashier\Subscription;
use Stripe\Exception\ApiErrorException;

/**
 * Move a running subscription up to a longer one, monthly to yearly, by changing the price on the
 * subscription Stripe already holds.
 *
 * Nothing is charged on the day of the change and the billing cycle is left where it is, so the
 * longer price is billed at the renewal already planned. A checkout would have opened a second
 * subscription beside the first, which is why this goes through the API instead.
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
        $this->swap();
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

    private function swap(): void
    {
        try {
            $this->subscription->noProrate()->swap($this->priceId);
        } catch (ApiErrorException $exception) {
            throw new SubscriptionCannotBeChanged(__('Stripe refused the change, so nothing was changed.'), 0, $exception);
        }
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
