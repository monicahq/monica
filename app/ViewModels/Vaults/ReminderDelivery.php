<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\NotificationChannelEnum;
use App\Models\Notification;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;

class ReminderDelivery
{
    public function __construct(
        private readonly User $user,
        private readonly Reminder $reminder,
    ) {}

    /**
     * @return array{channels: string, channelsNote: string, isSilent: bool, says: string, saysNote: string, lastSent: string|null, lastSentNote: string}
     */
    public function get(): array
    {
        $sent = $this->lastSent();

        return [
            'channels' => $this->channels(),
            'channelsNote' => $this->channelsNote(),
            'isSilent' => $this->enabled() === [],
            'says' => $this->reminder->name,
            'saysNote' => __('Its name, how far off the date is, and a link to the record it is about.'),
            'lastSent' => $sent,
            'lastSentNote' => $sent === null
                ? __('Nothing has gone out about this reminder yet.')
                : __('Read in the reminder’s own time zone, :zone.', ['zone' => $this->reminder->timezone]),
        ];
    }

    private function channels(): string
    {
        $enabled = $this->enabled();

        if ($enabled === []) {
            return __('No channel');
        }

        $words = array_map(fn (NotificationChannelEnum $channel): string => match ($channel) {
            NotificationChannelEnum::InApp => __('in the app'),
            NotificationChannelEnum::Email => __('by email'),
            NotificationChannelEnum::Push => __('pushed to a device'),
        }, $enabled);

        if (count($words) === 1) {
            return $words[0];
        }

        return implode(', ', array_slice($words, 0, -1)).' '.__('and').' '.end($words);
    }

    private function channelsNote(): string
    {
        if ($this->enabled() === []) {
            return __('Every channel is switched off, so this reminder will reach nobody. Change that in your notification settings.');
        }

        return __('Set for the whole account rather than for this reminder.');
    }

    /** @return list<NotificationChannelEnum> */
    private function enabled(): array
    {
        return $this->reminder->account->notificationPreference?->enabledChannels() ?? [];
    }

    private function lastSent(): ?string
    {
        $notification = Notification::query()
            ->where('account_id', $this->reminder->account_id)
            ->where('notifiable_type', ReminderOccurrence::class)
            ->whereIn('notifiable_id', $this->reminder->occurrences()->select('id'))
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->first();

        if ($notification === null || $notification->sent_at === null) {
            return null;
        }

        $at = $notification->sent_at
            ->setTimezone($this->reminder->timezone)
            ->locale(app()->getLocale());

        return __(':date at :time', [
            'date' => DateFormatEnum::write($at, $this->dateFormat()),
            'time' => $this->user->time_format->format($at),
        ]);
    }

    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) config('monica.locales.'.app()->getLocale().'.date_format', DateFormatEnum::MonthSpelled->value);
    }
}
