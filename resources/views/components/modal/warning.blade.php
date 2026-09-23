{{-- The card that stands in front of the screen and asks before something is destroyed. --}}
{{--
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

  <template x-teleport="body">
    <div x-cloak x-show="open" class="fixed print:hidden inset-0 z-70 flex items-end justify-center p-4 sm:items-center">
      <div x-show="open" x-transition.opacity.duration.170ms @click="open = false" class="absolute inset-0 bg-scrim" aria-hidden="true"></div>

      <div
        x-show="open"
        x-trap.noscroll.inert="open"
        x-transition:enter="transition ease-out duration-(--duration-slow)"
        x-transition:enter-start="translate-y-2 opacity-0"
        x-transition:leave="transition ease-out duration-(--duration-standard)"
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
          {{ $fields ?? '' }}

          <x-button.secondary size="small" type="button" @click="open = false">{{ $cancel ?? __('Keep it') }}</x-button.secondary>

          <x-button.danger size="small">{{ $confirm ?? __('Delete') }}</x-button.danger>
        </x-form>
      </div>
    </div>
  </template>
</div>
