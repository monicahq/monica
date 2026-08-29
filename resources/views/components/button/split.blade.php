{{--
  A primary button with a second action folded behind it: the label on the left,
  a chevron joined to its right edge, and the rest of what can be done to the
  thing on the screen hanging under that chevron.

  It exists so a header does not have to line up three buttons of equal weight
  for actions that are not of equal weight. One of them is what somebody came to
  do, and the others are worth reaching rather than worth reading.

  The left half is a plain submit button, so it submits the <x-form> this sits
  in and works with javascript switched off. The panel behind the chevron needs
  Alpine, as every menu in the application does. The items in it are links rather
  than forms, because this sits inside a form and a form inside a form is not
  markup a browser will keep.

  The chevron half narrows the padding the button component gives it, which is
  why that one utility is marked important: `px-4.5` and `px-2.75` are both real
  utilities, and which of the two lands last in the stylesheet is not something a
  class attribute decides.

  A menu with nothing in it draws no chevron at all. A caller whose second action
  is conditional can therefore write the condition inside the slot and let the
  button fall back to an ordinary one, rather than choosing between two shapes.

  The panel is pinned by `x-anchor`, which takes it out of the flow, so it is not
  clipped by whatever it sits in. Without javascript it simply follows the button.

  Usage:
    <x-form method="post" :action="route('...')">
      <x-button.split :label="__('Create a new version')">
        <x-slot:menu>
          <x-button.split-item :href="route('...')" icon="relationship">…</x-button.split-item>
        </x-slot:menu>
      </x-button.split>
    </x-form>

  @var string $label
--}}
@props([
  'label',
])

@php
  $hasMenu = isset($menu) && $menu->isNotEmpty();
@endphp

@if ($hasMenu)
  {{--
    The double colon on `aria-expanded` is what stops Blade from reading the
    Alpine expression after it as php.
  --}}
  <div x-data="{ open: false }" @keydown.escape.window="open = false" @ajax:before.window="open = false" {{ $attributes->class(['flex']) }}>
    <x-button class="flex-1 rounded-r-none">{{ $label }}</x-button>

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
        class="transition-transform duration-[170ms]"
        ::class="open && 'rotate-180'"
      />
    </x-button>

    <div
      x-cloak
      x-show="open"
      x-anchor.bottom-end.offset.6="$refs.trigger"
      x-transition:enter="transition ease-out duration-[170ms]"
      x-transition:enter-start="opacity-0"
      x-transition:leave="transition ease-out duration-[110ms]"
      x-transition:leave-end="opacity-0"
      role="menu"
      class="z-40 w-64 max-w-[calc(100vw-2rem)] rounded-lg border border-hairline-strong bg-canvas p-1.5 shadow-pop"
    >
      {{ $menu }}
    </div>
  </div>
@else
  <x-button {{ $attributes }}>{{ $label }}</x-button>
@endif
