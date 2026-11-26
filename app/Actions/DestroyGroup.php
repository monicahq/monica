<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\GroupNote;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Send a group to the vault's trash with its memberships, notes, files and reminders. The people in it
 * are left as they are, and restoring the group brings all of it back.
 */
class DestroyGroup
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
    ) {}

    public function execute(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->group->account_id !== $this->user->account_id || $this->group->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This group is in another vault.');
        }

        DB::transaction(function (): void {
            $this->group->delete();

            new DeactivateReminders(
                user: $this->user,
                subject: $this->group,
                reason: ReminderInactiveReasonEnum::RecordDeleted,
            )->execute();

            $this->group->members()->get()->each(fn (GroupMember $membership) => $membership->deleteWith($this->group));
            $this->group->notes()->get()->each(fn (GroupNote $note) => $note->deleteWith($this->group));
            $this->group->files()->get()->each(fn (GroupFile $attachment) => $attachment->deleteWith($this->group));
            $this->group->reminders()->get()->each(fn (Reminder $reminder) => $reminder->deleteWith($this->group));
        });

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::GroupDeletion,
            vault: $this->vault,
            parameters: ['name' => $this->group->name],
            loggable: $this->group,
        );
    }
}
