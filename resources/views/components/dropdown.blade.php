{{--
  A menu that picks one value out of a few and saves it there and then.

  The button shows what is chosen now. Opening it lists the choices, each with a
  hint on the right that says what picking it would look like, and a tick beside
  the one already in force. The chosen one is also heavier and sits on a tint, so
  it is not the tick alone that has to be spotted.

  Every choice is a submit button carrying the field name and its value, so the
  menu has to sit inside an <x-form>, and picking one saves without a separate
  button to press. That also means it still works with javascript switched off:
  all Alpine does here is open and close the panel.

  The panel is pinned to the button by `x-anchor`, which takes it out of the flow
  to do so. It has to be: these sit inside a panel, and a panel clips what
  overflows it, so a menu positioned inside one would be cut off at its edge. It
  hangs from the button's right, since these sit at the right hand edge of the rows
  they belong to.

  Without javascript the panel is not positioned and simply follows the button in
  the flow, which still reads and still submits. The choices are real submit
  buttons, so the only thing Alpine does here is open, close and place the panel.

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
<div x-data="{ open: false }" @ajax:before.window="open = false" {{ $attributes->class([]) }}>
  {{--
    The double colon on `aria-expanded` is what stops Blade from reading the Alpine
    expression after it as php. The trigger is a secondary button, so it is the
    component rather than its utilities written again here.
  --}}
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
      class="ml-auto text-muted transition-transform duration-[170ms]"
      ::class="open && 'rotate-180'"
    />
  </x-button.secondary>

  <div
    x-cloak
    x-show="open"
    x-anchor.bottom-end.offset.6="$refs.trigger"
    x-transition:enter="transition ease-out duration-[170ms]"
    x-transition:enter-start="opacity-0"
    x-transition:leave="transition ease-out duration-[110ms]"
    x-transition:leave-end="opacity-0"
    role="menu"
    class="z-40 w-64 max-w-[calc(100vw-2rem)] rounded-lg border border-hairline bg-canvas p-1.5 shadow-pop"
  >
    @isset($title)
      <p class="px-2.5 pt-2 pb-1.5 uppercase text-xs tracking-caption text-muted">{{ $title }}</p>
    @endisset

    @foreach ($options as $option)
      <button
        type="submit"
        role="menuitem"
        name="{{ $name }}"
        value="{{ $option['value'] }}"
        @if ($option['selected']) aria-checked="true" @endif
        class="flex w-full cursor-pointer items-center gap-2.5 rounded-sm px-2.5 py-2.25 text-left text-base text-ink transition-[background-color] duration-(--duration-fast) ease-out hover:bg-hover focus-visible:inset-ring-2 focus-visible:inset-ring-focus-ring/45 focus-visible:outline-none aria-[checked=true]:bg-hover aria-[checked=true]:font-medium"
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
