<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationChannelEnum;
use App\Models\Contact;
use App\Models\NotificationChannel;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Notifications\ReminderDue;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Send one reminder to whoever asked, right now, to show what it will look like.
 */
class SendTestReminderNotification
{
    public function __construct(
        private readonly User $user,
        private readonly ReminderOccurrence $occurrence,
    ) {}

    public function execute(): void
    {
        if ($this->occurrence->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This deadline is in another account.');
        }

        $reminder = $this->occurrence->reminder;

        if (! $reminder instanceof Reminder) {
            throw new ModelNotFoundException('This deadline has no rule left to send.');
        }

        $locale = $this->user->locale ?? (string) config('app.locale');
        $record = $reminder->remindable;

        $this->user->notify(new ReminderDue(
            reminderName: $reminder->name,
            recordName: $record instanceof Contact ? $record->name : '',
            sourceDate: $this->occurrence->source_local_date->format('Y-m-d'),
            whenItIsAbout: $this->occurrence->howFarOff($locale),
            channel: $this->emailChannel(),
        )->locale($locale));
    }

    /**
     * A preview goes out by email whatever the account has switched on, since
     * that is the channel the address on the user already answers for.
     */
    private function emailChannel(): NotificationChannel
    {
        return NotificationChannel::query()
            ->where('account_id', $this->user->account_id)
            ->where('channel', NotificationChannelEnum::Email->value)
            ->firstOrFail();
    }
}
