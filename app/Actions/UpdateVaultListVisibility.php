<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;

/**
 * Choose whether somebody lands on the list of vaults, or goes straight into their default vault.
 */
class UpdateVaultListVisibility
{
    public function __construct(
        private readonly User $user,
        private readonly bool $showsVaultList,
    ) {}

    public function execute(): User
    {
        $this->update();
        $this->log();

        return $this->user;
    }

    private function update(): void
    {
        $this->user->shows_vault_list = $this->showsVaultList;
        $this->user->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::VaultListVisibilityUpdate,
        );
    }
}
