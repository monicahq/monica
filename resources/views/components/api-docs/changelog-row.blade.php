{{-- One change in the API changelog, as a list item leading to its own page. --}}
{{--
  @var \App\ViewModels\Docs\ApiChangelogEntry $entry
  @var bool $filterable
--}}
@props(['entry', 'filterable' => false])

<li
  class="border-t border-hairline first:border-t-0"
  @if ($filterable)
    data-kind="{{ $entry->kind }}"
    data-breaking="{{ $entry->breaking ? 'true' : 'false' }}"
    data-text="{{ mb_strtolower($entry->title.' '.$entry->summary.' '.$entry->group) }}"
    x-show="matches($el)"
  @endif
>
  <a
    href="{{ route('docs.api.changelog.show', ['entry' => $entry->id]) }}"
    data-turbo="true"
    class="flex items-center gap-3 px-4 py-3 text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring-inset"
  >
    <span class="flex min-w-0 flex-1 flex-col gap-1 sm:grid sm:grid-cols-[5.5rem_minmax(0,1fr)_auto] sm:items-center sm:gap-3">
      <span class="text-xs font-medium text-muted">{{ $entry->kind }}</span>
      <span class="text-pretty">{{ $entry->title }}</span>
      <span class="flex items-center gap-1.5">
        @if ($entry->breaking)
          <x-badge tone="danger">Breaking</x-badge>
        @endif
        <span class="text-xs text-muted">{{ $entry->endpointCount() }}</span>
      </span>
    </span>
    <x-icon name="chevron-right" :size="16" class="text-muted-soft" />
  </a>
</li>
