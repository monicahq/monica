<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\NotificationChannel;
use App\Notifications\ChannelDisabled;

/**
 * Count one refusal against a channel and, past the threshold, switch the
 * channel off and say so. A channel that has quietly stopped working is worse
 * than one that is plainly off, so the account is told the moment we give up on
 * it, on a channel that still works.
 */
class RecordChannelFailure
{
    public function __construct(
        private readonly NotificationChannel $channel,
        private readonly string $reason,
    ) {}

    public function execute(): NotificationChannel
    {
        $this->channel->consecutive_failures++;

        if ($this->channel->consecutive_failures >= $this->threshold()) {
            $this->giveUp();
        }

        $this->channel->save();

        return $this->channel;
    }

    private function threshold(): int
    {
        return max(1, (int) config('monica.notification_channel_failure_threshold'));
    }

    private function giveUp(): void
    {
        if (! $this->channel->is_enabled) {
            return;
        }

        $this->channel->is_enabled = false;
        $this->channel->disabled_reason = mb_substr($this->reason, 0, 255);

        $user = $this->channel->account->user;

        LogUserAction::dispatch(
            account: $this->channel->account,
            user: null,
            action: UserActionEnum::NotificationChannelGaveUp,
            parameters: ['name' => __($this->channel->channel->label()), 'reason' => $this->channel->disabled_reason],
            loggable: $this->channel,
            source: LogSourceEnum::System,
        )->onQueue('low');

        if ($user !== null) {
            $user->notify(new ChannelDisabled(
                channelName: __($this->channel->channel->label()),
                reason: $this->channel->disabled_reason,
            )->locale($user->locale ?? (string) config('app.locale')));
        }
    }
}
