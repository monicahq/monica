<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Notifications\ReminderDue;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Send one reminder to whoever asked, right now, to show what it will look like.
 *
 * It answers "will this actually reach me" before the date arrives, which is
 * otherwise only answerable by waiting for it.
 *
 * The deadline is not touched. `notified_at`, `status` and `scheduled_for` all
 * stay as they were, so the real delivery still happens on the day and the Sent
 * tab, which keys off `notified_at`, never shows a test. Calling
 * {@see NotifyReminderOccurrence} instead would consume the deadline and the
 * real one would never arrive.
 *
 * Quiet hours are ignored on purpose. They exist to stop the product waking
 * somebody up; a person pressing a button at two in the morning has asked for
 * this one.
 *
 * The wording is the deadline's own, so the test says exactly what the real
 * message will say rather than something built for the occasion.
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
        )->locale($locale));
    }
}
