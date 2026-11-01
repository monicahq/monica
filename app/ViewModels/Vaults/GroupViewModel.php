<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\FlagState;
use App\Helpers\PageSize;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Pagination\LengthAwarePaginator;

class GroupViewModel
{
    public const int PER_PAGE = 20;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
        private readonly int|string $size = self::PER_PAGE,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function name(): string
    {
        return $this->group->name;
    }

    public function description(): ?string
    {
        return $this->group->description;
    }

    public function period(): ?string
    {
        return new GroupPeriod($this->user)->between(
            $this->group->started_on,
            $this->group->started_precision,
            $this->group->ended_on,
            $this->group->ended_precision,
        );
    }

    public function indexUrl(): string
    {
        return route('groups.index', $this->vault);
    }

    public function editUrl(): string
    {
        return route('groups.edit', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function addMemberUrl(): string
    {
        return route('groups.members.new', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function vCardUrl(): ?string
    {
        if (! FlagState::isOn('vcard-export', $this->user->account)) {
            return null;
        }

        return route('groups.vcard.show', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
    }

    /**
     * The people in the group today first, then those who left, the most recent departure first. A
     * deleted contact is left out until it is restored.
     *
     * @return LengthAwarePaginator<int, array{name: string, contactUrl: string, editUrl: string, role: string|null, period: string|null, isCurrent: bool, isOutsideTheGroupDates: bool}>
     */
    public function members(): LengthAwarePaginator
    {
        $period = new GroupPeriod($this->user);

        $query = GroupMember::query()
            ->where('group_id', $this->group->id)
            ->whereHas('contact')
            ->with('contact')
            ->orderByRaw('case when left_on is null then 0 else 1 end')
            ->orderByDesc('left_on')
            ->orderBy('id');

        return PageSize::paginate($query, $this->size)
            ->through(fn (GroupMember $membership): array => [
                'name' => $membership->contact->name,
                'contactUrl' => route('contacts.show', ['vault' => $this->vault, 'contact' => $membership->contact]),
                'editUrl' => route('groups.members.edit', ['vault' => $this->vault, 'group' => $this->group, 'membership' => $membership]),
                'role' => $membership->role,
                'period' => $period->between($membership->joined_on, $membership->joined_precision, $membership->left_on, $membership->left_precision),
                'isCurrent' => $membership->isCurrent(),
                'isOutsideTheGroupDates' => $this->isOutsideTheGroupDates($membership),
            ]);
    }

    /**
     * Whether the membership starts before the group began or ends after it ended. It is accepted,
     * since either date may be the wrong one, and pointed out.
     */
    private function isOutsideTheGroupDates(GroupMember $membership): bool
    {
        $startedOn = $this->group->started_on;
        $endedOn = $this->group->ended_on;

        if ($startedOn !== null && $membership->joined_on !== null && $membership->joined_on->lt($startedOn)) {
            return true;
        }

        return $endedOn !== null && $membership->left_on !== null && $membership->left_on->gt($endedOn);
    }
}
