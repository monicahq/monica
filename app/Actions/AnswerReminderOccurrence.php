<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderAnswerEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Record whether somebody remembered one date or forgot it. The answer is final: it takes the date
 * off the lists still waiting, drops the notifications not sent yet, and touches no other date.
 */
class AnswerReminderOccurrence
{
    public function __construct(
        private readonly User $user,
        private readonly ReminderOccurrence $occurrence,
        private readonly ReminderAnswerEnum $answer,
    ) {}

    public function execute(): ReminderOccurrence
    {
        $this->validate();

        DB::transaction(function (): void {
            $this->answer();
            $this->abandonPendingNotifications();
        });

        $this->log();

        return $this->occurrence->refresh();
    }

    private function validate(): void
    {
        if ($this->occurrence->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This deadline is in another account.');
        }

        $this->user->authorize(
            $this->answer === ReminderAnswerEnum::Remembered ? 'remember' : 'forget',
            $this->occurrence,
        );
    }

    private function answer(): void
    {
        $written = ReminderOccurrence::query()
            ->whereKey($this->occurrence->id)
            ->active()
            ->update([
                'answer' => $this->answer,
                'answered_at' => now(),
                'updated_at' => now(),
            ]);

        if ($written === 0) {
            throw new AuthorizationException(__('This reminder has already been answered.'));
        }
    }

    private function abandonPendingNotifications(): void
    {
        $this->occurrence->notifications()
            ->whereNull('sent_at')
            ->whereNull('failed_at')
            ->update([
                'failed_at' => now(),
                'failure_reason' => __('The reminder was answered.'),
                'updated_at' => now(),
            ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: $this->answer === ReminderAnswerEnum::Remembered
                ? UserActionEnum::ReminderOccurrenceRemembered
                : UserActionEnum::ReminderOccurrenceForgotten,
            vault: $this->occurrence->vault,
            parameters: [
                'name' => $this->occurrence->reminder->name ?? '',
                'date' => $this->occurrence->local_date->format('Y-m-d'),
            ],
            loggable: $this->occurrence,
        )->onQueue('low');
    }
}
