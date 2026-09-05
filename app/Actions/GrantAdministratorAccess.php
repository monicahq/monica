<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Exceptions\AdministratorCapacityIsAlreadyHeld;
use App\Jobs\LogAdministratorAction;
use App\Models\Administrator;
use App\Models\User;

/**
 * Let somebody run the instance.
 */
class GrantAdministratorAccess
{
    private Administrator $capacity;

    public function __construct(
        private readonly User $user,
        private readonly ?User $administrator = null,
    ) {}

    public function execute(): Administrator
    {
        $this->validate();
        $this->grant();
        $this->log();

        return $this->capacity;
    }

    private function validate(): void
    {
        if ($this->user->administrator()->exists()) {
            throw new AdministratorCapacityIsAlreadyHeld("{$this->user->name} already administers this instance.");
        }
    }

    private function grant(): void
    {
        $this->capacity = Administrator::query()->create([
            'user_id' => $this->user->id,
            'granted_by_user_id' => $this->administrator?->id,
        ]);
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::AdministratorAccessGranted,
            account: $this->user->account,
            parameters: ['name' => $this->user->name],
            subject: $this->user,
        )->onQueue('low');
    }
}
