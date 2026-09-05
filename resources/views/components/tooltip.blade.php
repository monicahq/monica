{{-- What the mark beside it means, said in full while the pointer rests on it. --}}
{{--
  @var string $text
  @var bool $focusable
--}}
@props([
  'text',
  'focusable' => false,
])

<span
  x-data="{ shown: false }"
  @mouseenter="shown = true"
  @mouseleave="shown = false"
  @focusin="shown = true"
  @focusout="shown = false"
  @keydown.escape="shown = false"
  @if ($focusable) tabindex="0" @endif
  role="img"
  aria-label="{{ $text }}"
  {{ $attributes->class(['relative inline-flex items-center', 'rounded-sm focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none' => $focusable]) }}
>
  {{ $slot }}

  <span
    x-show="shown"
    x-cloak
    x-transition.opacity
    aria-hidden="true"
    class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1.5 w-max max-w-3xs -translate-x-1/2 rounded-md bg-primary px-2.5 py-1.5 text-xs text-balance text-on-primary shadow-pop"
  >{{ $text }}</span>
</span>
