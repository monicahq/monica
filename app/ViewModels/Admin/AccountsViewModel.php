<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Helpers\PageSize;
use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountsViewModel
{
    public const int PER_PAGE = 20;

    public function __construct(
        private readonly string $query = '',
        private readonly int|string $size = self::PER_PAGE,
    ) {}

    /** @return LengthAwarePaginator<int, Account> */
    public function accounts(): LengthAwarePaginator
    {
        return once(fn (): LengthAwarePaginator => PageSize::paginate(
            $this->matching()
                ->with('userIncludingDeleted')
                ->latest(),
            $this->size,
        ));
    }

    /**
     * One row per account, as the list says it: who is on it, whether they are
     * still there, and when they joined.
     *
     * @return list<array{url: string, name: string, email: string, state: string|null, joined: string}>
     */
    public function rows(): array
    {
        return array_map(function (Account $account): array {
            $user = $account->userIncludingDeleted;

            return [
                'url' => route('admin.accounts.show', ['account' => $account->id]),
                'name' => $user->name ?? __('Account :id', ['id' => $account->id]),
                'email' => $user->email ?? __('nobody on this account'),
                'state' => match (true) {
                    $user === null => null,
                    $user->trashed() => __('deleted'),
                    ! $user->is_active => __('deactivated'),
                    default => null,
                },
                'joined' => __('joined :date', ['date' => $account->created_at->isoFormat('LL')]),
            ];
        }, $this->accounts()->items());
    }

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
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
