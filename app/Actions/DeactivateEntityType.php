<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Stop new records of a kind being created, without touching one that already exists.
 */
class DeactivateEntityType
{
    public function __construct(
        private readonly User $user,
        private readonly EntityType $entityType,
    ) {}

    public function execute(): EntityType
    {
        $this->validate();
        $this->deactivate();
        $this->log();

        return $this->entityType;
    }

    private function validate(): void
    {
        if ($this->entityType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This entity type is in another account.');
        }
    }

    private function deactivate(): void
    {
        $this->entityType->is_active = false;
        $this->entityType->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::EntityTypeDeactivated,
            parameters: ['name' => $this->entityType->name_plural],
            loggable: $this->entityType,
        );
    }
}
