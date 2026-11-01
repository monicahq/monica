<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\PageSize;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Pagination\LengthAwarePaginator;

class GroupsViewModel
{
    public const int PER_PAGE = 20;

    /**
     * @param  array{uuid: string, name: string}|null  $deletedGroup
     */
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly int|string $size = self::PER_PAGE,
        private readonly ?array $deletedGroup = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function addUrl(): string
    {
        return route('groups.new', $this->vault);
    }

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
    }

    /** @return LengthAwarePaginator<int, array{name: string, url: string, headcount: string, period: string|null}> */
    public function groups(): LengthAwarePaginator
    {
        $period = new GroupPeriod($this->user);

        $query = Group::query()
            ->inVault($this->vault)
            ->withCount('currentMembers')
            ->orderBy('name')
            ->orderBy('id');

        return PageSize::paginate($query, $this->size)
            ->through(fn (Group $group): array => [
                'name' => $group->name,
                'url' => route('groups.show', ['vault' => $this->vault, 'group' => $group]),
                'headcount' => $this->headcount((int) $group->current_members_count),
                'period' => $period->between($group->started_on, $group->started_precision, $group->ended_on, $group->ended_precision),
            ]);
    }

    /**
     * The group deleted a moment ago, with the way to take it back. It is offered once: after a
     * reload the flash is gone, and so is the offer.
     *
     * @return array{line: string, restoreUrl: string}|null
     */
    public function deletedGroup(): ?array
    {
        if ($this->deletedGroup === null) {
            return null;
        }

        return [
            'line' => __('The group :name is deleted.', ['name' => $this->deletedGroup['name']]),
            'restoreUrl' => route('groups.restoration.update', ['vault' => $this->vault, 'groupUuid' => $this->deletedGroup['uuid']]),
        ];
    }

    private function headcount(int $count): string
    {
        if ($count === 0) {
            return __('Nobody in it today');
        }

        return trans_choice(':count person today|:count people today', $count);
    }
}
