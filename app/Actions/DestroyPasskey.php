<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\WebauthnKey;
use App\Notifications\PasskeyRemoved;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Revoke a passkey. Only the person it belongs to may do so.
 *
 * The record is written before the row is deleted, not after. LogUserAction
 * takes what it is about in its constructor for exactly this reason: by the time
 * the job reaches the queue the passkey is gone, and a job that went looking for
 * it would lose the record of every removal the product performs.
 *
 * A removed passkey is genuinely deleted rather than deactivated. Everything
 * else in this product deactivates instead of deleting, and this is the
 * exception: a credential its owner has revoked must stop existing, and a
 * disabled row that could be turned back on is a liability rather than a way
 * back.
 */
class DestroyPasskey
{
    public function __construct(
        private readonly User $user,
        private readonly WebauthnKey $passkey,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->log();
        $this->notify();
        $this->destroy();
    }

    private function validate(): void
    {
        if ($this->passkey->user_id !== $this->user->id) {
            throw new ModelNotFoundException('Passkey not found');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PasskeyRemoved,
            parameters: ['name' => $this->passkey->name],
            loggable: $this->passkey,
        )->onQueue('low');
    }

    private function notify(): void
    {
        $this->user->notify(new PasskeyRemoved(name: $this->passkey->name));
    }

    private function destroy(): void
    {
        $this->passkey->delete();
    }
}
