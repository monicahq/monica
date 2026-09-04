<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Helpers\QuietHours;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Turn one deadline that has fallen due into the notifications that will carry
 * it, one per channel the account has switched on.
 *
 * The moment is not applied again here. A rule's moments became separate
 * deadlines long before this point, each already at the instant it names, so a
 * notification copies its deadline's instant and shifts nothing.
 *
 * Quiet hours are read here and hold the notification rather than the deadline.
 * `scheduled_for` keeps saying when the thing was really due and `held_until`
 * says when it may go out, and the two are never conflated: moving the deadline
 * would corrupt the record of when things were actually due.
 *
 * The deadline moves to notified once this has run, even on an account that has
 * asked for silence and where there was nothing to make. That is what stops the
 * sweep finding the same deadline every five minutes for ever. Whether anything
 * actually went out is `notified_at`, which {@see DeliverNotification} writes and
 * which stays null when nothing did.
 *
 * Running it twice tells nobody twice. The unique index carries the instant the
 * deadline was due, so a second run over an unchanged deadline collides and
 * writes nothing, while a deadline somebody put off has a new instant and is
 * therefore told about again, which is the whole of what putting one off means.
 */
class NotifyReminderOccurrence
{
    private int $written = 0;

    public function __construct(
        private readonly ReminderOccurrence $occurrence,
    ) {}

    /**
     * How many notifications were made.
     */
    public function execute(): int
    {
        if (! $this->occurrence->status->awaitsDelivery()) {
            return 0;
        }

        if ($this->occurrence->isStillWarranted()) {
            $this->write();
        }

        $this->markTheDeadlineDealtWith();

        return $this->written;
    }

    private function write(): void
    {
        $user = $this->occurrence->account->user;
        $preference = $this->preference();

        if ($user === null || $preference === null) {
            return;
        }

        $heldUntil = QuietHours::release($preference, $this->occurrence->scheduled_for);

        foreach ($preference->enabledChannels() as $channel) {
            $this->written += $this->rowFor($user, $channel, $heldUntil);
        }
    }

    private function rowFor(User $user, NotificationChannelEnum $channel, CarbonImmutable $heldUntil): int
    {
        $locale = $user->locale ?? (string) config('app.locale');
        $reminder = $this->occurrence->reminder;

        return Notification::query()->insertOrIgnore([
            'uuid' => (string) Str::uuid(),
            'account_id' => $this->occurrence->account_id,
            'user_id' => $user->id,
            'type' => NotificationTypeEnum::ReminderDue->value,
            'channel' => $channel->value,
            'notifiable_type' => ReminderOccurrence::class,
            'notifiable_id' => $this->occurrence->id,
            'title' => $reminder->name,
            'body' => $this->body($locale),
            'payload' => json_encode([
                'reminder_uuid' => $reminder->uuid,
                'occurrence_uuid' => $this->occurrence->uuid,
                'record_name' => $reminder->remindable?->name,
                'source_local_date' => $this->occurrence->source_local_date->format('Y-m-d'),
            ]),
            'locale' => $locale,
            'scheduled_for' => $this->occurrence->scheduled_for,
            'held_until' => $heldUntil,
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * What the notification says under its title, which is how far off the date
     * is rather than the date itself. A moment seven days before a birthday
     * arrives a week early on purpose, and a notification that only named the
     * day would be leaving out the one thing the reader needs.
     */
    private function body(string $locale): string
    {
        $days = (int) $this->occurrence->local_date
            ->startOfDay()
            ->diffInDays($this->occurrence->source_local_date->startOfDay(), absolute: false);

        if ($days === 0) {
            return __('It is today.', [], $locale);
        }

        return $days > 0
            ? trans_choice('It is in :count day.|It is in :count days.', $days, [], $locale)
            : trans_choice('It was :count day ago.|It was :count days ago.', abs($days), [], $locale);
    }

    private function preference(): ?NotificationPreference
    {
        return NotificationPreference::query()
            ->where('account_id', $this->occurrence->account_id)
            ->first();
    }

    private function markTheDeadlineDealtWith(): void
    {
        $this->occurrence->status = ReminderOccurrenceStatusEnum::Notified;
        $this->occurrence->save();
    }
}
