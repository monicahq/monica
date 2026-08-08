<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Allow records of a kind to be created again, in the place the type always
 * had. Deactivating one is meant to be undone as easily as it was done, which
 * is why the position survives it.
 */
class ActivateEntityType
{
    public function __construct(
        private readonly User $user,
        private readonly EntityType $entityType,
    ) {}

    public function execute(): EntityType
    {
        $this->validate();
        $this->activate();
        $this->log();

        return $this->entityType;
    }

    private function validate(): void
    {
        if ($this->entityType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This entity type is in another account.');
        }
    }

    private function activate(): void
    {
        $this->entityType->is_active = true;
        $this->entityType->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::EntityTypeActivated,
            parameters: ['name' => $this->entityType->name_plural],
            loggable: $this->entityType,
        )->onQueue('low');
    }
}
