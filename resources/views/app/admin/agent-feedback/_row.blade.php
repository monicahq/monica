{{-- One agent feedback report in the list: its status, title, badges, who it came from and the tool. --}}
{{-- @var array{url: string, title: string, statusLabel: string, statusIcon: string, severityLabel: string, severityTone: string, kindLabel: string, meta: string, tool: string|null, noteCount: int} $row --}}
<div class="grid grid-cols-[20px_minmax(0,1fr)_auto] items-start gap-3 border-b border-hairline-soft px-4 py-3 last:border-b-0 hover:bg-hover">
  <span class="flex pt-0.75 text-ink">
    <x-icon :name="$row['statusIcon']" :size="16" :label="$row['statusLabel']" />
  </span>

  <div class="flex min-w-0 flex-col gap-1">
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1.5">
      <a href="{{ $row['url'] }}" data-turbo="true" class="rounded-sm text-sm leading-snug font-semibold text-pretty text-ink no-underline hover:underline focus-ring">{{ $row['title'] }}</a>
      <x-badge :tone="$row['severityTone']">{{ $row['severityLabel'] }}</x-badge>
      <x-badge tone="info">{{ $row['kindLabel'] }}</x-badge>
    </div>
    <p class="text-xs text-pretty text-muted">{{ $row['meta'] }}</p>
  </div>

  <div class="flex flex-col items-end gap-1 pt-0.5">
    @if ($row['tool'] !== null)
      <code class="font-mono text-xs text-body">{{ $row['tool'] }}</code>
    @endif

    @if ($row['noteCount'] > 0)
      <span class="inline-flex items-center gap-1 text-xs text-muted">
        <x-icon name="note" :size="13" />
        {{ trans_choice(':count note|:count notes', $row['noteCount']) }}
      </span>
    @endif
  </div>
</div>
