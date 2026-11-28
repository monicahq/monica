{{-- The glyph and the colour something is marked with: behind one tile that opens both, or as a picker each. --}}
{{--
  @var string $id
  @var array<int, string> $glyphs
  @var array<int, array{value: string|null, label: string, tint: string, swatch: string|null}> $colours
  @var string|null $icon
  @var string|null $colour
  @var string $reach
  @var string $layout
--}}
@props([
  'id',
  'glyphs',
  'colours',
  'icon' => null,
  'colour' => null,
  'reach' => '',
  'layout' => 'combined',
])

@php
  use App\Models\Section;

  $swatch = 'flex size-8 cursor-pointer items-center justify-center rounded-md border transition-colors duration-(--duration-fast) ease-out';

  $tints = collect($colours)
    ->mapWithKeys(fn (array $option): array => [$option['value'] ?? '' => $option['tint']])
    ->all();

  $names = collect($colours)
    ->mapWithKeys(fn (array $option): array => [$option['value'] ?? '' => $option['label']])
    ->all();

  // The colour itself, not its tint: the split trigger shows a plain dot, the way the rows below it do.
  $swatches = collect($colours)
    ->mapWithKeys(fn (array $option): array => [$option['value'] ?? '' => $option['swatch']])
    ->all();

  $current = in_array($icon, $glyphs, true) ? $icon : null;

  $split = $layout === 'split';

  $trigger = 'flex h-9 cursor-pointer items-center gap-1.5 rounded-md border bg-canvas px-2 shadow-control transition-colors duration-(--duration-fast) ease-out hover:bg-hover';

  $popover = 'absolute top-[calc(100%+0.375rem)] start-0 z-40 flex rounded-lg border border-hairline bg-canvas p-2 shadow-pop';
@endphp

<input type="hidden" name="icon" value="{{ $icon }}" :value="icon" />
<input type="hidden" name="colour" value="{{ $colour }}" :value="colour" />

