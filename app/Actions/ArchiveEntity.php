<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Take a record out of the main lists without deleting it.
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

        new DeactivateReminders(
            user: $this->user,
            subject: $this->entity,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();
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
