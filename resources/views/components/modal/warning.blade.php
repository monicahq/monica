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
    <div x-show="open" x-transition.opacity.duration.150ms @click="open = false" class="absolute inset-0 bg-black/40" aria-hidden="true"></div>

    <div
      x-show="open"
      x-transition
      x-effect="open && $nextTick(() => $refs.cancel.focus())"
      role="dialog"
      aria-modal="true"
      @if ($title) aria-label="{{ $title }}" @endif
      class="relative w-full max-w-md overflow-hidden rounded-xl border border-hairline bg-canvas shadow-xl"
    >
      <div class="flex gap-3.5 p-5">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-error/10 text-error" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
            <path d="M8 2.4 14.2 13.2H1.8z"></path>
            <line x1="8" y1="6.4" x2="8" y2="9.2"></line>
            <circle cx="8" cy="11.3" r="0.5" fill="currentColor" stroke="none"></circle>
          </svg>
        </span>

        <div class="min-w-0 space-y-1.5">
          @if ($title)
            <p class="text-base leading-tight font-semibold tracking-tight text-ink">{{ $title }}</p>
          @endif

          <div class="space-y-2 text-sm leading-relaxed text-muted">{{ $slot }}</div>
        </div>
      </div>

      <x-form :method="$method" :action="$action" {{ $attributes }} class="flex flex-col-reverse gap-3 border-t border-hairline-soft bg-sunken px-5 py-3.5 sm:flex-row sm:justify-end">
        <x-button.secondary type="button" x-ref="cancel" @click="open = false">{{ $cancel ?? __('Keep it') }}</x-button.secondary>

        <x-button class="bg-error hover:bg-error/88">{{ $confirm ?? __('Delete') }}</x-button>
      </x-form>
    </div>
  </div>
</div>
