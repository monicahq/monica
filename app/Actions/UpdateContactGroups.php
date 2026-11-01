<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DatePrecisionEnum;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Put somebody in exactly the groups chosen for them: they join each new one today and leave today
 * each one they are no longer in. Memberships that already ended are left as they are.
 */
class UpdateContactGroups
{
    /** @param Collection<int, Group> $groups */
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly Collection $groups,
    ) {}

    public function execute(): void
    {
        $this->validate();

        DB::transaction(function (): void {
            $today = CarbonImmutable::today();

            $current = GroupMember::query()
                ->where('contact_id', $this->contact->id)
                ->whereNull('left_on')
                ->whereHas('group', fn (Builder $query): Builder => $query->where('vault_id', $this->vault->id))
                ->get();

            foreach ($this->groups->whereNotIn('id', $current->pluck('group_id')) as $group) {
                new CreateGroupMembership(
                    user: $this->user,
                    vault: $this->vault,
                    group: $group,
                    contact: $this->contact,
                    joinedOn: $today,
                    joinedPrecision: DatePrecisionEnum::Day,
                )->execute();
            }

            foreach ($current->whereNotIn('group_id', $this->groups->pluck('id')) as $membership) {
                new UpdateGroupMembership(
                    user: $this->user,
                    vault: $this->vault,
                    membership: $membership,
                    role: $membership->role,
                    joinedOn: $membership->joined_on,
                    joinedPrecision: $membership->joined_precision,
                    leftOn: $today,
                    leftPrecision: DatePrecisionEnum::Day,
                )->execute();
            }
        });
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->contact->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This contact is in another vault.');
        }

        foreach ($this->groups as $group) {
            if ($group->vault_id !== $this->vault->id) {
                throw new ModelNotFoundException('This group is in another vault.');
            }
        }
    }
}
