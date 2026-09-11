<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\ChannelIsNotValid;
use App\Jobs\LogUserAction;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * Mint the token a channel quotes back when it tells us where to send.
 *
 * Only a channel that hands us its own destination goes through this. The
 * account never reads the token: it travels in the link they follow, comes
 * back through the service, and is what matches one conversation to one
 * account.
 *
 * Only the hash of it is written down, the way a magic link does it, so a copy
 * of the table is not a set of working links. The hash is a plain sha256 rather
 * than a salted one because it has to be looked up by value when the webhook
 * arrives.
 *
 * Starting again throws away whatever was connected before, because the point
 * of starting again is to connect somewhere else.
 */
class StartChannelConnection
{
    public function __construct(
        private readonly User $user,
        private readonly NotificationChannel $channel,
    ) {}

    private string $token;

    public function execute(): NotificationChannel
    {
        if ($this->channel->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This channel is in another account.');
        }

        if (! $this->channel->channel->discoversDestination()) {
            throw new ChannelIsNotValid('This channel is told where to send rather than telling us.');
        }

        if (! $this->channel->channel->isConfiguredOnTheInstance()) {
            throw new ChannelIsNotValid('This server has not been set up to send on this channel.');
        }

        $this->token = Str::random(64);

        $this->channel->verification_token_hash = hash('sha256', $this->token);
        $this->channel->proof_expires_at = now()->addMinutes((int) config('monica.notification_proof_code_minutes'));
        $this->channel->destination = null;
        $this->channel->proven_at = null;
        $this->channel->is_enabled = false;
        $this->channel->consecutive_failures = 0;
        $this->channel->disabled_reason = null;
        $this->channel->save();

        $this->log();

        return $this->channel;
    }

    /**
     * The token as the account will quote it. Only its hash is kept, so this is
     * the one moment it exists in the clear and the caller has to use it now.
     */
    public function token(): string
    {
        return $this->token;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::NotificationChannelConnectionStarted,
            parameters: ['name' => __($this->channel->channel->label())],
            loggable: $this->channel,
        )->onQueue('low');
    }
}
