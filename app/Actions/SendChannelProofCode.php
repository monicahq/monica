<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\ChannelIsNotValid;
use App\Jobs\LogUserAction;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\ChannelProofCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

/**
 * Send a code to a destination to find out whether it is really the account's.
 *
 * Only a channel that accepts a wrong destination without complaint needs this.
 * A text message provider takes any well formed number, so a send that appears
 * to have worked proves nothing; a code that comes back proves somebody holding
 * that number typed it.
 *
 * The code is stored hashed, because it is only ever compared. How long it
 * lasts and how many may be asked for are instance settings, so a mistyped
 * number cannot be used to send messages at somebody for ever.
 */
class SendChannelProofCode
{
    public function __construct(
        private readonly User $user,
        private readonly NotificationChannel $channel,
    ) {}

    public function execute(): NotificationChannel
    {
        $this->validate();

        $code = $this->code();

        $this->channel->proof_code_hash = Hash::make($code);
        $this->channel->proof_expires_at = now()->addMinutes((int) config('monica.notification_proof_code_minutes'));
        $this->channel->proof_attempts = 0;
        $this->channel->proof_sent_count++;
        $this->channel->proof_last_sent_at = now();
        $this->channel->save();

        $this->user->notify(new ChannelProofCode(
            code: $code,
            channel: $this->channel,
        )->locale($this->user->locale ?? (string) config('app.locale')));

        $this->log();

        return $this->channel;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::NotificationChannelProofRequested,
            parameters: ['name' => __($this->channel->channel->label())],
            loggable: $this->channel,
        )->onQueue('low');
    }

    private function validate(): void
    {
        if ($this->channel->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This channel is in another account.');
        }

        if (! $this->channel->channel->provesSilently()) {
            throw new ChannelIsNotValid('This channel says for itself whether a destination is wrong, so it has nothing to prove.');
        }

        if (! $this->channel->channel->isConfiguredOnTheInstance()) {
            throw new ChannelIsNotValid('This server has not been set up to send on this channel, so no code can be sent.');
        }

        if (! $this->channel->isSetUp()) {
            throw new ChannelIsNotValid('Fill in the destination and the credential before asking for a code.');
        }

        if ($this->channel->proof_sent_count >= (int) config('monica.notification_proof_maximum_sends')) {
            throw new ChannelIsNotValid('No more codes will be sent to this destination. Change it and start again.');
        }
    }

    private function code(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
