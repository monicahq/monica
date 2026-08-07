<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete an account, and every user in it. Only somebody who belongs to the
 * account may do so.
 */
class DestroyAccount
{
    public function __construct(
        private readonly User $user,
        private readonly Account $account,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
    }

    private function validate(): void
    {
        if ($this->user->account_id !== $this->account->id) {
            throw new ModelNotFoundException('Account not found');
        }
    }

    private function destroy(): void
    {
        $this->account->delete();
    }
}
