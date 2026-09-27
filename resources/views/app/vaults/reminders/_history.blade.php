{{-- What was sent for one reminder and what was done to it, folded away until somebody asks. --}}
{{-- @var list<array{date: string, time: string, title: string, note: string, tone: string}> $entries --}}
<div x-data="{ expanded: false }" class="flex flex-col gap-3 border-t border-hairline pt-5">
  <button
    type="button"
    @click="expanded = ! expanded"
    :aria-expanded="expanded.toString()"
    aria-expanded="false"
    aria-controls="reminder-delivery"
    class="-ml-0.5 flex w-fit cursor-pointer items-center gap-1.5 rounded-md py-1 pr-1.5 pl-0.5 text-sm font-medium text-body transition-colors hover:bg-hover hover:text-ink focus-ring"
  >
    <x-icon name="chevron-right" :size="15" class="transition-transform duration-(--duration-standard) motion-reduce:transition-none" ::class="expanded && 'rotate-90'" />

    {{ __('Delivery details') }}
  </button>

  <ol id="reminder-delivery" x-cloak x-show="expanded" class="flex flex-col gap-2 pl-5">
    @foreach ($entries as $entry)
      <li class="flex flex-wrap gap-x-4 gap-y-0.5 text-sm">
        <span class="min-w-42 font-mono text-muted">{{ $entry['date'] }} {{ $entry['time'] }}</span>

        <span class="text-body">{{ $entry['note'] === '' ? $entry['title'] : "{$entry['title']} · {$entry['note']}" }}</span>
      </li>
    @endforeach
  </ol>
</div>
