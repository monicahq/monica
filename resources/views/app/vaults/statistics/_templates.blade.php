{{-- One row per template of a kind of record: how many records use it, their share, and the versions they are on. --}}
{{-- @var \App\ViewModels\Vaults\RecordStatisticsViewModel $viewModel --}}
<x-page-section :title="__('Templates')">
  <div class="rounded-lg border border-hairline bg-card">
    <div class="hidden grid-cols-[minmax(0,2fr)_minmax(0,1.4fr)_minmax(0,2fr)_auto] gap-4 border-b border-hairline px-4 py-2 text-xs font-medium text-muted sm:grid" aria-hidden="true">
      <span>{{ __('Template') }}</span>
      <span>{{ __('Records') }}</span>
      <span>{{ __('Versions in use') }}</span>
      <span class="w-40"></span>
    </div>

    <ul>
      @foreach ($viewModel->templates() as $template)
        <li class="grid grid-cols-1 gap-2 border-b border-hairline px-4 py-3 last:border-b-0 sm:grid-cols-[minmax(0,2fr)_minmax(0,1.4fr)_minmax(0,2fr)_auto] sm:items-center sm:gap-4">
          <div class="flex min-w-0 flex-wrap items-center gap-2">
            <span class="truncate font-semibold text-ink">{{ $template['name'] }}</span>

            @if ($template['isDefault'])
              <x-badge>{{ __('Default') }}</x-badge>
            @endif

            @if ($template['isInactive'])
              <x-badge tone="warning">{{ __('Inactive') }}</x-badge>
            @endif
          </div>

          <div class="flex flex-col gap-1.5">
            <span class="text-sm text-body tabular-nums">{{ $template['summary'] }}</span>

            @if ($template['records'] > 0)
              <x-meter :value="$template['share']" :label="__('Share of :name', ['name' => $template['name']])" class="max-w-40" />
            @endif
          </div>

          <div class="flex flex-wrap gap-x-3 gap-y-1 text-sm">
            @forelse ($template['versions'] as $version)
              <x-link :href="$version['url']" class="focus-ring">{{ $version['label'] }}</x-link>
            @empty
              <span class="text-muted" aria-hidden="true">—</span>
              <span class="sr-only">{{ __('No version in use') }}</span>
            @endforelse
          </div>

          <div class="flex items-center gap-2 sm:w-40 sm:justify-end">
            @if ($template['viewUrl'] !== null)
              <x-link :href="$template['viewUrl']" class="text-sm focus-ring">{{ $template['viewLabel'] }}</x-link>
            @endif

            @if ($template['actions'] !== [])
              <x-actions-menu :label="__('More actions for :name', ['name' => $template['name']])" width="wide">
                @foreach ($template['actions'] as $action)
                  <x-actions-menu.item :href="$action['url']" data-turbo="true">{{ $action['label'] }}</x-actions-menu.item>
                @endforeach
              </x-actions-menu>
            @endif
          </div>
        </li>
      @endforeach
    </ul>
  </div>
</x-page-section>
