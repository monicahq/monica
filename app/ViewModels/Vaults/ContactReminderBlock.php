<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Helpers\CountedPhraseWordings;
use App\Helpers\ReminderSchedule;
use App\Helpers\TimezoneCatalogue;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;

class ContactReminderBlock
{
    /** How many blank moments the panel holds ready behind the add button. */
    private const int BLANKS = 3;

    /** What a blank moment offers before somebody changes it. */
    private const int BLANK_AMOUNT = 7;

    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
        private readonly Field $field,
        private readonly ?Reminder $reminder,
    ) {}

    public function isOn(): bool
    {
        return $this->reminder instanceof Reminder;
    }

    public function name(): ?string
    {
        return $this->reminder?->name;
    }

    public function recurs(): bool
    {
        return $this->reminder === null || $this->reminder->cadence->recurs();
    }

    public function interval(): int
    {
        return $this->reminder instanceof Reminder ? $this->reminder->frequency_interval : 1;
    }

    public function hint(): string
    {
        return $this->isOn()
            ? __('It reads from this date and nothing else.')
            : __('Be told about this date, ahead of time, in your own time zone.');
    }

    public function errorKey(): string
    {
        return "reminders.{$this->field->id}";
    }

    public function blanks(): int
    {
        return self::BLANKS;
    }

    public function minimumAmount(): int
    {
        return Reminder::MINIMUM_AMOUNT;
    }

    public function maximumInterval(): int
    {
        return Reminder::MAXIMUM_INTERVAL;
    }

    /**
     * Everything the panel needs to say the sentence again as somebody types,
     * which is the one thing it cannot ask the server for.
     *
     * @return array<string, mixed>
     */
    public function live(): array
    {
        return [
            'locale' => str_replace('_', '-', app()->getLocale()),
            'on' => $this->reminder instanceof Reminder,
            'held' => count($this->moments()),
            'rows' => $this->rows(),
            'cadence' => $this->cadence(),
            'recurs' => $this->reminder === null || $this->reminder->cadence->recurs(),
            'interval' => $this->reminder instanceof Reminder ? $this->reminder->frequency_interval : 1,
            'time' => $this->time(),
            'zone' => $this->timezone(),
            'maximums' => $this->maximums(),
            'words' => $this->words(),
        ];
    }

    /**
     * The moments already held, then the blank ones the panel reveals one at a
     * time.
     *
     * @return array<int, array{direction: string, amount: int, unit: string, removed: bool}>
     */
    public function rows(): array
    {
        $rows = array_map(fn (array $moment): array => [
            'direction' => $moment['direction'],
            'amount' => $moment['amount'] ?? Reminder::MINIMUM_AMOUNT,
            'unit' => $moment['unit'] ?? ReminderMomentUnitEnum::Days->value,
            'removed' => false,
        ], $this->moments());

        for ($blank = 0; $blank < self::BLANKS; $blank++) {
            $rows[] = [
                'direction' => ReminderMomentDirectionEnum::Before->value,
                'amount' => self::BLANK_AMOUNT,
                'unit' => ReminderMomentUnitEnum::Days->value,
                'removed' => false,
            ];
        }

        return $rows;
    }

    /** @return array<string, int> */
    public function maximums(): array
    {
        $maximums = [];

        foreach (ReminderMomentUnitEnum::cases() as $unit) {
            $maximums[$unit->value] = $unit->maximumAmount();
        }

        return $maximums;
    }

    /** @return array<string, string> */
    public function directions(): array
    {
        return [
            ReminderMomentDirectionEnum::On->value => __('On the day'),
            ReminderMomentDirectionEnum::Before->value => __('Before'),
            ReminderMomentDirectionEnum::After->value => __('After'),
        ];
    }

    /** @return array<string, string> */
    public function units(): array
    {
        return [
            ReminderMomentUnitEnum::Days->value => __('days'),
            ReminderMomentUnitEnum::Weeks->value => __('weeks'),
            ReminderMomentUnitEnum::Months->value => __('months'),
        ];
    }

    /** @return array<string, string> */
    public function cadences(): array
    {
        $cadences = [];

        foreach (ReminderCadenceEnum::recurring() as $cadence) {
            $cadences[$cadence->value] = __($cadence->label());
        }

        return $cadences;
    }

    public function mark(): ?string
    {
        if ($this->reminder === null || ! $this->reminder->is_active) {
            return null;
        }

        return $this->summary();
    }

    /** @return array<string, mixed> */
    public function words(): array
    {
        return [
            'units' => [
                ReminderMomentUnitEnum::Days->value => CountedPhraseWordings::of(':count day|:count days'),
                ReminderMomentUnitEnum::Weeks->value => CountedPhraseWordings::of(':count week|:count weeks'),
                ReminderMomentUnitEnum::Months->value => CountedPhraseWordings::of(':count month|:count months'),
            ],
            'intervals' => [
                ReminderCadenceEnum::Weekly->value => CountedPhraseWordings::of('{1}every week|every :count weeks'),
                ReminderCadenceEnum::Monthly->value => CountedPhraseWordings::of('{1}every month|every :count months'),
                ReminderCadenceEnum::Yearly->value => CountedPhraseWordings::of('{1}every year|every :count years'),
            ],
            'onTheDay' => __('on the day'),
            'before' => __(':span before'),
            'after' => __(':span after'),
            'once' => __('once and no more'),
            'summary' => __('You will be reminded :moments, :cadence, at :time :zone.'),
            'nothing' => __('Choose at least one moment to be told about.'),
            'listSeparator' => __(', '),
            'listPattern' => __(':list and :last'),
        ];
    }

    public function placeholder(): string
    {
        return __(':label — :name', ['label' => $this->field->label, 'name' => $this->contact->name]);
    }

    public function cadence(): string
    {
        if ($this->reminder === null || ! $this->reminder->cadence->recurs()) {
            return ReminderCadenceEnum::Yearly->value;
        }

        return $this->reminder->cadence->value;
    }

    public function timezone(): string
    {
        return $this->reminder instanceof Reminder ? $this->reminder->timezone : $this->user->timezone;
    }

    public function time(): string
    {
        if ($this->reminder === null) {
            return Reminder::DEFAULT_TIME;
        }

        return mb_substr((string) $this->reminder->time_of_day, 0, 5);
    }

    /** @return list<array{timezone: string, label: string}> */
    public function zones(): array
    {
        return array_map(fn (array $zone): array => [
            'timezone' => $zone['timezone'],
            'label' => $zone['country'] === '' ? $zone['city'] : "{$zone['city']}, {$zone['country']}",
        ], TimezoneCatalogue::all());
    }

    public function zoneNote(): string
    {
        if ($this->reminder === null) {
            return __('Filled in from your preferences. It is stored with the reminder, so changing them later does not change it.');
        }

        return __('Fixed when the reminder was made. Changing your preferences does not change it.');
    }

    /** @return list<array{direction: string, amount: int|null, unit: string|null, phrase: string}> */
    public function moments(): array
    {
        if ($this->reminder === null) {
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
        ], ReminderSchedule::momentDates($this->reminder));
    }

    public function summary(): string
    {
        $said = array_column($this->moments(), 'phrase');

        if ($said === []) {
            return __('Choose at least one moment to be told about.');
        }

        $listed = count($said) === 1
            ? $said[0]
            : __(':list and :last', [
                'list' => implode(__(', '), array_slice($said, 0, -1)),
                'last' => end($said),
            ]);

        return __('You will be reminded :moments, :cadence, at :time :zone.', [
            'moments' => $listed,
            'cadence' => ReminderWords::cadence(
                ReminderCadenceEnum::from($this->cadence()),
                $this->reminder instanceof Reminder ? $this->reminder->frequency_interval : 1,
            ),
            'time' => $this->time(),
            'zone' => $this->timezone(),
        ]);
    }

    public function nextLine(): ?string
    {
        if ($this->reminder === null) {
            return null;
        }

        $next = ReminderSchedule::nextDate($this->reminder);

        if (! $next instanceof CarbonImmutable) {
            return __('It has nothing ahead of it any more.');
        }

        return __('Next: :date.', ['date' => $next->isoFormat('LL')]);
    }

    public function quietBecause(): ?string
    {
        if ($this->reminder === null || $this->reminder->is_active) {
            return null;
        }

        return match ($this->reminder->inactive_reason) {
            ReminderInactiveReasonEnum::ValueCleared => __('This reminder is stopped because the date it read is gone. Fill the date in again to start a new one.'),
            ReminderInactiveReasonEnum::RecordArchived => __('This reminder is stopped because this record is archived. Unarchive it and the reminder starts again.'),
            ReminderInactiveReasonEnum::RecordDeleted => __('This reminder is stopped because this record was deleted. Restore it and the reminder starts again.'),
            ReminderInactiveReasonEnum::FieldRemoved => __('This reminder is stopped because this field has left the structure. Put the field back and the reminder starts again.'),
            ReminderInactiveReasonEnum::PausedByUser => __('This reminder is stopped because you paused it. Open its own screen to start it again.'),
            null => __('This reminder is stopped.'),
        };
    }
}
