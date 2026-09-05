<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationChannelEnum;
use App\Models\Notification;
use App\Models\ReminderOccurrence;
use App\Notifications\ReminderDue;
use Throwable;

/**
 * Hand one notification to the channel it is meant to go out on.
 */
class DeliverNotification
{
    public function __construct(
        private readonly Notification $notification,
    ) {}

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

        $user->notify(new ReminderDue(
            reminderName: $this->notification->title,
            recordName: (string) ($payload['record_name'] ?? ''),
            sourceDate: (string) ($payload['source_local_date'] ?? ''),
            whenItIsAbout: $this->notification->body,
        )->locale($this->notification->locale));
    }

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
