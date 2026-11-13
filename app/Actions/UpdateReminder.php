<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\ReminderSourceEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ReminderIsNotValid;
use App\Helpers\ReminderSchedule;
use App\Helpers\ReminderTime;
use App\Helpers\TextSanitizer;
use App\Helpers\TimezoneCatalogue;
use App\Jobs\LogUserAction;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Change what a reminder says and when it fires, and, for a reminder somebody wrote about a contact,
 * the date it starts from. Answers already given are never touched.
 */
class UpdateReminder
{
    private string $wording;

    public function __construct(
        private readonly User $user,
        private readonly Reminder $reminder,
        private readonly ReminderCadenceEnum $cadence,
        private readonly string $timeOfDay,
        private readonly ?string $name = null,
        private readonly int $frequencyInterval = 1,
        private readonly ?string $timezone = null,
        private readonly ?string $startsOn = null,
    ) {}

    public function execute(): Reminder
    {
        $this->sanitize();
        $this->validate();

        $moved = $this->scheduleIsMoving();
        $renamed = $this->wording !== $this->reminder->name;

        DB::transaction(function () use ($moved): void {
            if ($moved) {
                $this->cancelWhatIsAhead();
            }

            $this->update();

            if ($moved) {
                $this->rebuildTheDeadlines();
            }
        });

        if ($moved || $renamed) {
            $this->log();
        }

        return $this->reminder;
    }

    private function scheduleIsMoving(): bool
    {
        return $this->reminder->cadence !== $this->cadence
            || $this->reminder->frequency_interval !== $this->intervalToStore()
            || ($this->startsOn !== null && $this->startsOn !== $this->reminder->starts_on?->format('Y-m-d'))
            || mb_substr($this->reminder->time_of_day, 0, 5) !== mb_substr($this->timeOfDay, 0, 5)
            || ($this->timezone !== null && $this->timezone !== $this->reminder->timezone);
    }

    private function candidate(): Reminder
    {
        $rule = $this->reminder->replicate();
        $rule->cadence = $this->cadence;
        $rule->frequency_interval = $this->intervalToStore();
        $rule->time_of_day = $this->timeOfDay;
        $rule->timezone = $this->timezone ?? $this->reminder->timezone;
        $rule->starts_on = $this->startsOn ?? $this->reminder->starts_on;
        $rule->schedule_changed_at = null;

        return $rule->setRelation('fieldValue', $this->reminder->fieldValue);
    }

    private function intervalToStore(): int
    {
        return $this->cadence->recurs() ? $this->frequencyInterval : 1;
    }

    private function cancelWhatIsAhead(): void
    {
        new CancelReminderOccurrences(
            reminder: $this->reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RuleChanged,
            includePast: false,
        )->execute();
    }

    private function rebuildTheDeadlines(): void
    {
        $this->reminder->schedule_revision++;
        $this->reminder->schedule_changed_at = now();
        $this->reminder->save();

        new GenerateReminderOccurrences(reminder: $this->reminder->refresh())->execute();
    }

    private function sanitize(): void
    {
        $given = TextSanitizer::nullablePlainText($this->name);

        $this->wording = $given ?? ($this->reminder->field === null
            ? $this->reminder->name
            : __(':label — :name', [
                'label' => $this->reminder->field->label,
                'name' => $this->reminder->remindable?->name,
            ]));
    }

    private function validate(): void
    {
        if ($this->reminder->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This reminder is in another account.');
        }

        if ($this->startsOn !== null && $this->reminder->source !== ReminderSourceEnum::Manual) {
            throw new ReminderIsNotValid(__('This reminder reads its date from the contact.'), field: 'starts_on');
        }

        if ($this->startsOn !== null && CarbonImmutable::hasFormat($this->startsOn, 'Y-m-d') === false) {
            throw new ReminderIsNotValid(__('This is not a date.'), field: 'starts_on');
        }

        if ($this->frequencyInterval < 1 || $this->frequencyInterval > Reminder::MAXIMUM_INTERVAL) {
            throw new ReminderIsNotValid(__('A reminder cannot come back that many times over.'), field: 'interval');
        }

        if (! ReminderTime::isATime($this->timeOfDay)) {
            throw new ReminderIsNotValid(__('This is not a time of day.'), field: 'time');
        }

        if ($this->timezone !== null && TimezoneCatalogue::find($this->timezone) === null) {
            throw new ReminderIsNotValid(__('This is not a time zone the application knows.'), field: 'timezone');
        }

        $today = CarbonImmutable::now($this->timezone ?? $this->reminder->timezone)->toDateString();

        if ($this->startsOn !== null && $this->startsOn !== $this->reminder->starts_on?->format('Y-m-d') && $this->startsOn < $today) {
            throw new ReminderIsNotValid(__('A reminder cannot start in the past.'), field: 'starts_on');
        }

        if ($this->scheduleIsMoving() && ! ReminderSchedule::hasADateLeft($this->candidate())) {
            throw new ReminderIsNotValid(
                __('This date has already passed, so a reminder sent once would never arrive.'),
                field: $this->reminder->source === ReminderSourceEnum::Manual ? 'starts_on' : 'cadence',
            );
        }
    }

    private function update(): void
    {
        $this->reminder->name = $this->wording;
        $this->reminder->cadence = $this->cadence;
        $this->reminder->frequency_interval = $this->intervalToStore();
        $this->reminder->starts_on = $this->startsOn ?? $this->reminder->starts_on;
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
            parameters: ['label' => $this->reminder->field->label ?? $this->reminder->name, 'name' => $this->reminder->remindable?->name],
            loggable: $this->reminder,
        );
    }
}
