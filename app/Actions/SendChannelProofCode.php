<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\ChannelIsNotValid;
use App\Jobs\LogUserAction;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\ChannelProofCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
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

        $this->store($code);

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
        );
    }

    private function validate(): void
    {
        if ($this->channel->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This channel is in another account.');
        }

        $this->user->authorize('sendProofCode', $this->channel);

        if (! $this->channel->isSetUp()) {
            throw new ChannelIsNotValid('Type a number before asking for a code.');
        }
    }

    private function store(string $code): void
    {
        $wait = (int) config('monica.notification_proof_resend_seconds');

        $stored = NotificationChannel::query()
            ->whereKey($this->channel->id)
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('proof_last_sent_at')
                ->orWhere('proof_last_sent_at', '<=', now()->subSeconds($wait)))
            ->update([
                'proof_code_hash' => Hash::make($code),
                'proof_expires_at' => now()->addMinutes((int) config('monica.notification_proof_code_minutes')),
                'proof_attempts' => 0,
                'proof_sent_count' => $this->channel->proof_sent_count + 1,
                'proof_last_sent_at' => now(),
                'updated_at' => now(),
            ]);

        $this->channel->refresh();

        if ($stored === 0) {
            $this->user->authorize('sendProofCode', $this->channel);

            throw new AuthorizationException;
        }
    }

    private function code(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
