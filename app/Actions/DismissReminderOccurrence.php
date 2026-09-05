<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderOccurrenceStatusEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ReminderOccurrenceIsFinished;
use App\Jobs\LogUserAction;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Stop one deadline from interrupting somebody again without switching its rule off.
 */
class DismissReminderOccurrence
{
    public function __construct(
        private readonly User $user,
        private readonly ReminderOccurrence $occurrence,
    ) {}

    public function execute(): ReminderOccurrence
    {
        $this->validate();

        DB::transaction(function (): void {
            $this->dismiss();
            $this->abandonPendingNotifications();
        });

        $this->log();

        return $this->occurrence;
    }

    private function validate(): void
    {
        if ($this->occurrence->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This deadline is in another account.');
        }

        if (! $this->occurrence->isActionable()) {
            throw new ReminderOccurrenceIsFinished('This reminder has already been dealt with.');
        }
    }

    private function dismiss(): void
    {
        $this->occurrence->status = ReminderOccurrenceStatusEnum::Dismissed;
        $this->occurrence->dismissed_at = now();
        $this->occurrence->save();
    }

    private function abandonPendingNotifications(): void
    {
        $this->occurrence->notifications()
            ->whereNull('sent_at')
            ->whereNull('failed_at')
            ->update([
                'failed_at' => now(),
                'failure_reason' => __('The reminder was dismissed.'),
                'updated_at' => now(),
            ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ReminderOccurrenceDismissal,
            vault: $this->occurrence->vault,
            parameters: [
                'name' => $this->occurrence->reminder->name ?? '',
                'date' => $this->occurrence->local_date->format('Y-m-d'),
            ],
            loggable: $this->occurrence,
        )->onQueue('low');
    }
}
