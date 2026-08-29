{{--
  The card that stands in front of the screen and asks before something is
  destroyed. Nothing in the application deletes on the first click, and this is
  what the second click looks like.

  The caller writes its own trigger in the `trigger` slot, since a button in a
  row and a button in a heading are not styled alike. Anything in that slot sits
  inside the component's scope, so `@click="open = true"` is all it takes to
  raise the card.

  Attributes land on the form rather than on the wrapper, so a caller can aim
  ajax at a region with `x-target` the way it does with any other form.

  `x-trap` is what makes the card a dialog rather than a card that happens to be on
  top: the tab key stays inside it, the button that keeps the thing takes focus on
  open, the trigger gets focus back on close, and `.inert` puts the rest of the page
  beyond the reach of both the pointer and a screen reader.

  The wrapper is `display: contents`, so it takes no room and the trigger lays
  out exactly where it would have without it.

  Usage:
    <x-modal.warning
      :title="__('Delete this?')"
      :confirm="__('Delete it')"
      :action="route('...')"
      x-target="the-region"
    >
      <x-slot:trigger>
        <button type="button" @click="open = true">{{ __('Delete') }}</button>
      </x-slot:trigger>

      {{ __('What is about to be lost.') }}
    </x-modal.warning>

  @var string|null $title
  @var string|null $confirm
  @var string|null $cancel
  @var string $method
  @var string $action
--}}
@props([
  'title' => null,
  'confirm' => null,
  'cancel' => null,
  'method' => 'delete',
  'action' => '',
])

<div x-data="{ open: false }" @keydown.escape.window="open = false" class="contents">
  {{ $trigger }}

  <div x-cloak x-show="open" class="fixed inset-0 z-70 flex items-end justify-center p-4 sm:items-center">
    <div x-show="open" x-transition.opacity.duration.170ms @click="open = false" class="absolute inset-0 bg-scrim" aria-hidden="true"></div>

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
      class="relative w-full max-w-md overflow-hidden rounded-lg border border-hairline bg-canvas shadow-pop"
    >
      <div class="flex gap-3.5 p-5">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-sm bg-error-tint text-error" aria-hidden="true">
          <x-icon name="info" :size="19" :stroke-width="1.6" />
        </span>

        <div class="flex min-w-0 flex-col gap-1.5">
          @if ($title)
            <p class="text-card-title text-ink">{{ $title }}</p>
          @endif

          <div class="flex flex-col gap-2 text-sm leading-relaxed text-body">{{ $slot }}</div>
        </div>
      </div>

      <x-form :method="$method" :action="$action" {{ $attributes }} class="flex flex-col-reverse gap-3 border-t border-hairline bg-sunken px-5 py-3.5 sm:flex-row sm:justify-end">
        <x-button.secondary size="small" type="button" @click="open = false">{{ $cancel ?? __('Keep it') }}</x-button.secondary>

        <x-button.danger size="small">{{ $confirm ?? __('Delete') }}</x-button.danger>
      </x-form>
    </div>
  </div>
</div>
