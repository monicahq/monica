{{-- A menu that picks one value out of a few and saves it there and then. --}}
{{--
  @var string $name
  @var string $label
  @var array<int, array{value: string, label: string, hint: string, selected: bool}> $options
  @var string|null $title
  @var bool $monospacedHints
--}}
@props([
  'name',
  'label',
  'options',
  'title' => null,
  'monospacedHints' => false,
])

<div x-data="{ open: false }" @ajax:before.window="open = false" {{ $attributes->class([]) }}>
  <x-button.secondary
    type="button"
    x-ref="trigger"
    @click="open = ! open"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    ::aria-expanded="open ? 'true' : 'false'"
    aria-haspopup="menu"
    class="w-full justify-start sm:w-44"
  >
    <span class="truncate">{{ $label }}</span>

    <x-icon
      name="chevron-down"
      :size="14"
      :stroke-width="1.7"
      class="ml-auto text-muted transition-transform duration-(--duration-standard)"
      ::class="open && 'rotate-180'"
    />
  </x-button.secondary>

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
    @isset($title)
      <x-caption tone="muted" class="block px-2.5 pt-2 pb-1.5">{{ $title }}</x-caption>
    @endisset

    @foreach ($options as $option)
      <button
        type="submit"
        role="menuitem"
        name="{{ $name }}"
        value="{{ $option['value'] }}"
        @if ($option['selected']) aria-checked="true" @endif
        class="flex w-full cursor-pointer items-center gap-2.5 rounded-sm px-2.5 py-2.25 text-left text-base text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring-inset aria-checked:bg-hover aria-checked:font-medium"
      >
        <span class="min-w-0 flex-1 truncate">{{ $option['label'] }}</span>

        <span class="ml-auto text-xs text-muted {{ $monospacedHints ? 'font-mono' : '' }}">{{ $option['hint'] }}</span>

        <span class="flex w-4 shrink-0 items-center justify-center {{ $option['selected'] ? '' : 'opacity-0' }}">
          <x-icon name="check" :size="15" :stroke-width="2" />
        </span>
      </button>
    @endforeach
  </div>
</div>
