{{--
  What is inside a tile, whichever of the three shapes the tile turned out to be.
  It exists so the glyph, the name and the count are written once rather than three
  times, and so `tile.blade.php` above is only about the decision it makes.

  @var array{label: string, icon: string, iconClass: string, note: string|null} $tile
--}}
@props([
  'tile',
])

<span class="flex size-8.5 shrink-0 items-center justify-center rounded-sm {{ $tile['iconClass'] }}">
  <x-icon :name="$tile['icon']" :size="19" :stroke-width="1.5" />
</span>

<span class="min-w-0">
  {{ $tile['label'] }}

  @if ($tile['note'] ?? null)
    <span class="block text-xs text-muted">{{ $tile['note'] }}</span>
  @endif
</span>
