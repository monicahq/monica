{{-- The cards at the end of a documentation page leading to the previous and the next page. --}}
{{--
  @var array{url: string, label: string}|null $previous
  @var array{url: string, label: string}|null $next
--}}
@props(['previous' => null, 'next' => null])

@php
  $card = 'flex flex-col gap-1 rounded-lg border border-hairline bg-canvas px-4 py-3 no-underline shadow-panel transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring';
@endphp

@if ($previous || $next)
  <nav aria-label="Previous and next pages" {{ $attributes->class('grid grid-cols-2 gap-3 print:hidden') }}>
    @if ($previous)
      <a href="{{ $previous['url'] }}" data-turbo="true" class="{{ $card }}">
        <span class="flex items-center gap-1.5 text-xs font-medium text-muted">
          <x-icon name="arrow-right" :size="14" class="rotate-180" />
          Previous
        </span>
        <span class="font-semibold text-ink">{{ $previous['label'] }}</span>
      </a>
    @endif

    @if ($next)
      <a href="{{ $next['url'] }}" data-turbo="true" class="{{ $card }} col-start-2 items-end text-end">
        <span class="flex items-center gap-1.5 text-xs font-medium text-muted">
          Next
          <x-icon name="arrow-right" :size="14" />
        </span>
        <span class="font-semibold text-ink">{{ $next['label'] }}</span>
      </a>
    @endif
  </nav>
@endif
