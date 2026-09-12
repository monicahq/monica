{{-- The small word in capitals that names what sits under it. --}}
{{-- @var string $tone --}}
@props([
  'tone' => 'soft',
])

@php
  $colour = match ($tone) {
      'muted' => 'text-muted',
      default => 'text-muted-soft',
  };
@endphp

<span {{ $attributes->class(['text-[11px] font-medium tracking-caption uppercase', $colour]) }}>{{ $slot }}</span>
