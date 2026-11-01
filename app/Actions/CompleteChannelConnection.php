<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\NotificationChannel;

/**
 * Record the destination a service handed back, and switch the channel on.
 *
 * There is no user here on purpose. This runs from a webhook, where nobody is
 * signed in: the token is the only thing saying which account is connecting,
 * which is why it is unique and why it stops counting after a few minutes.
 *
 * What is stored is the hash of the token, so the row is found by hashing what
 * came back and comparing the two.
 *
 * The channel is switched on rather than left for the account to switch on
 * afterwards. Following the link is the act of asking for it, and coming back
 * to a screen that still says off would read as a failure.
 */
class CompleteChannelConnection
{
    public function __construct(
        private readonly string $verificationToken,
        private readonly string $destination,
    ) {}

    public function execute(): ?NotificationChannel
    {
        $hash = hash('sha256', $this->verificationToken);

        $channel = NotificationChannel::query()
            ->where('verification_token_hash', $hash)
            ->first();

        if ($channel === null || ! $channel->isAwaitingConnection()) {
            return null;
        }

        if (! hash_equals((string) $channel->verification_token_hash, $hash)) {
            return null;
        }

        $channel->destination = $this->destination;
        $channel->proven_at = now();
        $channel->verification_token_hash = null;
        $channel->proof_expires_at = null;
        $channel->is_enabled = $channel->isReady();
        $channel->consecutive_failures = 0;
        $channel->disabled_reason = null;
        $channel->save();

        $this->log($channel);

        return $channel;
    }

    /**
     * The account is read off the channel rather than passed in, because the
     * only thing identifying it here was the token. The source says this came
     * from outside rather than from somebody at a screen.
     */
    private function log(NotificationChannel $channel): void
    {
        LogUserAction::dispatch(
            account: $channel->account,
            user: $channel->account->user,
            action: UserActionEnum::NotificationChannelConnected,
            parameters: ['name' => __($channel->channel->label())],
            loggable: $channel,
            source: LogSourceEnum::Automation,
            sourceDetail: $channel->channel->value,
        );
    }
}
