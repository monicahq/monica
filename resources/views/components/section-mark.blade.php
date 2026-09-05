{{--
  The tile a section is recognised by: one glyph on a ground tinted from the
  section's colour.

  It is a mark and not a label. Whatever it is beside already names the section
  in words, so the tile is hidden from a screen reader and the glyph carries no
  meaning of its own. Two sections are told apart by their headings first, and
  the tile only makes that faster for somebody who can see it. That is what stops
  the colour being the only difference between them.

  The tint comes from `VaultColourEnum`, which is also where the generated avatar
  gets its own and where a contrast test can read the values. A section with no
  colour, or one holding a value the set does not offer, gets the neutral pair:
  the tile is never absent and never a hole in the row.

  The glyph is checked against the list a section may use rather than against the
  whole icon set, so a value from an old export cannot put a chevron in a section
  header. A name outside the list falls back to the default page.

  @var string|null $icon
  @var string|null $colour
  @var int $size
--}}
@props([
  'icon' => null,
  'colour' => null,
  'size' => 28,
])

@php
  use App\Enums\VaultColourEnum;
  use App\Models\Section;

  $glyph = in_array($icon, Section::ICONS, true) ? $icon : Section::DEFAULT_ICON;

  /*
   * The pair carries its own border colour, so nothing here lays one down first.
   * The neutral one takes a hairline, since a grey tile needs the edge to read
   * as a tile; a hue at a twelfth of itself already separates itself from the
   * page and takes a transparent one.
   */
  $tint = VaultColourEnum::tintFor($colour);
@endphp

<span
  {{ $attributes->class(['flex shrink-0 items-center justify-center rounded-md border', $tint]) }}
  style="width: {{ $size }}px; height: {{ $size }}px"
  aria-hidden="true"
>
  <x-icon :name="$glyph" :size="(int) round($size * 0.54)" :stroke-width="1.5" />
</span>
