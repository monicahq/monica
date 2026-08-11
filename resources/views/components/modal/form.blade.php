{{--
  The card that stands in front of the screen and asks for something to be
  filled in. Its companion, `modal.warning`, asks before something is destroyed;
  this one is the same card with fields in it and nothing red about it.

  The caller writes its own trigger in the `trigger` slot, and its own fields in
  the default slot. Both sit inside the component's scope, so `@click="open = true"`
  is all a trigger takes.

  `open` says whether the card is up when the page arrives. A form that comes
  back rejected has to reopen on the field it was rejected on, and the only
  thing that knows is the error bag, which the caller reads.

  Attributes land on the form rather than on the wrapper, so a caller can aim
  ajax at a region with `x-target` the way it does with any other form. When it
  does, the card has to sit inside that region: the flag above is set in the
  browser, and a swap is what brings it back closed once the write went through.

  The wrapper is `display: contents`, so it takes no room and the trigger lays
  out exactly where it would have without it.

  `x-trap` is what makes the card a dialog rather than a card that happens to be on
  top: the tab key stays inside it, the first field takes focus on open, the trigger
  gets focus back on close, and `.inert` puts the rest of the page beyond the reach
  of both the pointer and a screen reader.

  Usage:
    <x-modal.form
      :title="__('Add a list')"
      :confirm="__('Add the list')"
      :action="route('...')"
      :open="$errors->getBag('newList')->has('name')"
      x-target="the-region"
    >
      <x-slot:trigger>
        <x-button type="button" @click="open = true">{{ __('Add a list') }}</x-button>
      </x-slot:trigger>

      <x-input id="name" :label="__('Name')" />
    </x-modal.form>

  @var string|null $title
  @var string|null $description
  @var string|null $confirm
  @var string|null $cancel
  @var string $method
  @var string $action
  @var bool $open
--}}
@props([
  'title' => null,
  'description' => null,
  'confirm' => null,
  'cancel' => null,
  'method' => 'post',
  'action' => '',
  'open' => false,
])

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" @keydown.escape.window="open = false" class="contents">
  {{ $trigger }}

  <div x-cloak x-show="open" class="fixed inset-0 z-70 flex items-end justify-center p-4 sm:items-center">
    <div x-show="open" x-transition.opacity.duration.170ms @click="open = false" class="erica-scrim absolute inset-0" aria-hidden="true"></div>

    <div
      x-show="open"
      x-trap.noscroll.inert="open"
      x-transition:enter="transition ease-out duration-[220ms]"
      x-transition:enter-start="translate-y-2 opacity-0"
      x-transition:leave="transition ease-out duration-[170ms]"
      x-transition:leave-end="opacity-0"
      role="dialog"
      aria-modal="true"
      @if ($title) aria-label="{{ $title }}" @endif
      class="relative w-full max-w-md overflow-hidden rounded-lg border border-hairline-strong bg-canvas shadow-pop"
    >
      <x-form :method="$method" :action="$action" {{ $attributes }}>
        <div class="flex flex-col gap-4 p-5">
          @if ($title)
            <div class="flex flex-col gap-1.5">
              <p class="erica-panel__title">{{ $title }}</p>

              @if ($description)
                <p class="erica-small">{{ $description }}</p>
              @endif
            </div>
          @endif

          {{ $slot }}
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-hairline bg-sunken px-5 py-3.5 sm:flex-row sm:justify-end">
          <x-button.secondary size="small" type="button" @click="open = false">{{ $cancel ?? __('Cancel') }}</x-button.secondary>

          <x-button size="small">{{ $confirm ?? __('Save') }}</x-button>
        </div>
      </x-form>
    </div>
  </div>
</div>
