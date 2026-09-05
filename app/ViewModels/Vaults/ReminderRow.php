<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\DefaultFieldEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Enums\ReminderPostponePresetEnum;
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

    /**
     * @return array{uuid: string, day: string, month: string, year: string, cadence: string, cadenceIcon: string, contactName: string|null, contactUrl: string|null, avatarUrl: string|null, text: string, icon: string, relative: string, exact: string, isLate: bool, zone: string, currently: string, dismissUrl: string, testUrl: string, postponeUrl: string, reminderUrl: string|null, presets: array<int, array{value: string, label: string, hint: string}>}
     */
    public function get(): array
    {
        $contact = $this->contact();

        return [
            'uuid' => $this->occurrence->uuid,
            'zone' => $this->zone(),
            'dismissUrl' => route('reminders.occurrences.dismissal.update', [
                'vault' => $this->occurrence->vault,
                'reminderOccurrence' => $this->occurrence,
            ]),
            'testUrl' => route('reminders.occurrences.testNotification.create', [
                'vault' => $this->occurrence->vault,
                'reminderOccurrence' => $this->occurrence,
            ]),
            'postponeUrl' => route('reminders.occurrences.postponement.update', [
                'vault' => $this->occurrence->vault,
                'reminderOccurrence' => $this->occurrence,
            ]),
            'reminderUrl' => $this->reminderUrl(),
            'presets' => $this->presets(),
            'currently' => $this->currently(),
            'day' => $this->dueAt()->translatedFormat('j'),
            'month' => $this->dueAt()->translatedFormat('M'),
            'year' => $this->dueAt()->translatedFormat('Y'),
            'cadence' => $this->cadence(),
            'cadenceIcon' => $this->cadenceIcon(),
            'contactName' => $contact?->name,
            'contactUrl' => $this->contactUrl($contact),
            'avatarUrl' => $this->avatarUrl($contact),
            'text' => $this->text($contact),
            'icon' => $this->icon(),
            'relative' => $this->relative(),
            'exact' => $this->exact(),
            'isLate' => $this->isLate(),
        ];
    }

    /**
     * @return array{uuid: string, relative: string, exact: string, iso: string, channels: array<int, string>, contactName: string|null, contactUrl: string|null, avatarUrl: string|null, text: string, icon: string, outcome: string, tone: string}
     */
    public function sent(): array
    {
        $contact = $this->contact();
        $at = $this->sentAt();

        return [
            'uuid' => $this->occurrence->uuid,
            'relative' => $at->diffForHumans(),
            'exact' => __('Sent :date at :time :abbreviation (:zone, UTC:offset)', $this->partsOf($at)),
            'iso' => $at->toIso8601String(),
            'channels' => $this->channels(),
            'contactName' => $contact?->name,
            'contactUrl' => $this->contactUrl($contact),
            'avatarUrl' => $this->avatarUrl($contact),
            'text' => $this->text($contact),
            'icon' => $this->icon(),
            'outcome' => $this->outcome(),
            'tone' => $this->tone(),
        ];
    }

    private function sentAt(): CarbonImmutable
    {
        return ($this->occurrence->notified_at ?? $this->occurrence->scheduled_for)
            ->setTimezone($this->zone())
            ->locale(app()->getLocale());
    }

    /** @return array<int, string> */
    private function channels(): array
    {
        $sent = $this->occurrence->notifications->pluck('channel');

        $words = [];

        foreach (NotificationChannelEnum::cases() as $channel) {
            if (! $sent->contains($channel)) {
                continue;
            }

            $words[] = match ($channel) {
                NotificationChannelEnum::InApp => __('In the app'),
                NotificationChannelEnum::Email => __('By email'),
                NotificationChannelEnum::Push => __('Pushed'),
            };
        }

        return $words;
    }

    private function outcome(): string
    {
        return match ($this->occurrence->status) {
            ReminderOccurrenceStatusEnum::Dismissed => __('Dismissed'),
            ReminderOccurrenceStatusEnum::Postponed => __('Put off'),
            default => __('Still open'),
        };
    }

    private function tone(): string
    {
        return match ($this->occurrence->status) {
            ReminderOccurrenceStatusEnum::Dismissed => 'neutral',
            ReminderOccurrenceStatusEnum::Postponed => 'info',
            default => 'warning',
        };
    }

    private function currently(): string
    {
        $due = $this->dueAt();

        return __('Currently set for :date at :time', [
            'date' => DateFormatEnum::write($due, $this->dateFormat()),
            'time' => $this->user->time_format->format($due),
        ]);
    }

    private function reminderUrl(): ?string
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
    private function presets(): array
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

    private function zone(): string
    {
        return $this->occurrence->reminder->timezone ?? $this->user->timezone;
    }

    public function contactName(): ?string
    {
        return $this->contact()?->name;
    }

    private function contact(): ?Contact
    {
        $record = $this->occurrence->reminder?->remindable;

        return $record instanceof Contact ? $record : null;
    }

    private function contactUrl(?Contact $contact): ?string
    {
        if ($contact === null) {
            return null;
        }

        return route('contacts.show', ['vault' => $this->occurrence->vault, 'contact' => $contact]);
    }

    private function avatarUrl(?Contact $contact): ?string
    {
        if ($contact?->avatar === null) {
            return null;
        }

        return route('files.show', ['vault' => $this->occurrence->vault, 'file' => $contact->avatar]);
    }

    private function text(?Contact $contact): string
    {
        $name = $this->occurrence->reminder?->name;

        if ($name !== null && mb_trim($name) !== '') {
            return $name;
        }

        return $contact->name ?? __('A reminder');
    }

    private function cadence(): string
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

    private function cadenceIcon(): string
    {
        return $this->occurrence->reminder?->cadence->recurs() === true ? 'calendar-repeat' : 'calendar';
    }

    private function icon(): string
    {
        $key = $this->occurrence->reminder?->field?->key;

        return $key === DefaultFieldEnum::Birthday->value ? 'gift' : 'calendar';
    }

    private function relative(): string
    {
        return $this->dueAt()->diffForHumans();
    }

    private function exact(): string
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
            ?? (string) config('monica.locales.'.app()->getLocale().'.date_format', DateFormatEnum::MonthSpelled->value);
    }
}
