{{-- A wide block that scrolls sideways, reachable by keyboard and named by the heading it sits under. --}}
{{-- @var string $labelledby --}}
@props([
  'labelledby',
])

<div {{ $attributes->class(['overflow-x-auto focus-ring-inset']) }} tabindex="0" role="region" aria-labelledby="{{ $labelledby }}">
  {{ $slot }}
</div>
