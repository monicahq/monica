<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EmailType;
use App\Enums\NotificationChannelEnum;
use App\Jobs\SendEmail;
use App\Mail\ReminderDue;
use App\Models\Notification;
use App\Models\ReminderOccurrence;
use Throwable;

/**
 * Hand one notification to the channel it is meant to go out on.
 *
 * Everything about it is written to be run twice safely. A notification already
 * sent, already abandoned or still held goes nowhere and says so by returning
 * false, so a sweep that overlaps with itself, or a worker that picks the same
 * row up twice, tells nobody twice.
 *
 * The world is checked again in the instant before anything goes out, and that
 * check is the point of the whole method. Quiet hours can hold a notification
 * for hours, and a deadline due at 3am on a contact archived at 7am must not
 * arrive at 8am. When the check fails, nothing is sent and the row stays with
 * the reason written on it, because a notification silently dropped is
 * indistinguishable from one that was never scheduled.
 *
 * A channel that refuses is tried again. Only after the configured number of
 * attempts is the notification abandoned, and abandoning it means writing why on
 * the row rather than removing it.
 *
 * The in-app channel has nothing to hand anything to: the row is the
 * notification, and sending it is saying so.
 */
class DeliverNotification
{
    public function __construct(
        private readonly Notification $notification,
    ) {}

    /**
     * Whether the notification went out.
     */
    public function execute(): bool
    {
        if (! $this->mayGoOut()) {
            return false;
        }

        $this->notification->attempts++;

        if (! $this->isStillWarranted()) {
            $this->abandon(__('The record it is about is no longer being reminded of.'));

            return false;
        }

        try {
            $this->hand();
        } catch (Throwable $e) {
            $this->stumble($e);

            return false;
        }

        $this->markSent();

        return true;
    }

    private function mayGoOut(): bool
    {
        return $this->notification->sent_at === null
            && $this->notification->failed_at === null
            && $this->notification->digest_id === null
            && ! $this->notification->held_until->isFuture();
    }

    /**
     * Whether anything should still be said. A notification about something
     * other than a reminder deadline has nothing to check, so it goes out.
     */
    private function isStillWarranted(): bool
    {
        $occurrence = $this->notification->notifiable;

        return ! $occurrence instanceof ReminderOccurrence
            || $occurrence->isStillWarranted($this->notification->scheduled_for);
    }

    private function hand(): void
    {
        if ($this->notification->channel !== NotificationChannelEnum::Email) {
            return;
        }

        $user = $this->notification->user;
        $payload = $this->notification->payload ?? [];

        SendEmail::dispatch(
            mailable: new ReminderDue(
                reminderName: $this->notification->title,
                recordName: (string) ($payload['record_name'] ?? ''),
                sourceDate: (string) ($payload['source_local_date'] ?? ''),
                whenItIsAbout: $this->notification->body,
            )->locale($this->notification->locale),
            account: $this->notification->account,
            emailType: EmailType::ReminderDue,
            user: $user,
        )->onQueue('high');
    }

    /**
     * Record that it went out, and tell the deadline it is about that something
     * finally reached somebody. Only the first one to arrive writes that, since
     * it is when the person was told rather than when the last channel finished.
     */
    private function markSent(): void
    {
        $this->notification->sent_at = now();
        $this->notification->save();

        $occurrence = $this->notification->notifiable;

        if ($occurrence instanceof ReminderOccurrence && $occurrence->notified_at === null) {
            $occurrence->notified_at = now();
            $occurrence->save();
        }
    }

    /**
     * One failed attempt. It is abandoned once there have been enough of them,
     * and left alone to be tried again until there have.
     */
    private function stumble(Throwable $e): void
    {
        if ($this->notification->attempts >= (int) config('monica.notification_maximum_attempts')) {
            $this->abandon($e->getMessage());

            return;
        }

        $this->notification->save();
    }

    private function abandon(string $reason): void
    {
        $this->notification->failed_at = now();
        $this->notification->failure_reason = mb_substr($reason, 0, 255);
        $this->notification->save();
    }
}
