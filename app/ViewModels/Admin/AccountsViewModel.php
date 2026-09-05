<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

class AccountsViewModel
{
    private const int PER_PAGE = 25;

    public function __construct(
        private readonly string $query = '',
    ) {}

    /** @return Paginator<int, Account> */
    public function accounts(): Paginator
    {
        return once(fn (): Paginator => $this->matching()
            ->with('userIncludingDeleted')
            ->latest()
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString());
    }

    public function query(): string
    {
        return $this->query;
    }

    public function isSearching(): bool
    {
        return mb_trim($this->query) !== '';
    }

    /** @return Builder<Account> */
    private function matching(): Builder
    {
        $accounts = Account::query();

        if (! $this->isSearching()) {
            return $accounts;
        }

        $needle = '%'.mb_strtolower(mb_trim($this->query)).'%';

        return $accounts->whereHas('userIncludingDeleted', fn (Builder $user) => $user
            ->whereRaw('lower(email) like ?', [$needle])
            ->orWhereRaw('lower(first_name) like ?', [$needle])
            ->orWhereRaw('lower(last_name) like ?', [$needle]));
    }
}
