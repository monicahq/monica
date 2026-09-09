{{-- Finding the account somebody is writing in about. --}}
{{-- @var \App\ViewModels\Admin\AccountsViewModel $viewModel --}}
<x-admin-layout :title="__('Accounts')">
  <header class="flex flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ __('Accounts') }}</h1>

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
      @forelse ($viewModel->rows() as $row)
        <div class="flex flex-col gap-1 border-b border-hairline px-4 py-3 last:border-b-0 sm:flex-row sm:items-center sm:gap-4">
          <div class="grow">
            <a href="{{ $row['url'] }}" data-turbo="true" class="font-semibold text-ink no-underline hover:underline">
              {{ $row['name'] }}
            </a>

            <p class="text-sm text-muted">
              {{ $row['email'] }}

              @if ($row['state'] !== null)
                &middot; {{ $row['state'] }}
              @endif
            </p>
          </div>

          <p class="shrink-0 text-sm text-muted">{{ $row['joined'] }}</p>
        </div>
      @empty
        <x-empty-state :title="$viewModel->isSearching() ? __('Nothing matches that') : __('There are no accounts yet')">
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

    @if ($viewModel->hasMorePages())
      <x-slot:footer>
        <div class="text-center">
          <x-link :href="$viewModel->nextPageUrl()">{{ __('Load more') }}</x-link>
        </div>
      </x-slot:footer>
    @endif
  </x-panel>
</x-admin-layout>
