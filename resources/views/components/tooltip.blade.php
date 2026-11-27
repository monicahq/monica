{{-- What the mark beside it means, said in full while the pointer rests on it. --}}
{{--
  @var string $text
  @var bool $focusable
--}}
@props([
  'text',
  'focusable' => false,
])

@php
  $bubbleId = 'tooltip-'.\Illuminate\Support\Str::random(8);
@endphp

<span
  x-data="{ shown: false }"
  @mouseenter="shown = true"
  @mouseleave="shown = false"
  @focusin="shown = true"
  @focusout="shown = false"
  @keydown.escape="shown = false"
  @unless ($focusable) role="img" aria-label="{{ $text }}" @endunless
  {{ $attributes->class(['relative inline-flex items-center']) }}
>
  @if ($focusable)
    <button
      type="button"
      @click="shown = true"
      aria-describedby="{{ $bubbleId }}"
      class="inline-flex cursor-default items-center rounded-sm focus-ring"
    >{{ $slot }}</button>
  @else
    {{ $slot }}
  @endif

  <span
    x-show="shown"
    x-cloak
    x-transition.opacity
    @if ($focusable) id="{{ $bubbleId }}" role="tooltip" @else aria-hidden="true" @endif
    class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1.5 w-max max-w-3xs -translate-x-1/2 rounded-md bg-primary px-2.5 py-1.5 text-xs text-balance text-on-primary shadow-pop"
  >{{ $text }}</span>
</span>
