<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Account;
use App\Models\User;

/**
 * Give an account the full product, as though the one time purchase had been made.
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
        $this->account->has_lifetime_access = true;
        $this->account->purchased_at = $this->account->purchased_at ?? now();
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
