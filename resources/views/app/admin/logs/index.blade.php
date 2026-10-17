{{-- Everything anybody with the administrator capacity has done to this instance, newest first. --}}
{{-- @var \App\ViewModels\Admin\AdministratorLogsViewModel $viewModel --}}
<x-admin-layout :title="__('Record of administrator actions')">
  <x-page-header :title="__('Record of administrator actions')" :description="__('Everything done in the admin space, newest first, with who did it. Nothing here can be changed or removed.')" />

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

      <input type="hidden" name="size" value="{{ $viewModel->size() }}">

      <x-button type="submit" size="small">{{ __('Narrow the record') }}</x-button>

      @if ($viewModel->isFiltered())
        <x-link :href="route('admin.logs.index')">{{ __('Show everything') }}</x-link>
      @endif
    </form>
  @endif

  <x-list-panel
    :items="$viewModel->entries()"
    id="entries"
    :paginator="$viewModel->entries()"
    :size="$viewModel->size()"
    :default="$viewModel->defaultSize()"
  >
    <x-slot:empty>
      <x-empty-state :title="__('Nothing has been done yet')">
        <x-slot:icon>
          <x-icon name="timeline" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ __('Every decision an administrator makes is written down here, with their name and the time it happened.') }}
      </x-empty-state>
    </x-slot:empty>

    @foreach ($viewModel->entries() as $entry)
      <div class="flex flex-col gap-1 border-b border-hairline px-4 py-3 last:border-b-0 sm:flex-row sm:items-baseline sm:gap-4">
        <p class="grow text-sm text-ink">{{ $entry->description }}</p>

        <p class="shrink-0 text-xs text-muted">
          {{ $entry->author }}

          @if ($entry->account !== null)
            ⋅ {{ __('account :id', ['id' => $entry->account_id]) }}
          @endif

          ⋅ {{ $entry->created_at->diffForHumans() }}
        </p>
      </div>
    @endforeach
  </x-list-panel>
</x-admin-layout>
