<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\NotificationChannelEnum;
use App\Models\Notification;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;

/**
 * Where a notification about this reminder goes, and what it says.
 *
 * It sits beside {@see ReminderViewModel} rather than inside it, for the reason a
 * rich page keeps its blocks in small objects: this is one panel of one screen.
 *
 * The channels are what the account has switched on, read now. That is a
 * statement about the future rather than about the past, and it is deliberately
 * not the same question the history panel answers: that one says what a given
 * notification actually went out on, which cannot change afterwards, while this
 * one says where the next one will go and moves the moment somebody changes
 * their preferences.
 *
 * A person who has switched everything off is told so rather than shown an empty
 * line. A reminder with nowhere to arrive is the one thing about this panel worth
 * noticing, and a blank space says nothing.
 */
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

    /**
     * The channels switched on, in the order the enum declares them, so that two
     * reminders switched on the same two ways read the same way round.
     */
    private function channels(): string
    {
        $enabled = $this->enabled();

        if ($enabled === []) {
            return __('Nowhere, for now');
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
            return __('Every channel is switched off, so this reminder will reach nobody. Your notification settings are where that changes.');
        }

        return __('Set for the whole account rather than for this reminder.');
    }

    /**
     * @return list<NotificationChannelEnum>
     */
    private function enabled(): array
    {
        return $this->reminder->account->notificationPreference?->enabledChannels() ?? [];
    }

    /**
     * When something about this reminder last actually reached somebody, or null
     * when nothing has. It asks the notifications rather than the deadlines,
     * because a deadline marked notified says one was written and this panel is
     * about one that went.
     */
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
