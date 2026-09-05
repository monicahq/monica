<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\User;

/**
 * Let somebody sign in again.
 */
class ActivateUser
{
    public function __construct(
        private readonly User $user,
        private readonly User $administrator,
    ) {}

    public function execute(): User
    {
        $this->activate();
        $this->log();

        return $this->user;
    }

    private function activate(): void
    {
        $this->user->is_active = true;
        $this->user->save();
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::UserReactivated,
            account: $this->user->account,
            parameters: ['name' => $this->user->name],
            subject: $this->user,
        )->onQueue('low');
    }
}
