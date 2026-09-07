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
 */
class CreateReminder
{
    private Reminder $reminder;

    private string $wording;

    /** @param  array<int, array<array-key, mixed>>  $moments */
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
            throw new ReminderIsNotValid(__('This is not a time zone the application knows.'));
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

    private function generate(): void
    {
        new GenerateReminderOccurrences(reminder: $this->reminder)->execute();
    }

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
