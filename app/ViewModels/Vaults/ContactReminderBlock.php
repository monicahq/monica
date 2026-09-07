<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Helpers\ReminderSchedule;
use App\Helpers\TimezoneCatalogue;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;

class ContactReminderBlock
{
    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
        private readonly Field $field,
        private readonly ?Reminder $reminder,
    ) {}

    /** @return array<string, mixed> */
    public function get(): array
    {
        $rule = $this->reminder;

        return [
            'isOn' => $rule instanceof Reminder,
            'name' => $rule?->name,
            'placeholder' => $this->placeholder(),
            'recurs' => $rule === null || $rule->cadence->recurs(),
            'cadence' => $this->cadence($rule),
            'interval' => $rule instanceof Reminder ? $rule->frequency_interval : 1,
            'time' => $this->time($rule),
            'timezone' => $this->zone($rule),
            'zones' => $this->zones(),
            'zoneNote' => $this->zoneNote($rule),
            'moments' => $this->moments($rule),
            'summary' => $this->summary($rule),
            'nextLine' => $this->nextLine($rule),
            'hint' => $rule instanceof Reminder
                ? __('It reads from this date and nothing else.')
                : __('Be told about this date, ahead of time, in your own time zone.'),
            'quietBecause' => $this->quietBecause($rule),
            'words' => $this->words(),
            'errorKey' => "reminders.{$this->field->id}",
        ];
    }

    public function mark(): ?string
    {
        $rule = $this->reminder;

        if ($rule === null || ! $rule->is_active) {
            return null;
        }

        return $this->summary($rule);
    }

    /** @return array<string, mixed> */
    private function words(): array
    {
        return [
            'units' => [
                ReminderMomentUnitEnum::Days->value => $this->pair(':count day|:count days'),
                ReminderMomentUnitEnum::Weeks->value => $this->pair(':count week|:count weeks'),
                ReminderMomentUnitEnum::Months->value => $this->pair(':count month|:count months'),
            ],
            'intervals' => [
                ReminderCadenceEnum::Weekly->value => $this->pair('{1}every week|every :count weeks'),
                ReminderCadenceEnum::Monthly->value => $this->pair('{1}every month|every :count months'),
                ReminderCadenceEnum::Yearly->value => $this->pair('{1}every year|every :count years'),
            ],
            'onTheDay' => __('on the day'),
            'before' => __(':span before'),
            'after' => __(':span after'),
            'and' => __('and'),
            'once' => __('once and no more'),
            'summary' => __('You will be reminded :moments, :cadence, at :time :zone.'),
            'nothing' => __('Choose at least one moment to be told about.'),
        ];
    }

    /** @return array{one: string, other: string} */
    private function pair(string $phrase): array
    {
        return [
            'one' => trans_choice($phrase, 1, ['count' => ':n']),
            'other' => trans_choice($phrase, 2, ['count' => ':n']),
        ];
    }

    private function placeholder(): string
    {
        return __(':label — :name', ['label' => $this->field->label, 'name' => $this->contact->name]);
    }

    private function cadence(?Reminder $rule): string
    {
        if ($rule === null || ! $rule->cadence->recurs()) {
            return ReminderCadenceEnum::Yearly->value;
        }

        return $rule->cadence->value;
    }

    private function zone(?Reminder $rule): string
    {
        return $rule instanceof Reminder ? $rule->timezone : $this->user->timezone;
    }

    private function time(?Reminder $rule): string
    {
        if ($rule === null) {
            return Reminder::DEFAULT_TIME;
        }

        return mb_substr((string) $rule->time_of_day, 0, 5);
    }

    /** @return list<array{timezone: string, label: string}> */
    private function zones(): array
    {
        return array_map(fn (array $zone): array => [
            'timezone' => $zone['timezone'],
            'label' => $zone['country'] === '' ? $zone['city'] : "{$zone['city']}, {$zone['country']}",
        ], TimezoneCatalogue::all());
    }

    private function zoneNote(?Reminder $rule): string
    {
        if ($rule === null) {
            return __('Filled in from your preferences. It is stored with the reminder, so changing them later does not change it.');
        }

        return __('Fixed when the reminder was made. Changing your preferences does not change it.');
    }

    /** @return list<array{direction: string, amount: int|null, unit: string|null, phrase: string}> */
    private function moments(?Reminder $rule): array
    {
        if ($rule === null) {
            return [[
                'direction' => ReminderMomentDirectionEnum::On->value,
                'amount' => null,
                'unit' => null,
                'phrase' => ReminderWords::moment(ReminderMomentDirectionEnum::On, null, null),
            ]];
        }

        return array_map(fn (array $moment): array => [
            'direction' => $moment['direction']->value,
            'amount' => $moment['amount'],
            'unit' => $moment['unit']?->value,
            'phrase' => ReminderWords::moment($moment['direction'], $moment['amount'], $moment['unit']),
        ], ReminderSchedule::momentDates($rule));
    }

    private function summary(?Reminder $rule): string
    {
        $said = array_column($this->moments($rule), 'phrase');

        if ($said === []) {
            return __('Choose at least one moment to be told about.');
        }

        $listed = count($said) === 1
            ? $said[0]
            : implode(', ', array_slice($said, 0, -1)).' '.__('and').' '.end($said);

        return __('You will be reminded :moments, :cadence, at :time :zone.', [
            'moments' => $listed,
            'cadence' => ReminderWords::cadence(
                ReminderCadenceEnum::from($this->cadence($rule)),
                $rule instanceof Reminder ? $rule->frequency_interval : 1,
            ),
            'time' => $this->time($rule),
            'zone' => $this->zone($rule),
        ]);
    }

    private function nextLine(?Reminder $rule): ?string
    {
        if ($rule === null) {
            return null;
        }

        $next = ReminderSchedule::nextDate($rule);

        if (! $next instanceof CarbonImmutable) {
            return __('It has nothing ahead of it any more.');
        }

        return __('Next: :date.', ['date' => $next->isoFormat('LL')]);
    }

    private function quietBecause(?Reminder $rule): ?string
    {
        if ($rule === null || $rule->is_active) {
            return null;
        }

        return match ($rule->inactive_reason) {
            ReminderInactiveReasonEnum::ValueCleared => __('This reminder is stopped because the date it read is gone. Fill the date in again to start a new one.'),
            ReminderInactiveReasonEnum::RecordArchived => __('This reminder is stopped because this record is archived. Unarchive it and the reminder starts again.'),
            ReminderInactiveReasonEnum::RecordDeleted => __('This reminder is stopped because this record was deleted. Restore it and the reminder starts again.'),
            ReminderInactiveReasonEnum::FieldRemoved => __('This reminder is stopped because this field has left the structure. Put the field back and the reminder starts again.'),
            ReminderInactiveReasonEnum::PausedByUser => __('This reminder is stopped because you paused it. Open its own screen to start it again.'),
            null => __('This reminder is stopped.'),
        };
    }
}
