<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Stop being reminded of a date, without touching the date.
 */
class DestroyReminder
{
    public function __construct(
        private readonly User $user,
        private readonly Reminder $reminder,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->log();

        DB::transaction(function (): void {
            new CancelReminderOccurrences(
                reminder: $this->reminder,
                reason: ReminderOccurrenceCancelledReasonEnum::RuleDeleted,
                includePast: true,
            )->execute();

            $this->reminder->delete();
        });
    }

    private function validate(): void
    {
        if ($this->reminder->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This reminder is in another account.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ReminderRemoval,
            vault: $this->reminder->vault,
            parameters: ['label' => $this->reminder->field->label ?? $this->reminder->name, 'name' => $this->reminder->remindable->name],
            loggable: $this->reminder,
        )->onQueue('low');
    }
}
