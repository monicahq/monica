<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationTypeEnum;
use App\Models\Account;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Notifications\NotificationDigest;
use Illuminate\Support\Collection;

/**
 * Fold everything an account has waiting into one message per channel.
 *
 * One summary carrying every channel could not record what happened to each of
 * them, and a channel that refused it would take the others down with it. So
 * each channel gets its own summary row, folding the notifications that were
 * waiting on that channel and nothing else.
 */
class SendNotificationDigest
{
    public function __construct(
        private readonly Account $account,
    ) {}

    public function execute(): int
    {
        $user = $this->account->user;

        if ($user === null) {
            return 0;
        }

        $folded = 0;

        foreach ($this->channels() as $channel) {
            $waiting = $this->waitingFor($user, $channel);

            if ($waiting->isEmpty()) {
                continue;
            }

            $digest = $this->write($user, $channel, $waiting);

            Notification::query()
                ->whereIn('id', $waiting->pluck('id'))
                ->update(['digest_id' => $digest->id, 'sent_at' => now(), 'updated_at' => now()]);

            $this->hand($user, $channel, $digest);

            ReminderOccurrence::query()
                ->whereIn('id', $waiting->where('notifiable_type', ReminderOccurrence::class)->pluck('notifiable_id'))
                ->whereNull('notified_at')
                ->update(['notified_at' => now()]);

            $folded += $waiting->count();
        }

        return $folded;
    }

    /** @return list<NotificationChannel> */
    private function channels(): array
    {
        return NotificationChannel::query()
            ->where('account_id', $this->account->id)
            ->where('is_enabled', true)
            ->orderBy('id')
            ->get()
            ->filter(fn (NotificationChannel $channel): bool => $channel->mayCarry())
            ->values()
            ->all();
    }

    /** @return Collection<int, Notification> */
    private function waitingFor(User $user, NotificationChannel $channel): Collection
    {
        return Notification::query()
            ->deliverable()
            ->where('user_id', $user->id)
            ->where('channel', $channel->channel->value)
            ->where('type', '!=', NotificationTypeEnum::Digest->value)
            ->orderBy('scheduled_for')
            ->with('notifiable')
            ->get()
            ->filter(fn (Notification $notification): bool => ! $notification->notifiable instanceof ReminderOccurrence
                || $notification->notifiable->isStillWarranted())
            ->values();
    }

    /** @param  Collection<int, Notification>  $waiting */
    private function write(User $user, NotificationChannel $channel, Collection $waiting): Notification
    {
        $locale = $user->locale ?? (string) config('app.locale');

        return Notification::query()->create([
            'account_id' => $this->account->id,
            'user_id' => $user->id,
            'type' => NotificationTypeEnum::Digest,
            'channel' => $channel->channel,
            'notifiable_type' => null,
            'notifiable_id' => null,
            'title' => trans_choice('You have :count reminder today|You have :count reminders today', $waiting->count(), [], $locale),
            'body' => $waiting->map(fn (Notification $one): string => $one->title)->implode("\n"),
            'payload' => [
                'lines' => $waiting->map(fn (Notification $one): array => [
                    'title' => $one->title,
                    'body' => $one->body,
                ])->values()->all(),
            ],
            'locale' => $locale,
            'scheduled_for' => now(),
            'held_until' => now(),
            'sent_at' => now(),
            'attempts' => 1,
        ]);
    }

    private function hand(User $user, NotificationChannel $channel, Notification $digest): void
    {
        /** @var list<array{title: string, body: string}> $lines */
        $lines = $digest->payload['lines'] ?? [];

        $user->notify(new NotificationDigest(
            heading: $digest->title,
            lines: $lines,
            channel: $channel,
        )->locale($digest->locale));
    }
}
