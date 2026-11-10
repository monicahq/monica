<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\GroupNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete a note about a group. Nothing else changes, since the note was only ever on the group.
 */
class DestroyGroupNote
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
        private readonly GroupNote $note,
    ) {}

    public function execute(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->group->account_id !== $this->user->account_id || $this->group->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This group is in another vault.');
        }

        if ($this->note->group_id !== $this->group->id) {
            throw new ModelNotFoundException('This note is about another group.');
        }

        $this->note->delete();

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::GroupNoteDeletion,
            vault: $this->vault,
            parameters: ['name' => $this->group->name],
            loggable: $this->note,
        );
    }
}
