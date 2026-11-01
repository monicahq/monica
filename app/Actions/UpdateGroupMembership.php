<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DatePrecisionEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ContactIsAlreadyInTheGroup;
use App\Exceptions\MembershipEndsBeforeItStarts;
use App\Jobs\LogUserAction;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change one period of somebody's membership: their role, when they joined, and when they left. Giving
 * a current membership a leave date is how somebody leaves a group.
 */
class UpdateGroupMembership
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly GroupMember $membership,
        private readonly ?string $role,
        private readonly ?CarbonImmutable $joinedOn,
        private readonly DatePrecisionEnum $joinedPrecision,
        private readonly ?CarbonImmutable $leftOn,
        private readonly DatePrecisionEnum $leftPrecision,
    ) {}

    public function execute(): GroupMember
    {
        $this->validate();

        $leaves = $this->membership->left_on === null && $this->leftOn !== null;

        $this->membership->update([
            'role' => $this->role,
            'joined_on' => $this->joinedOn,
            'joined_precision' => $this->joinedOn === null ? DatePrecisionEnum::Unknown : $this->joinedPrecision,
            'left_on' => $this->leftOn,
            'left_precision' => $this->leftOn === null ? DatePrecisionEnum::Unknown : $this->leftPrecision,
        ]);

        $this->membership->group->touch();

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: $leaves ? UserActionEnum::GroupMembershipEnd : UserActionEnum::GroupMembershipUpdate,
            vault: $this->vault,
            parameters: [
                'name' => $this->membership->contact->name,
                'group' => $this->membership->group->name,
            ],
            loggable: $this->membership,
        );

        return $this->membership;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->membership->account_id !== $this->user->account_id || $this->membership->group->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This membership is in another vault.');
        }

        if ($this->joinedOn instanceof CarbonImmutable && $this->leftOn instanceof CarbonImmutable && $this->leftOn->lt($this->joinedOn)) {
            throw new MembershipEndsBeforeItStarts(__('Somebody cannot leave a group before they joined it.'));
        }

        if ($this->leftOn !== null || $this->membership->left_on === null) {
            return;
        }

        $isAlreadyIn = GroupMember::query()
            ->where('group_id', $this->membership->group_id)
            ->where('contact_id', $this->membership->contact_id)
            ->whereNull('left_on')
            ->exists();

        if ($isAlreadyIn) {
            throw new ContactIsAlreadyInTheGroup(__(':name is already in this group.', ['name' => $this->membership->contact->name]));
        }
    }
}
