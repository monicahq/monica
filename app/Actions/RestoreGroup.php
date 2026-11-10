<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Bring a deleted group back, with every membership it held and the reminders its deletion stopped.
 */
class RestoreGroup
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

        if (! $this->group->trashed()) {
            throw new ModelNotFoundException('This group is not deleted.');
        }

        DB::transaction(function (): void {
            $this->group->restore();

            new ReactivateReminders(
                user: $this->user,
                subject: $this->group,
                reason: ReminderInactiveReasonEnum::RecordDeleted,
            )->execute();
        });

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::GroupRestoration,
            vault: $this->vault,
            parameters: ['name' => $this->group->name],
            loggable: $this->group,
        );
    }
}
