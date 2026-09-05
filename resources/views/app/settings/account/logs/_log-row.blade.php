{{-- One thing that happened on the account, as a row in a list. --}}
{{-- @var \App\Models\Log $log --}}
<div class="flex w-full items-start gap-3 border-b border-hairline px-4 py-3.5 text-left transition-[background-color] duration-(--duration-fast) ease-out last:border-b-0">
  <span class="flex size-8 shrink-0 items-center justify-center rounded-sm bg-forest-tint text-forest">
    <x-icon name="timeline" :size="18" :stroke-width="1.5" />
  </span>

  <div class="min-w-0 flex-1">
    <p class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
      <span class="text-base font-semibold text-ink">{{ $log->author }}</span>

      <code class="font-mono text-[12.5px] wrap-anywhere text-muted">{{ $log->action }}</code>
    </p>

    <p class="mt-px text-sm text-pretty text-muted">{{ $log->description }}</p>

    <p class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
      <time
        datetime="{{ $log->created_at->toIso8601String() }}"
        title="{{ $log->created_at->toDayDateTimeString() }}"
        class="text-xs text-pretty text-muted"
      >{{ $log->created_at->diffForHumans() }}</time>

      <span class="text-xs text-pretty text-muted" aria-hidden="true">·</span>

      @if ($log->ip_address)
        <span class="font-mono text-[12.5px] whitespace-nowrap text-muted">{{ $log->ip_address }}</span>
      @else
        <span class="text-xs text-pretty text-muted">{{ __('No address, so this was the application itself') }}</span>
      @endif
    </p>
  </div>
</div>
