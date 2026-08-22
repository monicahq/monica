{{--
  Finding the account somebody is writing in about.

  A full address, part of one, or part of a name all work, because an operator is
  handed whatever the person happened to include in their message. With nothing
  typed the screen shows the most recent accounts, which is the useful default on a
  fresh instance and harmless on a busy one.

  A search that matches nothing says so. An empty table with no explanation reads
  as a broken screen, and the operator wonders whether they typed it wrong or
  whether the account is gone.

  The results carry a name, an address and a date. Nothing about a vault appears
  here, not even a count: that is on the account itself, and only as a number.

  @var \App\ViewModels\Admin\AccountsViewModel $viewModel
--}}
<x-admin-layout :title="__('Accounts')">
  <header class="flex flex-col gap-2">
    <h1 class="font-display text-page-title text-ink">{{ __('Accounts') }}</h1>

    <p class="max-w-[64ch] text-base text-pretty text-body">{{ __('Look up an account by address or by name. You see what the account is, never what is inside its vaults.') }}</p>
  </header>

  <form method="get" action="{{ route('admin.accounts.index') }}" class="flex flex-wrap items-end gap-3">
    <div class="w-full max-w-96">
      <x-input
        id="query"
        name="query"
        :label="__('Address or name')"
        :value="$viewModel->query()"
        placeholder="michael.scott@dundermifflin.com"
        autofocus
      />
    </div>

    <x-button type="submit">{{ __('Look it up') }}</x-button>

    @if ($viewModel->isSearching())
      <x-link :href="route('admin.accounts.index')">{{ __('Clear') }}</x-link>
    @endif
  </form>

  <x-panel :flush="true">
    <div id="accounts">
      @forelse ($viewModel->accounts() as $account)
        <div class="flex flex-col gap-1 border-b border-hairline px-4 py-3 last:border-b-0 sm:flex-row sm:items-center sm:gap-4">
          <div class="grow">
            <a href="{{ route('admin.accounts.show', ['account' => $account->id]) }}" data-turbo="true" class="font-semibold text-ink no-underline hover:underline">
              {{ $account->userIncludingDeleted->name ?? __('Account :id', ['id' => $account->id]) }}
            </a>

            <p class="text-sm text-muted">
              {{ $account->userIncludingDeleted->email ?? __('nobody on this account') }}

              @if ($account->userIncludingDeleted?->trashed())
                &middot; {{ __('deleted') }}
              @elseif ($account->userIncludingDeleted !== null && ! $account->userIncludingDeleted->is_active)
                &middot; {{ __('deactivated') }}
              @endif
            </p>
          </div>

          <p class="shrink-0 text-sm text-muted">{{ __('joined :date', ['date' => $account->created_at->isoFormat('LL')]) }}</p>
        </div>
      @empty
        <x-empty-state :title="$viewModel->isSearching() ? __('Nothing matches that') : __('There are no accounts yet')" icon-class="bg-marigold-tint text-marigold-deep">
          <x-slot:icon>
            <x-icon name="search" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          @if ($viewModel->isSearching())
            {{ __('No account has an address or a name like :query. Check the spelling, or try part of it instead of all of it.', ['query' => $viewModel->query()]) }}
          @else
            {{ __('Nobody has signed up on this instance yet.') }}
          @endif
        </x-empty-state>
      @endforelse
    </div>

    @if ($viewModel->accounts()->hasMorePages())
      <x-slot:footer>
        <div class="text-center">
          <x-link :href="$viewModel->accounts()->nextPageUrl()">{{ __('Load more') }}</x-link>
        </div>
      </x-slot:footer>
    @endif
  </x-panel>
</x-admin-layout>
