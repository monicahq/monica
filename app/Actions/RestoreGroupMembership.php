<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Bring a membership of a group back from the vault's trash.
 */
class RestoreGroupMembership
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly GroupMember $membership,
    ) {}

    public function execute(): void
    {
        $this->validate();

        $this->membership->restoreFromTheTrash();

        $this->membership->group->touch();

        $this->log();
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->membership->account_id !== $this->user->account_id || $this->membership->group->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This record is in another vault.');
        }

        if (! $this->membership->trashed()) {
            throw new ModelNotFoundException('This record is not deleted.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::GroupMembershipRestoration,
            vault: $this->vault,
            parameters: ['name' => $this->membership->contact->name, 'group' => $this->membership->group->name],
            loggable: $this->membership->group,
        );
    }
}
