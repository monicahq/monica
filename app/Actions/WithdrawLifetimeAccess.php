<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Account;
use App\Models\User;

/**
 * Take the full product back off an account.
 *
 * The purchase date stays where it is. Access being withdrawn today does not
 * unmake a purchase that was made, and an operator looking at this account in a
 * year has to be able to see that one happened.
 */
class WithdrawLifetimeAccess
{
    public function __construct(
        private readonly Account $account,
        private readonly User $administrator,
    ) {}

    public function execute(): Account
    {
        $this->withdraw();
        $this->log();

        return $this->account;
    }

    private function withdraw(): void
    {
        $this->account->has_lifetime_access = false;
        $this->account->save();
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::LifetimeAccessWithdrawn,
            account: $this->account,
            parameters: ['name' => $this->account->ownerName()],
            subject: $this->account,
        )->onQueue('low');
    }
}
