<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\ProofCodeIsNotValid;
use App\Jobs\LogUserAction;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

/**
 * Take the code back and, if it is the one that was sent, mark the destination
 * as the account's own.
 *
 * Each of the three ways a code can fail is said separately, because "wrong",
 * "too late" and "too many tries" ask for three different things from whoever
 * is reading. Wrong guesses are counted, and past the limit the code is spent
 * whether or not the next guess would have been right.
 */
class ConfirmChannelProofCode
{
    public function __construct(
        private readonly User $user,
        private readonly NotificationChannel $channel,
        private readonly string $code,
    ) {}

    public function execute(): NotificationChannel
    {
        if ($this->channel->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This channel is in another account.');
        }

        if ($this->channel->proof_code_hash === null) {
            throw new ProofCodeIsNotValid('No code has been sent to this destination.');
        }

        if ($this->channel->proof_attempts >= (int) config('monica.notification_proof_maximum_attempts')) {
            throw new ProofCodeIsNotValid('This code has been tried too many times. Ask for another one.');
        }

        if ($this->channel->proof_expires_at !== null && $this->channel->proof_expires_at->isPast()) {
            throw new ProofCodeIsNotValid('This code has expired. Ask for another one.');
        }

        if (! Hash::check($this->code, $this->channel->proof_code_hash)) {
            $this->channel->proof_attempts++;
            $this->channel->save();

            throw new ProofCodeIsNotValid('That is not the code we sent.');
        }

        $this->channel->proven_at = now();
        $this->channel->proof_code_hash = null;
        $this->channel->proof_expires_at = null;
        $this->channel->proof_attempts = 0;
        $this->channel->save();

        $this->log();

        return $this->channel;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::NotificationChannelProven,
            parameters: ['name' => __($this->channel->channel->label())],
            loggable: $this->channel,
        )->onQueue('low');
    }
}
