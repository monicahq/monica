<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\DefaultFieldEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\ReminderAnswerEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderOccurrenceCategoryEnum;
use App\Enums\ReminderSourceEnum;
use App\Helpers\AvailableLocales;
use App\Models\Contact;
use App\Models\Group;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class ReminderRow
{
    public function __construct(
        private readonly User $user,
        private readonly ReminderOccurrence $occurrence,
    ) {}

    public function occurrence(): ReminderOccurrence
    {
        return $this->occurrence;
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

    public function testUrl(): ?string
    {
        if (! $this->occurrence->exists) {
            return null;
        }

        return route('reminders.occurrences.testNotification.create', [
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
        if ($this->occurrence->answer === ReminderAnswerEnum::Remembered) {
            return __('You remembered');
        }

        if ($this->occurrence->answer === ReminderAnswerEnum::Forgotten) {
            return __('You forgot');
        }

        if ($this->occurrence->cancelled_at !== null) {
            return __('Cancelled');
        }

        return __('No answer yet');
    }

    public function tone(): string
    {
        if ($this->occurrence->answer === ReminderAnswerEnum::Remembered) {
            return 'success';
        }

        if ($this->occurrence->answer !== null || $this->occurrence->cancelled_at !== null) {
            return 'neutral';
        }

        return 'warning';
    }

    public function answeredLine(): string
    {
        $due = DateFormatEnum::write($this->occurrence->local_date->locale(app()->getLocale()), $this->dateFormat());

        if ($this->occurrence->answered_at === null) {
            return __('Due :date', ['date' => $due]);
        }

        return __('Due :date · answered :answered', [
            'date' => $due,
            'answered' => DateFormatEnum::write(
                $this->occurrence->answered_at->setTimezone($this->user->timezone)->locale(app()->getLocale()),
                $this->dateFormat(),
            ),
        ]);
    }

    public function whenLine(): string
    {
        return __(':date · :relative', [
            'date' => DateFormatEnum::write($this->occurrence->local_date->locale(app()->getLocale()), $this->dateFormat()),
            'relative' => $this->relativeDay(),
        ]);
    }

    public function relativeDay(): string
    {
        $today = CarbonImmutable::now($this->user->timezone)->startOfDay();
        $day = CarbonImmutable::parse($this->occurrence->local_date->toDateString(), $this->user->timezone)->startOfDay();
        $days = (int) $today->diffInDays($day, false);

        return match ($days) {
            0 => __('today'),
            1 => __('tomorrow'),
            -1 => __('yesterday'),
            default => $day->locale(app()->getLocale())->diffForHumans($today, CarbonInterface::DIFF_RELATIVE_TO_NOW),
        };
    }

    public function sourceLabel(): ?string
    {
        return $this->occurrence->reminder?->field?->label;
    }

    public function editUrl(): ?string
    {
        $rule = $this->occurrence->reminder;
        $record = $this->record();

        if ($rule === null || $rule->deleted_at !== null || $record === null) {
            return null;
        }

        if ($rule->source !== ReminderSourceEnum::Manual) {
            return null;
        }

        if ($record instanceof Group) {
            return route('groups.reminders.edit', ['vault' => $this->occurrence->vault, 'group' => $record, 'reminder' => $rule]);
        }

        return route('contacts.reminders.edit', ['vault' => $this->occurrence->vault, 'contact' => $record, 'reminder' => $rule]);
    }

    public function deleteUrl(): ?string
    {
        $rule = $this->occurrence->reminder;
        $record = $this->record();

        if ($rule === null || $rule->deleted_at !== null || $record === null) {
            return null;
        }

        if ($record instanceof Group) {
            return route('groups.reminders.destroy', ['vault' => $this->occurrence->vault, 'group' => $record, 'reminder' => $rule]);
        }

        return route('contacts.reminders.destroy', ['vault' => $this->occurrence->vault, 'contact' => $record, 'reminder' => $rule]);
    }

    public function answerUrl(): ?string
    {
        if (! $this->occurrence->exists) {
            return null;
        }

        return route('reminders.occurrences.answer.update', [
            'vault' => $this->occurrence->vault,
            'reminderOccurrence' => $this->occurrence,
        ]);
    }

    public function canRemember(): bool
    {
        return $this->user->can('remember', $this->occurrence);
    }

    public function canForget(): bool
    {
        return $this->user->can('forget', $this->occurrence);
    }

    public function isOverdue(): bool
    {
        return $this->occurrence->isActive()
            && ReminderOccurrenceCategoryEnum::for($this->occurrence, CarbonImmutable::now($this->user->timezone)) === ReminderOccurrenceCategoryEnum::Overdue;
    }

    public function reminderUrl(): ?string
    {
        $rule = $this->occurrence->reminder;

        if ($rule === null || $rule->deleted_at !== null) {
            return null;
        }

        return route('reminders.show', [
            'vault' => $this->occurrence->vault,
            'reminder' => $rule,
        ]);
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

    /**
     * The name of whoever or whatever the reminder is about: a contact or a group.
     */
    public function recordName(): ?string
    {
        return $this->record()?->name;
    }

    private function record(): Contact|Group|null
    {
        return once(function (): Contact|Group|null {
            $record = $this->occurrence->reminder?->remindable;

            return $record instanceof Contact || $record instanceof Group ? $record : null;
        });
    }

    private function contact(): ?Contact
    {
        $record = $this->record();

        return $record instanceof Contact ? $record : null;
    }

    public function recordUrl(): ?string
    {
        $record = $this->record();

        if ($record instanceof Group) {
            return route('groups.show', ['vault' => $this->occurrence->vault, 'group' => $record]);
        }

        if ($record === null) {
            return null;
        }

        return route('contacts.show', ['vault' => $this->occurrence->vault, 'contact' => $record]);
    }

    public function avatarUrl(): ?string
    {
        $contact = $this->contact();

        if ($contact?->avatar === null) {
            return null;
        }

        return route('files.variants.show', ['vault' => $this->occurrence->vault, 'file' => $contact->avatar, 'size' => Contact::AVATAR_THUMBNAIL_PIXELS]);
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
