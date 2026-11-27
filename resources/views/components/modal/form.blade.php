{{-- The card that stands in front of the screen and asks for something to be filled in. --}}
{{--
  @var string|null $title
  @var string|null $description
  @var string|null $confirm
  @var string $confirmVariant
  @var string|null $cancel
  @var string $method
  @var string $action
  @var bool $open
--}}
@props([
  'title' => null,
  'description' => null,
  'confirm' => null,
  'confirmVariant' => 'primary',
  'cancel' => null,
  'method' => 'post',
  'action' => '',
  'open' => false,
])

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" @keydown.escape.window="open = false" class="contents">
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
        <x-form :method="$method" :action="$action" {{ $attributes }}>
          <div class="flex flex-col gap-4 p-5">
            @if (isset($header) && $header->isNotEmpty())
              {{ $header }}
            @elseif ($title)
              <div class="flex flex-col gap-1.5">
                <p class="text-card-title text-ink">{{ $title }}</p>

                @if ($description)
                  <p class="text-sm text-pretty text-muted">{{ $description }}</p>
                @endif
              </div>
            @endif

            {{ $slot }}
          </div>

          <div class="flex flex-col-reverse gap-3 border-t border-hairline bg-sunken px-5 py-3.5 sm:flex-row sm:justify-end">
            <x-button.secondary size="small" type="button" @click="open = false">{{ $cancel ?? __('Cancel') }}</x-button.secondary>

            <x-button size="small" :variant="$confirmVariant">{{ $confirm ?? __('Save') }}</x-button>
          </div>
        </x-form>
      </div>
    </div>
  </template>
</div>
