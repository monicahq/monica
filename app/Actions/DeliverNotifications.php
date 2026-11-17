<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationTypeEnum;
use App\Exceptions\ChannelCannotCarryIt;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Notifications\NotificationDigest;
use App\Notifications\ReminderDue;
use Illuminate\Support\Collection;
use LogicException;
use Throwable;
use Vonage\Client\Exception\RequestException as VonageRequestException;
use Vonage\Client\Exception\ServerException as VonageServerException;

/**
 * Hand notifications waiting for one person on one channel to that channel as one message: the
 * notification itself, or a digest folding all of them. Every notification is checked, claimed for a
 * while so a second sweep leaves it alone, then marked sent; a refusal counts against the channel and
 * either gives the claim back or abandons what the message carried, when trying again cannot work.
 */
class DeliverNotifications
{
    private const int CLAIM_MINUTES = 15;

    /** @param  Collection<int, Notification>  $notifications */
    public function __construct(
        private readonly Collection $notifications,
        private readonly bool $asDigest,
    ) {}

    public function execute(): int
    {
        throw_if(! $this->asDigest && $this->notifications->count() > 1, LogicException::class, 'Only a digest carries more than one notification.');

        $first = $this->notifications->first();

        if ($first === null) {
            return 0;
        }

        $channel = NotificationChannel::query()
            ->where('account_id', $first->account_id)
            ->where('channel', $first->channel->value)
            ->first();

        $claimed = $this->notifications
            ->filter(fn (Notification $notification): bool => $this->mayGoOut($notification)
                && $this->remainsDeliverable($notification, $channel)
                && $this->claim($notification))
            ->values();

        if ($claimed->isEmpty()) {
            return 0;
        }

        $digest = $this->asDigest ? $this->digest($first->user, $claimed) : null;

        try {
            $this->hand($first, $channel, $digest);
        } catch (Throwable $e) {
            $this->stumble($claimed, $channel, $e);

            return 0;
        }

        $this->markSent($claimed, $channel, $digest);

        return $claimed->count();
    }

    private function mayGoOut(Notification $notification): bool
    {
        return $notification->sent_at === null
            && $notification->failed_at === null
            && $notification->digest_id === null
            && ! $notification->held_until->isFuture();
    }

    private function remainsDeliverable(Notification $notification, ?NotificationChannel $channel): bool
    {
        $notification->attempts++;

        $occurrence = $notification->notifiable;

        if ($occurrence instanceof ReminderOccurrence && ! $occurrence->refresh()->isStillWarranted()) {
            $this->abandon($notification, __('The record it is about is no longer being reminded of.'));

            return false;
        }

        if ($channel?->isOffered() === false) {
            $this->abandon($notification, __('This channel is unavailable on this account for now.'));

            return false;
        }

        if ($channel?->mayReach($notification->user) === false) {
            $this->abandon($notification, __('The person it is for cannot be reached on this channel.'));

            return false;
        }

        return true;
    }

    private function claim(Notification $notification): bool
    {
        $heldUntil = now()->addMinutes(self::CLAIM_MINUTES);

        $claimed = Notification::query()
            ->whereKey($notification->id)
            ->whereNull('sent_at')
            ->whereNull('failed_at')
            ->whereNull('digest_id')
            ->where('held_until', '<=', now())
            ->update(['held_until' => $heldUntil]) === 1;

        if ($claimed) {
            $notification->held_until = $heldUntil;
            $notification->syncOriginalAttribute('held_until');
        }

        return $claimed;
    }

    /** @param  Collection<int, Notification>  $claimed */
    private function digest(User $user, Collection $claimed): Notification
    {
        $locale = $user->locale ?? (string) config('app.locale');

        return Notification::query()->make([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'type' => NotificationTypeEnum::Digest,
            'channel' => $claimed->first()?->channel,
            'notifiable_type' => null,
            'notifiable_id' => null,
            'title' => trans_choice('You have :count reminder today|You have :count reminders today', $claimed->count(), [], $locale),
            'body' => $claimed->map(fn (Notification $one): string => $one->title)->implode("\n"),
            'payload' => [
                'lines' => $claimed->map(fn (Notification $one): array => [
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

    private function hand(Notification $first, ?NotificationChannel $channel, ?Notification $digest): void
    {
        if ($channel === null || ! $channel->mayCarry()) {
            throw new ChannelCannotCarryIt('This channel is not switched on, or is not set up.');
        }

        if ($first->channel->laravelChannel() === null) {
            return;
        }

        if ($digest !== null) {
            /** @var list<array{title: string, body: string}> $lines */
            $lines = $digest->payload['lines'] ?? [];

            $first->user->notifyNow(new NotificationDigest(
                heading: $digest->title,
                lines: $lines,
                channel: $channel,
            )->locale($digest->locale));

            return;
        }

        $payload = $first->payload ?? [];

        $first->user->notifyNow(new ReminderDue(
            reminderName: $first->title,
            recordName: (string) ($payload['record_name'] ?? ''),
            sourceDate: (string) ($payload['local_date'] ?? ''),
            whenItIsAbout: $first->body,
            channel: $channel,
        )->locale($first->locale));
    }

    /** @param  Collection<int, Notification>  $claimed */
    private function markSent(Collection $claimed, ?NotificationChannel $channel, ?Notification $digest): void
    {
        $digest?->save();

        foreach ($claimed as $notification) {
            $notification->sent_at = now();
            $notification->digest_id = $digest?->id;
            $notification->save();
        }

        $channel?->clearFailures();

        ReminderOccurrence::query()
            ->whereIn('id', $claimed->where('notifiable_type', ReminderOccurrence::class)->pluck('notifiable_id'))
            ->whereNull('notified_at')
            ->update(['notified_at' => now()]);
    }

    /** @param  Collection<int, Notification>  $claimed */
    private function stumble(Collection $claimed, ?NotificationChannel $channel, Throwable $e): void
    {
        if ($channel !== null) {
            new RecordChannelFailure(channel: $channel, reason: $e->getMessage())->execute();
        }

        $isPermanent = $this->isPermanent($e);

        foreach ($claimed as $notification) {
            if ($isPermanent || $notification->attempts >= (int) config('monica.notification_maximum_attempts')) {
                $this->abandon($notification, $e->getMessage());

                continue;
            }

            $notification->held_until = now();
            $notification->save();
        }
    }

    private function isPermanent(Throwable $e): bool
    {
        if ($e instanceof ChannelCannotCarryIt) {
            return true;
        }

        if ($e instanceof VonageRequestException) {
            return true;
        }

        if ($e instanceof VonageServerException) {
            return false;
        }

        return $this->readsAsPermanent($e->getMessage());
    }

    private function readsAsPermanent(string $message): bool
    {
        $refusals = [
            'channel_not_found',
            'not_in_channel',
            'invalid_auth',
            'account_inactive',
            'token_revoked',
            'token_expired',
            'missing_scope',
            'is_archived',
            'chat not found',
            'bot was blocked',
            'unauthorized',
            'forbidden',
            'user is deactivated',
            'non-whitelisted destination',
            'invalid sender address',
        ];

        $message = mb_strtolower($message);

        foreach ($refusals as $refusal) {
            if (str_contains($message, $refusal)) {
                return true;
            }
        }

        return false;
    }

    private function abandon(Notification $notification, string $reason): void
    {
        $notification->failed_at = now();
        $notification->failure_reason = mb_substr($reason, 0, 255);
        $notification->save();
    }
}
