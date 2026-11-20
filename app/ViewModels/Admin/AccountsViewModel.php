<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\AccessKindEnum;
use App\Helpers\FlagState;
use App\Helpers\PageSize;
use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Pennant\Feature;

class AccountsViewModel
{
    public const int PER_PAGE = 20;

    private const array STATES = ['active', 'deactivated', 'deleted', 'nobody'];

    private const array ACCESS_KINDS = ['free', 'subscription', 'lifetime', 'granted'];

    private const array JOINED_PERIODS = ['last-7-days', 'last-30-days', 'last-12-months'];

    private readonly string $state;

    private readonly string $access;

    private readonly string $joined;

    private readonly string $flag;

    public function __construct(
        private readonly string $query = '',
        private readonly int|string $size = self::PER_PAGE,
        string $state = '',
        string $access = '',
        string $joined = '',
        string $flag = '',
    ) {
        $this->state = in_array($state, self::STATES, true) ? $state : '';
        $this->access = ! $this->isSelfHosted() && in_array($access, self::ACCESS_KINDS, true) ? $access : '';
        $this->joined = in_array($joined, self::JOINED_PERIODS, true) ? $joined : '';
        $this->flag = in_array($flag, Feature::defined(), true) ? $flag : '';
    }

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

    public function emptyTitle(): string
    {
        if ($this->isSearching() || $this->hasFilters()) {
            return __('Nothing matches that');
        }

        return __('There are no accounts yet');
    }

    public function emptyBody(): string
    {
        if ($this->hasFilters()) {
            return __('No account matches all of these filters. Clear one of them, or clear them all.');
        }

        if ($this->isSearching()) {
            return __('No account has an address or a name like :query. Check the spelling, or try part of it instead of all of it.', ['query' => $this->query]);
        }

        return __('Nobody has signed up on this instance yet.');
    }

    public function hasFilters(): bool
    {
        return $this->state !== '' || $this->access !== '' || $this->joined !== '' || $this->flag !== '';
    }

    public function state(): string
    {
        return $this->state;
    }

    public function access(): string
    {
        return $this->access;
    }

    public function joined(): string
    {
        return $this->joined;
    }

    public function flag(): string
    {
        return $this->flag;
    }

    /** @return list<array{value: string, label: string}> */
    public function stateOptions(): array
    {
        return [
            ['value' => '', 'label' => __('Any state')],
            ['value' => 'active', 'label' => __('Active')],
            ['value' => 'deactivated', 'label' => __('Deactivated')],
            ['value' => 'deleted', 'label' => __('Deleted')],
            ['value' => 'nobody', 'label' => __('Nobody on it')],
        ];
    }

    /**
     * Empty on a self hosted instance, which bills nobody, so the screen leaves the filter out.
     *
     * @return list<array{value: string, label: string}>
     */
    public function accessOptions(): array
    {
        if ($this->isSelfHosted()) {
            return [];
        }

        return [
            ['value' => '', 'label' => __('Any access')],
            ['value' => 'free', 'label' => __('Free plan')],
            ...array_map(fn (AccessKindEnum $kind): array => ['value' => $kind->value, 'label' => __($kind->label())], AccessKindEnum::cases()),
        ];
    }

    /** @return list<array{value: string, label: string}> */
    public function joinedOptions(): array
    {
        return [
            ['value' => '', 'label' => __('Any time')],
            ['value' => 'last-7-days', 'label' => __('Last 7 days')],
            ['value' => 'last-30-days', 'label' => __('Last 30 days')],
            ['value' => 'last-12-months', 'label' => __('Last 12 months')],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    public function flagOptions(): array
    {
        return [
            ['value' => '', 'label' => __('Any flag')],
            ...array_map(fn (string $flag): array => ['value' => $flag, 'label' => $flag], array_values(Feature::defined())),
        ];
    }

    private function isSelfHosted(): bool
    {
        return (bool) config('monica.self_hosted');
    }

    /** @return Builder<Account> */
    private function matching(): Builder
    {
        $needle = '%'.mb_strtolower(mb_trim($this->query)).'%';

        return Account::query()
            ->when($this->isSearching(), fn (Builder $accounts) => $accounts->whereHas('userIncludingDeleted', fn (Builder $user) => $user
                ->whereRaw('lower(email) like ?', [$needle])
                ->orWhereRaw('lower(first_name) like ?', [$needle])
                ->orWhereRaw('lower(last_name) like ?', [$needle])))
            ->when($this->state === 'active', fn (Builder $accounts) => $accounts->whereHas('userIncludingDeleted', fn (Builder $user) => $user->withoutTrashed()->where('is_active', true)))
            ->when($this->state === 'deactivated', fn (Builder $accounts) => $accounts->whereHas('userIncludingDeleted', fn (Builder $user) => $user->withoutTrashed()->where('is_active', false)))
            ->when($this->state === 'deleted', fn (Builder $accounts) => $accounts->whereHas('userIncludingDeleted', fn (Builder $user) => $user->onlyTrashed()))
            ->when($this->state === 'nobody', fn (Builder $accounts) => $accounts->doesntHave('userIncludingDeleted'))
            ->when($this->access !== '', fn (Builder $accounts) => $accounts->whereAccessKind(AccessKindEnum::tryFrom($this->access)))
            ->when($this->joined !== '', fn (Builder $accounts) => $accounts->where('created_at', '>=', match ($this->joined) {
                'last-7-days' => now()->subDays(7),
                'last-30-days' => now()->subDays(30),
                default => now()->subMonths(12),
            }))
            ->when($this->flag !== '', fn (Builder $accounts) => FlagState::whereOn($accounts, $this->flag));
    }
}
