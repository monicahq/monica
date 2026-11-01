<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderSourceEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ReminderIsNotValid;
use App\Helpers\ReminderSchedule;
use App\Helpers\ReminderTime;
use App\Helpers\TextSanitizer;
use App\Helpers\TimezoneCatalogue;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Write a reminder about a contact that carries its own date, rather than one read from a date on
 * their page.
 */
class CreateContactReminder
{
    private Reminder $reminder;

    private string $wording;

    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
        private readonly string $name,
        private readonly string $startsOn,
        private readonly ReminderCadenceEnum $cadence,
        private readonly string $timeOfDay,
        private readonly int $frequencyInterval = 1,
        private readonly ?string $timezone = null,
    ) {}

    public function execute(): Reminder
    {
        $this->sanitize();
        $this->validate();

        DB::transaction(function (): void {
            $this->create();
            $this->generate();
        });

        $this->log();

        return $this->reminder;
    }

    private function sanitize(): void
    {
        $this->wording = TextSanitizer::plainText($this->name);
    }

    private function validate(): void
    {
        if ($this->contact->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }

        if ($this->contact->is_archived) {
            throw new ReminderIsNotValid(__('This contact is archived, so a reminder about them would never be sent.'));
        }

        if ($this->wording === '') {
            throw new ReminderIsNotValid(__('Say what the reminder is about.'), field: 'name');
        }

        if (! CarbonImmutable::hasFormat($this->startsOn, 'Y-m-d')) {
            throw new ReminderIsNotValid(__('This is not a date.'), field: 'starts_on');
        }

        if ($this->frequencyInterval < 1 || $this->frequencyInterval > Reminder::MAXIMUM_INTERVAL) {
            throw new ReminderIsNotValid(__('A reminder cannot come back that many times over.'), field: 'interval');
        }

        if (! ReminderTime::isATime($this->timeOfDay)) {
            throw new ReminderIsNotValid(__('This is not a time of day.'), field: 'time');
        }

        if (TimezoneCatalogue::find($this->zone()) === null) {
            throw new ReminderIsNotValid(__('This is not a time zone the application knows.'), field: 'timezone');
        }

        $today = CarbonImmutable::now($this->zone())->toDateString();

        if ($this->startsOn < $today) {
            throw new ReminderIsNotValid(__('A reminder cannot start in the past.'), field: 'starts_on');
        }

        if (! ReminderSchedule::hasADateLeft($this->candidate())) {
            throw new ReminderIsNotValid(__('The time for this reminder has already passed today.'), field: 'time');
        }
    }

    private function candidate(): Reminder
    {
        $rule = new Reminder;
        $rule->source = ReminderSourceEnum::Manual;
        $rule->starts_on = CarbonImmutable::parse($this->startsOn, $this->zone());
        $rule->cadence = $this->cadence;
        $rule->frequency_interval = $this->frequencyInterval;
        $rule->time_of_day = $this->timeOfDay;
        $rule->timezone = $this->zone();

        return $rule;
    }

    private function create(): void
    {
        $this->reminder = Reminder::query()->create([
            'account_id' => $this->contact->account_id,
            'vault_id' => $this->contact->vault_id,
            'remindable_type' => $this->contact->getMorphClass(),
            'remindable_id' => $this->contact->id,
            'field_id' => null,
            'field_value_id' => null,
            'name' => $this->wording,
            'source' => ReminderSourceEnum::Manual,
            'starts_on' => $this->startsOn,
            'cadence' => $this->cadence,
            'frequency_interval' => $this->cadence->recurs() ? $this->frequencyInterval : 1,
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
            vault: $this->contact->vault,
            parameters: ['label' => $this->wording, 'name' => $this->contact->name],
            loggable: $this->reminder,
        );
    }
}
