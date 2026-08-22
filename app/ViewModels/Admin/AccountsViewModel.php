<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

/**
 * What the account lookup shows: the accounts matching what somebody typed, or
 * the most recent ones when they have typed nothing.
 *
 * The search is a scan and is meant to be. An operator handed an address by
 * somebody writing in needs a partial match on a name as much as an exact match
 * on an address, and a leading wildcard is what gives them that. Lowercasing on
 * both sides is what makes it behave the same on SQLite, MySQL and PostgreSQL,
 * whose collations disagree.
 *
 * Accounts are paged with `simplePaginate`, which asks for no count. The count is
 * the expensive half on a table of a hundred thousand rows, and nobody looking
 * up one account needs to be told how many there are in total.
 *
 * The person is loaded even when they have been deleted. An account whose owner
 * is gone is exactly the kind of account somebody writes in about.
 */
class AccountsViewModel
{
    /**
     * How many accounts a page holds.
     */
    private const int PER_PAGE = 25;

    public function __construct(
        private readonly string $query = '',
    ) {}

    /**
     * @return Paginator<int, Account>
     */
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

    /**
     * @return Builder<Account>
     */
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
