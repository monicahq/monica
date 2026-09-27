<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Helpers\ReminderSchedule;
use App\Helpers\TimezoneCatalogue;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;

class ContactReminderBlock
{
    public const string ONCE = 'once';

    /** @var list<int> */
    private const array YEARLY_INTERVALS = [1, 2, 5, 10];

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

    public function errorKey(): string
    {
        return "reminders.{$this->field->id}";
    }

    /**
     * How often the reminder comes back, as one value of the Repeats list: `once`, or the cadence
     * and its interval, such as `yearly:2`.
     */
    public function repeat(): string
    {
        if ($this->reminder === null) {
            return 'yearly:1';
        }

        if (! $this->reminder->cadence->recurs()) {
            return self::ONCE;
        }

        return "{$this->reminder->cadence->value}:{$this->reminder->frequency_interval}";
    }

    /**
     * The choices the Repeats list offers. A rhythm set before the list was this short, such as
     * every month, stays on offer for the reminder that has it, so saving never changes it silently.
     *
     * @return array<string, string>
     */
    public function repeats(): array
    {
        $repeats = [self::ONCE => __('Only once')];

        foreach (self::YEARLY_INTERVALS as $interval) {
            $repeats["yearly:{$interval}"] = $this->wordsFor(ReminderCadenceEnum::Yearly, $interval);
        }

        if (! array_key_exists($this->repeat(), $repeats) && $this->reminder instanceof Reminder) {
            $repeats[$this->repeat()] = $this->wordsFor($this->reminder->cadence, $this->reminder->frequency_interval);
        }

        return $repeats;
    }

    /**
     * Everything the panel needs to say again, as somebody types, when the next reminder falls.
     *
     * @return array<string, mixed>
     */
    public function live(): array
    {
        return [
            'locale' => str_replace('_', '-', app()->getLocale()),
            'on' => $this->reminder instanceof Reminder,
            'repeat' => $this->repeat(),
            'time' => $this->time(),
            'next' => $this->nextLine(),
            'words' => [
                'next' => __('Next reminder on :when, on the day.'),
                'when' => __(':date at :time'),
                'nothing' => __('It has nothing ahead of it any more.'),
                'noDate' => __('Choose a date to see when the next reminder falls.'),
            ],
        ];
    }

    public function mark(): ?string
    {
        if ($this->reminder === null || ! $this->reminder->is_active) {
            return null;
        }

        return $this->summary();
    }

    public function placeholder(): string
    {
        return __(':label — :name', ['label' => $this->field->label, 'name' => $this->contact->name]);
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
            return __('You will be notified on the day. The time zone is yours by default.');
        }

        return __('You will be notified on the day. The time zone was fixed when the reminder was made.');
    }

    public function summary(): string
    {
        $cadence = $this->reminder->cadence ?? ReminderCadenceEnum::Yearly;

        return __('You will be reminded on the day, :cadence, at :time :zone.', [
            'cadence' => ReminderWords::cadence($cadence, $this->reminder->frequency_interval ?? 1),
            'time' => $this->time(),
            'zone' => $this->timezone(),
        ]);
    }

    /**
     * When the saved reminder next falls, with the date and the hour in bold. The date and the hour
     * are escaped here, since the sentence around them is printed as it is.
     */
    public function nextLine(): ?string
    {
        if ($this->reminder === null) {
            return null;
        }

        $next = ReminderSchedule::nextDate($this->reminder);

        if (! $next instanceof CarbonImmutable) {
            return e(__('It has nothing ahead of it any more.'));
        }

        $when = __(':date at :time', [
            'date' => $next->locale(app()->getLocale())->isoFormat('LL'),
            'time' => $this->time(),
        ]);

        return __('Next reminder on :when, on the day.', [
            'when' => '<strong class="font-semibold text-ink">'.e($when).'</strong>',
        ]);
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
            null => __('This reminder is stopped.'),
        };
    }

    private function wordsFor(ReminderCadenceEnum $cadence, int $interval): string
    {
        if ($interval === 0) {
            return '';
        }

        return match ($cadence) {
            ReminderCadenceEnum::Once => __('Only once'),
            ReminderCadenceEnum::Weekly => trans_choice('{1}Every week|Every :count weeks', $interval),
            ReminderCadenceEnum::Monthly => trans_choice('{1}Every month|Every :count months', $interval),
            ReminderCadenceEnum::Yearly => trans_choice('{1}Every year|Every :count years', $interval),
        };
    }
}
