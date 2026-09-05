<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Models\AdministratorLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class AdministratorLogsViewModel
{
    private const int PER_PAGE = 25;

    public function __construct(
        private readonly ?int $administratorId = null,
        private readonly ?int $accountId = null,
    ) {}

    /** @return Paginator<int, AdministratorLog> */
    public function entries(): Paginator
    {
        return once(fn (): Paginator => $this->matching()
            ->with(['user', 'account.user'])
            ->latest()
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString());
    }

    /** @return Collection<int, User> */
    public function administrators(): Collection
    {
        return once(fn (): Collection => User::query()
            ->whereIn('id', AdministratorLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->get()
            ->sortByCollator('first_name'));
    }

    public function administratorId(): ?int
    {
        return $this->administratorId;
    }

    public function accountId(): ?int
    {
        return $this->accountId;
    }

    public function isFiltered(): bool
    {
        return $this->administratorId !== null || $this->accountId !== null;
    }

    /** @return Builder<AdministratorLog> */
    private function matching(): Builder
    {
        $entries = AdministratorLog::query();

        if ($this->administratorId !== null) {
            $entries->where('user_id', $this->administratorId);
        }

        if ($this->accountId !== null) {
            $entries->where('account_id', $this->accountId);
        }

        return $entries;
    }
}
