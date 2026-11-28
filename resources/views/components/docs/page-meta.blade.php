{{-- The block above the contents of a documentation page: when it changed, and where to fix or question it. --}}
{{--
  @var \Carbon\CarbonInterface|null $updated
  @var string $editUrl
  @var string $issueUrl
--}}
@props(['updated' => null, 'editUrl', 'issueUrl'])

@php
  $row = 'group flex items-center gap-3 border-t border-hairline first:border-t-0 px-4 py-3 text-base text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring-inset';
@endphp

<div {{ $attributes->class('overflow-hidden rounded-lg border border-hairline bg-canvas') }}>
  @if ($updated)
    <div class="flex items-center justify-between gap-3 bg-card px-4 py-3 text-base">
      <span class="text-muted">Last updated</span>
      <time datetime="{{ $updated->toDateString() }}" class="font-semibold text-ink">{{ $updated->format('j M Y') }}</time>
    </div>
  @endif

  <a href="{{ $editUrl }}" target="_blank" rel="noopener" class="{{ $row }}">
    <svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="shrink-0">
      <path d="M10.6 2.9a1.4 1.4 0 0 1 2 0l.5.5a1.4 1.4 0 0 1 0 2L5.8 12.7l-3 .6.6-3z"></path>
      <path d="m9.6 3.9 2.5 2.5"></path>
    </svg>
    <span>Edit page</span>
    <x-icon name="arrow-right" :size="16" class="ms-auto text-muted group-hover:text-ink" />
  </a>

  <a href="{{ $issueUrl }}" target="_blank" rel="noopener" class="{{ $row }}">
    <svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="shrink-0">
      <path d="M3.6 14V2.6"></path>
      <path d="M3.6 3h8.2l-1.6 2.9 1.6 2.9H3.6"></path>
    </svg>
    <span>Report issue</span>
    <x-icon name="arrow-right" :size="16" class="ms-auto text-muted group-hover:text-ink" />
  </a>
</div>
