{{-- What was sent for one reminder and what was done to it, folded away until somebody asks. --}}
{{--
  @var \Illuminate\Pagination\Paginator $entries
  @var bool $showsEnd
--}}
<div x-data="{ expanded: false }" class="flex flex-col gap-3 border-t border-hairline pt-5">
  <button
    type="button"
    @click="expanded = ! expanded"
    :aria-expanded="expanded.toString()"
    aria-expanded="false"
    aria-controls="reminder-delivery"
    class="-ms-0.5 flex w-fit cursor-pointer items-center gap-1.5 rounded-md py-1 pe-1.5 ps-0.5 text-sm font-medium text-body transition-colors hover:bg-hover hover:text-ink focus-ring"
  >
    <x-icon name="chevron-right" :size="15" class="transition-transform duration-(--duration-standard) motion-reduce:transition-none" ::class="expanded && 'rotate-90 rtl:-rotate-90'" />

    {{ __('Delivery details') }}
  </button>

  <x-list-panel
    :items="$entries"
    id="reminder-delivery"
    merge="append"
    target="reminder-delivery reminder-delivery-footer"
    footer-id="reminder-delivery-footer"
    :next-page-url="$entries->nextPageUrl()"
    :shows-end="$showsEnd"
    x-cloak
    x-show="expanded"
  >
    @foreach ($entries as $entry)
      <div class="flex flex-wrap gap-x-4 gap-y-0.5 border-t border-hairline px-4 py-3 text-sm first:border-t-0">
        <span class="min-w-42 font-mono text-muted">{{ $entry['date'] }} {{ $entry['time'] }}</span>

        <span class="text-body">{{ $entry['note'] === '' ? $entry['title'] : "{$entry['title']} · {$entry['note']}" }}</span>
      </div>
    @endforeach

    <x-slot:empty></x-slot:empty>
  </x-list-panel>
</div>
