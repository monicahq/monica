<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Models\AdministratorLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * What the record of administrator actions shows: everything anybody with the
 * capacity has done to this instance, newest first.
 *
 * It is the operator's own journal and is never the user's. Somebody reading
 * their own account sees {@see Log}, which holds what they did. The
 * two are separate tables so that no forgotten condition can show one in place
 * of the other.
 *
 * Paging is `simplePaginate` rather than a count and a set of numbered pages.
 * The count is the expensive half on a table that only grows, and nobody reading
 * a journal needs to know it holds eleven thousand entries.
 */
class AdministratorLogsViewModel
{
    /**
     * How many entries a page holds.
     */
    private const int PER_PAGE = 25;

    public function __construct(
        private readonly ?int $administratorId = null,
        private readonly ?int $accountId = null,
    ) {}

    /**
     * @return Paginator<int, AdministratorLog>
     */
    public function entries(): Paginator
    {
        return once(fn (): Paginator => $this->matching()
            ->with(['user', 'account.user'])
            ->latest()
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString());
    }

    /**
     * Everybody who appears in the record, so the filter offers the people it
     * can actually narrow to. It is drawn from the entries rather than from who
     * holds the capacity today: somebody whose capacity was withdrawn is still
     * in the record, and hiding them from the filter would hide what they did.
     *
     * Ordered once the rows are in hand, no database ordering by an alphabet the
     * same way on all three this product runs on. The list is short.
     *
     * A plain collection, not an Eloquent one: sorting goes through a helper
     * taking any collection, and the screen only counts and walks it.
     *
     * @return Collection<int, User>
     */
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

    /**
     * @return Builder<AdministratorLog>
     */
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
