{{-- One call of the MCP history: a line saying what was asked, unfolding into everything recorded. --}}
{{-- @var array<string, mixed> $row --}}
<details class="group border-b border-hairline last:border-b-0">
  <summary class="focus-ring-inset grid cursor-pointer list-none grid-cols-[4.5rem_minmax(0,1fr)] items-center gap-x-3.5 gap-y-2 px-4 py-3 hover:bg-hover sm:grid-cols-[4.5rem_minmax(0,1fr)_auto] [&::-webkit-details-marker]:hidden">
    <x-timestamp :at="$row['startedAt']" class="font-mono text-sm text-ink-soft">{{ $row['time'] }}</x-timestamp>

    <span class="flex min-w-0 flex-col gap-0.5">
      <span class="truncate text-sm font-medium text-ink">{{ $row['description'] }}</span>
      <span class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted">
        <span class="font-medium text-body">{{ $row['connection'] }}</span>
        <span aria-hidden="true">·</span>
        <span class="font-mono">{{ $row['tool'] }}</span>

        @if ($row['operation'] !== null)
          <span aria-hidden="true">·</span>
          <span>{{ $row['operation'] }}</span>
        @endif

        @if ($row['vault'] !== null)
          <span aria-hidden="true">·</span>
          <span>{{ $row['vault'] }}</span>
        @endif
      </span>
    </span>

    <span class="col-start-2 flex items-center gap-3 sm:col-start-auto">
      @if ($row['duration'] !== null)
        <span class="font-mono text-xs text-muted">{{ $row['duration'] }}</span>
      @endif

      <x-badge :tone="$row['tone']">{{ $row['result'] }}</x-badge>

      <x-icon name="chevron-down" :size="16" class="text-muted transition-transform duration-(--duration-fast) group-open:rotate-180 motion-reduce:transition-none" />
    </span>
  </summary>

  <div class="flex flex-col gap-4 px-4 pt-1 pb-5 sm:pl-24">
    @if ($row['reason'] !== null)
      <x-notice :tone="$row['tone'] === 'danger' ? 'danger' : 'warning'">{{ $row['reason'] }}</x-notice>
    @endif

    <x-detail-list label-style="caption">
      <x-detail-list.row :label="__('When')">{{ $row['when'] }}</x-detail-list.row>
      <x-detail-list.row :label="__('Connection')">{{ $row['connection'] }}</x-detail-list.row>
      <x-detail-list.row :label="__('Tool')"><span class="font-mono">{{ $row['tool'] }}</span></x-detail-list.row>

      @if ($row['operation'] !== null)
        <x-detail-list.row :label="__('Operation')">{{ $row['operation'] }}</x-detail-list.row>
      @endif

      @if ($row['vault'] !== null)
        <x-detail-list.row :label="__('Vault')">{{ $row['vault'] }}</x-detail-list.row>
      @endif

      @if ($row['duration'] !== null)
        <x-detail-list.row :label="__('Took')">{{ $row['duration'] }}</x-detail-list.row>
      @endif

      @if ($row['ipAddress'] !== null)
        <x-detail-list.row :label="__('IP address')"><span class="font-mono">{{ $row['ipAddress'] }}</span></x-detail-list.row>
      @endif

      <x-detail-list.row :label="__('Call identifier')"><span class="font-mono break-all">{{ $row['uuid'] }}</span></x-detail-list.row>
    </x-detail-list>

    <div class="grid gap-3 lg:grid-cols-2">
      <div class="flex min-w-0 flex-col gap-1.5">
        <x-caption id="parameters-{{ $row['uuid'] }}">{{ __('Parameters received') }}</x-caption>
        <pre aria-labelledby="parameters-{{ $row['uuid'] }}" class="max-h-56 overflow-auto rounded-md border border-hairline bg-card px-3 py-2.5 font-mono text-xs break-all whitespace-pre-wrap text-ink-soft">{{ $row['parameters'] ?? __('Nothing') }}</pre>

        @if ($row['parametersTruncated'])
          <p class="text-xs text-muted">{{ __('Truncated: only the beginning was kept.') }}</p>
        @endif
      </div>

      <div class="flex min-w-0 flex-col gap-1.5">
        <x-caption id="response-{{ $row['uuid'] }}">{{ __('Response returned') }}</x-caption>
        <pre aria-labelledby="response-{{ $row['uuid'] }}" class="max-h-56 overflow-auto rounded-md border border-hairline bg-card px-3 py-2.5 font-mono text-xs break-all whitespace-pre-wrap text-ink-soft">{{ $row['response'] ?? __('Nothing') }}</pre>

        @if ($row['responseTruncated'])
          <p class="text-xs text-muted">{{ __('Truncated: only the beginning was kept.') }}</p>
        @endif
      </div>
    </div>
  </div>
</details>
