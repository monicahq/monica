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
 * Remove what the account gave a channel, its number or its Telegram conversation, and switch the
 * channel off. The channel itself stays, ready to be set up again from the start.
 */
class RemoveChannelSetup
{
    public function __construct(
        private readonly User $user,
        private readonly NotificationChannel $channel,
    ) {}

    public function execute(): NotificationChannel
    {
        $this->validate();

        if (! $this->channel->holdsSetup()) {
            return $this->channel;
        }

        $this->remove();
        $this->log();

        return $this->channel;
    }

    private function validate(): void
    {
        if ($this->channel->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This channel is in another account.');
        }

        if (! $this->channel->channel->needsDestination()) {
            throw new ChannelIsNotValid('This channel has nothing to remove.');
        }
    }

    private function remove(): void
    {
        $this->channel->destination = null;
        $this->channel->verification_token_hash = null;
        $this->channel->proven_at = null;
        $this->channel->proof_code_hash = null;
        $this->channel->proof_expires_at = null;
        $this->channel->proof_attempts = 0;
        $this->channel->proof_sent_count = 0;
        $this->channel->proof_last_sent_at = null;
        $this->channel->is_enabled = false;
        $this->channel->consecutive_failures = 0;
        $this->channel->disabled_reason = null;
        $this->channel->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::NotificationChannelSetupRemoved,
            parameters: ['name' => __($this->channel->channel->label())],
            loggable: $this->channel,
        )->onQueue('low');
    }
}
