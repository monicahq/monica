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
 * Remove a membership that was recorded by mistake. Somebody who really left is given a leave date
 * instead, so the group keeps its history.
 */
class DestroyGroupMembership
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly GroupMember $membership,
    ) {}

    public function execute(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->membership->account_id !== $this->user->account_id || $this->membership->group->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This membership is in another vault.');
        }

        $this->membership->delete();
        $this->membership->group->touch();

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::GroupMembershipDeletion,
            vault: $this->vault,
            parameters: [
                'name' => $this->membership->contact->name,
                'group' => $this->membership->group->name,
            ],
            loggable: $this->membership,
        )->onQueue('low');
    }
}
