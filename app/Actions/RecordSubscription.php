<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccessKindEnum;
use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Notifications\AccessUnlocked;
use Laravel\Cashier\Subscription;

/**
 * Write down that a subscription started.
 *
 * Access is read from Cashier's own record of the subscription, so nothing about access is written
 * here: only the address the invoices go to, the email saying it is open, and the entry in the
 * account's journal.
 */
class RecordSubscription
{
    public function __construct(
        private readonly Account $account,
        private readonly Subscription $subscription,
    ) {}

    public function execute(): Account
    {
        $this->record();
        $this->notify();
        $this->log();

        return $this->account;
    }

    private function record(): void
    {
        $this->account->billing_email = $this->account->stripeEmail() ?? $this->account->billing_email;
        $this->account->save();
    }

    private function notify(): void
    {
        $this->account->user?->notify(new AccessUnlocked(kind: AccessKindEnum::Subscription));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->account,
            user: $this->account->user,
            action: UserActionEnum::SubscriptionStarted,
            loggable: $this->subscription,
            source: LogSourceEnum::System,
        );
    }
}
