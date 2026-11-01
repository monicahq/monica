<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\ChannelIsNotValid;
use App\Jobs\LogUserAction;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\ChannelTestMessage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

/**
 * Send one message on a channel to find out whether it works, and say what
 * happened.
 *
 * It touches no deadline and writes no notification row. Nothing here is
 * something the account was owed, so nothing about it belongs in the record of
 * what the account was told.
 *
 * A refusal is returned rather than thrown, because "it did not work, and here
 * is what came back" is the answer the screen asked for.
 */
class SendChannelTestMessage
{
    public function __construct(
        private readonly User $user,
        private readonly NotificationChannel $channel,
    ) {}

    /** @return array{sent: bool, reason: string|null} */
    public function execute(): array
    {
        if ($this->channel->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This channel is in another account.');
        }

        if (! $this->channel->channel->isConfiguredOnTheInstance()) {
            throw new ChannelIsNotValid('This server has not been set up to send on this channel.');
        }

        if (! $this->channel->isReady()) {
            throw new ChannelIsNotValid('Finish setting this channel up before testing it.');
        }

        try {
            $this->user->notify(new ChannelTestMessage(
                channel: $this->channel,
            )->locale($this->user->locale ?? (string) config('app.locale')));
        } catch (Throwable $e) {
            new RecordChannelFailure(channel: $this->channel, reason: $e->getMessage())->execute();

            $this->log(sent: false);

            return ['sent' => false, 'reason' => mb_substr($e->getMessage(), 0, 255)];
        }

        $this->channel->clearFailures();
        $this->log(sent: true);

        return ['sent' => true, 'reason' => null];
    }

    /**
     * A test that did not arrive is the one worth being able to look up
     * afterwards, so both outcomes are written down rather than only the happy
     * one.
     */
    private function log(bool $sent): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::NotificationChannelTested,
            parameters: ['name' => __($this->channel->channel->label()), 'sent' => $sent],
            loggable: $this->channel,
        );
    }
}
