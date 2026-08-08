<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteAnEntityTypeInUse;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete a kind of object, which is only allowed while no record of it exists.
 *
 * A type that still holds records refuses to go, and the answer is to delete
 * those records first, deliberately, or to deactivate the type and keep them.
 * Taking a hundred animals down with the word "Animal" is not something a
 * single button should be able to do.
 *
 * The deletion is soft, and the slug is not released with it: a link that went
 * out pointing at this type must not later land on a different one.
 */
class DestroyEntityType
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly EntityType $entityType,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->entityType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This entity type is in another account.');
        }

        if ($this->entityType->entity_count > 0) {
            throw new CannotDeleteAnEntityTypeInUse("This type still holds {$this->entityType->entity_count} records.");
        }
    }

    private function destroy(): void
    {
        $this->name = $this->entityType->name_plural;

        $this->entityType->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::EntityTypeDeletion,
            parameters: ['name' => $this->name],
            loggable: $this->entityType,
        )->onQueue('low');
    }
}