@if ($split)
  <span x-data="{ openIcon: false, openColour: false }" class="flex shrink-0 gap-4">
    <span class="flex flex-col gap-1.5">
      <span class="text-label text-ink">{{ __('Icon') }}</span>

      <span class="relative flex">
        <button
          type="button"
          x-ref="iconTrigger"
          @click.stop="openIcon = ! openIcon; openColour = false"
          :aria-expanded="openIcon ? 'true' : 'false'"
          aria-haspopup="dialog"
          aria-controls="{{ $id }}-icon-picker"
          :aria-label="@js(__('Icon: :name. Pick another.', ['name' => '__mark__'])).replace('__mark__', icon || @js(__('none')))"
          :class="openIcon ? 'border-ink' : 'border-hairline-strong'"
          class="{{ $trigger }} focus-ring"
        >
          <span class="flex size-6 items-center justify-center text-ink" aria-hidden="true">
            @foreach ($glyphs as $glyph)
              <span x-show="icon === @js($glyph)" @if ($current !== $glyph) style="display: none" @endif class="flex">
                <x-icon :name="$glyph" :size="17" :stroke-width="1.5" />
              </span>
            @endforeach

            <span x-show="! icon" @if ($current !== null) style="display: none" @endif class="flex">
              <x-icon :name="Section::DEFAULT_ICON" :size="17" :stroke-width="1.5" />
            </span>
          </span>

          <x-icon
            name="chevron-down"
            :size="14"
            :stroke-width="1.6"
            class="text-muted transition-transform duration-(--duration-standard)"
            ::class="openIcon && 'rotate-180'"
          />
        </button>

        <div
          x-cloak
          x-show="openIcon"
          @click.outside="openIcon = false"
          @keydown.escape.window="if (openIcon) { openIcon = false; $refs.iconTrigger.focus() }"
          x-transition:enter="transition ease-out duration-(--duration-standard)"
          x-transition:enter-start="opacity-0"
          x-transition:leave="transition ease-out duration-(--duration-fast)"
          x-transition:leave-end="opacity-0"
          id="{{ $id }}-icon-picker"
          role="dialog"
          aria-label="{{ __('Pick an icon') }}"
          class="{{ $popover }} w-67 max-w-[calc(100vw-3rem)] flex-wrap gap-1"
        >
          <button
            type="button"
            :aria-pressed="! icon ? 'true' : 'false'"
            @click="icon = ''; openIcon = false; $refs.iconTrigger.focus()"
            :class="! icon ? 'border-ink bg-hover' : 'border-transparent bg-canvas'"
            class="{{ $swatch }} focus-ring-inset"
          >
            <x-icon name="close" :size="15" :stroke-width="1.6" class="text-muted" />

            <span class="sr-only">{{ __('No icon') }}</span>
          </button>

          @foreach ($glyphs as $glyph)
            <button
              type="button"
              :aria-pressed="icon === @js($glyph) ? 'true' : 'false'"
              @click="icon = @js($glyph); openIcon = false; $refs.iconTrigger.focus()"
              :class="icon === @js($glyph) ? 'border-ink bg-hover' : 'border-transparent bg-canvas'"
              class="{{ $swatch }} focus-ring-inset"
            >
              <x-icon :name="$glyph" :size="16" :stroke-width="1.5" />

              <span class="sr-only">{{ $glyph }}</span>
            </button>
          @endforeach
        </div>
      </span>
    </span>

    <span class="flex flex-col gap-1.5">
      <span class="text-label text-ink">{{ __('Colour') }}</span>

      <span class="relative flex">
        <button
          type="button"
          x-ref="colourTrigger"
          @click.stop="openColour = ! openColour; openIcon = false"
          :aria-expanded="openColour ? 'true' : 'false'"
          aria-haspopup="dialog"
          aria-controls="{{ $id }}-colour-picker"
          :aria-label="@js(__('Colour: :name. Pick another.', ['name' => '__mark__'])).replace('__mark__', @js($names)[colour ?? ''] ?? @js(__('none')))"
          :class="openColour ? 'border-ink' : 'border-hairline-strong'"
          class="{{ $trigger }} focus-ring"
        >
          {{-- Alpine owns the border colour and the ground, as it does on the combined trigger: naming
               either in the static class as well would leave two utilities fighting over one property. --}}
          <span
            x-data="{ swatches: @js($swatches) }"
            :style="swatches[colour ?? ''] ? 'background: ' + swatches[colour ?? ''] : ''"
            :class="swatches[colour ?? ''] ? 'border-transparent' : 'border-hairline-strong bg-card'"
            class="size-5 rounded-full border"
            aria-hidden="true"
          ></span>

          <x-icon
            name="chevron-down"
            :size="14"
            :stroke-width="1.6"
            class="text-muted transition-transform duration-(--duration-standard)"
            ::class="openColour && 'rotate-180'"
          />
        </button>

        <div
          x-cloak
          x-show="openColour"
          @click.outside="openColour = false"
          @keydown.escape.window="if (openColour) { openColour = false; $refs.colourTrigger.focus() }"
          x-transition:enter="transition ease-out duration-(--duration-standard)"
          x-transition:enter-start="opacity-0"
          x-transition:leave="transition ease-out duration-(--duration-fast)"
          x-transition:leave-end="opacity-0"
          id="{{ $id }}-colour-picker"
          role="dialog"
          aria-label="{{ __('Pick a colour') }}"
          class="{{ $popover }} w-44 flex-col gap-0.5"
        >
          @foreach ($colours as $option)
            <button
              type="button"
              :aria-pressed="colour === @js($option['value'] ?? '') ? 'true' : 'false'"
              @click="colour = @js($option['value'] ?? ''); openColour = false; $refs.colourTrigger.focus()"
              :class="colour === @js($option['value'] ?? '') && 'bg-hover'"
              class="flex w-full cursor-pointer items-center gap-2 rounded-xs px-2 py-1.5 text-start text-sm text-ink transition-colors hover:bg-hover focus-ring-inset"
            >
              @if ($option['swatch'] === null)
                <span class="size-4 shrink-0 rounded-full border border-hairline-strong bg-card" aria-hidden="true"></span>
              @else
                <span class="size-4 shrink-0 rounded-full" style="background: {{ $option['swatch'] }}" aria-hidden="true"></span>
              @endif

              <span class="min-w-0 flex-1 truncate">{{ $option['label'] }}</span>

              {{-- The guard sits on a plain element: @js() is not compiled inside a component tag. --}}
              <span x-cloak x-show="colour === @js($option['value'] ?? '')" class="flex shrink-0 text-ink">
                <x-icon name="check" :size="14" :stroke-width="1.8" />
              </span>
            </button>
          @endforeach
        </div>
      </span>
    </span>
  </span>
