<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderSourceEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ReminderIsNotValid;
use App\Exceptions\ReminderMomentIsNotValid;
use App\Exceptions\ValueAlreadyHasAReminder;
use App\Helpers\ReminderTime;
use App\Helpers\TextSanitizer;
use App\Helpers\TimezoneCatalogue;
use App\Jobs\LogUserAction;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\User;
use App\Rules\ReminderMoments;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Ask to be reminded of a date somebody has written on a record.
 *
 * The rule is built from the value rather than from the record and the field
 * separately, because the value is the thing it reads. That is what caps it at
 * one live rule per value, and what lets a date inside a repetition carry its
 * own rule with nothing added.
 *
 * The zone is frozen here and never read again from the user. "Every year at
 * 9am" means 9am where somebody was when they asked for it, and moving away
 * later must not shift a reminder already set.
 *
 * An empty wording is filled in rather than refused. Somebody who ticked the box
 * and typed nothing has said what they want, and the field and the record
 * between them already say it: the label and the name are what the screen offers
 * as a placeholder, so storing them is storing what was on the screen.
 *
 * What it refuses is what could not fire, or could only fire twice over: a field
 * whose type reads no date, a value that holds none, a set of moments that says
 * nothing, and a second rule on a value that already has one.
 */
class CreateReminder
{
    private Reminder $reminder;

    private string $wording;

    /**
     * @param  array<int, array<array-key, mixed>>  $moments
     */
    public function __construct(
        private readonly User $user,
        private readonly FieldValue $fieldValue,
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
            $this->refuseASecondRule();
            $this->create();
            $this->generate();
        });

        $this->log();

        return $this->reminder;
    }

    /**
     * Refuse a second live rule on a date that already carries one, with the
     * value locked while the question is asked.
     *
     * The lock is what makes it hold. Two saves arriving together would both
     * find no rule and both write one, and a unique index cannot be the backstop
     * because it cannot say "one *live* row" under soft deletes. Locking the row
     * every rule hangs off makes the second save wait for the first to finish
     * and then see it.
     */
    private function refuseASecondRule(): void
    {
        FieldValue::query()->whereKey($this->fieldValue->id)->lockForUpdate()->first();

        if ($this->fieldValue->reminder()->exists()) {
            throw new ValueAlreadyHasAReminder(__('There is already a reminder on this date.'));
        }
    }

    private function sanitize(): void
    {
        $given = TextSanitizer::nullablePlainText($this->name);

        $this->wording = $given ?? __(':label — :name', [
            'label' => $this->fieldValue->field->label,
            'name' => $this->fieldValue->valuable->name,
        ]);
    }

    private function validate(): void
    {
        if ($this->fieldValue->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value is in another account.');
        }

        if (! $this->fieldValue->field->type->canDriveAReminder()) {
            throw new ReminderIsNotValid(__('This kind of field holds no date to be reminded of.'));
        }

        if ($this->fieldValue->value_date === null) {
            throw new ReminderIsNotValid(__('There is no date here to be reminded of.'));
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

        if (TimezoneCatalogue::find($this->zone()) === null) {
            throw new ReminderIsNotValid(__('This is not a time zone the product knows.'));
        }
    }

    private function create(): void
    {
        $this->reminder = Reminder::query()->create([
            'account_id' => $this->fieldValue->account_id,
            'vault_id' => $this->fieldValue->vault_id,
            'remindable_type' => $this->fieldValue->valuable_type,
            'remindable_id' => $this->fieldValue->valuable_id,
            'field_id' => $this->fieldValue->field_id,
            'field_value_id' => $this->fieldValue->id,
            'name' => $this->wording,
            'source' => ReminderSourceEnum::FieldValue,
            'cadence' => $this->cadence,
            'frequency_interval' => $this->cadence->recurs() ? $this->frequencyInterval : 1,
            'moments' => ReminderMoments::normalise($this->moments),
            'time_of_day' => $this->timeOfDay,
            'timezone' => $this->zone(),
            'is_active' => true,
            'created_by_user_id' => $this->user->id,
        ]);
    }

    /**
     * The deadlines the new rule produces inside the rolling window, written now
     * rather than waiting for the nightly sweep. A reminder somebody has just
     * asked for and which produces nothing until tomorrow reads as a reminder
     * that did not work.
     */
    private function generate(): void
    {
        new GenerateReminderOccurrences(reminder: $this->reminder)->execute();
    }

    /**
     * The zone the rule is frozen in, which is whatever was chosen and otherwise
     * whatever the person's preferences say.
     */
    private function zone(): string
    {
        return $this->timezone ?? $this->user->timezone;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ReminderCreation,
            vault: $this->fieldValue->vault,
            parameters: ['label' => $this->fieldValue->field->label, 'name' => $this->fieldValue->valuable->name],
            loggable: $this->reminder,
        )->onQueue('low');
    }
}
