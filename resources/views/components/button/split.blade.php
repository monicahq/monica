{{-- A primary button with the rest of what can be done hanging behind a chevron on its edge. --}}
{{--
  @var string $label
  @var string|null $href
  @var bool $turbo
  @var string|null $unavailable
--}}
@props([
  'label',
  'href' => null,
  'turbo' => true,
  'unavailable' => null,
])

@php
  $hasMenu = isset($menu) && $menu->isNotEmpty();
@endphp

@if ($hasMenu)
  <div x-data="{ open: false }" @keydown.escape.window="open = false" @ajax:before.window="open = false" {{ $attributes->class(['flex']) }}>
    @if ($unavailable !== null)
      @php
        $unavailableId = 'split-unavailable-'.md5($label.$unavailable);
      @endphp

      <x-button type="button" :dim-when-disabled="false" aria-disabled="true" :aria-describedby="$unavailableId" class="flex-1 rounded-r-none">{{ $label }}</x-button>
      <span id="{{ $unavailableId }}" class="sr-only">{{ $unavailable }}</span>
    @else
      <x-button :href="$href" :data-turbo="$turbo ? 'true' : null" class="flex-1 rounded-r-none">{{ $label }}</x-button>
    @endif

    <x-button
      type="button"
      x-ref="trigger"
      @click="open = ! open"
      @click.outside="open = false"
      ::aria-expanded="open ? 'true' : 'false'"
      aria-haspopup="menu"
      class="rounded-l-none border-l border-l-white/30 px-2.75!"
    >
      <x-icon
        name="chevron-down"
        :size="15"
        :stroke-width="1.9"
        :label="__('More actions')"
        class="transition-transform duration-(--duration-standard)"
        ::class="open && 'rotate-180'"
      />
    </x-button>

    <div
      x-cloak
      x-show="open"
      x-anchor.bottom-end.offset.6="$refs.trigger"
      x-transition:enter="transition ease-out duration-(--duration-standard)"
      x-transition:enter-start="opacity-0"
      x-transition:leave="transition ease-out duration-(--duration-fast)"
      x-transition:leave-end="opacity-0"
      role="menu"
      class="z-40 w-64 max-w-[calc(100vw-2rem)] rounded-lg border border-hairline bg-canvas p-1.5 shadow-pop"
    >
      {{ $menu }}
    </div>
  </div>
@else
  <x-button :href="$href" :data-turbo="$turbo ? 'true' : null" {{ $attributes }}>{{ $label }}</x-button>
@endif
