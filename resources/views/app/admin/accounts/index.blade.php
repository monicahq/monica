{{-- Finding the account somebody is writing in about. --}}
{{-- @var \App\ViewModels\Admin\AccountsViewModel $viewModel --}}
<x-admin-layout :title="__('Accounts')">
  <x-page-header :title="__('Accounts')" :description="__('Look up an account by address or by name. You see what the account is, never what is inside its vaults.')" />

  <form method="get" action="{{ route('admin.accounts.index') }}" class="flex flex-wrap items-end gap-3" role="search" aria-label="{{ __('Filter accounts') }}">
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

    <x-select id="state" name="state" :label="__('State')">
      @foreach ($viewModel->stateOptions() as $option)
        <option value="{{ $option['value'] }}" @selected($viewModel->state() === $option['value'])>{{ $option['label'] }}</option>
      @endforeach
    </x-select>

    @if ($viewModel->accessOptions() !== [])
      <x-select id="access" name="access" :label="__('Access')">
        @foreach ($viewModel->accessOptions() as $option)
          <option value="{{ $option['value'] }}" @selected($viewModel->access() === $option['value'])>{{ $option['label'] }}</option>
        @endforeach
      </x-select>
    @endif

    <x-select id="joined" name="joined" :label="__('Joined')">
      @foreach ($viewModel->joinedOptions() as $option)
        <option value="{{ $option['value'] }}" @selected($viewModel->joined() === $option['value'])>{{ $option['label'] }}</option>
      @endforeach
    </x-select>

    <x-select id="flag" name="flag" :label="__('Flag')">
      @foreach ($viewModel->flagOptions() as $option)
        <option value="{{ $option['value'] }}" @selected($viewModel->flag() === $option['value'])>{{ $option['label'] }}</option>
      @endforeach
    </x-select>

    <input type="hidden" name="size" value="{{ $viewModel->size() }}">

    <x-button type="submit">{{ __('Look it up') }}</x-button>

    @if ($viewModel->isSearching() || $viewModel->hasFilters())
      <x-link :href="route('admin.accounts.index')">{{ __('Clear') }}</x-link>
    @endif
  </form>

  <x-list-panel
    :items="$viewModel->rows()"
    id="accounts"
    :paginator="$viewModel->accounts()"
    :size="$viewModel->size()"
    :default="$viewModel->defaultSize()"
  >
    <x-slot:empty>
      <x-empty-state :title="$viewModel->emptyTitle()">
        <x-slot:icon>
          <x-icon name="search" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ $viewModel->emptyBody() }}
      </x-empty-state>
    </x-slot:empty>

    @foreach ($viewModel->rows() as $row)
      <div class="flex flex-col gap-1 border-b border-hairline px-4 py-3 last:border-b-0 sm:flex-row sm:items-center sm:gap-4">
        <div class="grow">
          <a href="{{ $row['url'] }}" data-turbo="true" class="font-semibold text-ink no-underline hover:underline">
            {{ $row['name'] }}
          </a>

          <p class="text-sm text-muted">
            {{ $row['email'] }}

            @if ($row['state'] !== null)
              ⋅ {{ $row['state'] }}
            @endif
          </p>
        </div>

        <p class="shrink-0 text-sm text-muted">{{ $row['joined'] }}</p>
      </div>
    @endforeach
  </x-list-panel>
</x-admin-layout>
