<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Account;
use App\Models\User;

/**
 * Give an account the full product without anything being ordered or paid.
 *
 * A grant is a dated fact on the account rather than a row among the orders: nothing was ordered at
 * Stripe, so there is no session, no state and no price to write. Who granted it is in the
 * administrator log. Granting again leaves the first date alone.
 */
class GrantLifetimeAccess
{
    public function __construct(
        private readonly Account $account,
        private readonly User $administrator,
    ) {}

    public function execute(): Account
    {
        $this->grant();
        $this->log();

        return $this->account;
    }

    private function grant(): void
    {
        $this->account->access_granted_at = $this->account->access_granted_at ?? now();
        $this->account->save();
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::LifetimeAccessGranted,
            account: $this->account,
            parameters: ['name' => $this->account->ownerName()],
            subject: $this->account,
        )->onQueue('low');
    }
}
