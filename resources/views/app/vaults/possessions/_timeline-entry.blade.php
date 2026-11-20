{{-- One entry of a possession's timeline, on its rail, leading back to the section it was read from. --}}
{{--
  @var array{id: string, icon: string, colour: string, noun: string, date: string, title: string, url: string, badge: string|null, amount: string|null, amountNote: string|null, detail: string|null} $entry
  @var bool $isLast
--}}
<li class="grid grid-cols-[2rem_minmax(0,1fr)] gap-x-4">
  <div class="flex flex-col items-center" aria-hidden="true">
    <span class="flex size-8 flex-none items-center justify-center rounded-full border border-hairline bg-card {{ $entry['colour'] }}">
      <x-icon :name="$entry['icon']" :size="15" />
    </span>
    <span @class(['my-1 min-h-3 w-px flex-1', 'bg-hairline' => ! $isLast])></span>
  </div>

  <div class="flex min-w-0 flex-col gap-1 pt-1 pb-6">
    <h4 id="{{ $entry['id'] }}" class="text-base font-semibold text-ink">
      <a href="{{ $entry['url'] }}" class="rounded-xs wrap-break-word text-ink no-underline hover:underline focus-ring">{{ $entry['title'] }}</a>
    </h4>

    <div class="order-first flex flex-wrap items-center gap-2 text-sm">
      <span class="font-medium text-ink-soft">{{ $entry['noun'] }}</span>
      <span class="text-muted">{{ $entry['date'] }}</span>
      @if ($entry['badge'] !== null)
        <x-badge tone="success" icon="check">{{ $entry['badge'] }}</x-badge>
      @endif
    </div>

    @if ($entry['amount'] !== null)
      <span class="flex flex-wrap items-baseline gap-1.5">
        <span class="text-base font-semibold text-ink tabular-nums">{{ $entry['amount'] }}</span>
        @if ($entry['amountNote'] !== null)
          <span class="text-xs text-muted">{{ $entry['amountNote'] }}</span>
        @endif
      </span>
    @endif

    @if ($entry['detail'] !== null)
      <span class="text-sm wrap-break-word text-pretty text-body">{{ $entry['detail'] }}</span>
    @endif
  </div>
</li>
