<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Reset a notification channel's connection, including its destination and proof status.
 */
class ResetNotificationChannel
{
    public function __construct(
        private readonly User $user,
        private readonly NotificationChannel $channel,
    ) {}

    public function execute(): NotificationChannel
    {
        $this->validate();

        $this->update();
        $this->log();

        return $this->channel;
    }

    private function validate(): void
    {
        if ($this->channel->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This channel is in another account.');
        }
    }

    private function update(): void
    {
        $this->channel->destination = null;
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

    /**
     * Switching a channel on or off is the thing worth reading later, so it is
     * recorded as itself rather than as one more settings change.
     */
    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::NotificationChannelDisabled,
            parameters: ['name' => __($this->channel->channel->label())],
            loggable: $this->channel,
        )->onQueue('low');
    }
}
