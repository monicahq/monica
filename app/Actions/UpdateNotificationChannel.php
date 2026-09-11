<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\ChannelIsNotValid;
use App\Jobs\LogUserAction;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change where a channel sends, what it signs in with, and whether it is on.
 *
 * A channel may only be switched on once it has everything it asks for. Saying
 * so here rather than at the moment of delivery is what stops a notification
 * being written for a channel that was never going to carry it.
 *
 * Changing the destination throws away the proof, because the proof was about
 * the old destination and says nothing about the new one.
 */
class UpdateNotificationChannel
{
    public function __construct(
        private readonly User $user,
        private readonly NotificationChannel $channel,
        private readonly bool $isEnabled,
        private readonly ?string $destination = null,
    ) {}

    public function execute(): NotificationChannel
    {
        $this->validate();

        $wasEnabled = $this->channel->is_enabled;

        $this->update();
        $this->log($wasEnabled);

        return $this->channel;
    }

    private function validate(): void
    {
        if ($this->channel->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This channel is in another account.');
        }

        if ($this->channel->channel->discoversDestination() && $this->destination !== null) {
            throw new ChannelIsNotValid('This channel is told where to send by the service, not from here.');
        }

        if (! $this->isEnabled) {
            return;
        }

        if (! $this->channel->channel->isConfiguredOnTheInstance()) {
            throw new ChannelIsNotValid('This server has not been set up to send on this channel.');
        }

        if ($this->channel->channel->needsDestination() && $this->destinationAfter() === null) {
            throw new ChannelIsNotValid('This channel needs somewhere to send to.');
        }

        if ($this->channel->channel->provesSilently() && ! $this->staysProven()) {
            throw new ChannelIsNotValid('Type back the code sent to this destination before switching it on.');
        }
    }

    private function update(): void
    {
        if ($this->destinationChanges()) {
            $this->channel->destination = $this->destinationAfter();
            $this->channel->proven_at = null;
            $this->channel->proof_code_hash = null;
            $this->channel->proof_expires_at = null;
            $this->channel->proof_attempts = 0;
            $this->channel->proof_sent_count = 0;
            $this->channel->proof_last_sent_at = null;
        }

        $this->channel->is_enabled = $this->isEnabled;
        $this->channel->consecutive_failures = 0;
        $this->channel->disabled_reason = null;
        $this->channel->save();
    }

    /**
     * Switching a channel on or off is the thing worth reading later, so it is
     * recorded as itself rather than as one more settings change.
     */
    private function log(bool $wasEnabled): void
    {
        $action = match (true) {
            $this->isEnabled && ! $wasEnabled => UserActionEnum::NotificationChannelEnabled,
            ! $this->isEnabled && $wasEnabled => UserActionEnum::NotificationChannelDisabled,
            default => UserActionEnum::NotificationChannelUpdate,
        };

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: $action,
            parameters: ['name' => __($this->channel->channel->label())],
            loggable: $this->channel,
        )->onQueue('low');
    }

    private function destinationChanges(): bool
    {
        return $this->channel->channel->needsDestination()
            && $this->destination !== null
            && $this->destination !== $this->channel->destination;
    }

    private function destinationAfter(): ?string
    {
        $destination = $this->destination ?? $this->channel->destination;

        return $destination === '' ? null : $destination;
    }

    private function staysProven(): bool
    {
        return ! $this->destinationChanges() && $this->channel->proven_at !== null;
    }
}
