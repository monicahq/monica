<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Bring a deleted vault back, with everything it held still inside it.
 *
 * Deleting a vault is the most destructive thing somebody can do short of
 * deleting the account, which is why it is reversible. It comes back where it
 * was in the list, and not as the default one: another vault took that on when
 * this one left, and exactly one holds it.
 */
class RestoreVault
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    public function execute(): Vault
    {
        $this->validate();
        $this->restore();
        $this->log();

        return $this->vault;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }
    }

    private function restore(): void
    {
        $this->vault->restore();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            vault: $this->vault,
            user: $this->user,
            action: UserActionEnum::VaultRestoration,
            parameters: ['name' => $this->vault->name],
            loggable: $this->vault,
        )->onQueue('low');
    }
}
