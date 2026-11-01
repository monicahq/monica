<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DatePrecisionEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ContactIsAlreadyInTheGroup;
use App\Exceptions\MembershipEndsBeforeItStarts;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Put a contact of the vault in a group, for one period. Somebody already in the group today cannot
 * join it twice; somebody who left and comes back gets a new membership, and the old one stays.
 */
class CreateGroupMembership
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
        private readonly Contact $contact,
        private readonly ?string $role = null,
        private readonly ?CarbonImmutable $joinedOn = null,
        private readonly DatePrecisionEnum $joinedPrecision = DatePrecisionEnum::Unknown,
        private readonly ?CarbonImmutable $leftOn = null,
        private readonly DatePrecisionEnum $leftPrecision = DatePrecisionEnum::Unknown,
    ) {}

    public function execute(): GroupMember
    {
        $this->validate();

        $membership = GroupMember::query()->create([
            'account_id' => $this->user->account_id,
            'group_id' => $this->group->id,
            'contact_id' => $this->contact->id,
            'role' => $this->role,
            'joined_on' => $this->joinedOn,
            'joined_precision' => $this->joinedOn === null ? DatePrecisionEnum::Unknown : $this->joinedPrecision,
            'left_on' => $this->leftOn,
            'left_precision' => $this->leftOn === null ? DatePrecisionEnum::Unknown : $this->leftPrecision,
        ]);

        $this->group->touch();

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::GroupMembershipCreation,
            vault: $this->vault,
            parameters: [
                'name' => $this->contact->name,
                'group' => $this->group->name,
            ],
            loggable: $membership,
        );

        return $membership;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->group->account_id !== $this->user->account_id || $this->group->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This group is in another vault.');
        }

        if ($this->contact->account_id !== $this->user->account_id || $this->contact->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This contact is in another vault.');
        }

        if ($this->joinedOn instanceof CarbonImmutable && $this->leftOn instanceof CarbonImmutable && $this->leftOn->lt($this->joinedOn)) {
            throw new MembershipEndsBeforeItStarts(__('Somebody cannot leave a group before they joined it.'));
        }

        if ($this->leftOn !== null) {
            return;
        }

        $isAlreadyIn = GroupMember::query()
            ->where('group_id', $this->group->id)
            ->where('contact_id', $this->contact->id)
            ->whereNull('left_on')
            ->exists();

        if ($isAlreadyIn) {
            throw new ContactIsAlreadyInTheGroup(__(':name is already in this group.', ['name' => $this->contact->name]));
        }
    }
}
