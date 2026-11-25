<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Bring a reminder back from the vault's trash, with the upcoming dates its deletion cancelled.
 */
class RestoreReminder
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Reminder $reminder,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->restore();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->reminder->account_id !== $this->user->account_id || $this->reminder->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This reminder is in another vault.');
        }

        if (! $this->reminder->trashed()) {
            throw new ModelNotFoundException('This reminder is not deleted.');
        }
    }

    private function restore(): void
    {
        DB::transaction(function (): void {
            $this->reminder->restoreFromTheTrash();

            new RestoreReminderOccurrences(
                reminder: $this->reminder,
                reason: ReminderOccurrenceCancelledReasonEnum::RuleDeleted,
            )->execute();

            $this->reminder->schedule_revision++;
            $this->reminder->schedule_changed_at = now();
            $this->reminder->save();

            new GenerateReminderOccurrences(reminder: $this->reminder)->execute();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ReminderRestoration,
            vault: $this->vault,
            parameters: ['label' => $this->reminder->field->label ?? $this->reminder->name, 'name' => $this->reminder->remindable?->name],
            loggable: $this->reminder,
        );
    }
}
