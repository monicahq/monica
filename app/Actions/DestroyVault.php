<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteTheLastVault;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete a vault and everything it separates from the rest. It is the most
 * destructive thing somebody can do short of deleting the account, so the
 * screen that calls this has to say what the vault holds before it does.
 *
 * The deletion is soft, and the vault can be brought back with
 * {@see RestoreVault} while it is still there to bring back.
 *
 * An account always has at least one vault, so the last one refuses to go. A
 * vault that was the default gives the title up before leaving, both because
 * exactly one vault holds it and because a vault brought back later must not
 * come back holding it too.
 */
class DestroyVault
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        $remaining = Vault::query()
            ->where('account_id', $this->user->account_id)
            ->count();

        if ($remaining <= 1) {
            throw new CannotDeleteTheLastVault('An account always has at least one vault.');
        }
    }

    private function destroy(): void
    {
        $this->name = $this->vault->name;
        $wasDefault = $this->vault->is_default;

        DB::transaction(function () use ($wasDefault): void {
            $this->vault->is_default = false;
            $this->vault->save();
            $this->vault->delete();

            if ($wasDefault) {
                $this->promoteTheFirstVaultLeft();
            }
        });
    }

    private function promoteTheFirstVaultLeft(): void
    {
        $vault = Vault::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->firstOrFail();

        $vault->is_default = true;
        $vault->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            vault: $this->vault,
            user: $this->user,
            action: UserActionEnum::VaultDeletion,
            parameters: ['name' => $this->name],
            loggable: $this->vault,
        )->onQueue('low');
    }
}
