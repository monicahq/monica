{{--
  What is inside a tile, whichever of the three shapes the tile turned out to be.
  It exists so the glyph, the name and the count are written once rather than three
  times, and so `tile.blade.php` above is only about the decision it makes.

  @var array{label: string, icon: string, hue: string, tint: string, note: string|null} $tile
--}}
@props([
  'tile',
])

<span class="erica-launcher__glyph" style="background: {{ $tile['tint'] }}; color: {{ $tile['hue'] }}">
  <x-icon :name="$tile['icon']" :size="19" :stroke-width="1.5" />
</span>

<span class="min-w-0">
  {{ $tile['label'] }}

  @if ($tile['note'] ?? null)
    <span class="erica-launcher__note">{{ $tile['note'] }}</span>
  @endif
</span>
