{{-- The button that unfolds the rest of what may be done here, and the menu it unfolds. --}}
{{--
  @var string $label
  @var string $width
--}}
@props([
  'label',
  'width' => 'default',
])

@php
  $panel = match ($width) {
    'wide' => 'w-64',
    default => 'w-56',
  };
@endphp

<div x-data="{ menuOpen: false }" @ajax:before.window="menuOpen = false" class="relative flex">
  <x-button.secondary
    type="button"
    size="small"
    @click="menuOpen = ! menuOpen"
    @click.outside="menuOpen = false"
    @keydown.escape.window="menuOpen = false"
    ::aria-expanded="menuOpen ? 'true' : 'false'"
    aria-haspopup="menu"
    :aria-label="$label"
  >
    @isset($glyph)
      {{ $glyph }}
    @else
      <x-icon name="menu" :size="15" :stroke-width="1.6" />
    @endisset
  </x-button.secondary>

  <div
    x-cloak
    x-show="menuOpen"
    role="menu"
    class="absolute top-[calc(100%+6px)] right-0 z-40 flex {{ $panel }} flex-col gap-0.5 rounded-lg border border-hairline bg-card p-1.5 shadow-lg"
  >
    {{ $slot }}
  </div>
</div>
