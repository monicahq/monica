<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderCadenceEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Helpers\TimezoneCatalogue;
use App\Jobs\LogUserAction;
use App\Models\Reminder;
use App\Models\User;
use App\Rules\ReminderMoments;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

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
        $this->update();
        $this->log();

        return $this->reminder;
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
            throw new InvalidArgumentException($problem);
        }

        if ($this->frequencyInterval < 1 || $this->frequencyInterval > Reminder::MAXIMUM_INTERVAL) {
            throw new InvalidArgumentException('A reminder cannot come back that many times over.');
        }

        if ($this->timezone !== null && TimezoneCatalogue::find($this->timezone) === null) {
            throw new InvalidArgumentException('This is not a time zone the product knows.');
        }
    }

    private function update(): void
    {
        $this->reminder->name = $this->wording;
        $this->reminder->cadence = $this->cadence;
        $this->reminder->frequency_interval = $this->cadence->recurs() ? $this->frequencyInterval : 1;
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
