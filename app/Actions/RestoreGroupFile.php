<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\GroupFile;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Bring a file kept with a group back from the vault's trash. The attachment kept its reference on the file while it waited, so the file is intact.
 */
class RestoreGroupFile
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly GroupFile $attachment,
    ) {}

    public function execute(): void
    {
        $this->validate();

        $this->attachment->restoreFromTheTrash();

        $this->log();
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->attachment->account_id !== $this->user->account_id || $this->attachment->group->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This record is in another vault.');
        }

        if (! $this->attachment->trashed()) {
            throw new ModelNotFoundException('This record is not deleted.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::GroupFileRestoration,
            vault: $this->vault,
            parameters: ['file' => $this->attachment->file->original_name, 'name' => $this->attachment->group->name],
            loggable: $this->attachment->group,
        );
    }
}
