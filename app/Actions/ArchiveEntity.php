<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Take a record out of the main lists without deleting it. It is what a company
 * nobody deals with any more is for: nothing about it is lost, and it stops
 * getting in the way of what is current.
 *
 * An archived record is also silent: no reminder driven by its own date fields
 * fires while it is archived. A contract renewal on a company nobody deals with
 * any more is exactly the reason somebody archives it. Reminders do not exist
 * yet, so nothing here cancels anything.
 */
class ArchiveEntity
{
    public function __construct(
        private readonly User $user,
        private readonly Entity $entity,
    ) {}

    public function execute(): Entity
    {
        $this->validate();
        $this->archive();
        $this->log();

        return $this->entity;
    }

    private function validate(): void
    {
        if ($this->entity->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This entity is in another account.');
        }
    }

    private function archive(): void
    {
        $this->entity->is_archived = true;
        $this->entity->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::EntityArchived,
            vault: $this->entity->vault,
            parameters: ['name' => $this->entity->name],
            loggable: $this->entity,
        )->onQueue('low');
    }
}
