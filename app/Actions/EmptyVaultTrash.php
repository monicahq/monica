<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TrashItem;
use App\Helpers\VaultTrash;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete everything in the vault's trash for good.
 */
class EmptyVaultTrash
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    public function execute(): void
    {
        $this->validate();

        new VaultTrash(vault: $this->vault)
            ->everything()
            ->each(fn (TrashItem $item) => new DestroyTrashItem(
                user: $this->user,
                vault: $this->vault,
                kind: $item->kind,
                uuid: $item->uuid(),
                writesToLog: false,
            )->execute());

        $this->log();
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TrashEmptied,
            vault: $this->vault,
            parameters: ['vault' => $this->vault->name],
        );
    }
}
