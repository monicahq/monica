<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Exceptions\AdministratorCannotLockThemselvesOut;
use App\Exceptions\ProviderUnreachable;
use App\Jobs\LogAdministratorAction;
use App\Models\Account;
use App\Models\User;

/**
 * Delete somebody else's account and everything in it, on an administrator's word. The record keeps
 * the account number and nothing else about whose account it was.
 */
class DestroyAccountAsAdministrator
{
    public function __construct(
        private readonly Account $account,
        private readonly User $administrator,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->administrator->account_id === $this->account->id) {
            throw new AdministratorCannotLockThemselvesOut(__('You cannot delete your own account from here. Delete it from your account settings instead.'));
        }
    }

    private function destroy(): void
    {
        try {
            new DestroyAccount(account: $this->account)->execute();
        } catch (ProviderUnreachable $exception) {
            throw new ProviderUnreachable(__('We could not reach Stripe to stop their subscription, so nothing was deleted. Please try again in a moment.'), 0, $exception);
        }
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::AccountDestroyed,
            parameters: ['account' => $this->account->id],
            subject: $this->account,
        )->onQueue('low');
    }
}
