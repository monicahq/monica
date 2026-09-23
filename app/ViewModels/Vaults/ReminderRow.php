<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\DefaultFieldEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Enums\ReminderPostponePresetEnum;
use App\Helpers\AvailableLocales;
use App\Models\Contact;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;

class ReminderRow
{
    public function __construct(
        private readonly User $user,
        private readonly ReminderOccurrence $occurrence,
    ) {}

    public function uuid(): string
    {
        return $this->occurrence->uuid;
    }

    public function day(): string
    {
        return $this->dueAt()->translatedFormat('j');
    }

    public function month(): string
    {
        return $this->dueAt()->translatedFormat('M');
    }

    public function year(): string
    {
        return $this->dueAt()->translatedFormat('Y');
    }

    public function sentRelative(): string
    {
        return $this->sentAt()->diffForHumans();
    }

    public function sentExact(): string
    {
        return __('Sent :date at :time :abbreviation (:zone, UTC:offset)', $this->partsOf($this->sentAt()));
    }

    public function sentIso(): string
    {
        return $this->sentAt()->toIso8601String();
    }

    public function dismissUrl(): string
    {
        return route('reminders.occurrences.dismissal.update', [
            'vault' => $this->occurrence->vault,
            'reminderOccurrence' => $this->occurrence,
        ]);
    }

    public function testUrl(): string
    {
        return route('reminders.occurrences.testNotification.create', [
            'vault' => $this->occurrence->vault,
            'reminderOccurrence' => $this->occurrence,
        ]);
    }

    public function postponeUrl(): string
    {
        return route('reminders.occurrences.postponement.update', [
            'vault' => $this->occurrence->vault,
            'reminderOccurrence' => $this->occurrence,
        ]);
    }

    private function sentAt(): CarbonImmutable
    {
        return ($this->occurrence->notified_at ?? $this->occurrence->scheduled_for)
            ->setTimezone($this->zone())
            ->locale(app()->getLocale());
    }

    /** @return array<int, string> */
    public function channels(): array
    {
        $sent = $this->occurrence->notifications->pluck('channel');

        $words = [];

        foreach (NotificationChannelEnum::cases() as $channel) {
            if (! $sent->contains($channel)) {
                continue;
            }

            $words[] = __($channel->label());
        }

        return $words;
    }

    public function outcome(): string
    {
        return match ($this->occurrence->status) {
            ReminderOccurrenceStatusEnum::Dismissed => __('Dismissed'),
            ReminderOccurrenceStatusEnum::Postponed => __('Postponed'),
            default => __('Still open'),
        };
    }

    public function tone(): string
    {
        return match ($this->occurrence->status) {
            ReminderOccurrenceStatusEnum::Dismissed => 'neutral',
            ReminderOccurrenceStatusEnum::Postponed => 'info',
            default => 'warning',
        };
    }

    public function currently(): string
    {
        $due = $this->dueAt();

        return __('Currently set for :date at :time', [
            'date' => DateFormatEnum::write($due, $this->dateFormat()),
            'time' => $this->user->time_format->format($due),
        ]);
    }

    public function reminderUrl(): ?string
    {
        $rule = $this->occurrence->reminder;

        if ($rule === null) {
            return null;
        }

        return route('reminders.show', [
            'vault' => $this->occurrence->vault,
            'reminder' => $rule,
        ]);
    }

    /** @return array<int, array{value: string, label: string, hint: string}> */
    public function presets(): array
    {
        $rule = $this->occurrence->reminder;

        if ($rule === null) {
            return [];
        }

        $presets = [];

        foreach (ReminderPostponePresetEnum::cases() as $preset) {
            $at = $preset->resolve($rule)->setTimezone($this->zone())->locale(app()->getLocale());

            $presets[] = [
                'value' => $preset->value,
                'label' => __($preset->label()),
                'hint' => __(':date at :time', [
                    'date' => DateFormatEnum::write($at, $this->dateFormat()),
                    'time' => $this->user->time_format->format($at),
                ]),
            ];
        }

        return $presets;
    }

    public function isLate(): bool
    {
        return $this->occurrence->scheduled_for->isPast();
    }

    public function dueAt(): CarbonImmutable
    {
        return $this->occurrence->scheduled_for
            ->setTimezone($this->zone())
            ->locale(app()->getLocale());
    }

    public function zone(): string
    {
        return $this->occurrence->reminder->timezone ?? $this->user->timezone;
    }

    public function contactName(): ?string
    {
        return $this->contact()?->name;
    }

    private function contact(): ?Contact
    {
        return once(function (): ?Contact {
            $record = $this->occurrence->reminder?->remindable;

            return $record instanceof Contact ? $record : null;
        });
    }

    public function contactUrl(): ?string
    {
        $contact = $this->contact();

        if ($contact === null) {
            return null;
        }

        return route('contacts.show', ['vault' => $this->occurrence->vault, 'contact' => $contact]);
    }

    public function avatarUrl(): ?string
    {
        $contact = $this->contact();

        if ($contact?->avatar === null) {
            return null;
        }

        return route('files.show', ['vault' => $this->occurrence->vault, 'file' => $contact->avatar]);
    }

    public function text(): string
    {
        $name = $this->occurrence->reminder?->name;

        if ($name !== null && mb_trim($name) !== '') {
            return $name;
        }

        return $this->contact()->name ?? __('A reminder');
    }

    public function cadence(): string
    {
        $rule = $this->occurrence->reminder;

        if ($rule === null) {
            return '';
        }

        $interval = $rule->frequency_interval;

        if ($interval === 0) {
            return '';
        }

        return match ($rule->cadence) {
            ReminderCadenceEnum::Once => __('One time'),
            ReminderCadenceEnum::Weekly => trans_choice('{1}Every week|Every :count weeks', $interval),
            ReminderCadenceEnum::Monthly => trans_choice('{1}Every month|Every :count months', $interval),
            ReminderCadenceEnum::Yearly => trans_choice('{1}Every year|Every :count years', $interval),
        };
    }

    public function cadenceIcon(): string
    {
        return $this->occurrence->reminder?->cadence->recurs() === true ? 'calendar-repeat' : 'calendar';
    }

    public function dismissalNote(): string
    {
        if ($this->occurrence->reminder?->cadence->recurs() === true) {
            return __('This one will not be sent. The next one stays scheduled.');
        }

        return __('This one will not be sent, and nothing comes after it.');
    }

    public function icon(): string
    {
        $key = $this->occurrence->reminder?->field?->key;

        return $key === DefaultFieldEnum::Birthday->value ? 'gift' : 'calendar';
    }

    public function relative(): string
    {
        return $this->dueAt()->diffForHumans();
    }

    public function exact(): string
    {
        $when = $this->partsOf($this->dueAt());

        if ($this->isLate()) {
            return __('Sent :date at :time :abbreviation (:zone, UTC:offset)', $when);
        }

        return __('Will be sent :date at :time :abbreviation (:zone, UTC:offset)', $when);
    }

    /** @return array<string, string> */
    private function partsOf(CarbonImmutable $moment): array
    {
        return [
            'date' => DateFormatEnum::write($moment, $this->dateFormat()),
            'time' => $this->user->time_format->format($moment),
            'abbreviation' => $moment->format('T'),
            'zone' => $this->zone(),
            'offset' => $moment->format('P'),
        ];
    }

    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];
    }
}
