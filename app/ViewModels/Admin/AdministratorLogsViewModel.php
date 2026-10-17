<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Helpers\PageSize;
use App\Models\AdministratorLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdministratorLogsViewModel
{
    public const int PER_PAGE = 20;

    public function __construct(
        private readonly ?int $administratorId = null,
        private readonly ?int $accountId = null,
        private readonly int|string $size = self::PER_PAGE,
    ) {}

    /** @return LengthAwarePaginator<int, AdministratorLog> */
    public function entries(): LengthAwarePaginator
    {
        return once(fn (): LengthAwarePaginator => PageSize::paginate(
            $this->matching()
                ->with(['user', 'account.user'])
                ->latest(),
            $this->size,
        ));
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

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
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
