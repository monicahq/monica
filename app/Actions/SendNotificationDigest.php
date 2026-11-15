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
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Fold everything an account has waiting into one message per channel.
 *
 * One summary carrying every channel could not record what happened to each of
 * them, and a channel that refused it would take the others down with it. So
 * each channel gets its own summary row, folding the notifications that were
 * waiting on that channel and nothing else. The summary goes out before its
 * writes are kept, so a channel that refuses it leaves the notifications waiting,
 * and the next run of the same day tries that channel again.
 *
 * The waiting notifications are claimed before anything goes out, so a second
 * run that read them at the same moment finds them taken and sends nothing.
 * Somebody who may not be reached on a channel, because they were deactivated
 * or never confirmed their email address, has what waits there abandoned rather
 * than kept for the day they come back.
 */
class SendNotificationDigest
{
    public function __construct(
        private readonly Account $account,
        private readonly CarbonInterface $since,
    ) {}

    public function execute(): int
    {
        $user = $this->account->user;

        if ($user === null) {
            return 0;
        }

        $folded = 0;

        foreach ($this->channels() as $channel) {
            if ($this->alreadySent($channel)) {
                continue;
            }

            $waiting = $this->waitingFor($user, $channel);

            if ($waiting->isEmpty()) {
                continue;
            }

            if (! $channel->mayReach($user)) {
                $this->abandon($waiting);

                continue;
            }

            try {
                $folded += $this->fold($user, $channel, $waiting);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $folded;
    }

    /** @param  Collection<int, Notification>  $waiting */
    private function fold(User $user, NotificationChannel $channel, Collection $waiting): int
    {
        DB::beginTransaction();

        try {
            $claimed = Notification::query()
                ->whereIn('id', $waiting->pluck('id'))
                ->whereNull('sent_at')
                ->whereNull('failed_at')
                ->whereNull('digest_id')
                ->update(['sent_at' => now(), 'updated_at' => now()]);

            if ($claimed !== $waiting->count()) {
                DB::rollBack();

                return 0;
            }

            $digest = $this->write($user, $channel, $waiting);

            Notification::query()
                ->whereIn('id', $waiting->pluck('id'))
                ->update(['digest_id' => $digest->id]);

            ReminderOccurrence::query()
                ->whereIn('id', $waiting->where('notifiable_type', ReminderOccurrence::class)->pluck('notifiable_id'))
                ->whereNull('notified_at')
                ->update(['notified_at' => now()]);

            $this->hand($user, $channel, $digest);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return $waiting->count();
    }

    private function alreadySent(NotificationChannel $channel): bool
    {
        return Notification::query()
            ->where('account_id', $this->account->id)
            ->where('type', NotificationTypeEnum::Digest->value)
            ->where('channel', $channel->channel->value)
            ->where('created_at', '>=', $this->since)
            ->exists();
    }

    /** @param  Collection<int, Notification>  $waiting */
    private function abandon(Collection $waiting): void
    {
        Notification::query()
            ->whereIn('id', $waiting->pluck('id'))
            ->update([
                'failed_at' => now(),
                'failure_reason' => __('The person it is for cannot be reached on this channel.'),
                'updated_at' => now(),
            ]);
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

        $user->notifyNow(new NotificationDigest(
            heading: $digest->title,
            lines: $lines,
            channel: $channel,
        )->locale($digest->locale));
    }
}
