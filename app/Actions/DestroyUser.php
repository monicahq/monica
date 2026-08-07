<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete a user. Anybody in the account may delete anybody else in it, but not
 * themselves: leaving the account through this door would sign the person out
 * of a screen they are still standing on.
 */
class DestroyUser
{
    public function __construct(
        private readonly User $author,
        private readonly User $user,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->author->account,
            user: $this->author,
            action: UserActionEnum::UserDeletion,
            parameters: ['email' => $this->user->email],
        )->onQueue('low');
    }

    private function validate(): void
    {
        if ($this->author->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('User not found');
        }

        if ($this->author->id === $this->user->id) {
            throw new ModelNotFoundException('User not found');
        }
    }

    private function destroy(): void
    {
        $this->user->delete();
    }
}
