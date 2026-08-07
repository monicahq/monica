{{--
  A menu that picks one value out of a few and saves it there and then.

  The button shows what is chosen now. Opening it lists the choices, each with a
  hint on the right that says what picking it would look like, and a tick beside
  the one already in force.

  Every choice is a submit button carrying the field name and its value, so the
  menu has to sit inside an <x-form>, and picking one saves without a separate
  button to press. That also means it still works with javascript switched off:
  all Alpine does here is open and close the panel.

  The panel is anchored to the right, since these sit at the right hand edge of
  the rows they belong to. Below sm there is no room to sit beside anything, so
  the button and the panel take the width they are given instead.

  Usage:
    <x-form method="put" :action="route('...')">
      <x-dropdown name="locale" :label="$current" :options="$locales" :title="__('Language')" />
    </x-form>

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

{{--
  A menu inside a form that saves over ajax is morphed back into place rather
  than replaced, so `open` survives the save and the panel would stay hanging
  there. Closing it once the request is on its way puts that right. The event
  is listened for on the window because it is fired on the form, which is a
  parent of this, and events do not travel downwards.
--}}
<div x-data="{ open: false }" @ajax:before.window="open = false" {{ $attributes->class(['relative']) }}>
  <button
    type="button"
    @click="open = ! open"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    :aria-expanded="open ? 'true' : 'false'"
    :class="open ? 'border-focus' : 'border-hairline-strong'"
    class="flex w-full cursor-pointer items-center gap-2 rounded-md border bg-canvas px-3 py-2 text-sm text-ink transition-colors hover:bg-hover sm:w-42"
  >
    <span class="truncate">{{ $label }}</span>

    <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" class="ml-auto shrink-0 text-muted transition-transform duration-200" :class="open && 'rotate-180'" aria-hidden="true">
      <path d="M4.4 6.4 8 10l3.6-3.6"></path>
    </svg>
  </button>

  <div
    x-cloak
    x-show="open"
    x-transition.opacity.duration.120ms
    class="absolute top-full right-0 z-10 mt-1.5 w-full overflow-hidden rounded-lg border border-hairline-strong bg-canvas shadow-lg sm:w-60"
  >
    @isset($title)
      <div class="px-3 pt-2.5 pb-1.5 text-xs tracking-widest text-muted-soft uppercase">{{ $title }}</div>
    @endisset

    @foreach ($options as $option)
      <button
        type="submit"
        name="{{ $name }}"
        value="{{ $option['value'] }}"
        @if ($option['selected']) aria-current="true" @endif
        class="flex w-full cursor-pointer items-center gap-2.5 px-3 py-2 text-left transition-colors hover:bg-hover {{ $option['selected'] ? 'bg-card' : '' }}"
      >
        <span class="flex-1 truncate text-sm text-ink {{ $option['selected'] ? 'font-semibold' : '' }}">{{ $option['label'] }}</span>

        <span class="text-xs text-muted-soft {{ $monospacedHints ? 'font-mono' : '' }}">{{ $option['hint'] }}</span>

        <span class="flex w-3.5 items-center justify-center text-ink {{ $option['selected'] ? '' : 'opacity-0' }}">
          <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true">
            <path d="M2.2 6.3 4.7 8.8 9.8 3.4"></path>
          </svg>
        </span>
      </button>
    @endforeach
  </div>
</div>