@else
<span class="flex flex-col gap-1.5">
  <span class="text-label text-ink">{{ __('Mark') }}</span>

  <button
    type="button"
    x-ref="trigger"
    @click.stop="open = ! open"
    :aria-expanded="open ? 'true' : 'false'"
    aria-controls="{{ $id }}-mark-picker"
    class="flex h-9.5 w-fit cursor-pointer items-center gap-2 rounded-md border border-hairline-strong bg-canvas py-1 pe-2.5 ps-1 transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring"
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
      class="text-muted transition-transform duration-(--duration-standard)"
      ::class="open && 'rotate-180'"
    />

    <span class="sr-only">{{ __('Choose the icon and colour for this section') }}</span>
  </button>
</span>

<div
  x-cloak
  x-show="open"
  @click.outside="open = false"
  @keydown.escape.window="if (open) { open = false; $refs.trigger.focus() }"
  x-anchor.bottom-start.offset.6="$refs.trigger"
  x-transition:enter="transition ease-out duration-(--duration-standard)"
  x-transition:enter-start="opacity-0"
  x-transition:leave="transition ease-out duration-(--duration-fast)"
  x-transition:leave-end="opacity-0"
  id="{{ $id }}-mark-picker"
  class="z-40 flex w-72 max-w-[calc(100vw-2rem)] flex-col gap-3.5 rounded-lg border border-hairline bg-canvas p-3.5 shadow-pop"
>
  <fieldset class="flex flex-col gap-2">
    <legend class="pb-1 text-xs tracking-caption text-muted uppercase">{{ __('Icon') }}</legend>

    <div class="grid grid-cols-7 gap-1.5">
      <button
        type="button"
        :aria-pressed="! icon ? 'true' : 'false'"
        @click="icon = ''"
        :class="! icon ? 'border-ink bg-hover' : 'border-hairline bg-canvas'"
        class="{{ $swatch }} focus-ring-inset"
      >
        <x-icon name="close" :size="15" :stroke-width="1.6" class="text-muted" />

        <span class="sr-only">{{ __('No icon') }}</span>
      </button>

      @foreach ($glyphs as $glyph)
        <button
          type="button"
          :aria-pressed="icon === @js($glyph) ? 'true' : 'false'"
          @click="icon = @js($glyph)"
          :class="icon === @js($glyph) ? 'border-ink bg-hover' : 'border-hairline bg-canvas'"
          class="{{ $swatch }} focus-ring-inset"
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
          class="{{ $swatch }} bg-canvas focus-ring-inset"
        >
          @if ($option['swatch'] === null)
            <span class="size-4.5 rounded-xs border border-hairline-strong bg-card"></span>
          @else
            <span class="size-4.5 rounded-xs" style="background: {{ $option['swatch'] }}"></span>
          @endif

          <span class="sr-only">{{ $option['label'] }}</span>
        </button>
      @endforeach
    </div>
  </fieldset>

  <div class="flex items-center justify-between gap-3 border-t border-hairline pt-3">
    <p class="text-xs text-pretty text-muted">{{ $reach }}</p>

    <x-button.secondary size="small" type="button" @click="open = false; $refs.trigger.focus()">{{ __('Done') }}</x-button.secondary>
  </div>
</div>
@endif
