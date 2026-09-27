<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Helpers\QuietHours;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\NotificationPreference;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Turn one deadline that has fallen due into the notifications that will carry it, one per channel
 * the account has switched on.
 */
class NotifyReminderOccurrence
{
    private int $written = 0;

    public function __construct(
        private readonly ReminderOccurrence $occurrence,
    ) {}

    public function execute(): int
    {
        if ($this->occurrence->notifications_created_at !== null || ! $this->occurrence->isActive()) {
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

        foreach ($this->channels() as $channel) {
            $this->written += $this->rowFor($user, $channel->channel, $heldUntil);
        }
    }

    /**
     * The channels the account has switched on and finished setting up. A
     * channel switched on but still missing its destination, its credential or
     * its proof carries nothing, and writing a row for it would only produce a
     * failure with nothing behind it.
     *
     * @return list<NotificationChannel>
     */
    private function channels(): array
    {
        return NotificationChannel::query()
            ->where('account_id', $this->occurrence->account_id)
            ->where('is_enabled', true)
            ->orderBy('id')
            ->get()
            ->filter(fn (NotificationChannel $channel): bool => $channel->mayCarry())
            ->values()
            ->all();
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
            'body' => __('Due :date', ['date' => $this->occurrence->local_date->locale($locale)->isoFormat('LL')], $locale),
            'payload' => json_encode([
                'reminder_uuid' => $reminder->uuid,
                'occurrence_uuid' => $this->occurrence->uuid,
                'record_name' => $reminder->remindable?->name,
                'local_date' => $this->occurrence->local_date->format('Y-m-d'),
            ]),
            'locale' => $locale,
            'scheduled_for' => $this->occurrence->scheduled_for,
            'held_until' => $heldUntil,
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function preference(): ?NotificationPreference
    {
        return NotificationPreference::query()
            ->where('account_id', $this->occurrence->account_id)
            ->first();
    }

    private function markTheDeadlineDealtWith(): void
    {
        $this->occurrence->notifications_created_at = now();
        $this->occurrence->save();
    }
}
