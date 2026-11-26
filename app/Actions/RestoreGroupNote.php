<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\GroupNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Bring a note on a group back from the vault's trash.
 */
class RestoreGroupNote
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly GroupNote $note,
    ) {}

    public function execute(): void
    {
        $this->validate();

        $this->note->restoreFromTheTrash();

        $this->log();
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->note->account_id !== $this->user->account_id || $this->note->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This record is in another vault.');
        }

        if (! $this->note->trashed()) {
            throw new ModelNotFoundException('This record is not deleted.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::GroupNoteRestoration,
            vault: $this->vault,
            parameters: ['name' => $this->note->group->name],
            loggable: $this->note->group,
        );
    }
}
