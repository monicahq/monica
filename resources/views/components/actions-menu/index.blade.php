{{-- The button that unfolds the rest of what may be done here, and the menu it unfolds. --}}
{{--
  @var string $label
  @var string|null $text
  @var string $width
  @var string $align
  @var string $variant
--}}
@props([
  'label',
  'text' => null,
  'width' => 'default',
  'align' => 'right',
  'variant' => 'secondary',
])

@php
  $panel = match ($width) {
    'wide' => 'w-64',
    'columns' => 'max-h-105 w-90 max-w-[calc(100vw-48px)] overflow-y-auto',
    default => 'w-50',
  };

  $side = match ($align) {
    'left' => 'left-0',
    default => 'right-0',
  };
@endphp

<div x-data="{ menuOpen: false }" @ajax:before.window="menuOpen = false" class="relative flex">
  <x-button
    :variant="$variant"
    type="button"
    size="small"
    x-ref="menuTrigger"
    @click="menuOpen = ! menuOpen"
    @click.outside="menuOpen = false"
    @keydown.escape.window="menuOpen = false"
    ::aria-expanded="menuOpen ? 'true' : 'false'"
    aria-haspopup="menu"
    :aria-label="$text === null ? $label : null"
  >
    @isset($glyph)
      {{ $glyph }}
    @else
      <x-icon :name="$text === null ? 'menu' : 'plus'" :size="15" :stroke-width="1.6" />
    @endisset

    {{ $text }}
  </x-button>

  <div
    x-cloak
    x-show="menuOpen"
    role="menu"
    class="absolute top-[calc(100%+6px)] {{ $side }} z-40 flex {{ $panel }} flex-col rounded-lg border border-hairline bg-canvas p-1 shadow-pop"
  >
    {{ $slot }}
  </div>
</div>
