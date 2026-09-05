{{-- The glyph and the colour a section is marked with, behind one tile that opens them. --}}
{{--
  @var string $id
  @var array<int, string> $glyphs
  @var array<int, array{value: string|null, label: string, tint: string, swatch: string|null}> $colours
  @var string|null $icon
  @var string|null $colour
  @var string $reach
--}}
@php
  $swatch = 'flex size-8 cursor-pointer items-center justify-center rounded-md border transition-colors duration-(--duration-fast) ease-out';

  $tints = collect($colours)
    ->mapWithKeys(fn (array $option): array => [$option['value'] ?? '' => $option['tint']])
    ->all();

  $current = in_array($icon, $glyphs, true) ? $icon : null;
@endphp

<span class="flex flex-col gap-1.5">
  <span class="text-label text-ink">{{ __('Mark') }}</span>

  <input type="hidden" name="icon" value="{{ $icon }}" :value="icon" />
  <input type="hidden" name="colour" value="{{ $colour }}" :value="colour" />

  <button
    type="button"
    x-ref="trigger"
    @click.stop="open = ! open"
    @keydown.escape.window="open = false"
    :aria-expanded="open ? 'true' : 'false'"
    aria-controls="{{ $id }}-mark-picker"
    class="flex h-9.5 w-fit cursor-pointer items-center gap-2 rounded-md border border-hairline-strong bg-canvas py-1 pr-2.5 pl-1 transition-colors duration-(--duration-fast) ease-out hover:bg-hover"
  >
    <span
      x-data="{ tints: @js($tints) }"
      :class="tints[colour] ?? tints['']"
      class="flex size-7 shrink-0 items-center justify-center rounded-md border"
      aria-hidden="true"
    >
      @foreach ($glyphs as $glyph)
        <span
          x-show="icon === @js($glyph)"
          @if ($current !== $glyph) style="display: none" @endif
          class="flex"
        >
          <x-icon :name="$glyph" :size="15" :stroke-width="1.5" />
        </span>
      @endforeach

      <span
        x-show="! icon"
        @if ($current !== null) style="display: none" @endif
        class="flex"
      >
        <x-icon :name="\App\Models\Section::DEFAULT_ICON" :size="15" :stroke-width="1.5" />
      </span>
    </span>

    <x-icon
      name="chevron-down"
      :size="15"
      :stroke-width="1.6"
      class="text-muted transition-transform duration-[170ms]"
      ::class="open && 'rotate-180'"
    />

    <span class="sr-only">{{ __('Choose the glyph and colour for this section') }}</span>
  </button>
</span>

<div
  x-cloak
  x-show="open"
  @click.outside="open = false"
  x-anchor.bottom-start.offset.6="$refs.trigger"
  x-transition:enter="transition ease-out duration-[170ms]"
  x-transition:enter-start="opacity-0"
  x-transition:leave="transition ease-out duration-[110ms]"
  x-transition:leave-end="opacity-0"
  id="{{ $id }}-mark-picker"
  class="z-40 flex w-72 max-w-[calc(100vw-2rem)] flex-col gap-3.5 rounded-lg border border-hairline bg-canvas p-3.5 shadow-pop"
>
  <fieldset class="flex flex-col gap-2">
    <legend class="pb-1 text-xs tracking-caption text-muted uppercase">{{ __('Glyph') }}</legend>

    <div class="grid grid-cols-7 gap-1.5">
      <button
        type="button"
        :aria-pressed="! icon ? 'true' : 'false'"
        @click="icon = ''"
        :class="! icon ? 'border-ink bg-hover' : 'border-hairline bg-canvas'"
        class="{{ $swatch }}"
      >
        <x-icon name="close" :size="15" :stroke-width="1.6" class="text-muted" />

        <span class="sr-only">{{ __('No glyph') }}</span>
      </button>

      @foreach ($glyphs as $glyph)
        <button
          type="button"
          :aria-pressed="icon === @js($glyph) ? 'true' : 'false'"
          @click="icon = @js($glyph)"
          :class="icon === @js($glyph) ? 'border-ink bg-hover' : 'border-hairline bg-canvas'"
          class="{{ $swatch }}"
        >
          <x-icon :name="$glyph" :size="16" :stroke-width="1.5" />

          <span class="sr-only">{{ $glyph }}</span>
        </button>
      @endforeach
    </div>
  </fieldset>

  <fieldset class="flex flex-col gap-2">
    <legend class="pb-1 text-xs tracking-caption text-muted uppercase">{{ __('Colour') }}</legend>

    <div class="flex flex-wrap gap-1.5">
      @foreach ($colours as $option)
        <button
          type="button"
          :aria-pressed="colour === @js($option['value'] ?? '') ? 'true' : 'false'"
          @click="colour = @js($option['value'] ?? '')"
          :class="colour === @js($option['value'] ?? '') ? 'border-ink' : 'border-hairline'"
          class="{{ $swatch }} bg-canvas"
        >
          @if ($option['swatch'] === null)
            <span class="size-[18px] rounded-xs border border-hairline-strong bg-card"></span>
          @else
            <span class="size-[18px] rounded-xs" style="background: {{ $option['swatch'] }}"></span>
          @endif

          <span class="sr-only">{{ $option['label'] }}</span>
        </button>
      @endforeach
    </div>
  </fieldset>

  <div class="flex items-center justify-between gap-3 border-t border-hairline pt-3">
    <p class="text-xs text-pretty text-muted">{{ $reach }}</p>

    <x-button.secondary size="small" type="button" @click="open = false">{{ __('Done') }}</x-button.secondary>
  </div>
</div>
