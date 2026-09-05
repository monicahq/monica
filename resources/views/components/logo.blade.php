{{-- The mark of the application, shown wherever the product signs its name. --}}
{{-- @var int $size --}}
@props([
  'size' => 30,
])

@php
  $width = (int) round($size * 32 / 29);
@endphp

<img
  src="{{ asset('images/logo.svg') }}"
  alt=""
  width="{{ $width }}"
  height="{{ $size }}"
  decoding="async"
  {{ $attributes->class(['shrink-0']) }}
  style="width: {{ $width }}px; height: {{ $size }}px;"
  aria-hidden="true"
/>
