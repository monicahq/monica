{{-- A value the reader is meant to copy exactly, drawn in a fixed-width face. --}}
{{-- @var string $surface --}}
@props([
  'surface' => 'sunken',
])

@php
  $background = match ($surface) {
      'canvas' => 'bg-canvas',
      default => 'bg-sunken',
  };
@endphp

<p {{ $attributes->class(['rounded-md border border-hairline px-3 py-2 font-mono text-sm break-all text-ink select-all', $background]) }}>{{ $slot }}</p>
