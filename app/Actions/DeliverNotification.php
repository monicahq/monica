<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ChannelCannotCarryIt;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\ReminderOccurrence;
use App\Notifications\ReminderDue;
use Throwable;
use Vonage\Client\Exception\RequestException as VonageRequestException;
use Vonage\Client\Exception\ServerException as VonageServerException;

/**
 * Hand one notification to the channel it is meant to go out on.
 *
 * The channel is a value rather than a branch: the enum says which of Laravel's
 * own channels carries it, and the account's row says where it goes and what it
 * signs in with. A channel with nothing behind it, the in application one, is
 * already delivered by the row existing.
 *
 * A refusal is either temporary or permanent. A service that is busy is worth
 * another attempt; a token that has been revoked or a destination that does not
 * exist will refuse the next two attempts exactly as it refused this one, so
 * the notification is abandoned at once and the count against the channel goes
 * up either way.
 *
 * The row is claimed before it is handed over, so two sweeps that load it at
 * once send it once. The claim holds the row back for a while rather than
 * marking it sent, so a worker that dies in the middle of the send leaves it
 * to be tried again once the claim lapses. A refusal gives the claim back.
 */
class DeliverNotification
{
    private const int CLAIM_MINUTES = 15;

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

        if ($this->channelRow()?->isOffered() === false) {
            $this->abandon(__('This channel is unavailable on this account for now.'));

            return false;
        }

        if ($this->channelRow()?->mayReach($this->notification->user) === false) {
            $this->abandon(__('The person it is for cannot be reached on this channel.'));

            return false;
        }

        if (! $this->claim()) {
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

    private function claim(): bool
    {
        $heldUntil = now()->addMinutes(self::CLAIM_MINUTES);

        $claimed = Notification::query()
            ->whereKey($this->notification->id)
            ->whereNull('sent_at')
            ->whereNull('failed_at')
            ->whereNull('digest_id')
            ->where('held_until', '<=', now())
            ->update(['held_until' => $heldUntil]) === 1;

        if ($claimed) {
            $this->notification->held_until = $heldUntil;
            $this->notification->syncOriginalAttribute('held_until');
        }

        return $claimed;
    }

    private function isStillWarranted(): bool
    {
        $occurrence = $this->notification->notifiable;

        return ! $occurrence instanceof ReminderOccurrence
            || $occurrence->refresh()->isStillWarranted();
    }

    private function hand(): void
    {
        $channel = $this->channelRow();

        if ($channel === null || ! $channel->mayCarry()) {
            throw new ChannelCannotCarryIt('This channel is not switched on, or is not set up.');
        }

        if ($this->notification->channel->laravelChannel() === null) {
            return;
        }

        $user = $this->notification->user;
        $payload = $this->notification->payload ?? [];

        $user->notifyNow(new ReminderDue(
            reminderName: $this->notification->title,
            recordName: (string) ($payload['record_name'] ?? ''),
            sourceDate: (string) ($payload['local_date'] ?? ''),
            whenItIsAbout: $this->notification->body,
            channel: $channel,
        )->locale($this->notification->locale));
    }

    private function channelRow(): ?NotificationChannel
    {
        return NotificationChannel::query()
            ->where('account_id', $this->notification->account_id)
            ->where('channel', $this->notification->channel->value)
            ->first();
    }

    private function markSent(): void
    {
        $this->notification->sent_at = now();
        $this->notification->save();

        $this->channelRow()?->clearFailures();

        $occurrence = $this->notification->notifiable;

        if ($occurrence instanceof ReminderOccurrence && $occurrence->notified_at === null) {
            $occurrence->notified_at = now();
            $occurrence->save();
        }
    }

    private function stumble(Throwable $e): void
    {
        $channel = $this->channelRow();

        if ($channel !== null) {
            new RecordChannelFailure(channel: $channel, reason: $e->getMessage())->execute();
        }

        if ($this->isPermanent($e) || $this->notification->attempts >= (int) config('monica.notification_maximum_attempts')) {
            $this->abandon($e->getMessage());

            return;
        }

        $this->notification->held_until = now();
        $this->notification->save();
    }

    /**
     * Whether trying again could ever work.
     *
     * Every service says the same two things in its own words. A room that does
     * not exist, a token that has been revoked and a number that is not a
     * number will refuse the next two attempts exactly as they refused this
     * one, so the notification is abandoned now rather than in an hour. A
     * service that is busy or broken gets another go.
     */
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

    private function abandon(string $reason): void
    {
        $this->notification->failed_at = now();
        $this->notification->failure_reason = mb_substr($reason, 0, 255);
        $this->notification->save();
    }
}
