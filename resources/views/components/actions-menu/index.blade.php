{{-- The button that unfolds the rest of what may be done here, and the menu it unfolds. --}}
{{--
  @var string $label
  @var string|null $text
  @var string $width
  @var string $align
  @var string $variant
  @var string|null $labelledby
  @var string|null $describedby
  @var string|null $triggerId
--}}
@props([
  'label',
  'text' => null,
  'width' => 'default',
  'align' => 'right',
  'variant' => 'secondary',
  'labelledby' => null,
  'describedby' => null,
  'triggerId' => null,
])

@php
  $panel = match ($width) {
    'wide' => 'w-64',
    'columns' => 'max-h-105 w-90 max-w-[calc(100vw-48px)] overflow-y-auto',
    'full' => 'left-0 max-h-95 overflow-y-auto',
    default => 'w-50',
  };

  $side = match ($align) {
    'left' => 'left-0',
    default => 'right-0',
  };
@endphp

<div x-data="{ menuOpen: false }" @ajax:before.window="menuOpen = false" @class(['relative flex', 'w-full' => $variant === 'control'])>
  @if ($variant === 'control')
    <button
      type="button"
      x-ref="menuTrigger"
      @click="menuOpen = ! menuOpen"
      @click.outside="menuOpen = false"
      @keydown.escape.window="if (menuOpen) { menuOpen = false; $refs.menuTrigger.focus() }"
      :aria-expanded="menuOpen ? 'true' : 'false'"
      aria-haspopup="menu"
      @if ($triggerId !== null) id="{{ $triggerId }}" @endif
      @if ($labelledby !== null) aria-labelledby="{{ $labelledby }}" @else aria-label="{{ $label }}" @endif
      @if ($describedby !== null) aria-describedby="{{ $describedby }}" @endif
      class="flex min-h-9 w-full cursor-pointer items-center gap-2.5 rounded-md border border-hairline-strong bg-input px-3 py-1.5 text-left font-sans text-[16px] text-ink transition duration-(--duration-fast) ease-out hover:border-muted-soft focus-ring sm:text-base"
    >
      {{ $text }}

      <x-icon name="chevron-down" :size="14" class="ml-auto shrink-0 text-muted motion-safe:transition-transform" ::class="menuOpen && 'rotate-180'" />
    </button>
  @else
    <x-button
      :variant="$variant"
      type="button"
      size="small"
      x-ref="menuTrigger"
      @click="menuOpen = ! menuOpen"
      @click.outside="menuOpen = false"
      @keydown.escape.window="if (menuOpen) { menuOpen = false; $refs.menuTrigger.focus() }"
      ::aria-expanded="menuOpen ? 'true' : 'false'"
      aria-haspopup="menu"
      :id="$triggerId"
      :aria-labelledby="$labelledby"
      :aria-label="$text === null && $labelledby === null ? $label : null"
    >
      @isset($glyph)
        {{ $glyph }}
      @else
        <x-icon :name="$text === null ? 'menu' : 'plus'" :size="15" :stroke-width="1.6" />
      @endisset

      {{ $text }}
    </x-button>
  @endif

  <div
    x-cloak
    x-show="menuOpen"
    role="menu"
    class="absolute top-[calc(100%+6px)] {{ $side }} z-40 flex {{ $panel }} flex-col rounded-lg border border-hairline bg-canvas p-1 shadow-pop"
  >
    {{ $slot }}
  </div>
</div>
