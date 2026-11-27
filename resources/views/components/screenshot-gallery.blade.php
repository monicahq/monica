{{-- The screenshots of one change, and the view that shows one of them full screen. --}}
{{--
  @var list<array{url: string, description: string, width: int, height: int}> $screenshots
  @var string $id
  @var string $title
  @var bool $grid
--}}
@props([
  'screenshots',
  'id',
  'title',
  'grid' => false,
])

@php
  $count = count($screenshots);
  $alone = $count === 1 && ! $grid;
  $prefix = "screenshots-{$id}";
  $positions = $count === 1
    ? [__('Screenshot')]
    : array_map(fn (int $position): string => __(':position of :total', ['position' => $position, 'total' => $count]), range(1, $count));
@endphp

<div
  x-data="{
    open: false,
    index: 0,
    count: {{ $count }},
    positions: @js($positions),
    descriptions: @js(array_column($screenshots, 'description')),
    announcement: '',
    show(index) { this.index = index; this.announcement = ''; this.open = true },
    move(step) {
      this.index = (this.index + step + this.count) % this.count;
      this.announcement = `${this.positions[this.index]}. ${this.descriptions[this.index]}`;
    },
  }"
  {{ $attributes }}
>
  @if ($alone)
    <figure class="flex flex-col gap-2">
      <button
        type="button"
        @click="show(0)"
        aria-labelledby="{{ $prefix }}-label {{ $prefix }}-caption-0"
        class="focus-ring relative block w-fit max-w-full cursor-zoom-in overflow-hidden rounded-lg border border-hairline bg-card shadow-panel"
      >
        <x-image :src="$screenshots[0]['url']" :srcset="$screenshots[0]['url'].' 2x'" :alt="$screenshots[0]['description']" :width="intdiv($screenshots[0]['width'], 2)" :height="intdiv($screenshots[0]['height'], 2)" class="block h-auto max-w-full" />
        <span id="{{ $prefix }}-label" class="absolute right-2.5 bottom-2.5 inline-flex h-7 items-center rounded-md border border-hairline bg-canvas px-2.5 text-label text-ink">{{ __('View full screen') }}</span>
      </button>

      <figcaption id="{{ $prefix }}-caption-0" aria-hidden="true" class="text-xs text-pretty text-muted">{{ $screenshots[0]['description'] }}</figcaption>
    </figure>
  @else
    <span id="{{ $prefix }}-label" hidden>{{ __('View full screen') }}</span>

    <div class="grid grid-cols-[repeat(auto-fill,minmax(180px,1fr))] items-start gap-3">
      @foreach ($screenshots as $index => $screenshot)
        <figure class="flex min-w-0 flex-col gap-1.5">
          <button
            type="button"
            @click="show({{ $index }})"
            aria-labelledby="{{ $prefix }}-label {{ $prefix }}-caption-{{ $index }}"
            class="focus-ring block w-fit max-w-full cursor-zoom-in overflow-hidden rounded-lg border border-hairline bg-card shadow-panel"
          >
            <x-image :src="$screenshot['url']" :srcset="$screenshot['url'].' 2x'" :alt="$screenshot['description']" :width="intdiv($screenshot['width'], 2)" :height="intdiv($screenshot['height'], 2)" class="block h-auto max-w-full" />
          </button>

          <figcaption id="{{ $prefix }}-caption-{{ $index }}" aria-hidden="true" class="text-xs text-pretty text-muted">{{ $screenshot['description'] }}</figcaption>
        </figure>
      @endforeach
    </div>
  @endif

  <template x-teleport="body">
    <div
      x-cloak
      x-show="open"
      x-trap.noscroll.inert="open"
      x-transition:enter="transition-opacity duration-(--duration-standard)"
      x-transition:enter-start="opacity-0"
      x-transition:leave="transition-opacity duration-(--duration-standard)"
      x-transition:leave-end="opacity-0"
      @click="if (! $event.target.closest('button, img')) open = false"
      @keydown.escape.window="open = false"
      @keydown.left.window="open && move(-1)"
      @keydown.right.window="open && move(1)"
      role="dialog"
      aria-modal="true"
      aria-labelledby="{{ $prefix }}-title {{ $prefix }}-position"
      class="fixed inset-0 z-70 grid grid-rows-[auto_minmax(0,1fr)_auto] bg-scrim-strong print:hidden"
    >
      <div class="flex items-center justify-between gap-4 px-4 py-3">
        <p class="text-sm font-medium text-on-scrim">
          <span id="{{ $prefix }}-title" class="sr-only">{{ $title }}</span>
          <span id="{{ $prefix }}-position" x-text="positions[index]">{{ $positions[0] }}</span>
        </p>

        <x-button variant="inverted" size="small" type="button" @click="open = false">
          <x-slot:icon><x-icon name="close" :size="16" /></x-slot:icon>
          {{ __('Close') }}
        </x-button>
      </div>

      <div class="relative flex min-h-0 items-center justify-center px-4 sm:px-18">
        @foreach ($screenshots as $index => $screenshot)
          <x-image
            x-show="index === {{ $index }}"
            :src="$screenshot['url']"
            :srcset="$screenshot['url'].' 2x'"
            :alt="$screenshot['description']"
            :width="intdiv($screenshot['width'], 2)"
            :height="intdiv($screenshot['height'], 2)"
            class="h-auto max-h-full w-auto max-w-full rounded-lg bg-canvas object-contain shadow-pop"
          />
        @endforeach

        @if ($count > 1)
          <button type="button" @click="move(-1)" aria-label="{{ __('Previous screenshot') }}" class="focus-ring-on-scrim absolute top-1/2 left-4 flex size-9 -translate-y-1/2 cursor-pointer items-center justify-center rounded-md text-on-scrim transition-colors duration-(--duration-fast) hover:bg-on-scrim/10">
            <x-icon name="chevron-left" :size="18" />
          </button>

          <button type="button" @click="move(1)" aria-label="{{ __('Next screenshot') }}" class="focus-ring-on-scrim absolute top-1/2 right-4 flex size-9 -translate-y-1/2 cursor-pointer items-center justify-center rounded-md text-on-scrim transition-colors duration-(--duration-fast) hover:bg-on-scrim/10">
            <x-icon name="chevron-right" :size="18" />
          </button>
        @endif
      </div>

      @foreach ($screenshots as $index => $screenshot)
        <p x-show="index === {{ $index }}" aria-hidden="true" class="cursor-default px-6 pt-4 pb-6 text-center text-base text-pretty text-on-scrim-muted">{{ $screenshot['description'] }}</p>
      @endforeach

      <p class="sr-only" aria-live="polite" x-text="announcement"></p>
    </div>
  </template>
</div>
