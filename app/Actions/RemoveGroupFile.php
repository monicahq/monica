<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Stop keeping a file with a group. The attachment goes to the vault's trash and keeps its reference
 * on the file, so restoring it finds the file intact; deleting it for good lets go of the file.
 */
class RemoveGroupFile
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
        private readonly GroupFile $attachment,
    ) {}

    public function execute(): void
    {
        $this->validate();

        $file = $this->attachment->file;

        $this->attachment->delete();

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::GroupFileRemoval,
            vault: $this->vault,
            parameters: ['file' => $file->original_name, 'name' => $this->group->name],
            loggable: $this->group,
        );
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->group->account_id !== $this->user->account_id || $this->group->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This group is in another vault.');
        }

        if ($this->attachment->group_id !== $this->group->id) {
            throw new ModelNotFoundException('This file is kept with another group.');
        }
    }
}
