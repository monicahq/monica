<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderOccurrenceStatusEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ReminderCannotBePostponedIntoThePast;
use App\Exceptions\ReminderOccurrenceIsFinished;
use App\Jobs\LogUserAction;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Put one deadline off until later.
 */
class PostponeReminderOccurrence
{
    public function __construct(
        private readonly User $user,
        private readonly ReminderOccurrence $occurrence,
        private readonly CarbonImmutable $until,
    ) {}

    public function execute(): ReminderOccurrence
    {
        $this->validate();

        DB::transaction(function (): void {
            $this->abandonPendingNotifications();
            $this->postpone();
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

        if (! $this->until->isFuture()) {
            throw new ReminderCannotBePostponedIntoThePast('A reminder can only be put off until later.');
        }
    }

    private function postpone(): void
    {
        $zone = $this->occurrence->reminder->timezone ?? 'UTC';

        $this->occurrence->scheduled_for = $this->until;
        $this->occurrence->postponed_to = $this->until;
        $this->occurrence->local_date = $this->until->setTimezone($zone)->startOfDay();
        $this->occurrence->postponed_count++;
        $this->occurrence->status = ReminderOccurrenceStatusEnum::Postponed;
        $this->occurrence->save();
    }

    private function abandonPendingNotifications(): void
    {
        $this->occurrence->notifications()
            ->whereNull('sent_at')
            ->whereNull('failed_at')
            ->update([
                'failed_at' => now(),
                'failure_reason' => __('The reminder was postponed.'),
                'updated_at' => now(),
            ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ReminderOccurrencePostponement,
            vault: $this->occurrence->vault,
            parameters: [
                'name' => $this->occurrence->reminder->name ?? '',
                'date' => $this->occurrence->local_date->format('Y-m-d'),
            ],
            loggable: $this->occurrence,
        )->onQueue('low');
    }
}
