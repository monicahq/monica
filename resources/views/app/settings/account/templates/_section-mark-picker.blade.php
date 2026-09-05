{{--
  The glyph and the colour a section is marked with, behind one tile that opens
  them.

  The tile is the trigger and the preview at once, which is what keeps the row
  one line tall. Height is expensive here: this sits under a list of sections
  that is already long, and three stacked controls would push the list off the
  screen every time somebody added anything.

  The panel is pinned by `x-anchor`, which takes it out of the flow. It has to
  be: a section is drawn in a card that clips what overflows it, so a panel
  positioned inside one is cut off at its edge, which is exactly what happened
  when this was written with `absolute`. Without javascript it is not positioned
  and simply follows the tile in the flow, which still reads and still submits.

  Every glyph is drawn and all but one hidden, so picking one needs no request.
  Which one starts visible is decided here rather than left to Alpine, because
  `[x-cloak]` hides with `!important` and an element that keeps the attribute is
  an element nothing can show again. The server marks the other thirty-five
  hidden and Alpine takes over from there, so exactly one glyph is in the tile
  before and after it boots.

  This is a popover and not a dialog, so focus is not trapped in it. The name
  field sits beside the tile and somebody filling the row in order reaches it by
  tabbing forward out of here; holding the keyboard inside would make the one
  required field unreachable. Escape and a click outside close it, and neither
  discards what was picked, because the values live on the form rather than here.

  What counts as outside is the panel, not the tile. A menu whose choices save
  and close can hang that on its trigger, since the panel is going either way.
  This one is picked from twice, a glyph and then a colour, so hanging it on the
  tile shuts the panel on the first click and reads as the click having done
  nothing at all. The tile stops its own click instead, so opening the panel is
  not immediately read as a click outside it.

  Both values travel in hidden fields, because a row of buttons posts nothing on
  its own. Clearing is a choice in each list rather than a separate control: the
  neutral swatch and the crossed out glyph are what "no mark" looks like, so the
  way back is in the same place as the way in.

  The enclosing form supplies `icon`, `colour` and `open` on its own `x-data`,
  and it is what closes the panel when an ajax save is on its way. The builder is
  one region that morphs back into place, so a panel left open would still be
  hanging there afterwards.

  @var string $id
  @var array<int, string> $glyphs
  @var array<int, array{value: string|null, label: string, tint: string, swatch: string|null}> $colours
  @var string|null $icon
  @var string|null $colour
  @var string $reach
--}}
@php
  $swatch = 'flex size-8 cursor-pointer items-center justify-center rounded-md border transition-colors duration-(--duration-fast) ease-out';

  /*
   * The tint has to be chosen in the browser, since the colour changes with no
   * round trip, so the table crosses over as a map keyed by what the hidden
   * field holds. The empty key is the neutral pair, which is what no colour
   * means. Nothing here writes a border colour of its own: every pair names one,
   * and a second `border-*` utility in the same class attribute would be
   * resolved by the stylesheet rather than by which was written last. That is
   * also why the tile carries no tint until Alpine binds one, rather than being
   * given the neutral pair to sit on in the meantime: a static pair and a bound
   * pair would both be on the element, and a coloured section would draw the
   * wrong one of the two.
   */
  $tints = collect($colours)
    ->mapWithKeys(fn (array $option): array => [$option['value'] ?? '' => $option['tint']])
    ->all();

  $current = in_array($icon, $glyphs, true) ? $icon : null;
@endphp

<span class="flex flex-col gap-1.5">
  <span class="text-label text-ink">{{ __('Mark') }}</span>

  {{--
    Each field carries what is stored as well as the binding that follows the
    picking. A binding alone leaves the field empty until Alpine has booted, and
    a save that landed first would clear the mark rather than keep it.
  --}}
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

    {{--
      All thirty-six fit, so none of this scrolls. It did, and a fixed height cut
      the last row through the middle, which reads as a bug rather than as more
      to come.
    --}}
    <div class="grid grid-cols-7 gap-1.5">
      {{--
        No glyph is a choice in the grid rather than a control beside it, so
        clearing the mark is the same gesture as setting it.
      --}}
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
          {{--
            A solid square of the hue, which is how a vault and a kind of record
            are already picked. The pair the header ends up drawn in is a twelfth
            of this, far too pale to choose between. The name is what a screen
            reader reads: a hex code read out is not a colour anybody recognises.
          --}}
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
