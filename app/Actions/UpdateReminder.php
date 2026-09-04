<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ReminderIsNotValid;
use App\Exceptions\ReminderMomentIsNotValid;
use App\Helpers\ReminderTime;
use App\Helpers\TextSanitizer;
use App\Helpers\TimezoneCatalogue;
use App\Jobs\LogUserAction;
use App\Models\Reminder;
use App\Models\User;
use App\Rules\ReminderMoments;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Change what a reminder says and when it fires.
 *
 * What it does not change is what the rule reads. The value, the field and the
 * record are settled when the rule is made, and correcting the date is a change
 * to the value rather than to the rule: the rule goes on pointing at the same
 * answer and simply reads a different day out of it.
 *
 * The zone moves only when somebody moves it. Leaving it out keeps whatever was
 * frozen, which is the whole of Principle VI here: a preference changed later
 * must not shift a reminder already set, and neither may a save that never
 * mentioned the zone.
 *
 * A change to the schedule takes the deadlines ahead of it away and makes new
 * ones. What is behind is left exactly as it was, because it is the record of
 * what somebody was actually told. The cancelled rows say the rule changed and
 * are never brought back: the replacements are the deadlines the new schedule
 * defines, and reviving the old ones would fire reminders for a schedule nobody
 * asked for any more.
 *
 * Renaming the reminder changes none of that. The wording is not the schedule,
 * so nothing is cancelled and nothing is regenerated, and a deadline somebody
 * has already put off survives being renamed.
 */
class UpdateReminder
{
    private string $wording;

    /**
     * @param  array<int, array<array-key, mixed>>  $moments
     */
    public function __construct(
        private readonly User $user,
        private readonly Reminder $reminder,
        private readonly ReminderCadenceEnum $cadence,
        private readonly array $moments,
        private readonly string $timeOfDay,
        private readonly ?string $name = null,
        private readonly int $frequencyInterval = 1,
        private readonly ?string $timezone = null,
    ) {}

    public function execute(): Reminder
    {
        $this->sanitize();
        $this->validate();

        DB::transaction(function (): void {
            $moved = $this->scheduleIsMoving();
            $this->update();

            if ($moved) {
                $this->rebuildTheDeadlines();
            }
        });

        $this->log();

        return $this->reminder;
    }

    /**
     * Whether this save moves anything a deadline is computed from. The wording
     * is not one of those things.
     */
    private function scheduleIsMoving(): bool
    {
        return $this->reminder->cadence !== $this->cadence
            || $this->reminder->frequency_interval !== $this->intervalToStore()
            || ReminderMoments::normalise($this->reminder->moments) !== ReminderMoments::normalise($this->moments)
            || mb_substr($this->reminder->time_of_day, 0, 5) !== mb_substr($this->timeOfDay, 0, 5)
            || ($this->timezone !== null && $this->timezone !== $this->reminder->timezone);
    }

    private function intervalToStore(): int
    {
        return $this->cadence->recurs() ? $this->frequencyInterval : 1;
    }

    /**
     * Take the deadlines still ahead away and make the ones the new schedule
     * defines. The revision is bumped first, so every new deadline is written
     * under an identity nothing has held before and cannot collide with a row
     * just cancelled.
     */
    private function rebuildTheDeadlines(): void
    {
        new CancelReminderOccurrences(
            reminder: $this->reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RuleChanged,
        )->execute();

        $this->reminder->schedule_revision++;
        $this->reminder->save();

        new GenerateReminderOccurrences(reminder: $this->reminder->refresh())->execute();
    }

    private function sanitize(): void
    {
        $given = TextSanitizer::nullablePlainText($this->name);

        $this->wording = $given ?? __(':label — :name', [
            'label' => $this->reminder->field->label,
            'name' => $this->reminder->remindable->name,
        ]);
    }

    private function validate(): void
    {
        if ($this->reminder->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This reminder is in another account.');
        }

        $problem = ReminderMoments::problemWith($this->moments);

        if ($problem !== null) {
            throw new ReminderMomentIsNotValid($problem);
        }

        if ($this->frequencyInterval < 1 || $this->frequencyInterval > Reminder::MAXIMUM_INTERVAL) {
            throw new ReminderIsNotValid(__('A reminder cannot come back that many times over.'));
        }

        if (! ReminderTime::isATime($this->timeOfDay)) {
            throw new ReminderIsNotValid(__('This is not a time of day.'));
        }

        if ($this->timezone !== null && TimezoneCatalogue::find($this->timezone) === null) {
            throw new ReminderIsNotValid(__('This is not a time zone the product knows.'));
        }
    }

    private function update(): void
    {
        $this->reminder->name = $this->wording;
        $this->reminder->cadence = $this->cadence;
        $this->reminder->frequency_interval = $this->intervalToStore();
        $this->reminder->moments = ReminderMoments::normalise($this->moments);
        $this->reminder->time_of_day = $this->timeOfDay;
        $this->reminder->timezone = $this->timezone ?? $this->reminder->timezone;
        $this->reminder->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ReminderUpdate,
            vault: $this->reminder->vault,
            parameters: ['label' => $this->reminder->field->label, 'name' => $this->reminder->remindable->name],
            loggable: $this->reminder,
        )->onQueue('low');
    }
}
