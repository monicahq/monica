{{--
  The initials of somebody, in a circle, for when there is no picture to show.

  @var string $name
  @var int $size
--}}
@props([
  'name',
  'size' => 34,
])

@php
  $initials = collect(preg_split('/\s+/', trim($name)))
    ->filter()
    ->take(2)
    ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
    ->implode('');
@endphp

<span
  {{ $attributes->class(['flex shrink-0 items-center justify-center rounded-full border border-hairline-soft bg-card font-semibold text-body']) }}
  style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.35) }}px;"
  aria-hidden="true"
>{{ $initials }}</span>
