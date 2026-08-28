{{--
  Everything anybody with the administrator capacity has done to this instance,
  newest first.

  It is the operator's own journal, and it is not the one a user reads about their
  own account. Nothing on this screen can be edited or removed, which is the whole
  value of it: a record an operator can rewrite says nothing about what happened.

  The filter narrows by administrator, since the question worth asking of a record
  is usually who. Narrowing to one account arrives in the address rather than from
  a picker, because a list of every account on the instance is not a filter.

  @var \App\ViewModels\Admin\AdministratorLogsViewModel $viewModel
--}}
<x-admin-layout :title="__('Record of administrator actions')">
  <header class="flex flex-col gap-2">
    <h1 class="font-display text-page-title text-ink">{{ __('Record of administrator actions') }}</h1>

    <p class="max-w-[64ch] text-base text-pretty text-body">{{ __('Everything done in the admin space, newest first, with who did it. Nothing here can be changed or removed.') }}</p>
  </header>

  @if ($viewModel->administrators()->isNotEmpty() || $viewModel->isFiltered())
    <form method="get" action="{{ route('admin.logs.index') }}" class="flex flex-wrap items-end gap-3">
      <div class="w-full max-w-72">
        <x-select id="administrator" name="administrator" :label="__('Administrator')" size="small">
          <option value="">{{ __('Everybody') }}</option>

          @foreach ($viewModel->administrators() as $administrator)
            <option value="{{ $administrator->id }}" @selected($viewModel->administratorId() === $administrator->id)>{{ $administrator->name }}</option>
          @endforeach
        </x-select>
      </div>

      @if ($viewModel->accountId() !== null)
        <input type="hidden" name="account" value="{{ $viewModel->accountId() }}">
      @endif

      <x-button type="submit" size="small">{{ __('Narrow the record') }}</x-button>

      @if ($viewModel->isFiltered())
        <x-link :href="route('admin.logs.index')">{{ __('Show everything') }}</x-link>
      @endif
    </form>
  @endif

  <x-panel :flush="true">
    <div id="entries">
      @forelse ($viewModel->entries() as $entry)
        <div class="flex flex-col gap-1 border-b border-hairline px-4 py-3 last:border-b-0 sm:flex-row sm:items-baseline sm:gap-4">
          <p class="grow text-sm text-ink">{{ $entry->description }}</p>

          <p class="shrink-0 text-xs text-muted">
            {{ $entry->author }}

            @if ($entry->account !== null)
              &middot; {{ __('account :id', ['id' => $entry->account_id]) }}
            @endif

            &middot; {{ $entry->created_at->diffForHumans() }}
          </p>
        </div>
      @empty
        <x-empty-state :title="__('Nothing has been done yet')">
          <x-slot:icon>
            <x-icon name="timeline" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ __('As soon as an administrator decides something, it is written down here, with their name and the moment it happened.') }}
        </x-empty-state>
      @endforelse
    </div>

    @if ($viewModel->entries()->hasMorePages())
      <x-slot:footer>
        <div class="text-center">
          <x-link :href="$viewModel->entries()->nextPageUrl()">{{ __('Load more') }}</x-link>
        </div>
      </x-slot:footer>
    @endif
  </x-panel>
</x-admin-layout>
