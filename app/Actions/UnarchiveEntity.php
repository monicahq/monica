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
 * Put a record back into the main lists, and with it the reminders archiving
 * silenced. Only those: a rule quiet because the field it read has left the
 * structure stays quiet and goes on saying so.
 */
class UnarchiveEntity
{
    public function __construct(
        private readonly User $user,
        private readonly Entity $entity,
    ) {}

    public function execute(): Entity
    {
        $this->validate();
        $this->unarchive();
        $this->log();

        return $this->entity;
    }

    private function validate(): void
    {
        if ($this->entity->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This entity is in another account.');
        }
    }

    private function unarchive(): void
    {
        $this->entity->is_archived = false;
        $this->entity->save();

        new ReactivateReminders(
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
            action: UserActionEnum::EntityUnarchived,
            vault: $this->entity->vault,
            parameters: ['name' => $this->entity->name],
            loggable: $this->entity,
        )->onQueue('low');
    }
}
