{{-- Everything anybody with the administrator capacity has done to this instance, newest first. --}}
{{-- @var \App\ViewModels\Admin\AdministratorLogsViewModel $viewModel --}}
<x-admin-layout :title="__('Administrator log')">
  <div class="mx-auto flex w-full max-w-240 flex-col gap-6">
    <x-page-header :title="__('Administrator log')" :description="__('Everything done in the admin space, newest first, with who did it. Nothing here can be changed or removed.')">
      <x-slot:actions>
        <x-badge icon="lock">{{ __('Write once') }}</x-badge>
      </x-slot:actions>
    </x-page-header>

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

      @foreach ($viewModel->days() as $day)
        <section class="border-b border-hairline last:border-b-0">
          <div class="flex flex-wrap items-baseline gap-x-2 border-b border-hairline bg-card px-4 py-2.5">
            <h2 class="text-card-title text-ink">{{ $day['heading'] }}</h2>

            <span class="text-sm text-muted">{{ $day['summary'] }}</span>
          </div>

          <ol role="list">
            @foreach ($day['entries'] as $entry)
              <li class="grid grid-cols-[auto_minmax(0,1fr)] items-start gap-3 border-b border-hairline px-4 py-3 last:border-b-0 xs:grid-cols-[auto_minmax(0,1fr)_auto]">
                <span class="flex size-7 items-center justify-center rounded-md border border-hairline bg-card text-ink-soft">
                  <x-icon :name="$viewModel->icon($entry)" :size="15" />
                </span>

                <div class="flex min-w-0 flex-col gap-0.5 pt-1">
                  <p class="text-pretty break-words text-ink">@foreach ($viewModel->sentence($entry) as $part)@if ($part['kind'] === 'code')<code class="rounded-xs bg-hover px-1.25 py-px font-mono text-sm text-ink">{{ $part['text'] }}</code>@elseif ($part['kind'] === 'link')<x-link :href="$part['url']">{{ $part['text'] }}</x-link>@else{{ $part['text'] }}@endif
@endforeach</p>

                  <p class="flex items-center gap-1.5 text-xs text-muted">
                    <x-icon :name="$viewModel->authorIcon($entry)" :size="13" />
                    <span class="sr-only">{{ __('By :author', ['author' => $entry->author]) }}</span>
                    <span aria-hidden="true" class="min-w-0 break-words">{{ $entry->author }}</span>
                  </p>
                </div>

                <p class="col-start-2 flex flex-wrap gap-x-2 text-sm whitespace-nowrap xs:col-start-auto xs:flex-col xs:items-end xs:gap-0.5 xs:pt-1">
                  <span class="text-body">{{ $viewModel->ago($entry) }}</span>
                  <x-timestamp :at="$viewModel->moment($entry)" class="font-mono text-xs text-muted">{{ $viewModel->time($entry) }}</x-timestamp>
                </p>
              </li>
            @endforeach
          </ol>
        </section>
      @endforeach
    </x-list-panel>
  </div>
</x-admin-layout>
